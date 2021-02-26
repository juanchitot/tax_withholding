<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Helper\BulkInsertQuery;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDynamicRuleProvinceRateRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDynamicRuleRepository;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ManageRegisterByProvinceService
{
    public const ROWS_TO_BULK = 8000;

    private const MAX_ALLOWED_FAIL_LINES_UNTIL_ABORT = 4;
    const SUCCCESS = '-- Succcess';
    private const SIRTAC = 'SIRTAC';

    /**
     * @var WithholdingTaxDynamicRuleProvinceRateRepository
     */
    private $withholdingTaxDynamicRuleProvinceRateRepository;

    /**
     * @var WithholdingTaxDynamicRuleRepository
     */
    private $withholdingTaxDynamicRuleRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /** @var string */
    private $publicStoragePath;

    /** @var string */
    private $filePath;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $force;

    /** @var bool */
    private $verbose;

    /** @var WithholdingTaxRuleFile */
    private $registerToProcess;

    /** @var RuleFileParserInterface */
    private $ruleFileParser;

    public function __construct(
        WithholdingTaxDynamicRuleProvinceRateRepository $withholdingTaxDynamicRuleProvinceRateRepository,
        WithholdingTaxDynamicRuleRepository $withholdingTaxDynamicRuleRepository,
        EntityManagerInterface $em,
        FilesystemInterface $filesystem,
        LoggerInterface $logger,
        KernelInterface $kernel
    ) {
        $this->withholdingTaxDynamicRuleProvinceRateRepository = $withholdingTaxDynamicRuleProvinceRateRepository;
        $this->withholdingTaxDynamicRuleRepository = $withholdingTaxDynamicRuleRepository;
        $this->entityManager = $em;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->verbose = false;
        $this->publicStoragePath = $kernel->getProjectDir().'/storage/public/';
    }

    public function setForce($force)
    {
        $this->force = $force;

        return $this;
    }

    public function setRegisterProvince(WithholdingTaxRuleFile $pendingRegister): self
    {
        $this->registerToProcess = $pendingRegister;

        return $this;
    }

    public function setParser(RuleFileParserInterface $fileParser)
    {
        $this->ruleFileParser = $fileParser;

        return $this;
    }

    public function shouldRun()
    {
        if (null == $this->registerToProcess) {
            return false;
        }

        if (($this->registerToProcess->getDate() != Carbon::now()->lastOfMonth()->addDay()->format('m-Y')) && !$this->force) {
            $this->logger->error('Register to process from '.$this->getRegisterToProcessString().' isn\'t from next month');

            return false;
        }

        return true;
    }

    public function process(): bool
    {
        if (!$this->shouldRun()) {
            return false;
        }

        $rtnValue = true;

        try {
            $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

            $this->logger->info('Start Process of '.$this->getRegisterToProcessString());

            $file = $this->prepareFile();

            $this->cleanData($this->registerToProcess->getDate());

            $this->insertBulkData(pathinfo($file));

            $this->logger->info('Update status and rule date');
            $this->registerToProcess->setStatus(WithholdingTaxRuleFileStatus::SUCCESS);
            $this->logger->info(self::SUCCCESS);
        } catch (\Exception $e) {
            $rtnValue = false;
            $this->logger->error('Error processing '.$this->getRegisterToProcessString()."\n".
                $e->getMessage()."\n".$e->getFile().':'.$e->getLine()
            );

            // We leave the registry empty.
            $this->cleanData($this->registerToProcess->getDate());
            $this->registerToProcess->setStatus(WithholdingTaxRuleFileStatus::FAILED);
        }

        $this->entityManager->persist($this->registerToProcess);
        $this->entityManager->flush();

        return $rtnValue;
    }

    private function cleanData($date)
    {
        $this->logger->info('Cleaning registry');

        switch ($this->registerToProcess->getFileType()) {
            case WithholdingTaxRuleFile::GROSS_INCOME_TYPE:
                $this->withholdingTaxDynamicRuleRepository->deleteByMonthYear(
                    $date,
                    $this->registerToProcess->getProvince(),
                    $this->ruleFileParser->getTaxTypeForDynamicRule()
                );

                break;
            case WithholdingTaxRuleFile::SIRTAC_TYPE:
            case WithholdingTaxRuleFile::MICRO_ENTERPRISE:
                $this->withholdingTaxDynamicRuleRepository->deleteByMonthYearWithNullProvince(
                    $date,
                    $this->ruleFileParser->getTaxTypeForDynamicRule()
                );

                break;
        }
        $this->logger->info(self::SUCCCESS);
    }

    private function insertBulkData(array $fileInfo)
    {
        $this->logger->info('Inserting entries');

        $stream = $this->filesystem->readStream(
            WithholdingTaxRuleFile::DB_FILE_PATH.
            DIRECTORY_SEPARATOR.
            $fileInfo['basename']
        );

        $bulkPrepared = 1;
        $failLines = 0;
        $bulkData = [];
        $registerCounter = 0;
        $registerToProcessDate = $this->registerToProcess->getDate();

        if ($this->ruleFileParser->skipFirstLine()) {
            $row = fgets($stream, 1024);
            $this->ruleFileParser->parse($row);
        }

        while (!feof($stream)) {
            try {
                ++$registerCounter;
                $row = fgets($stream, 1024);
                $register = $this->ruleFileParser->parse($row);

                if ($registerToProcessDate != $register->getMonthYear()) {
                    $this->logger->info('--- Bad registry date');

                    throw new \Exception('Bad registry date');
                }

                $bulkData[] = [
                    $register->taxPayer,
                    $register->getProvinceIdentifier(),
                    $register->getRate(),
                    $register->getMonthYear(),
                    $register->getTaxType(),
                    $register->getProvinceGroup(),
                    $register->getStatusJurisdiction(),
                    $register->getCrc(),
                ];

                ++$bulkPrepared;
            } catch (\Exception $e) {
                ++$failLines;
                $this->logger->info('--- Error parsing line '.$registerCounter.' (Line content: \"'.$row.'\")');
            }

            if (self::MAX_ALLOWED_FAIL_LINES_UNTIL_ABORT <= $failLines) {
                throw new \Exception('Too many errors in parsing, check file format.');
            }

            if (self::ROWS_TO_BULK == $bulkPrepared) {
                $this->insertDynamicRule($bulkData);

                $bulkData = [];
                $bulkPrepared = 0;

                if ($this->force) {
                    $this->logger->info('- Inserted '.($registerCounter + 1).' rows.');
                }
            }
        }
        $this->insertDynamicRule($bulkData);

        fclose($stream);

        $finalImported = $registerCounter - $failLines;
        $this->registerToProcess->setImported($finalImported);
        $this->logger->info($this->getRegisterToProcessString()." processed. Imported $finalImported records.");
        $this->logger->info(self::SUCCCESS);
    }

    private function insertDynamicRule(array $bulkData)
    {
        $bulkInsertQuery = new BulkInsertQuery($this->entityManager->getConnection(), 'withholding_tax_dynamic_rule');
        $bulkInsertQuery->setColumns(['id_fiscal', 'province_id', 'rate', 'month_year', 'tax_type', 'provinces_group_id', 'status_jurisdictions', 'crc']);
        $bulkInsertQuery->setValues($bulkData);
        $bulkInsertQuery->execute();
    }

    private function getFileType()
    {
        $mimeType = $this->filesystem->getMimetype($this->registerToProcess->getDbFile());

        switch ($mimeType) {
            case 'application/zip':
            case 'application/x-zip-compressed':
            case 'multipart/x-zip':
                return 'zip';
            case 'application/x-rar-compressed':
            case 'application/x-rar':
                return 'rar';
            case 'text/plain':
            default:
                return 'txt';
        }
    }

    private function prepareFile(): string
    {
        $this->logger->info('Preparing file');

        //absolute path with the route of the adapter
        $absolutePath = $this->publicStoragePath.$this->registerToProcess->getDbFile();

        // if the file is inside the adapter
        if (!$this->filesystem->has($this->registerToProcess->getDbFile())) {
            throw new \Exception('File '.$absolutePath." doesn't exist.");
        }

        switch ($this->getFileType()) {
            case 'zip':
                $zip = new \ZipArchive();
                if (true === $zip->open($absolutePath)) {
                    $file = '';
                    $this->logger->info('- Decompressing ZIP '.$absolutePath);
                    for ($i = 0; $i < $zip->numFiles; ++$i) {
                        $filename = $zip->getNameIndex($i);
                        $fileinfo = pathinfo($filename);
                        $file = $this->publicStoragePath.
                            WithholdingTaxRuleFile::DB_FILE_PATH.
                            '/'.
                            $fileinfo['basename'];
                        copy('zip://'.$absolutePath.'#'.$filename, $file);
                    }
                    $zip->close();
                } else {
                    throw new \Exception('Can\'t open file '.$absolutePath);
                }

                break;

            case 'rar':
                $rar_arch = \RarArchive::open($absolutePath);
                if (false === $rar_arch) {
                    throw new \Exception('Can\'t open file '.$absolutePath);
                }

                $rar_entries = $rar_arch->getEntries();
                if (false === $rar_entries) {
                    throw new \Exception('Can\'t open file '.$absolutePath);
                }

                $this->logger->info('- Decompressing RAR '.$absolutePath);
                foreach ($rar_entries as $entry) {
                    $file = $this->publicStoragePath.
                        WithholdingTaxRuleFile::DB_FILE_PATH.
                        '/'.
                        $entry->getName();
                    copy('rar://'.$absolutePath.'#'.$entry->getName(), $file);
                }
                $rar_arch->close();

                break;

            case 'txt':
                $file = $this->publicStoragePath.$this->registerToProcess->getDbFile();

                break;
        }

        $this->logger->info(self::SUCCCESS);

        return $file;
    }

    private function getRegisterToProcessString()
    {
        $rtnValue = '';
        switch ($this->registerToProcess->getFileType()) {
            case WithholdingTaxRuleFile::MICRO_ENTERPRISE:
                $rtnValue = 'Micro enterprise registry for '.$this->registerToProcess->getDate();

                break;
            case WithholdingTaxRuleFile::GROSS_INCOME_TYPE:
                $name = $this->registerToProcess->getProvince()->getName();
                $rtnValue = $name.' registry for '.$this->registerToProcess->getDate();

                break;
            case WithholdingTaxRuleFile::SIRTAC_TYPE:
                $rtnValue = self::SIRTAC.' registry for '.$this->registerToProcess->getDate();

                break;
        }

        return $rtnValue;
    }
}
