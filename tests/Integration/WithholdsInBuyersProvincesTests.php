<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Entity\TransactionDetail;
use GeoPagos\ApiBundle\Exceptions\AmountUnavailableException;
use GeoPagos\ApiBundle\Model\StaticConstant;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxCategoryPerProvince;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Exceptions\EmptyTransactionException;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\HabitualsService;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\Scene;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Money\Currency;
use Symfony\Component\HttpFoundation\ParameterBag;

class WithholdsInBuyersProvincesTests extends TestCase
{
    use FactoriesTrait;
    use WithholdingMocks;

    private const PBA_DYNAMIC_RULE_RATE = 2.25;

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
        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $this->habitualsService = self::$container->get(HabitualsService::class);
        $this->getMockedConfigurationManager(
            true,
            false,
            false,
            true,
            false,
            false,
            false
        );
    }

    public function withholdingTaxCategoryPerProvinceSuccessCases(): array
    {
        return [
            [
                // Province from transaction is configured - Simple Rule - SHOULD WITHHOLD WITH THE TAX CATEGORY CONFIGURED
                [
                    'province_id' => 66,
                    'tax_category_id' => 1,
                ],
                [
                    'transaction_province_id' => 66,
                    'transaction_quantity' => 1,
                    'amount' => 5000,
                    'input_mode' => StaticConstant::READ_MODE_ECOMMERCE,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'deposit_amount' => $this->calculateFinalAmount(5000, (self::$salta_inscripto_local_rate + 1.5)),
                    'tax_category_id' => self::INSCRIPTO_LOCAL,
                ],
            ],
            [
                // Province from transaction is same as province of subsidiary - Simple Rule - SHOULD WITHHOLD WITH THE TAX CATEGORY OF THE SUBSIDIARY
                [
                    'province_id' => 10,
                    'tax_category_id' => 4,
                ],
                [
                    'transaction_province_id' => 10,
                    'transaction_quantity' => 1,
                    'amount' => 5000,
                    'input_mode' => StaticConstant::READ_MODE_ECOMMERCE,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'deposit_amount' => $this->calculateFinalAmount(5000, 2.50),
                    'tax_category_id' => self::INSCRIPTO_LOCAL,
                ],
            ],
            [
                // Province from transaction is configured - Dynamic Rule - SHOULD WITHHOLD
                [
                    'province_id' => 6,
                    'tax_category_id' => 1,
                ],
                [
                    'transaction_province_id' => 6,
                    'transaction_quantity' => 1,
                    'amount' => 500,
                    'input_mode' => StaticConstant::READ_MODE_ECOMMERCE,
                ],
                [
                    'has_dynamic_rule' => true,
                    'dynamic_rule_rate' => self::PBA_DYNAMIC_RULE_RATE,
                ],
                [
                    'deposit_amount' => $this->calculateFinalAmount(500, 2.25),
                    'tax_category_id' => self::INSCRIPTO_LOCAL,
                ],
            ],
            [
                // Province from transaction is configured - Hard Rule - SHOULD WITHHOLD WITH HARD RULE OF BUYER'S PROVINCE
                [
                    'province_id' => 66,
                    'tax_category_id' => 3,
                ],
                [
                    'transaction_province_id' => 66,
                    'transaction_quantity' => 3,
                    'amount' => 1500,
                    'input_mode' => StaticConstant::READ_MODE_ECOMMERCE,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'deposit_amount' => $this->calculateFinalAmount(3 * 1500, self::$salta_habituality_rate),
                    'tax_category_id' => 3,
                ],
            ],
            [
                // Province from transaction is NOT configured - SHOULD WITHHOLD AS NO INSCRIPTO OF BUYER'S PROVINCE
                [
                    'province_id' => 6,
                    'tax_category_id' => 1,
                ],
                [
                    'transaction_province_id' => 18,
                    'transaction_quantity' => 11,
                    'amount' => 5000,
                    'input_mode' => StaticConstant::READ_MODE_ECOMMERCE,
                ],
                [
                    'has_dynamic_rule' => false,
                    'dynamic_rule_rate' => null,
                ],
                [
                    'deposit_amount' => $this->calculateFinalAmount(11 * 5000, 2.50),
                    'tax_category_id' => 3,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider withholdingTaxCategoryPerProvinceSuccessCases
     *
     * @param $categoryPerProvinceScenario
     * @param $transactionsScenario
     * @param $expectedResultScenario
     *
     * @throws AmountUnavailableException
     * @throws EmptyTransactionException
     */
    public function it_withhold_tax_from_buyer_province_when_feature_is_enabled_and_properly_configured(
        array $categoryPerProvinceScenario,
        array $transactionsScenario,
        array $rulesScenario,
        array $expectedResultScenario
    ): void {
        $scene = $this->buildAccountAndSubsidiary(self::INSCRIPTO_LOCAL, 10);
        $account = $scene->getAccount();
        $subsidiary = $account->getSubsidiaries()->first();

        $deposit = $this->buildWithholdingTaxTransactionsPerProvinceScenario(
            $subsidiary,
            $categoryPerProvinceScenario,
            $rulesScenario,
            $transactionsScenario
        );

        $saleBag = new SaleBag(
            $deposit->getTransactions()->toArray(),
            new Currency($deposit->getCurrencyCode()),
            $deposit->getAvailableDate()
        );

        $this->withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $deposit->setAmount($saleBag->getNetAmount());

        $this->assertEquals(
            $expectedResultScenario['deposit_amount'],
            $deposit->getAmount()
        );

        foreach ($deposit->getTransactions()->toArray() as $transaction) {
            /** @var WithholdingTaxLog $log */
            $log = $this->entityManager->getRepository(WithholdingTaxLog::class)->findOneBy([
                'transaction' => $transaction,
            ]);

            $this->assertNotEmpty($log);

            $this->assertSame(
                $transactionsScenario['transaction_province_id'],
                $log->getProvince()->getId()
            );

            $this->assertSame(
                $expectedResultScenario['tax_category_id'],
                $log->getTaxCategory()->getId()
            );
        }
    }

    /** @test */
    public function one_subsidiary_can_be_habitual_in_one_province_and_dont_in_another()
    {
        $scene = $this->buildAccountAndSubsidiary(self::NO_INSCRIPTO, 10);

        $subsidiary = $scene->getAccount()->getSubsidiaries()->first();
        $deposit = $this->createDeposit(10000);

        $aProvince = $this->entityManager->getRepository(Province::class)->find(82);
        for ($i = 0; $i < 10; ++$i) {
            $this->createTransaction(
                $deposit,
                $subsidiary,
                $aProvince,
                10000,
                StaticConstant::READ_MODE_ECOMMERCE,
                Carbon::now()
            );
        }

        $santaFeAmount = $this->calculateFinalAmount(10 * 10000, 7.00);

        $anotherProvince = $this->entityManager->getRepository(Province::class)->find(90);
        for ($i = 0; $i < 5; ++$i) {
            $this->createTransaction(
                $deposit,
                $subsidiary,
                $anotherProvince,
                5000,
                StaticConstant::READ_MODE_ECOMMERCE,
                Carbon::now()
            );
        }

        $tucumanAmount = $this->calculateFinalAmount(5 * 5000, 3.50);

        $yetAnotherProvince = $this->entityManager->getRepository(Province::class)->find(86);
        for ($i = 0; $i < 2; ++$i) {
            $this->createTransaction(
                $deposit,
                $subsidiary,
                $yetAnotherProvince,
                6000,
                StaticConstant::READ_MODE_ECOMMERCE,
                Carbon::now()
            );
        }

        $santiagoAmount = $this->calculateFinalAmount(2 * 6000, 0);

        $this->entityManager->persist($deposit);
        $this->entityManager->flush();

        $saleBag = new SaleBag(
            $deposit->getTransactions()->toArray(),
            new Currency($deposit->getCurrencyCode()),
            $deposit->getAvailableDate()
        );

        $this->withholdingTaxService->withhold($saleBag);
        $this->entityManager->flush();

        $deposit->setAmount($saleBag->getNetAmount());

        $totalAmount = $santaFeAmount + $tucumanAmount + $santiagoAmount;

        $this->assertEquals(
            $totalAmount,
            $deposit->getAmount()
        );

        $this->assertTrue(
            $this->habitualsService->isSubjectMarkedAsHabitual(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $aProvince
            )
        );

        $this->assertTrue(
            $this->habitualsService->isSubjectMarkedAsHabitual(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $anotherProvince
            )
        );

        $this->assertFalse(
            $this->habitualsService->isSubjectMarkedAsHabitual(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $yetAnotherProvince
            )
        );
    }

    private function buildWithholdingTaxTransactionsPerProvinceScenario(
        Subsidiary $subsidiary,
        array $categoryPerProvinceScenario,
        array $rulesScenario,
        array $transactionsScenario
    ) {
        $province = $this->entityManager->getRepository(Province::class)->find(
            $categoryPerProvinceScenario['province_id']
        );

        $taxCategory = $this->entityManager->getRepository(TaxCategory::class)->find(
            $categoryPerProvinceScenario['tax_category_id']
        );

        $withholdingTaxCategoryPerProvince = $this->factory->instance(WithholdingTaxCategoryPerProvince::class, [
            'subsidiary' => $subsidiary,
            'province' => $province,
            'taxCategory' => $taxCategory,
        ]);

        $this->entityManager->persist($withholdingTaxCategoryPerProvince);

        /** @var Deposit $deposit */
        $deposit = $this->createDeposit($transactionsScenario['amount']);

        $transactionProvince = $this->entityManager->getRepository(Province::class)->find(
            $transactionsScenario['transaction_province_id']
        );

        $availableDate = Carbon::now();

        if ($rulesScenario['has_dynamic_rule']) {
            $this->factory->create(WithholdingTaxRuleFile::class, [
                'date' => $availableDate->format('m-Y'),
                'province' => $transactionProvince,
                'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
                'status' => WithholdingTaxRuleFileStatus::SUCCESS,
                'imported' => 1,
            ]);

            $this->factory->create(WithholdingTaxDynamicRule::class, [
                'id_fiscal' => $subsidiary->getAccount()->getIdFiscal(),
                'month_year' => $availableDate->format('m-Y'),
                'rate' => $rulesScenario['dynamic_rule_rate'],
                'province' => $transactionProvince,
                'type' => WithholdingTaxTypeEnum::TAX_TYPE,
            ]);
        }

        $transaction_count = $transactionsScenario['transaction_quantity'];
        for ($i = 0; $i < $transaction_count; ++$i) {
            $this->createTransaction(
                $deposit,
                $subsidiary,
                $transactionProvince,
                $transactionsScenario['amount'],
                $transactionsScenario['input_mode'],
                $availableDate
            );
        }

        $this->entityManager->persist($deposit);
        $this->entityManager->flush();

        return $deposit;
    }

    private function createDeposit(float $amount): Deposit
    {
        return $this->factory->instance(Deposit::class, [
            'amount' => $amount,
        ]);
    }

    private function createTransaction(
        Deposit $deposit,
        Subsidiary $subsidiary,
        Province $province,
        float $amount,
        string $inputMode,
        $availableDate
    ): void {
        $transactionDetail = new TransactionDetail();
        $transactionDetail->setProvince($province);

        /** @var Transaction $transaction */
        $transaction = $this->factory->instance(Transaction::class, [
            'typeId' => Transaction::TYPE_SALE,
            'commission' => 0,
            'commissionTax' => 0,
            'amount' => $amount,
            'inputMode' => $inputMode,
            'subsidiary' => $subsidiary,
            'transactionDetail' => $transactionDetail,
            'availableDate' => $availableDate,
        ]);

        $transaction->setTransactionDetail($transactionDetail);
        $this->entityManager->persist($transaction);
        $deposit->addTransaction($transaction);
    }

    private function buildAccountAndSubsidiary($taxCategoryId, $provinceId): Scene
    {
        $params = new ParameterBag([
            'subsidiary.taxCategoryId' => $taxCategoryId,
            'subsidiary.address.provinceId' => $provinceId,
        ]);

        return $this->sceneBuilder()
            ->reset()
            ->buildAccount($params)
            ->buildSubsidiary($params)
            ->getResult();
    }
}
