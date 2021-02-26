<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\City;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Entity\PaymentMethodType;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Role;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Entity\TransactionDetail;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\ApiBundle\Enum\TaxCategoryCode;
use GeoPagos\ApiBundle\Repository\ProvinceRepository;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\SendWithholdingTaxesReportTaxAgenciesCommand;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxSystem;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Helper\WithholdingTaxPeriodHelper;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\HabitualsService;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\ProvinceCertificateGenerator;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Sircar;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Sircar2;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use League\Flysystem\FilesystemInterface;
use Money\Currency;

class SendWithholdingTaxesReportCommandTest extends TestCase
{
    use FactoriesTrait;
    use WithholdingMocks;

    private const MOCK_CONTENT_OF_REPORT_SERVICE = 'content_from_report_service_data';
    private const ROLE_ID = 3;
    private const TAX_CONDITION_ID = 1;
    private const SIGNATURE_LIMIT_AMOUNT = 15000;
    private const TAX_MODE = 'FIXED';
    private const TRANSACTIONS_AMOUNT = 1234.50;
    private const TRANSACTIONS_QTY = 30;
    private const COUNTRY_ID = 10;
    private const PAYMENT_TYPES = [PaymentMethod::TYPE_CREDIT, PaymentMethod::TYPE_DEBIT];

    /** @var ProvinceRepository */
    private $provinceRepository;

    /** @var FilesystemInterface */
    private $fileSystem;

    /** @var EntityManagerInterface */
    private $em;

    /** @var HabitualsService */
    private $habitualsService;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public $configurationManager;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessDay::enable('Carbon\Carbon');

        $this->em = self::$container->get(EntityManagerInterface::class);

        $this->habitualsService = self::$container->get(HabitualsService::class);

        $this->clearLastPeriodStartDatesSettings();
    }

    private function clearLastPeriodStartDatesSettings(): void
    {
        $settings = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findAll();
        foreach ($settings as $setting) {
            /* @var $setting ProvinceWithholdingTaxSetting */
            $setting->setLastPeriodStartDate(null);
            $this->em->persist($setting);
        }
        $this->em->flush();
    }

    /** @test */
    public function arba_tax_agency_file_is_generated_if_withholding_tax_is_tax_and_province_is_pba(): void
    {
        $this->getMockedConfigurationManager();

        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->findOneByName('Buenos Aires');

        $this->arrangeWithheldTaxes($province);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        ]);

        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;
        $expectedFilename = Carbon::now()->startOfMonth()->subMonth()->format('mY').'-arba-'.$province->getAcronym();

        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/arba/'.$expectedFilename;
        $this->assertFileExists($internalPath.'.txt');
        $this->assertFileExists($internalPath.'.xls');

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);

        /* @var $withholdingTax WithholdingTax */
        $withholdingTax = current($withholdingTaxes);
        $this->assertEquals(ProvinceCertificateGenerator::ALL_PAYMENT_TYPES, $withholdingTax->getPaymentType());
        $this->assertEquals(WithholdingTaxStatus::CREATED, $withholdingTax->getStatus());
        $this->assertEquals(WithholdingTaxTypeEnum::TAX, $withholdingTax->getType());
    }

    /** @test */
    public function agip_tax_agency_file_is_generated_if_withholding_tax_is_tax_and_province_is_caba(): void
    {
        $this->getMockedConfigurationManager();

        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->findOneByAcronym('caba');

        $this->arrangeWithheldTaxes($province);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        ]);

        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;

        $expectedFilename = Carbon::now()->startOfMonth()->subMonth()->format('mY').'-agip-'.$province->getAcronym();

        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/agip/'.$expectedFilename;
        $this->assertFileExists($internalPath.'.txt');
        $this->assertFileExists($internalPath.'.xls');

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);

        /* @var $withholdingTax WithholdingTax */
        $withholdingTax = current($withholdingTaxes);
        $this->assertEquals(ProvinceCertificateGenerator::ALL_PAYMENT_TYPES, $withholdingTax->getPaymentType());
        $this->assertEquals(WithholdingTaxStatus::CREATED, $withholdingTax->getStatus());
        $this->assertEquals(WithholdingTaxTypeEnum::TAX, $withholdingTax->getType());
    }

    /** @test */
    public function atm_tax_agency_file_is_generated_if_withholding_tax_is_tax_and_province_is_mendoza(): void
    {
        $this->getMockedConfigurationManager();

        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->findOneByAcronym('mza');

        $this->arrangeWithheldTaxes($province);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        ]);

        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;
        $grouperCuit = self::$container->get(ConfigurationManager::class)->get('grouper_cuit');
        $expectedFilename = 'rr'.$grouperCuit.Carbon::now()->startOfMonth()->subMonth()->format('Ym');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/atm/'.$expectedFilename;
        $this->assertFileExists($internalPath.'.txt');
        $this->assertFileExists($internalPath.'.xls');

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);

        /* @var $withholdingTax WithholdingTax */
        $withholdingTax = current($withholdingTaxes);
        $this->assertEquals(ProvinceCertificateGenerator::ALL_PAYMENT_TYPES, $withholdingTax->getPaymentType());
        $this->assertEquals(WithholdingTaxStatus::CREATED, $withholdingTax->getStatus());
        $this->assertEquals(WithholdingTaxTypeEnum::TAX, $withholdingTax->getType());
    }

    /** @test */
    public function withholding_taxes_are_not_regenerated_for_period_if_latter_period_already_reported(): void
    {
        $this->createProvinceWithholdingTaxSetting(Period::MONTHLY);
        $this->createProvinceWithholdingTaxSetting(Period::SEMI_MONTHLY);

        /* @var $provinceCertificateGenerator ProvinceCertificateGenerator */
        $provinceCertificateGenerator = self::$container->get(ProvinceCertificateGenerator::class);

        $executionDate = WithholdingTaxPeriodHelper::getDayInLastPeriod(Period::SEMI_MONTHLY);
        $this->assertFalse($provinceCertificateGenerator->setDate($executionDate)->executionDateIsValidForCertificateRegeneration());

        $executionDate = WithholdingTaxPeriodHelper::getDayInLastPeriod(Period::MONTHLY);
        $this->assertFalse($provinceCertificateGenerator->setDate($executionDate)->executionDateIsValidForCertificateRegeneration());
    }

    /** @test */
    public function withholding_taxes_are_regenerated_for_period_if_latter_period_not_already_reported(): void
    {
        $this->createProvinceWithholdingTaxSetting(Period::MONTHLY);
        $this->createProvinceWithholdingTaxSetting(Period::SEMI_MONTHLY);

        /* @var $provinceCertificateGenerator ProvinceCertificateGenerator */
        $provinceCertificateGenerator = self::$container->get(ProvinceCertificateGenerator::class);

        $executionDate = WithholdingTaxPeriodHelper::getDayInLastPeriod(Period::SEMI_MONTHLY)->addDays(18);
        $this->assertTrue($provinceCertificateGenerator->setDate($executionDate)->executionDateIsValidForCertificateRegeneration());

        $executionDate = WithholdingTaxPeriodHelper::getDayInLastPeriod(Period::MONTHLY)->addDays(35);
        $this->assertTrue($provinceCertificateGenerator->setDate($executionDate)->executionDateIsValidForCertificateRegeneration());
    }

    private function createProvinceWithholdingTaxSetting(string $periodicity): void
    {
        /* @var $province Province */
        $province = $this->em->getRepository(Province::class)->findOneByName('Buenos Aires');

        $setting = (new ProvinceWithholdingTaxSetting($periodicity))
            ->setWithholdingTaxSystem('ARBA')
            ->setMinAmount(0)
            ->setProvince($province);
        $this->em->persist($setting);
        $this->em->flush();
    }

    /** @test */
    public function sicore_tax_agency_file_is_generated_if_withholding_tax_is_vat_and_setting_is_federal_with_monthly_reporting(
    ): void {
        $this->getMockedConfigurationManager(false, true);
        Carbon::setTestNow(Carbon::now()->startOfMonth()->subMonth());

        /** @var ProvinceWithholdingTaxSetting $semiMonthlyWithholdingTaxSetting */
        $semiMonthlyWithholdingTaxSetting = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
            'period' => Period::SEMI_MONTHLY,
            'withholdingTaxSystem' => WithholdingTaxSystem::VALUE_TAX_ADDED,
            'withholdingTaxType' => WithholdingTaxTypeEnum::VAT,
        ]);

        if ($semiMonthlyWithholdingTaxSetting) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->arrangeFederalWithholdingTax([Carbon::now()->subMonth()]);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        ]);

        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;
        $expectedFilename = Carbon::now()->subMonth()->format('mY').'-sicore-iva-destinatarios';
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');
        $expectedFilename = Carbon::now()->subMonth()->format('mY').'-sicore-iva-detalles';
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/sicore/'.$expectedFilename;
        $this->assertFileExists($internalPath.'.txt');
        $this->assertFileExists($internalPath.'.xls');
    }

    /**
     * @test
     * @dataProvider withholdingTaxesInsidePeriod
     */
    public function sicore_tax_agency_file_is_generated_if_withholding_tax_is_income_tax_and_setting_is_federal_with_semimonthly_reporting(
        Carbon $executionDate,
        array $withholdingTaxDates
    ): void {
        $this->getMockedConfigurationManager(false, false, true);

        /** @var ProvinceWithholdingTaxSetting $semiMonthlyWithholdingTaxSetting */
        $semiMonthlyWithholdingTaxSetting = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
            'period' => Period::SEMI_MONTHLY,
            'withholdingTaxSystem' => WithholdingTaxSystem::INCOME_TAX,
            'withholdingTaxType' => WithholdingTaxTypeEnum::INCOME_TAX,
        ]);

        if (!$semiMonthlyWithholdingTaxSetting) {
            $this->expectNotToPerformAssertions();

            return;
        }
        Carbon::setTestNow($executionDate);

        $this->arrangeFederalWithholdingTax($withholdingTaxDates);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $executionDate->format('Y-m-d'),
        ]);

        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;

        [$month, $q] = $this->getReportMonthAndHalfFromExecutionDate($executionDate);

        $expectedFilename = $month.'-q'.$q.'-sicore-ganancias-destinatarios';
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $expectedFilename = $month.'-q'.$q.'-sicore-ganancias-detalles';
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/sicore/'.$expectedFilename;
        $this->assertFileExists($internalPath.'.txt');
        $this->assertFileExists($internalPath.'.xls');
    }

    /**
     * @test
     * @dataProvider withholdingTaxesInsidePeriod
     */
    public function sire_tax_agency_file_is_generated_if_withholding_tax_is_vat_and_setting_is_federal_with_semimonthly_reporting(
        Carbon $executionDate,
        array $withholdingTaxDates
    ): void {
        $this->markTestSkipped('This is skipped until SIRE is operational');
        $this->getMockedConfigurationManager(false, true);
        Carbon::setTestNow($executionDate);

        /** @var ProvinceWithholdingTaxSetting $semiMonthlyWithholdingTaxSetting */
        $semiMonthlyWithholdingTaxSetting = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
            'period' => Period::SEMI_MONTHLY,
            'withholdingTaxSystem' => WithholdingTaxSystem::VALUE_TAX_ADDED,
            'withholdingTaxType' => WithholdingTaxTypeEnum::VAT,
        ]);

        if (!$semiMonthlyWithholdingTaxSetting) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->arrangeFederalWithholdingTax($withholdingTaxDates);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $executionDate->format('Y-m-d'),
        ]);

        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;

        [$month, $q] = $this->getReportMonthAndHalfFromExecutionDate($executionDate);

        // Details should be sent to the sftp only as txt
        $expectedFilename = $month.'-q'.$q.'-sire-iva-detalles';
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileNotExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        // Details should be persisted in local storage only as txt
        $internalPathForDetails = './tests/../storage/public/files/sire/'.$expectedFilename;
        $this->assertFileExists($internalPathForDetails.'.txt');
        $this->assertFileNotExists($internalPathForDetails.'.xls');
    }

    /**
     * @test
     */
    public function sircar_file_isnt_generated_if_withholding_tax_is_tax_and_province_is_pba(): void
    {
        $this->getMockedConfigurationManager();

        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->findOneByName('Buenos Aires');

        $this->arrangeWithheldTaxes($province);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        ]);
        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;
        $expectedFilename = Carbon::now()->subMonth()->format('mY').'-sircar-'.$province->getAcronym();

        $this->assertFileNotExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileNotExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/sircar/'.$expectedFilename;
        $this->assertFileNotExists($internalPath.'.txt');
        $this->assertFileNotExists($internalPath.'.xls');
    }

    /**
     * @test
     */
    public function sircar2_file_is_generated_if_withholding_tax_is_tax_and_province_is_tucuman(): void
    {
        $this->getMockedConfigurationManager();

        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->findOneByAcronym('tucuman');

        $this->arrangeWithheldTaxes($province);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        ]);
        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;
        $month = Carbon::now()->startOfMonth()->subMonth()->format('mY');

        $expectedFilenameData = Sircar2::DATA_REPORT_BASE_FILENAME.'-'.$month;
        $expectedFilenameSubjects = Sircar2::SUBJECTS_REPORT_BASE_FILENAME.'-'.$month;

        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilenameData.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilenameData.'.xls');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilenameSubjects.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilenameSubjects.'.xls');

        $internalPath = './tests/../storage/public/files/sircar2/'.$expectedFilenameData;
        $this->assertFileExists($internalPath.'.txt');
        $this->assertFileExists($internalPath.'.xls');
    }

    public function withholdingTaxesInsidePeriod(): array
    {
        return [
            [
                'executionDate' => Carbon::now()->day(16),
                'withholdingTaxDates' => [
                    Carbon::now('America/Argentina/Buenos_Aires')->day(1)->startOfDay()->setTimezone('UTC'),
                ],
            ],
            [
                'executionDate' => Carbon::now()->day(16),
                'withholdingTaxDates' => [
                    Carbon::now('America/Argentina/Buenos_Aires')->day(13)->endOfDay()->setTimezone('UTC'),
                ],
            ],
            [
                'executionDate' => Carbon::now()->day(1),
                'withholdingTaxDates' => [
                    Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subMonth()->day(16)->startOfDay()->setTimezone('UTC'),
                ],
            ],
            [
                'executionDate' => Carbon::now()->day(1),
                'withholdingTaxDates' => [
                    Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subMonth()->endOfMonth()->subSecond()->setTimezone('UTC'),
                ],
            ],
            [
                'executionDate' => Carbon::now()->day(1),
                'withholdingTaxDates' => [
                    Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subMonth()->endOfMonth()->subSecond()->setTimezone('UTC'),
                    Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subMonth()->endOfMonth()->subDay()->setTimezone('UTC'),
                    Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subMonth()->endOfMonth()->subWeek()->setTimezone('UTC'),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider withholdingTaxesInsidePeriod
     */
    public function a_province_with_semi_monthly_report_setting_will_report_taxes_withheld_in_appropriate_half_of_month(
        Carbon $executionDate,
        array $withholdingTaxDates
    ): void {
        $this->getMockedConfigurationManager();

        /** @var ProvinceWithholdingTaxSetting $semiMonthlyWithholdingTaxSetting */
        $semiMonthlyWithholdingTaxSetting = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
            'period' => Period::SEMI_MONTHLY,
            'withholdingTaxSystem' => WithholdingTaxSystem::SIRCAR,
        ]);

        if (!$semiMonthlyWithholdingTaxSetting) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $province = $semiMonthlyWithholdingTaxSetting->getProvince();

        $this->arrangeWithheldTaxes($province, $withholdingTaxDates);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $executionDate->format('Y-m-d'),
        ]);
        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;

        [$month, $q] = $this->getReportMonthAndHalfFromExecutionDate($executionDate);

        $expectedFilename = $month.'-'.Sircar::BASE_FILENAME.'-'.$province->getAcronym().'-q'.$q;

        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/sircar/'.$expectedFilename;
        $this->assertFileExists($internalPath.'.txt');
        $this->assertFileExists($internalPath.'.xls');
    }

    public function withholdingTaxesOutsidePeriod(): array
    {
        return [
            [
                'executionDate' => Carbon::now()->day(16),
                'withholdingTaxDates' => [
                    Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subDay()->setTimezone('UTC'),
                    Carbon::now('America/Argentina/Buenos_Aires')->day(16)->startOfDay()->setTimezone('UTC'),
                ],
            ],
            [
                'executionDate' => Carbon::now()->day(1),
                'withholdingTaxDates' => [
                    Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subMonth()->day(15)->endOfDay()->subSecond()->setTimezone('UTC'),
                    Carbon::now('America/Argentina/Buenos_Aires')->day(1)->startOfDay()->setTimezone('UTC'),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider withholdingTaxesOutsidePeriod
     */
    public function a_province_with_semi_monthly_report_setting_will_not_report_taxes_withheld_in_dates_outside_relevant_month_half(
        Carbon $executionDate,
        array $withholdingTaxDates
    ): void {
        $this->getMockedConfigurationManager();

        /** @var ProvinceWithholdingTaxSetting $semiMonthlyWithholdingTaxSetting */
        $semiMonthlyWithholdingTaxSetting = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
            'period' => Period::SEMI_MONTHLY,
            'withholdingTaxSystem' => WithholdingTaxSystem::SIRCAR,
        ]);

        if (!$semiMonthlyWithholdingTaxSetting) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $province = $semiMonthlyWithholdingTaxSetting->getProvince();

        $this->arrangeWithheldTaxes($province, $withholdingTaxDates);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $executionDate->format('Y-m-d'),
        ]);
        $withholdingTaxOutputFolder = self::$container->get(ConfigurationManager::class)->get('withholding_tax_output_folder');
        $sftpBasePath = self::$container->get(ConfigurationManager::class)->get('grouper_sftp_basepath');

        $testOutputDirectory = './tests/../storage/sftp/mpos/Out/'.$sftpBasePath.$withholdingTaxOutputFolder;

        [$month, $q] = $this->getReportMonthAndHalfFromExecutionDate($executionDate);

        $expectedFilename = $month.'-'.Sircar::BASE_FILENAME.'-'.$province->getAcronym().'-q'.$q;

        $this->assertFileNotExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.txt');
        $this->assertFileNotExists($testOutputDirectory.DIRECTORY_SEPARATOR.$expectedFilename.'.xls');

        $internalPath = './tests/../storage/public/files/sircar/'.$expectedFilename;
        $this->assertFileNotExists($internalPath.'.txt');
        $this->assertFileNotExists($internalPath.'.xls');
    }

    private function getReportMonthAndHalfFromExecutionDate(Carbon $executionDate): array
    {
        if ($executionDate->format('j') > 15) {
            $month = Carbon::now()->format('mY');
            $q = 1;
        } else {
            $month = Carbon::now()->startOfMonth()->subDay()->format('mY');
            $q = 2;
        }

        return [$month, $q];
    }

    private function arrangeFederalWithholdingTax(array $saleAvailabilityDates = []): void
    {
        $currentNowDate = Carbon::getTestNow();
        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        foreach ($saleAvailabilityDates as $saleAvailabilityDate) {
            $account = $this->buildMerchantAndUser();

            $subsidiary = $account->getSubsidiaries()->first();

            $deposit = $this->buildWithholdingTaxScenario([
                'amount' => self::TRANSACTIONS_AMOUNT,
                'subsidiary' => $subsidiary,
                'transactions' => 5,
                'saleAvailabilityDate' => $saleAvailabilityDate,
            ]);

            $saleBag = new SaleBag(
                $deposit->getTransactions()->toArray(),
                new Currency($deposit->getCurrencyCode()),
                $deposit->getAvailableDate()
            );
            Carbon::setTestNow($saleAvailabilityDate);
            $this->buildTaxInformation($account->getIdFiscal());
            $this->withholdingTaxService->withhold($saleBag);
        }
        Carbon::setTestNow($currentNowDate);
        $this->em->flush();
    }

    private function buildMerchantAndUser(Province $province = null): Account
    {
        if (!$province) {
            $province = $this->em->getRepository(Province::class)->findOneByName('Buenos Aires');
        }

        /** @var City $city */
        $city = $this->em->getRepository(City::class)->findOneBy(['province' => $province]);
        $country = $province->getCountry();
        /** @var TaxCategory $tax_category */
        $taxCategory = $this->em->getRepository(TaxCategory::class)->find(TaxCategoryCode::NO_INSCRIPTO);
        /** @var Role $role */
        $role = $this->em->getRepository(Role::class)->find(self::ROLE_ID);
        /** @var TaxCondition $taxCondition */
        $taxCondition = $this->em->getRepository(TaxCondition::class)->find(self::TAX_CONDITION_ID);

        /** @var Address $address */
        $address = $this->factory->instance(Address::class, [
            'country' => $country,
            'province' => $province,
            'city' => $city,
        ]);
        $this->em->persist($address);

        /** @var Account $account */
        $account = $this->factory->create(Account::class, ['country' => $country]);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class,
            [
                'account' => $account,
                'address' => $address,
                'taxCondition' => $taxCondition,
                'taxCategory' => $taxCategory,
                'taxMode' => self::TAX_MODE,
                'batchClosureHour' => 1,
                'signatureLimitAmount' => self::SIGNATURE_LIMIT_AMOUNT,
            ]);

        $account->addSubsidiary($subsidiary);

        $this->habitualsService->markSubjectAsHabitual(
            $subsidiary,
            WithholdingTaxTypeEnum::TAX,
            $province
        );

        $this->habitualsService->markSubjectAsHabitual(
            $subsidiary,
            WithholdingTaxTypeEnum::VAT,
            null
        );

        $this->habitualsService->markSubjectAsHabitual(
            $subsidiary,
            WithholdingTaxTypeEnum::INCOME_TAX,
            null
        );

        /** @var User $user */
        $user = $this->factory->create(User::class,
            ['account' => $account, 'role' => $role, 'defaultSubsidiary' => $subsidiary]);

        $user->addSubsidiary($subsidiary);
        $this->em->persist($user);

        $this->em->flush();

        return $account;
    }

    /** @test */
    public function withholding_taxes_are_generated_with_one_row_and_certificate_per_payment_type(): void
    {
        $this->getMockedConfigurationManager(false, false, true);
        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->findOneByAcronym('caba');

        $incomeTaxPeriodicity = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)
            ->findOneByWithholdingTaxType(WithholdingTaxTypeEnum::INCOME_TAX)
            ->getPeriod();

        $testExecutionDate = Carbon::now('America/Argentina/Buenos_Aires')->timezone('UTC');
        $dayPreviousPeriod = WithholdingTaxPeriodHelper::getDayInLastPeriod($incomeTaxPeriodicity, $testExecutionDate);
        Carbon::setTestNow($dayPreviousPeriod);
        $this->arrangeWithheldTaxes(
            $province,
            [$dayPreviousPeriod],
            true
        );

        $cronExecutionDate = WithholdingTaxPeriodHelper::getPeriodStartDate($incomeTaxPeriodicity, $testExecutionDate);
        Carbon::setTestNow($cronExecutionDate);
        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronExecutionDate->format('Y-m-d'),
        ]);

        $wt = $this->em->getRepository(WithholdingTax::class)->findAll();

        foreach (self::PAYMENT_TYPES as $paymentType) {
            $withholdingTax = $this->em->getRepository(WithholdingTax::class)->findOneBy(['paymentType' => $paymentType]);
            $this->assertInstanceOf(WithholdingTax::class, $withholdingTax);
        }
    }

    private function buildWithholdingTaxScenario($data = [], $ensureMultiplePaymentTypes = false): Deposit
    {
        $deposit_data = [];
        $transaction_data = [
            'commissionTax' => 0,
            'commission' => 0,
        ];
        // Build scenario data
        if (isset($data['amount'])) {
            $deposit_data['amount'] = $data['amount'];
            $transaction_data['amount'] = $data['amount'];
        }
        if (isset($data['transaction_amount'])) {
            $transaction_data['amount'] = $data['transact   ion_amount'];
        }

        if (isset($data['subsidiary'])) {
            $transaction_data['subsidiary'] = $data['subsidiary'];
        }
        $transaction_data['availableDate'] = $deposit_data['availableDate'] = $data['saleAvailabilityDate'];

        /** @var Deposit $deposit */
        $deposit = $this->factory->instance(Deposit::class, $deposit_data);

        /** @var Transaction $transaction */
        $transaction_count = array_key_exists('transactions', $data) ? $data['transactions'] : 1;

        $paymentTypes = [
            $this->em->getRepository(PaymentMethodType::class)->findOneBy(['type' => PaymentMethod::TYPE_CREDIT]),
            $this->em->getRepository(PaymentMethodType::class)->findOneBy(['type' => PaymentMethod::TYPE_DEBIT]),
        ];

        /* @var $paymentMethod PaymentMethod */
        $paymentMethod = $this->factory->instance(PaymentMethod::class,
            ['type' => $paymentTypes[array_rand($paymentTypes)]]);

        $this->em->persist($paymentMethod->getProcessor());
        $this->em->persist($paymentMethod);
        for ($i = 0; $i < $transaction_count; ++$i) {
            /* @var $paymentMethod PaymentMethod */
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)->findOneByType(
                $this->getPaymentType(self::PAYMENT_TYPES, $ensureMultiplePaymentTypes, $i)
            );
            /* @var $transactionDetail TransactionDetail */
            $transactionDetail = $this->factory->create(TransactionDetail::class,
                [
                    'name' => 'GeoCredit',
                    'paymentMethod' => $paymentMethod,
                ]
            );

            $transaction = $this->factory->instance(Transaction::class, $transaction_data);
            $transaction->setTransactionDetail($transactionDetail);

            $this->em->persist($transactionDetail);
            $deposit->addTransaction($transaction);
        }

        $this->em->persist($deposit);
        $this->em->flush();

        return $deposit;
    }

    private function getPaymentType(
        array $paymentTypes,
        bool $ensureMultiplePaymentTypes = false,
        ?int $index = null
    ): string {
        if ($ensureMultiplePaymentTypes && isset($index)) {
            // quiero asegurarme de que haya varios PaymentType
            $paymentTypesCount = count($paymentTypes);

            return $paymentTypes[$this->getIndex($paymentTypesCount, $index)];
        }

        // NO quiero asegurarme de que haya varios PaymentType => se obtiene aleatoriamente
        return $paymentTypes[array_rand($paymentTypes)];
    }

    private function getIndex($arrayLength, $i)
    {
        if ($i >= $arrayLength) {
            $newI = $i - $arrayLength;

            return $this->getIndex($arrayLength, $newI);
        }

        return $i;
    }

    private function arrangeWithheldTaxes(
        Province $province,
        array $saleAvailabilityDates = null,
        $ensureMultiplePaymentTypes = false
    ): void {
        if (!$saleAvailabilityDates) {
            $saleAvailabilityDates = [Carbon::now('America/Argentina/Buenos_Aires')->startOfMonth()->subMonth()->timezone('UTC')];
        }
        $currentNowDate = Carbon::getTestNow();
        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        foreach ($saleAvailabilityDates as $saleAvailabilityDate) {
            $account = $this->buildMerchantAndUser($province);

            $subsidiary = $account->getSubsidiaries()->first();

            $deposit = $this->buildWithholdingTaxScenario(
                [
                    'amount' => self::TRANSACTIONS_AMOUNT,
                    'subsidiary' => $subsidiary,
                    'transactions' => 5,
                    'saleAvailabilityDate' => $saleAvailabilityDate,
                ],
                $ensureMultiplePaymentTypes
            );

            $saleBag = new SaleBag(
                $deposit->getTransactions()->toArray(),
                new Currency($deposit->getCurrencyCode()),
                $deposit->getAvailableDate()
            );
            Carbon::setTestNow($saleAvailabilityDate);
            $this->buildTaxInformation($account->getIdFiscal());
            $this->withholdingTaxService->withhold($saleBag);

            $this->em->flush();
        }
        Carbon::setTestNow($currentNowDate);
    }

    public function tearDown(): void
    {
        $this->clearDirectoryForTest('./tests/../storage/sftp/mpos/Out/');
        $this->clearDirectoryForTest('./tests/../storage/public/files/');
        parent::tearDown();
    }
}
