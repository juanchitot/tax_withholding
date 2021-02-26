<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\WithholdingTaxBundle\Exceptions\TaxGenerationStrategyNotFound;
use GeoPagos\WithholdingTaxBundle\Repository\ProvinceWithholdingTaxSettingRepository;
use GeoPagos\WithholdingTaxBundle\Repository\Strategy\TaxGenerationStrategy\DeclarationGenerationStrategyFactory;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDetailRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRepository;
use Psr\Log\LoggerInterface;

class WithholdingTaxCreator
{
    public const ALL_PAYMENT_TYPES = 'ALL';
    private const BATCH_FLUSH = 500;

    /** @var EntityManagerInterface */
    private $em;

    /** @var LoggerInterface */
    private $logger;

    /** @var Carbon */
    protected $date;

    /** @var WithholdingTaxDetailRepository */
    private $taxDetailRepository;

    /** @var ProvinceWithholdingTaxSettingRepository */
    private $provinceTaxSetting;

    /** @var WithholdingTaxRepository */
    private $taxRepository;

    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var DeclarationGenerationStrategyFactory */
    private $factory;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        ProvinceWithholdingTaxSettingRepository $provinceTaxSetting,
        WithholdingTaxRepository $taxRepository,
        WithholdingTaxDetailRepository $taxDetailRepository,
        ConfigurationManager $configurationManager,
        DeclarationGenerationStrategyFactory $factory
    ) {
        $this->date = Carbon::now();
        $this->em = $em;
        $this->logger = $logger;
        $this->provinceTaxSetting = $provinceTaxSetting;
        $this->taxRepository = $taxRepository;
        $this->taxDetailRepository = $taxDetailRepository;
        $this->configurationManager = $configurationManager;
        $this->factory = $factory;
    }

    public function setDate(Carbon $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function process(): void
    {
        $this->removeOldWithholdingTaxes();

        $this->logger->info('[WITHHOLDING TAX CREATOR] Starting process.');
        $taxDetailProcessed = 1;

        $executionDateInTimezone = $this->getExecutionDateInApplicationTimezone($this->getDate());
        $activeSettings = $this->provinceTaxSetting->findActiveConfigurations();
        foreach ($activeSettings as $activeSetting) {
            [$startDate, $endDate] = $activeSetting->getLastPeriodStartAndEndDateInUTC($executionDateInTimezone);

            try {
                $generatorStrategy = $this->factory->getDeclarationGenerationStrategy($activeSetting);
                $declarations = $generatorStrategy->generate($activeSetting, $startDate, $endDate);
            } catch (Exception | TaxGenerationStrategyNotFound $e) {
                $system = $activeSetting->getWithholdingTaxSystem();
                $message = "Something went wrong. Skipping {$system} setting: {$e->getMessage()}";
                $this->logger->info($message);

                continue;
            }

            foreach ($declarations as $withholdingTax) {
                $this->em->persist($withholdingTax);

                if (0 == (++$taxDetailProcessed % self::BATCH_FLUSH)) {
                    $this->em->flush();
                    $this->logger->info('[WITHHOLDING TAX CREATOR] '.$taxDetailProcessed.'  Withholding Tax Created.');
                }
            }
        }

        $this->em->flush();

        $this->logger->info('[WITHHOLDING TAX CREATOR] Final FLUSH. '.$taxDetailProcessed.' Withholding Tax Created.');

        $this->em->clear();

        $this->logger->info('[WITHHOLDING TAX CREATOR] End process.');
    }

    private function removeOldWithholdingTaxes(): void
    {
        $totalRemoved = 0;

        $activeSettings = $this->provinceTaxSetting->findActiveConfigurations();
        foreach ($activeSettings as $activeSetting) {
            try {
                $generatorStrategy = $this->factory->getDeclarationGenerationStrategy($activeSetting);
                $totalRemoved += $generatorStrategy->removeOld($activeSetting, $this->getDate());
            } catch (Exception | TaxGenerationStrategyNotFound $e) {
                $system = $activeSetting->getWithholdingTaxSystem();
                $message = "Something went wrong removing old declarations. Skipping {$system} setting: {$e->getMessage()}";
                $this->logger->info($message);

                continue;
            }
            $this->em->flush();
        }

        $this->em->clear();

        if ($totalRemoved > 0) {
            $this->logger->info('[WITHHOLDING TAX CREATOR] Previous Withholding Taxes were found and DELETED',
                ['removed' => $totalRemoved]
            );
        }
    }

    private function getExecutionDateInApplicationTimezone(Carbon $executionDate)
    {
        return $executionDate
            ->copy()
            ->hour(5)   // This is because if you move between timezones you could get the day before today
            ->setTimezone($this->configurationManager->get('timezone'));
    }
}
