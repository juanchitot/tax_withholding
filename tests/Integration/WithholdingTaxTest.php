<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\City;
use GeoPagos\ApiBundle\Entity\Country;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Role;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Entity\TransactionDetail;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\ApiBundle\Enum\TaxConditionAfipRelationEnum;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\ApiAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\Tests\Traits\RefundTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\HabitualsService;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Money\Currency;

class WithholdingTaxTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;
    use WithholdingMocks;
    use RefundTrait;

    private const PBA_UNPUBLISH_RATE = 1.75;
    private const PBA_DYNAMIC_RULE_RATE = 2.25;
    private const CATAMARCA_INSCRIPTO_CM_RATE = 2.50;
    private const COUNTRY_ID = 10;
    private const TAX_CONDITION_ID = 1;
    private const ROLE_ID = 3;
    private const SIGNATURE_LIMIT_AMOUNT = 15000;
    private const TAX_MODE = 'FIXED';
    private const MENDOZA_GENERAL_RATE = 0.0400;
    private const MENDONZA_SIMPLE_RULE_RATE = 1.5;
    private const SIRTAC_PENALTY_RATE = 1.5;

    public const SALTA_INSCRIPTO_LOCAL_RATE = 3.6;
    public const SALTA_HABITUAL_RATE = 3.6;

    private const MENDOZA = 50;
    private const TUPUNGATO = 5984;

    private const BUENOS_AIRES = 6;
    private const MUNRO = 4789;

    private const INSCRIPTO_LOCAL = 1;
    private const NO_INSCRIPTO = 5;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    /** @var HabitualsService */
    private $habitualsService;

    public function setUp(): void
    {
        parent::setUp();
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->habitualsService = self::$container->get(HabitualsService::class);
    }

    public function cases(): array
    {
        return [
            [
                [
                    'province_id' => 6,         // BSAS
                    'city_id' => 4789,          // Munro
                    'tax_category_id' => 1,
                    'subsidiary_withheld_tax_ini' => false,
                ],
                [
                    'amount' => 150,
                    'transaction_quantity' => 1,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => 150,
                ],
            ],
            [
                [
                    'province_id' => 6,         // BSAS
                    'city_id' => 4789,          // Munro
                    'tax_category_id' => 1,
                    'subsidiary_withheld_tax_ini' => false,
                ],
                [
                    'amount' => 500,
                    'transaction_quantity' => 1,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => $this->calculateFinalAmount(500, self::PBA_UNPUBLISH_RATE),
                ],
            ],
            [
                [
                    'province_id' => 6,         // BSAS
                    'city_id' => 4789,          // Munro
                    'tax_category_id' => 1,
                    'subsidiary_withheld_tax_ini' => false,
                ],
                [
                    'amount' => 500,
                    'transaction_quantity' => 1,
                ],
                [
                    'has_dynamic_rule' => true,
                    'dynamic_rule_rate' => self::PBA_DYNAMIC_RULE_RATE,
                ],
                [
                    'depositAmount' => $this->calculateFinalAmount(500, self::PBA_DYNAMIC_RULE_RATE),
                ],
            ],
            [   // Catamarca - aplica simple rule por categoria (aunque por transacciones no aplicaría hard rule)
                [
                    'province_id' => 10,
                    'city_id' => 4936,
                    'tax_category_id' => 1,
                    'subsidiary_withheld_tax_ini' => false,
                ],
                [
                    'amount' => 5000,
                    'transaction_quantity' => 1,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => $this->calculateFinalAmount(5000, self::CATAMARCA_INSCRIPTO_CM_RATE),
                    'subsidiary_withheld_tax_end' => false,
                ],
            ],
            [   // Catamarca - aplica simple rule por categoria (por montos/transacciones aplicaría hard rule)
                [
                    'province_id' => 10,
                    'city_id' => 4936,
                    'tax_category_id' => 1,
                    'subsidiary_withheld_tax_ini' => false,
                ],
                [
                    'amount' => 1100,
                    'transaction_quantity' => 3,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => $this->calculateFinalAmount(3 * 1100, self::CATAMARCA_INSCRIPTO_CM_RATE),
                    'subsidiary_withheld_tax_end' => false,
                ],
            ],
            [   // Catamarca - aplica hard rule por categoría (No Inscripto) y montos/transacciones suficientes (no tenía retención previa)
                [
                    'province_id' => 10,
                    'city_id' => 4936,
                    'tax_category_id' => 3,
                    'subsidiary_withheld_tax_ini' => false,
                ],
                [
                    'amount' => 1100,
                    'transaction_quantity' => 3,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => $this->calculateFinalAmount(3 * 1100, self::CATAMARCA_INSCRIPTO_CM_RATE),
                    'subsidiary_withheld_tax_end' => true,
                ],
            ],
            [   // Catamarca - aplica hard rule por categoría (No Inscripto) y montos/transacciones suficientes (ya tenía retención previa)
                [
                    'province_id' => 10,
                    'city_id' => 4936,
                    'tax_category_id' => 3,
                    'subsidiary_withheld_tax_ini' => true,
                ],
                [
                    'amount' => 1100,
                    'transaction_quantity' => 3,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => $this->calculateFinalAmount(3 * 1100, self::CATAMARCA_INSCRIPTO_CM_RATE),
                    'subsidiary_withheld_tax_end' => true,
                ],
            ],
            [   // Catamarca - no aplica hard rule por transacciones insuficientes => no hay retención
                [
                    'province_id' => 10,
                    'city_id' => 4936,
                    'tax_category_id' => 3,
                    'subsidiary_withheld_tax_ini' => false,
                ],
                [
                    'amount' => 4000,
                    'transaction_quantity' => 2,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => 8000,
                    'subsidiary_withheld_tax_end' => false,
                ],
            ],
            [   // Catamarca - aplica hard rule porque hubo retencion previa solamente (transacciones insuficientes para activar regla)
                [
                    'province_id' => 10,
                    'city_id' => 4936,
                    'tax_category_id' => 3,
                    'subsidiary_withheld_tax_ini' => true,
                ],
                [
                    'amount' => 2500,
                    'transaction_quantity' => 1,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'depositAmount' => $this->calculateFinalAmount(2500, self::CATAMARCA_INSCRIPTO_CM_RATE),
                    'subsidiary_withheld_tax_end' => true,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider cases
     */
    public function withholding_taxes_are_calculated_correctly(
        $accountData,
        $transactionsData,
        $rulesData,
        $expectedResults
    ): void {
        $account = $this->buildMerchantAndUser(
            $accountData['province_id'],
            $accountData['city_id'],
            $accountData['tax_category_id'],
            $accountData['subsidiary_withheld_tax_ini']
        );

        $subsidiary = $account->getSubsidiaries()->first();

        $availableDate = Carbon::now();
        /** @var Deposit $deposit */
        $deposit = $this->buildWithholdingTaxScenario([
            'amount' => $transactionsData['amount'],
            'subsidiary' => $subsidiary,
            'transactions' => $transactionsData['transaction_quantity'],
            'availableDate' => $availableDate,
        ]);

        if ($rulesData['has_dynamic_rule']) {
            $this->factory->create(WithholdingTaxRuleFile::class, [
                'date' => $availableDate->format('m-Y'),
                'province' => $subsidiary->getProvince(),
                'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
                'status' => WithholdingTaxRuleFileStatus::SUCCESS,
                'imported' => 1,
            ]);
            $this->factory->create(WithholdingTaxDynamicRule::class, [
                'id_fiscal' => $account->getIdFiscal(),
                'month_year' => $availableDate->format('m-Y'),
                'rate' => $rulesData['dynamic_rule_rate'],
                'province' => $subsidiary->getProvince(),
                'type' => WithholdingTaxTypeEnum::TAX_TYPE,
            ]);
        }

        $grossAmount = ($transactionsData['amount'] * $transactionsData['transaction_quantity']);

        $saleBag = new SaleBag(
            $deposit->getTransactions()->toArray(),
            new Currency($deposit->getCurrencyCode()),
            $deposit->getAvailableDate()
        );

        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $deposit->setAmount($saleBag->getNetAmount());

        $this->assertEquals($expectedResults['depositAmount'], $deposit->getAmount());

        if (isset($expectedResults['subsidiary_withheld_tax_end'])) {
            $this->assertEquals(
                $expectedResults['subsidiary_withheld_tax_end'],
                $this->habitualsService->isSubjectMarkedAsHabitual($subsidiary, WithholdingTaxTypeEnum::TAX, $subsidiary->getProvince())
            );
        }
    }

    /**
     * @test
     */
    public function it_does_not_withhold_when_hard_rule_applies_and_there_are_benefits(): void
    {
        $account = $this->buildMerchantAndUser(10, 4936, 3, false);
        $subsidiary = $account->getSubsidiaries()->first();

        $transactionGrossAmount = 1100;
        $transactionQuantity = 2;
        $adjustmentGrossAmount = 50;
        $adjustmentNetAmount = 0;

        /** @var Deposit $deposit */
        $deposit = $this->buildWithholdingTaxScenario([
            'amount' => $transactionGrossAmount,
            'subsidiary' => $subsidiary,
            'transactions' => $transactionQuantity,
        ]);

        $this->addWithholdAdjustmentToDepositTransactions($deposit, $adjustmentGrossAmount, $adjustmentNetAmount);

        $saleBag = new SaleBag(
            $deposit->getTransactions()->toArray(),
            new Currency($deposit->getCurrencyCode()),
            $deposit->getAvailableDate()
        );

        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $deposit->setAmount($saleBag->getNetAmount());

        $depositAmount = ($transactionGrossAmount * $transactionQuantity) + ($adjustmentGrossAmount * $transactionQuantity);
        $this->assertEquals($depositAmount, $deposit->getAmount());
        $this->assertEquals(4, $deposit->getTransactions()->count());
    }

    /**
     * @test
     */
    public function it_withhold_from_hard_rule_when_there_are_benefits_of_type_net_that_exceeds_count_and_amount(): void
    {
        $this->mockWithholdingTaxesConfiguration(false, true, false, false, true);
        $account = $this->buildMerchantAndUser(
            self::BUENOS_AIRES,
            self::MUNRO,
            self::INSCRIPTO_LOCAL
        );
        $subsidiary = $account->getSubsidiaries()->first();

        $transactionGrossAmount = 8000;
        $transactionQuantity = 10;
        $commissionRate = 0;
        $commissionTaxRate = 0;
        $adjustmentGrossAmount = 0;
        $adjustmentNetAmount = 500;

        /** @var Deposit $deposit */
        $deposit = $this->buildWithholdingTaxScenario([
            'amount' => $transactionGrossAmount,
            'subsidiary' => $subsidiary,
            'transactions' => $transactionQuantity,
            'generate_detail' => true,
        ]);

        $this->addWithholdAdjustmentToDepositTransactions($deposit, $adjustmentGrossAmount, $adjustmentNetAmount);

        $saleBag = new SaleBag(
            $deposit->getTransactions()->toArray(),
            new Currency($deposit->getCurrencyCode()),
            $deposit->getAvailableDate()
        );

        $this->buildTaxInformation($account->getIdFiscal());
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $deposit->setAmount($saleBag->getNetAmount());

        $depositAmount = $this->calculateTotalDepositAmount(
            $transactionGrossAmount,
            $transactionQuantity,
            $commissionRate,
            $commissionTaxRate,
            10.5,
            $adjustmentGrossAmount,
            $adjustmentNetAmount
        );

        $this->assertEquals($depositAmount, $deposit->getAmount());
        $this->assertEquals(20, $deposit->getTransactions()->count());
    }

    /**
     * @test
     */
    public function it_withhold_from_hard_rule_when_there_are_benefits_of_type_gross_that_exceeds_count_and_amount(): void
    {
        $account = $this->buildMerchantAndUser(10, 4936, 3);
        $subsidiary = $account->getSubsidiaries()->first();

        $transactionGrossAmount = 600;
        $transactionQuantity = 3;
        $commissionRate = 0;
        $commissionTaxRate = 0;
        $adjustmentGrossAmount = 300;
        $adjustmentNetAmount = 0;

        /** @var Deposit $deposit */
        $deposit = $this->buildWithholdingTaxScenario([
            'amount' => $transactionGrossAmount,
            'subsidiary' => $subsidiary,
            'transactions' => $transactionQuantity,
        ]);

        $this->addWithholdAdjustmentToDepositTransactions($deposit, $adjustmentGrossAmount, $adjustmentNetAmount);

        $saleBag = new SaleBag(
            $deposit->getTransactions()->toArray(),
            new Currency($deposit->getCurrencyCode()),
            $deposit->getAvailableDate()
        );

        $this->buildTaxInformation($account->getIdFiscal());
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $deposit->setAmount($saleBag->getNetAmount());

        $depositAmount = $this->calculateTotalDepositAmount(
            $transactionGrossAmount,
            $transactionQuantity,
            $commissionRate,
            $commissionTaxRate,
            self::CATAMARCA_INSCRIPTO_CM_RATE,
            $adjustmentGrossAmount,
            $adjustmentNetAmount
        );

        $this->assertEquals($depositAmount, $deposit->getAmount());
        $this->assertEquals(6, $deposit->getTransactions()->count());
    }

    /** @test * */
    public function a_withheld_transaction_has_a_log()
    {
        $account = $this->buildMerchantAndUser(
            self::BUENOS_AIRES,
            self::MUNRO,
            self::INSCRIPTO_LOCAL
        );

        $subsidiary = $account->getSubsidiaries()->first();

        $availableDate = Carbon::now();
        $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => $availableDate->format('m-Y'),
            'province' => $subsidiary->getAddress()->getProvince(),
            'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
            'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            'imported' => 1,
        ]);
        $this->factory->create(WithholdingTaxDynamicRule::class, [
            'id_fiscal' => $account->getIdFiscal(),
            'month_year' => $availableDate->format('m-Y'),
            'rate' => $rate = 3.4,
            'province' => $subsidiary->getAddress()->getProvince(),
            'type' => WithholdingTaxTypeEnum::TAX_TYPE,
        ]);

        $grossAmount = (($transactionsDataAmount = 5000) * 2);

        /** @var Deposit $deposit */
        $deposit = $this->buildWithholdingTaxScenario([
            'amount' => $transactionsDataAmount,
            'subsidiary' => $subsidiary,
            'transactions' => 2,
            'availableDate' => $availableDate,
        ]);

        $saleBag = new SaleBag(
            $deposit->getTransactions()->toArray(),
            new Currency($deposit->getCurrencyCode()),
            $deposit->getAvailableDate()
        );

        $this->buildTaxInformation($account->getIdFiscal());
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $deposit->setAmount($saleBag->getNetAmount());

        $this->assertEquals(
            $this->calculateFinalAmount($grossAmount, $rate),
            $deposit->getAmount()
        );

        foreach ($deposit->getTransactions()->toArray() as $transaction) {
            /** @var WithholdingTaxLog $log */
            $log = $this->entityManager->getRepository(WithholdingTaxLog::class)->findOneBy(['transaction' => $transaction]);
            $this->assertNotEmpty($log);
            $this->assertSame(self::INSCRIPTO_LOCAL, $log->getTaxCategory()->getId());
            $this->assertSame(self::NO_INSCRIPTO, $log->getTaxCondition()->getId());
        }

        $this->assertEquals(
            false,
            $this->habitualsService->isSubjectMarkedAsHabitual($subsidiary, WithholdingTaxTypeEnum::TAX, $subsidiary->getProvince())
        );
    }

    /** @test */
    public function should_use_deposit_available_date_month_for_registry_checks()
    {
        $anAmount = 25000;
        $aCommission = 0.05;
        $aTaxCommission = 0.05;
        $aRate = 2.5;

        /** @var Account $account */
        $account = $this->factory->create(Account::class, [
            'idFiscal' => '30241490673',
        ]);
        $address = $this->factory->create(Address::class, [
            'country' => $this->entityManager->getRepository(Country::class)->find(self::COUNTRY_ID),
            'province' => $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES),
            'city' => $this->entityManager->getRepository(Province::class)->find(self::MUNRO),
        ]);
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $account,
            'address' => $address,
            'taxCategory' => $this->entityManager->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL),
        ]);
        $account->addSubsidiary($subsidiary);

        $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => Carbon::now()->addDays(2)->format('m-Y'),
            'province' => $subsidiary->getAddress()->getProvince(),
            'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
            'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            'imported' => 1,
        ]);
        $this->factory->create(WithholdingTaxDynamicRule::class, [
            'id_fiscal' => $account->getIdFiscal(),
            'month_year' => Carbon::now()->addDays(2)->format('m-Y'),
            'rate' => $aRate,
            'province' => $subsidiary->getAddress()->getProvince(),
            'type' => WithholdingTaxTypeEnum::TAX_TYPE,
        ]);

        /** @var Transaction $transaction */
        $transaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
            'amount' => $anAmount,
            'commissionTax' => $aTaxCommission,
            'commission' => $aCommission,
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
            'amount' => $transaction->getAmount(),
            'availableDate' => Carbon::now()->addDays(2),
        ]);
        $aDeposit->addTransaction($transaction);
        $this->entityManager->persist($aDeposit);
        $this->entityManager->flush();

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );

        $this->buildTaxInformation($account->getIdFiscal());
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $saleBag = $withholdingTaxService->withhold($saleBag);

        $commission = ($anAmount * $aCommission) + (($anAmount * $aCommission) * $aTaxCommission);
        $taxableIncome = $anAmount - $commission;
        $amount = round($taxableIncome - ($taxableIncome * ($aRate / 100)), 2);

        $aDeposit->setAmount($saleBag->getNetAmount());
        $this->assertEquals($amount, $aDeposit->getAmount());
    }

    /** @test */
    public function it_can_zero_out_a_deposit_when_it_contains_a_sale_and_the_corresponding_refund(): void
    {
        $account = $this->buildMerchantAndUser(self::BUENOS_AIRES, self::MUNRO, self::INSCRIPTO_LOCAL);

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => 500,
            'availableDate' => Carbon::tomorrow(),
        ]);

        /** @var Transaction $aRefundTransaction */
        $aRefundTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_REFUND,
            'subsidiary' => $aSaleTransaction->getSubsidiary(),
            'amount' => $aSaleTransaction->getAmount(),
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
        ]);
        $aDeposit->addTransaction($aSaleTransaction);
        $aDeposit->addTransaction($aRefundTransaction);

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );

        $this->buildTaxInformation($account->getIdFiscal());
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);

        $aDeposit->setAmount($saleBag->getNetAmount());

        $this->assertEquals(0, $aDeposit->getAmount());
    }

    /** @test */
    public function it_withhold_when_minimum_amount_is_reached_upon_the_calculation_basis(): void
    {
        $transactionAmount = 3000;
        $commission = 0.03;
        $commissionTax = 0.21;

        $sale = $this->buildRuleTestScenario(
            $this->entityManager->getReference(Province::class, self::MENDOZA),
            self::INSCRIPTO_LOCAL,
            1,
            $transactionAmount,
            $commission,
            $commissionTax
        );

        $commissionAmount = round($transactionAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $withholdedAmount = $transactionAmount * 0.02; // 2% From Inscripto Local from Mendoza
        $expected = $transactionAmount - $commissionAmount - $commissionTaxAmount - $withholdedAmount;

        $this->assertEquals($expected, $sale->getNetAmount());
    }

    /** @test * */
    public function generated_withholding_tax_details_have_amount_and_taxable_amount_positive()
    {
        $account = $this->buildMerchantAndUser(self::MENDOZA, self::TUPUNGATO, self::INSCRIPTO_LOCAL);

        $transactionAmount = 3000;

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
        ]);

        /** @var Transaction $aRefundTransaction */
        $aRefundTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_REFUND,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
        ]);

        $aDeposit->addTransaction($aSaleTransaction)
            ->addTransaction($aRefundTransaction);

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );

        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);

        $this->entityManager->flush(); // For persisting WithholdingTaxDetail

        $aDeposit->setAmount($saleBag->getNetAmount());

        // Deposit must be 0
        $this->assertEquals(0, $aDeposit->getAmount());

        $details = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();
        /** @var WithholdingTaxDetail $wtDetail */
        foreach ($details as $wtDetail) {
            $this->assertGreaterThan(0, $wtDetail->getAmount());
            $this->assertGreaterThan(0, $wtDetail->getTaxableIncome());
        }
    }

    /** @test */
    public function it_wont_withhold_when_minimum_amount_isnt_reached_upon_the_calculation_basis(): void
    {
        $transactionAmount = 200;
        $commission = 0.03;
        $commissionTax = 0.21;

        $sale = $this->buildRuleTestScenario(
            $this->entityManager->getReference(Province::class, self::BUENOS_AIRES),
            self::NO_INSCRIPTO,
            1,
            $transactionAmount,
            $commission,
            $commissionTax
        );

        $commissionAmount = round($transactionAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $withholdedAmount = 0; // Gross Amount of transaction is less than $200 from Buenos Aires minimun with registry
        $expected = $transactionAmount - $commissionAmount - $commissionTaxAmount - $withholdedAmount;

        $this->assertEquals($expected, $sale->getNetAmount());
    }

    /** @test */
    public function taxable_income_comes_from_transaction_net_amount_when_rule_calculation_basis_is_NET(): void
    {
        $account = $this->buildMerchantAndUser(self::BUENOS_AIRES, self::MUNRO, self::INSCRIPTO_LOCAL);

        $transactionAmount = 1000;
        $commission = 0.03;
        $commissionTax = 0.21;

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
        ]);
        /** @var Transaction $anotherSaleTransaction */
        $anotherSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
        ]);
        /** @var Transaction $thirdSaleTransaction */
        $thirdSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
        ]);
        $aDeposit->addTransaction($aSaleTransaction);
        $aDeposit->addTransaction($anotherSaleTransaction);
        $aDeposit->addTransaction($thirdSaleTransaction);

        $transactionsCount = $aDeposit->getTransactions()->count();

        $grossAmount = ($transactionAmount * $transactionsCount) - $aSaleTransaction->getCommissionAmount()
            - $aSaleTransaction->getTaxAmount()
            - $anotherSaleTransaction->getCommissionAmount()
            - $anotherSaleTransaction->getTaxAmount()
            - $thirdSaleTransaction->getTaxAmount()
            - $thirdSaleTransaction->getCommissionAmount();

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );

        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $saleBag = $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $aDeposit->setAmount($saleBag->getNetAmount());

        $withholdingTaxDetails = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();
        $actualTotalTaxableIncome = 0;
        $actualTotalAmount = 0;
        foreach ($withholdingTaxDetails as $withholdingTaxDetail) {
            /* @var $withholdingTaxDetail WithholdingTaxDetail */
            $actualTotalTaxableIncome += $withholdingTaxDetail->getTaxableIncome();
            $actualTotalAmount += $withholdingTaxDetail->getAmount();
        }

        $summarizedTransactionTaxableIncome = $aSaleTransaction->getAmountWithoutCommissionAndFinancialCost()
            + $anotherSaleTransaction->getAmountWithoutCommissionAndFinancialCost()
            + $thirdSaleTransaction->getAmountWithoutCommissionAndFinancialCost();

        $this->assertEquals($summarizedTransactionTaxableIncome, $actualTotalTaxableIncome);
    }

    /** @test */
    public function taxable_income_comes_from_transaction_gross_amount_when_rule_calculation_basis_is_GROSS(): void
    {
        $account = $this->buildMerchantAndUser(self::MENDOZA, self::TUPUNGATO, self::INSCRIPTO_LOCAL);

        $transactionAmount = 3000;
        $commission = 0.03;
        $commissionTax = 0.21;

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
        ]);
        /** @var Transaction $anotherSaleTransaction */
        $anotherSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
        ]);
        /** @var Transaction $thirdSaleTransaction */
        $thirdSaleTransaction = $this->factory->create(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'subsidiary' => $account->getSubsidiaries()->first(),
            'amount' => $transactionAmount,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
        ]);
        $aDeposit->addTransaction($aSaleTransaction);
        $aDeposit->addTransaction($anotherSaleTransaction);
        $aDeposit->addTransaction($thirdSaleTransaction);

        $transactionsCount = $aDeposit->getTransactions()->count();

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );

        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $saleBag = $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $aDeposit->setAmount($saleBag->getNetAmount());

        $withholdingTaxDetails = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();
        $actualTotalTaxableIncome = 0;
        $actualTotalAmount = 0;
        foreach ($withholdingTaxDetails as $withholdingTaxDetail) {
            /* @var $withholdingTaxDetail WithholdingTaxDetail */
            $actualTotalTaxableIncome += $withholdingTaxDetail->getTaxableIncome();
            $actualTotalAmount += $withholdingTaxDetail->getAmount();
        }

        $oneTransactionTaxableIncome = $aSaleTransaction->getAmount(); // Mendoza IS GROSS
        $withholdedAmount = round(
            $oneTransactionTaxableIncome * 0.02,
            2
        ) * $transactionsCount; // Mendoza is 2% for Inscripto Local )

        $this->assertEquals($oneTransactionTaxableIncome * $transactionsCount, $actualTotalTaxableIncome);
        $this->assertEquals($withholdedAmount, $actualTotalAmount);
    }

    /** @test */
    public function it_checks_and_updates_subsidiary_tax_condition_before_processing(): void
    {
        $account = $this->buildMerchantAndUser(self::MENDOZA, self::TUPUNGATO, self::INSCRIPTO_LOCAL);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $account->getSubsidiaries()->first();

        /** @var TaxCondition $taxConditionAC */
        $taxConditionAC = $this->entityManager->getRepository(TaxCondition::class)->find(TaxConditionAfipRelationEnum::AC);
        $subsidiary->setTaxCondition($taxConditionAC);
        $this->entityManager->persist($subsidiary);

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
        ]);
        $aDeposit->addTransaction($aSaleTransaction);

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );

        // Tax Identity checker gives NI when the service isn't available
        $this->buildTaxInformation($account->getIdFiscal());
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();
        // Get fresh from database
        $this->entityManager->clear();
        $subsidiary = $this->entityManager->getRepository(Subsidiary::class)->findOneById($subsidiary->getId());

        $this->assertEquals(TaxConditionAfipRelationEnum::NI, $subsidiary->getTaxCondition()->getId());
    }

    private function mockWithholdingTaxesConfiguration(
        bool $iibb,
        bool $vat,
        bool $income,
        bool $configurable_per_province,
        bool $promotions_process_adjustments = false
    ): void {
        $configurationManager = $this->getMockedConfigurationManager(
            $iibb,
            $vat,
            $income,
            $configurable_per_province,
            false,
            false,
            $promotions_process_adjustments
        );
    }

    private function buildWithholdingTaxScenario($data = [])
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
        if (isset($data['availableDate'])) {
            $transaction_data['availableDate'] = $data['availableDate'];
        }

        if (isset($data['subsidiary'])) {
            $transaction_data['subsidiary'] = $data['subsidiary'];
        }
        /** @var Deposit $deposit */
        $deposit = $this->factory->instance(Deposit::class, $deposit_data);
        /** @var Transaction $transaction */
        $transaction_count = array_key_exists('transactions', $data) ? $data['transactions'] : 1;

        for ($i = 0; $i < $transaction_count; ++$i) {
            $transaction = $this->factory->instance(Transaction::class, $transaction_data);
            $this->entityManager->persist($transaction);
            $deposit->addTransaction($transaction);
            if (isset($data['generate_detail'])) {
                /* @var $transactionDetail TransactionDetail */
                $transactionDetail = $this->factory->create(TransactionDetail::class, [
                    'paymentMethod' => $this->entityManager->getRepository(PaymentMethod::class)->find(1),
                ]);
                $this->entityManager->persist($transactionDetail);
                $transaction->setTransactionDetail($transactionDetail);
            }
        }

        $this->entityManager->persist($deposit);
        $this->entityManager->flush();

        return $deposit;
    }

    private function buildMerchantAndUser(
        $provinceId,
        $cityId,
        $taxCategoryId,
        bool $maskAsHabitual = null
    ): Account {
        /** @var Country $country */
        $country = $this->entityManager->getRepository(Country::class)->find(self::COUNTRY_ID);
        /** @var Province $province */
        $province = $this->entityManager->getRepository(Province::class)->find($provinceId);
        /** @var City $city */
        $city = $this->entityManager->getRepository(City::class)->find($cityId); // City from BsAs
        /** @var TaxCategory $tax_category */
        $taxCategory = $this->entityManager->getRepository(TaxCategory::class)->find($taxCategoryId); // Inscripto Local
        /** @var Role $role */
        $role = $this->entityManager->getRepository(Role::class)->find(self::ROLE_ID);
        /** @var TaxCondition $taxCondition */
        $taxCondition = $this->entityManager->getRepository(TaxCondition::class)->find(self::TAX_CONDITION_ID);

        /** @var Address $address */
        $address = $this->factory->instance(Address::class, [
            'country' => $country,
            'province' => $province,
            'city' => $city,
        ]);
        $this->entityManager->persist($address);

        /** @var Account $account */
        $account = $this->factory->create(Account::class, [
            'country' => $country,
            'idFiscal' => $this->generateRandomIdFiscal(),
        ]);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(
            Subsidiary::class,
            [
                'account' => $account,
                'address' => $address,
                'taxCondition' => $taxCondition,
                'taxCategory' => $taxCategory,
                'taxMode' => self::TAX_MODE,
                'batchClosureHour' => 1,
                'signatureLimitAmount' => self::SIGNATURE_LIMIT_AMOUNT,
            ]
        );

        $account->addSubsidiary($subsidiary);

        if ($maskAsHabitual) {
            $this->habitualsService->markSubjectAsHabitual(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $subsidiary->getProvince()
            );
        }

        /** @var User $user */
        $user = $this->factory->create(
            User::class,
            ['account' => $account, 'role' => $role, 'defaultSubsidiary' => $subsidiary]
        );

        $user->addSubsidiary($subsidiary);
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        return $account;
    }
}
