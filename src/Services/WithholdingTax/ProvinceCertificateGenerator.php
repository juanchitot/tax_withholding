<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Exceptions\ReportErrorException;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\ApiBundle\Services\Reports\ReportsResponseManager;
use GeoPagos\FileManagementBundle\Helper\FileUtilsHelper;
use GeoPagos\FileManagementBundle\Services\FileGenerationService;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Exceptions\InvalidPeriodicityException;
use GeoPagos\WithholdingTaxBundle\Helper\StringHelper;
use GeoPagos\WithholdingTaxBundle\Helper\WithholdingTaxPeriodHelper;
use GeoPagos\WithholdingTaxBundle\Repository\ProvinceWithholdingTaxSettingRepository;
use GeoPagos\WithholdingTaxBundle\Repository\SirtacDeclarationRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRepository;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;

class ProvinceCertificateGenerator
{
    public const ALL_PAYMENT_TYPES = 'ALL';
    private const WITHHOLDING_TAXES_CANNOT_BE_RE_GENERATED_MESSAGE = 'withholding taxes cannot be re-generated because latter periods have been processed and reported. Reports will be created with existing withholding taxes';

    /** @var string */
    private $directoryName;

    /** @var ReportsResponseManager */
    private $reportsResponseManager;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var EntityManagerInterface */
    private $em;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    protected $sftpBasePath;

    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var Carbon */
    protected $date;

    /** @var WithholdingTaxSettingsService */
    private $withholdingTaxSettingsService;

    /** @var WithholdingTaxCreator */
    private $withholdingTaxCreator;

    /*** @var WithholdingTaxRepository */
    private $taxRepository;

    /*** @var ProvinceWithholdingTaxSettingRepository */
    private $provinceTaxSetting;

    /** @var FileGenerationService */
    private $fileGenerationService;

    /** @var SirtacDeclarationRepository */
    private $sirtacDeclarationRepository;

    public function __construct(
        ReportsResponseManager $reportsResponseManager,
        FilesystemInterface $filesystem,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        ConfigurationManager $configurationManager,
        WithholdingTaxSettingsService $withholdingTaxSettingsService,
        WithholdingTaxCreator $withholdingTaxCreator,
        WithholdingTaxRepository $taxRepository,
        ProvinceWithholdingTaxSettingRepository $provinceTaxSetting,
        FileGenerationService $fileGenerationService,
        SirtacDeclarationRepository $sirtacDeclarationRepository
    ) {
        $this->reportsResponseManager = $reportsResponseManager;
        $this->configurationManager = $configurationManager;
        $this->filesystem = $filesystem;
        $this->em = $em;
        $this->logger = $logger;
        // Removes trailing slashes from the dir so no errors are generated due to different dir patterns across apps
        $this->sftpBasePath = rtrim($configurationManager->get('grouper_sftp_basepath'), '/');
        $this->directoryName = $configurationManager->get('withholding_tax_output_folder');
        $this->date = Carbon::now();
        $this->withholdingTaxSettingsService = $withholdingTaxSettingsService;
        $this->withholdingTaxCreator = $withholdingTaxCreator;
        $this->taxRepository = $taxRepository;
        $this->provinceTaxSetting = $provinceTaxSetting;
        $this->fileGenerationService = $fileGenerationService;
        $this->sirtacDeclarationRepository = $sirtacDeclarationRepository;
    }

    public function setDate(Carbon $date): self
    {
        $this->date = $date;
        $this->withholdingTaxCreator->setDate($date);

        return $this;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public function executionDateIsValidForCertificateRegeneration(): bool
    {
        $mostRecentLastPeriodStartDate = $this->withholdingTaxSettingsService->getMostRecentLastPeriodStartDate();

        if (!$mostRecentLastPeriodStartDate) {
            return true;
        }
        $executionDatePreviousPeriodStartDate = WithholdingTaxPeriodHelper::getLastPeriodStartDate(Period::SEMI_MONTHLY,
            $this->date);

        $isValidForRegeneration = $mostRecentLastPeriodStartDate->isSameDay($executionDatePreviousPeriodStartDate)
            || $mostRecentLastPeriodStartDate->isBefore($executionDatePreviousPeriodStartDate);

        if (!$isValidForRegeneration) {
            $this->logger->info('[withholdingTax] '.self::WITHHOLDING_TAXES_CANNOT_BE_RE_GENERATED_MESSAGE,
                ['date' => $this->getDate()->format('Y-m-d')]);
        }

        return $isValidForRegeneration;
    }

    /**
     * @throws InvalidPeriodicityException
     * @throws ReportErrorException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function process(bool $recreateWithholdingTax = true): void
    {
        if ($recreateWithholdingTax) {
            if ($this->executionDateIsValidForCertificateRegeneration()) {
                $this->withholdingTaxSettingsService->setupPeriodCertificateGeneration($this->date);
                $this->withholdingTaxCreator->process();
            }
        } else {
            $this->logger->info('[withholdingTax] Skipped Withholding Tax regeneration.');
        }

        /**
         * We must refactor this in the next iteration, process should run with different tax strategies transparently.
         */
        $provinces = $this->taxRepository->findProvincesByReportExecutionDate($this->getDate());

        $this->logger->info('[withholdingTax] start upload provinces txt to sftp', [
            'provinceQuantity' => count($provinces),
            'date' => $this->getDate()->format('Y-m-d'),
        ]);

        $filesCreated = [];
        /* @var Province $province */
        foreach ($provinces as $row) {
            $province = $row['0'];
            $provinceWithholdingTaxSetting = $province->getProvinceWithholdingTaxSetting();

            if (!$provinceWithholdingTaxSetting) {
                // Skip if setting isn't found
                $this->logger->info(sprintf('[withholdingTax] No settings for province "%s"', $province->getName()));

                continue;
            }
            $withholdingSystem = WithholdingSystemFactory::get(
                $provinceWithholdingTaxSetting,
                $this->date,
                $this->configurationManager
            );

            if (!$withholdingSystem) {
                // Skip if setting isn't setup
                $this->logger->info(
                    sprintf('[withholdingTax] No settings configured for province "%s"', $province->getName())
                );

                continue;
            }

            $this->runSystem($withholdingSystem, $filesCreated, null, $province);
        }

        // We need to process IVA and Ganancias , they don't have a province.
        $settings = $this->taxRepository->findFederalTaxesToReportByExecutionDate($this->getDate());

        foreach ($settings as $setting) {
            /** @var ProvinceWithholdingTaxSetting $taxSetting */
            $taxSetting = $this->provinceTaxSetting->findOneBy([
                'withholdingTaxType' => $setting['type'],
            ]);

            $withholdingSystem = WithholdingSystemFactory::get(
                $taxSetting,
                $this->date,
                $this->configurationManager
            );

            if (!$withholdingSystem) {
                // Skip if setting isn't setup
                $this->logger->info(
                    sprintf('[withholdingTax] No settings configured for tax "%s"', $setting['type'])
                );

                continue;
            }

            $this->runSystem($withholdingSystem, $filesCreated, $setting);
        }

        // We need to process SIRTAC declarations, they are completely different
        $shouldRunSirtacSystem = $this->sirtacDeclarationRepository->thereAreDeclarationsToReport($this->getDate());
        if ($shouldRunSirtacSystem) {
            $taxSetting = $this->provinceTaxSetting->findOneBy([
                'withholdingTaxType' => WithholdingTaxTypeEnum::SIRTAC,
            ]);

            $withholdingSystem = WithholdingSystemFactory::get(
                $taxSetting,
                $this->date,
                $this->configurationManager
            );

            if ($withholdingSystem) {
                $this->runSystem($withholdingSystem, $filesCreated, null, null, true);
            } else {
                // Skip if setting isn't setup
                $this->logger->info(
                    sprintf('[withholdingTax] No settings configured for tax "%s"', WithholdingTaxTypeEnum::SIRTAC)
                );
            }
        }

        $this->logger->info('[withholdingTax] finish upload provinces txt to sftp', [
            'filesCreated' => implode(',', $filesCreated),
        ]);
    }

    private function runSystem(
        WithholdingSystemInterface $system,
        $filesCreated,
        $setting = null,
        $province = null,
        $compression = false
    ) {
        foreach ($system->getReportNames() as $reportName) {
            foreach ($system->getFormats() as $format) {
                $dateTo = $system->getDateTo();
                $options = [
                    'start_date' => $system->getDateFrom()->format('Y-m-d'),
                    'end_date' => $dateTo->format('Y-m-d'),
                ];

                if ($setting) {
                    $options['type'] = $setting['type'];
                } elseif ($province) {
                    $options['province_id'] = $province->getId();
                }

                $fileName = $system->getFileName($reportName, $format);

                $report = $this->reportsResponseManager->getResponse($reportName, $options, $format);

                $content = $report->getContent();

                if ('xls' != $format) {
                    $content = str_replace(
                        "\n",
                        "\r\n",
                        StringHelper::remove_accents($content)
                    );
                }

                if ($compression) {
                    $content = $this->fileGenerationService->createZipFromContent($fileName, $content);
                    $fileName = FileUtilsHelper::getFilenameWithoutExtension($fileName);
                    $fileName .= FileUtilsHelper::COMPRESSION_EXTENSION;
                }

                $path = $this->sftpBasePath.DIRECTORY_SEPARATOR.$this->directoryName.DIRECTORY_SEPARATOR.$fileName;
                $this->filesystem->put($path, $content);

                $this->fileGenerationService->persistFile($fileName, $content, $system, $dateTo);

                $filesCreated[] = $fileName;
            }
        }

        return $filesCreated;
    }
}
