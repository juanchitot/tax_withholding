<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\City;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
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
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\SendWithholdingTaxesReportAccountsCommand;
use GeoPagos\WithholdingTaxBundle\Command\SendWithholdingTaxesReportTaxAgenciesCommand;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxSystem;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\HabitualsService;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use League\Flysystem\FilesystemInterface;
use Money\Currency;

class SendSubsidiaryWithheldTaxesReportTest extends TestCase
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

        Carbon::setTestNow(Carbon::now());
        BusinessDay::enable('Carbon\Carbon');

        $this->em = self::$container->get(EntityManagerInterface::class);

        $this->habitualsService = self::$container->get(HabitualsService::class);
        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);

        $this->clearLastPeriodStartDatesSettings();
    }

    /**
     * @test
     */
    public function sicore_subsidiary_monthly_file_is_generated_if_withholding_tax_is_vat(): void
    {
        $this->markTestSkipped('Skipped mientras se desarrollan los nuevos tests');

        $this->mockWithholdingTaxesConfiguration(false, true);

        /** @var ProvinceWithholdingTaxSetting $semiMonthlyWithholdingTaxSetting */
        $semiMonthlyWithholdingTaxSetting = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
            'period' => Period::MONTHLY,
            'withholdingTaxSystem' => WithholdingTaxSystem::VALUE_TAX_ADDED,
            'withholdingTaxType' => WithholdingTaxTypeEnum::VAT,
        ]);

        if ($semiMonthlyWithholdingTaxSetting) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $taxAgencyReportDate = Carbon::now()->startOfMonth();
        $saleDates = [
            $taxAgencyReportDate->copy()->subMonth()->day(19),
            $taxAgencyReportDate->copy()->subMonth()->day(28),
            $taxAgencyReportDate->copy()->subMonth()->day(22),
        ];

        $this->arrangeFederalWithholdingTax($taxAgencyReportDate, $saleDates);

        $this->runCommandWithParameters(SendWithholdingTaxesReportAccountsCommand::NAME, [
            '--month' => $taxAgencyReportDate->copy()->subMonth()->format('Ym'),
        ]);

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $maxExpectedCertificateNumbers = [1, 2, 3, 4, 5, 6];
        foreach ($withholdingTaxes as $withholdingTax) {
            /* @var $withholdingTax WithholdingTax */
            $this->assertContains($withholdingTax->getPaymentType(),
                [PaymentMethod::TYPE_CREDIT, PaymentMethod::TYPE_DEBIT]);
            $this->assertEquals(WithholdingTaxStatus::SENT, $withholdingTax->getStatus());
            $this->assertEquals(WithholdingTaxTypeEnum::VAT, $withholdingTax->getType());
            $this->assertContains($withholdingTax->getCertificateNumber(), $maxExpectedCertificateNumbers);

            if (false !== ($key = array_search($withholdingTax->getCertificateNumber(), $maxExpectedCertificateNumbers,
                    false))) {
                unset($maxExpectedCertificateNumbers[$key]);
            }
        }
    }

    /**
     * @test
     */
    public function sicore_subsidiary_monthly_file_is_generated_if_withholding_tax_is_income_tax(): void
    {
        $this->markTestSkipped('Skipped mientras se desarrollan los nuevos tests');

        $this->mockWithholdingTaxesConfiguration(false, false, true);

        /** @var ProvinceWithholdingTaxSetting $semiMonthlyWithholdingTaxSetting */
        $semiMonthlyWithholdingTaxSetting = $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
            'period' => Period::MONTHLY,
            'withholdingTaxSystem' => WithholdingTaxSystem::INCOME_TAX,
            'withholdingTaxType' => WithholdingTaxTypeEnum::INCOME_TAX,
        ]);

        if ($semiMonthlyWithholdingTaxSetting) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $taxAgencyReportDate = Carbon::now()->startOfMonth();
        $saleDates = [
            $taxAgencyReportDate->copy()->subMonth()->day(19),
            $taxAgencyReportDate->copy()->subMonth()->day(28),
            $taxAgencyReportDate->copy()->subMonth()->day(22),
        ];

        $this->arrangeFederalWithholdingTax($taxAgencyReportDate, $saleDates);

        $this->runCommandWithParameters(SendWithholdingTaxesReportAccountsCommand::NAME, [
            '--month' => $taxAgencyReportDate->copy()->subMonth()->format('Ym'),
        ]);

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $maxExpectedCertificateNumbers = [1, 2, 3, 4, 5, 6];
        foreach ($withholdingTaxes as $withholdingTax) {
            /* @var $withholdingTax WithholdingTax */
            $this->assertContains($withholdingTax->getPaymentType(),
                [PaymentMethod::TYPE_CREDIT, PaymentMethod::TYPE_DEBIT]);
            $this->assertEquals(WithholdingTaxStatus::SENT, $withholdingTax->getStatus());
            $this->assertEquals(WithholdingTaxTypeEnum::INCOME_TAX, $withholdingTax->getType());
            $this->assertContains($withholdingTax->getCertificateNumber(), $maxExpectedCertificateNumbers);

            if (false !== ($key = array_search($withholdingTax->getCertificateNumber(), $maxExpectedCertificateNumbers,
                    false))) {
                unset($maxExpectedCertificateNumbers[$key]);
            }
        }
    }

    private function arrangeFederalWithholdingTax(Carbon $taxAgencyReportDate, array $saleAvailabilityDates): void
    {
        $account = $this->buildMerchantAndUser();

        $subsidiary = $account->getSubsidiaries()->first();

        foreach ($saleAvailabilityDates as $saleAvailabilityDate) {
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

            $this->buildTaxInformation($account->getIdFiscal());
            $this->withholdingTaxService->withhold($saleBag);
        }

        $this->em->flush();

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $taxAgencyReportDate->format('Y-m-d'),
        ]);
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
            $transaction_data['amount'] = $data['transaction_amount'];
        }

        if (isset($data['subsidiary'])) {
            $transaction_data['subsidiary'] = $data['subsidiary'];
        }
        $transaction_data['availableDate'] = $deposit_data['availableDate'] = $data['saleAvailabilityDate'];

        /** @var Deposit $deposit */
        $deposit = $this->factory->instance(Deposit::class, $deposit_data);
        /** @var Transaction $transaction */
        $transaction_count = array_key_exists('transactions', $data) ? $data['transactions'] : 1;

        for ($i = 0; $i < $transaction_count; ++$i) {
            /* @var $paymentMethod PaymentMethod */
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)->findOneByType(
                $this->getPaymentType(self::PAYMENT_TYPES, $ensureMultiplePaymentTypes, $i)
            );
            /* @var $transactionDetail TransactionDetail */
            $transactionDetail = $this->factory->instance(TransactionDetail::class, ['name' => 'GeoCredit']);
            $transactionDetail->setPaymentMethod($paymentMethod);
            $transaction = $this->factory->instance(Transaction::class, $transaction_data);
            $transaction->setTransactionDetail($transactionDetail);
            $this->em->persist($transaction);
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

    private function mockWithholdingTaxesConfiguration(bool $iibb = true, bool $vat = false, bool $income = false): void
    {
        $configurationManager = $this->getMockedConfigurationManager($iibb, $vat, $income);
    }

    public function tearDown(): void
    {
        $this->clearDirectoryForTest('./tests/../storage/sftp/mpos/Out/');
        $this->clearDirectoryForTest('./tests/../storage/public/certificate/');
        parent::tearDown();
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
}
