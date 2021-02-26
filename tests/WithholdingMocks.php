<?php

namespace GeoPagos\WithholdingTaxBundle\Tests;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Entity\TransactionDetail;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\WithholdingTaxBundle\Adapter\TaxInformationAdapter;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Model\TaxInformation;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\SceneBuilder;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Service\DecoratedConfigurationManager;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Money\Currency;

trait WithholdingMocks
{
    public static $salta_inscripto_local_rate = 3.6;
    public static $salta_habituality_rate = 3.6;
    public static $fakeTaxType = 'FAKE';
    public static $anotherFakeTaxType = 'A_FAKE';

    /** @Var MockHandler */
    protected static $guzzleMockedHandler;

    protected function setConfigurationManagerValues($settings = []): ConfigurationManagerInterface
    {
        $configurationManager = static::$container->get(ConfigurationManagerInterface::class);
        foreach ($settings as $key => $value) {
            $configurationManager->set($key, $value);
        }

        return $configurationManager;
    }

    protected function getMockedConfigurationManager(
        $iibb = true,
        $vat = false,
        $income_tax = false,
        $iibb_configurable_per_province = false,
        $show_certificates_in_backoffice = false,
        $itbis = false,
        $promotions_process_adjustments = false
    ): ConfigurationManagerInterface {
        $features = [
            ['process_iibb', $iibb],
            ['process_vat', $vat],
            ['process_income_tax', $income_tax],
            ['process_itbis', $itbis],
            ['is_iibb_configurable_per_province', $iibb_configurable_per_province],
            ['uif', false],
            ['account_documentation_state', false],
            ['shipments_module', false],
            ['invoice_module', false],
            ['has_decimal', false],
            ['common_bank_column_feature', false],
            ['backoffice.show_date_in_menu', false],
            ['show_pending_registrations_module', false],
            ['common_bank_column_feature', false],
            ['product_banned_words_module', false],
            ['withholding_tax.certificates.show_in_backoffice', $show_certificates_in_backoffice],
            ['configuration_financial_cost', false],
            ['file_management.section.show_in_backoffice', false],
            ['promotions.process_adjustments', $promotions_process_adjustments],
            ['orderbundle.show_admin_module', false],
            ['withholding_tax.skip_tax_identity_checker_validation', false],
        ];
        foreach ($features as $entry) {
            list($key, $value) = $entry;
            $this->setConfigurationManagerValues([DecoratedConfigurationManager::FEATURES_CONFIGURATION_PREFIX.$key => $value]);
        }

        return $this->setConfigurationManagerValues([
            'decimal_round_threshold' => 50,
            'has_decimal' => true,
            'grouper_cuit' => '4354353454',
        ]);
    }

    protected function sceneBuilder(): SceneBuilder
    {
        return $this->sceneBuilder ?? $this->sceneBuilder = new SceneBuilder($this->entityManager, $this->factory);
    }

    protected function buildRuleTestScenario(
        Province $province,
        $taxCategoryId,
        $transactionCount = 1,
        $transactionAmount = 5000,
        $commission = 0.1,
        $commissionTax = 0.5,
        $registryRate = null
    ) {
        /** @var User $owner */
        $owner = $this->factory->create(User::class);

        /** @var Subsidiary $SaltaSubsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $this->factory->create(Account::class,
                ['idFiscal' => $this->generateRandomIdFiscal()]
            ),
        ])->setAddress($this->factory->create(Address::class, ['province' => $province]));

        if ($taxCategoryId) {
            $subsidiary->setTaxCategory($this->entityManager->getRepository(TaxCategory::class)->find($taxCategoryId));
        }

        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);
        $account->setOwner($owner);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
        ]);

        for ($i = 0; $i < $transactionCount; ++$i) {
            $aSaleTransaction = $this->buildTransaction(
                $subsidiary,
                Carbon::now(),
                $transactionAmount,
                $commission,
                $commissionTax
            );

            $aDeposit->addTransaction($aSaleTransaction);
        }

        if (!empty($registryRate)) {
            // Generate Registry entrance for period.
            $this->factory->create(WithholdingTaxRuleFile::class, [
                'date' => Carbon::now()->format('m-Y'),
                'province' => $province,
                'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
                'status' => WithholdingTaxRuleFileStatus::SUCCESS,
                'imported' => 1,
            ]);

            $this->factory->create(WithholdingTaxDynamicRule::class, [
                'id_fiscal' => $account->getIdFiscal(),
                'month_year' => Carbon::now()->format('m-Y'),
                'rate' => $registryRate,
                'province' => $province,
                'type' => WithholdingTaxTypeEnum::TAX_TYPE,
            ]);
        }

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );
        $taxInformation = $this->buildTaxInformation($account->getIdFiscal());

        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);

        $this->entityManager->flush();

        return $saleBag;
    }

    public function buildTransaction(
        Subsidiary $subsidiary,
        Carbon $transactionDate,
        $amount,
        $commission,
        $commmissionTax,
        $type = Transaction::TYPE_SALE
    ): Transaction {
        /** @var Transaction $aTransaction */
        $aTransaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commmissionTax,
            'amount' => $amount,
            'availableDate' => $transactionDate,
            'typeId' => $type,
        ]);

        /* @var $aTransactionDetail TransactionDetail */
        $aTransactionDetail = $this->factory->create(TransactionDetail::class,
            [
                'paymentMethod' => $this->entityManager->getRepository(PaymentMethod::class)->find(1),
                'account' => $subsidiary->getAccount(),
                'subsidiary' => $subsidiary,
            ]
        );

        $aTransaction->setTransactionDetail($aTransactionDetail);
        $this->entityManager->persist($aTransactionDetail);
        $this->entityManager->persist($aTransaction);

        return $aTransaction;
    }

    public function buildTaxInformation($idFiscal, array $additionalData = [])
    {
        $taxInformationAdapter = $this->setTaxInformationMock($idFiscal, $additionalData);

        return $taxInformationAdapter->taxInformation($idFiscal);
    }

    private function mockGuzzleResponse($status = 200, string $content = ''): void
    {
        self::$guzzleMockedHandler = static::$container->get('guzzle.mocked_handler');
        self::$guzzleMockedHandler->append(
            new Response($status, [], $content)
        );
    }

    protected function generateRandomIdFiscal()
    {
        return substr(str_replace('.', '', microtime(true)), -10);
    }

    protected function setTaxInformationMock($idFiscal, array $additionalData = [], $responses = []): TaxInformationAdapter
    {
        /* @var $taxInformationAdapter TaxInformationAdapter */
        $taxInformationAdapter = static::$container->get(TaxInformationAdapter::class);
        $data = array_merge([
            'id' => $idFiscal,
            'iva' => TaxInformation::NI,
            'monotributo' => TaxInformation::NI,
            'incomeTax' => null,
        ], $additionalData);
        $this->mockGuzzleResponse(200, json_encode($data));

        return $taxInformationAdapter;
    }

    private function calculateTotalDepositAmount(
        $transactionGrossAmount,
        $transactionsQuantity,
        $commissionRate,
        $commissionTaxRate,
        $taxRate,
        $adjustmentGrossAmount = 0,
        $adjustmentNetAmount = 0
    ): float {
        $transactionCommission = $transactionGrossAmount * $commissionRate;
        $taxCommission = $transactionCommission * $commissionTaxRate;
        $withhold = ($transactionGrossAmount + $adjustmentGrossAmount + $adjustmentNetAmount) * ($taxRate / 100);

        $transactionNet = ($transactionGrossAmount - $transactionCommission - $taxCommission - $withhold);
        $adjustmentsAmount = ($transactionsQuantity * $adjustmentGrossAmount) + ($transactionsQuantity * $adjustmentNetAmount);

        return round(($transactionNet * $transactionsQuantity) + $adjustmentsAmount, 2);
    }

    protected function addWithholdAdjustmentToDepositTransactions(
        Deposit $deposit,
        $adjustmentGrossAmount = 0,
        $adjustmentNetAmount = 0
    ) {
        foreach ($deposit->getTransactions() as $transaction) {
            if ($adjustmentGrossAmount) {
                $this->addWithholdAdjustmentTransactionToDeposit(
                    $deposit,
                    $transaction,
                    $adjustmentGrossAmount,
                    Transaction::WITHHOLD_ADJUSTMENT_GROSS
                );
            }

            if ($adjustmentNetAmount) {
                $this->addWithholdAdjustmentTransactionToDeposit(
                    $deposit,
                    $transaction,
                    $adjustmentNetAmount,
                    Transaction::WITHHOLD_ADJUSTMENT_NET
                );
            }
        }
        $this->entityManager->flush();
    }

    protected function addWithholdAdjustmentTransactionToDeposit(
        Deposit $deposit,
        Transaction $transaction,
        $transactionAmount,
        $typeId
    ) {
        /** @var Transaction $adjustment */
        $adjustment = $this->factory->instance(Transaction::class, [
            'amount' => $transactionAmount,
            'typeId' => $typeId,
            'subsidiary' => $transaction->getSubsidiary(),
            'commissionTax' => 0,
            'commission' => 0,
        ]);
        $adjustment->setTransactionId($transaction->getId());
        $this->entityManager->persist($adjustment);
        $deposit->addTransaction($adjustment);
    }

    private function calculateFinalAmount($base, $hold)
    {
        return $base - ($base * $hold / 100);
    }
}
