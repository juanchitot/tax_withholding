<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Entity\TransactionDetail;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\ApiBundle\Model\StaticConstant;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\DepositBundle\Enum\DepositState;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\SendWithholdingTaxesReportTaxAgenciesCommand;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Money\Currency;

class WithholdingTaxesCreationStrategyTest extends TestCase
{
    use FactoriesTrait;
    use WithholdingMocks;

    private const MOCK_CONTENT_OF_REPORT_SERVICE = 'content_from_report_service_data';

    private const PBA_PROVINCE_ID = 6;
    private const MENDOZA_PROVINCE_ID = 50;
    private const CABA_PROVINCE_ID = 10;
    private const INSCRIPTO_LOCAL = 1;

    /** @var EntityManagerInterface */
    private $em;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    /** @var ConfigurationManager */
    public $configurationManager;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessDay::enable('Carbon\Carbon');

        $this->em = self::$container->get(EntityManagerInterface::class);
        $this->getMockedConfigurationManager(true, false, false, false);

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
    }

    /** @test */
    public function by_default_withholding_tax_are_created_based_on_transaction_available_date(): void
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        $this->generateWithholdedScenario(
            self::MENDOZA_PROVINCE_ID,
            self::INSCRIPTO_LOCAL,
            $transactionDate
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);
        $this->assertEquals(self::INSCRIPTO_LOCAL, $withholdingTaxes[0]->getTaxCategory()->getId());
    }

    /** @test */
    public function if_they_are_for_tax_type_they_are_created_with_province_reference(): void
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        $this->generateWithholdedScenario(
            self::MENDOZA_PROVINCE_ID,
            self::INSCRIPTO_LOCAL,
            $transactionDate
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        /* @var WithholdingTax[] $withholdingTaxes */
        $this->assertCount(1, $withholdingTaxes);
        $this->assertEquals(self::MENDOZA_PROVINCE_ID, $withholdingTaxes[0]->getProvince()->getId());
        $this->assertEquals(self::INSCRIPTO_LOCAL, $withholdingTaxes[0]->getTaxCategory()->getId());
    }

    /** @test */
    public function can_use_deposit_to_deposit_as_withholding_tax_creation(): void
    {
        $this->setConfigurationManagerValues([
            'tax_generation_strategy' => 'deposit_to_deposit',
        ]);

        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 2:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        $this->generateWithholdedScenario(
            self::MENDOZA_PROVINCE_ID,
            self::INSCRIPTO_LOCAL,
            $transactionDate,
            $transactionDate->copy()->day(20)
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /** @var WithholdingTax[] $withholdingTaxes */
        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);
        $this->assertEquals($transactionDate->day, $withholdingTaxes[0]->getDate()->day);
        $this->assertEquals(self::INSCRIPTO_LOCAL, $withholdingTaxes[0]->getTaxCategory()->getId());
    }

    /** @test */
    public function should_use_buyer_declared_province_in_ecommerce_payments_when_ff_is_enabled(): void
    {
        $this->getMockedConfigurationManager(true, false, false, true);
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        $this->generateWithholdedScenario(
            self::CABA_PROVINCE_ID,
            self::INSCRIPTO_LOCAL,
            $transactionDate,
            null,
            self::MENDOZA_PROVINCE_ID
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        $withholdingTaxes = $this->em->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);
        $this->assertEquals(self::MENDOZA_PROVINCE_ID, $withholdingTaxes[0]->getProvince()->getId());
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearDirectoryForTest('./tests/../storage/sftp/mpos/Out/');
    }

    public function generateWithholdedScenario(
        $provinceId,
        $taxCategoryId,
        $transactionDate,
        $depositTransferredAt = null,
        $provinceIdFromTransaction = null
    ) {
        $currentTestDate = Carbon::getTestNow();
        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->find($provinceId);

        /** @var User $owner */
        $owner = $this->factory->create(User::class);

        /** @var Subsidiary $SaltaSubsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class, [
                'account' => $this->factory->create(Account::class,
                    ['idFiscal' => $this->generateRandomIdFiscal()]),
            ]
        )
            ->setAddress($this->factory->create(Address::class, ['province' => $province]))
            ->setTaxCategory($this->em->getRepository(TaxCategory::class)->find($taxCategoryId));

        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);
        $account->setOwner($owner);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $account,
            'amount' => 0,
            'availableDate' => $transactionDate,
            'toDeposit' => $transactionDate,
        ]);

        /* @var Transaction $aSaleTransaction */
        for ($i = 0; $i < 10; ++$i) {
            $aSaleTransaction = $this->factory->create(Transaction::class, [
                'subsidiary' => $subsidiary,
                'commission' => 0.1,
                'commissionTax' => 0.5,
                'amount' => 6000,
                //'inputMode' => StaticConstant::READ_MODE_SWIPE,
                'availableDate' => $transactionDate,
            ]);

            if (null != $provinceIdFromTransaction) {
                $transactionDetail = $this->factory->create(TransactionDetail::class,
                    [
                        'paymentMethod' => $this->em->getRepository(PaymentMethod::class)->find(1),
                        'account' => $account,
                        'subsidiary' => $subsidiary,
                        'province' => $this->em->getReference(Province::class, $provinceIdFromTransaction),
                    ]
                );
                $aSaleTransaction->setTransactionDetail($transactionDetail);
            }

            $aDeposit->addTransaction($aSaleTransaction);
        }

        if ($depositTransferredAt) {
            $aDeposit->setState(DepositState::SUCCESS);
            $aDeposit->setTransferredAt($depositTransferredAt);
        }

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate()
        );

        Carbon::setTestNow($transactionDate);
        $this->buildTaxInformation($account->getIdFiscal());
        $this->withholdingTaxService->withhold($saleBag);
        $this->em->flush();

        Carbon::setTestNow($currentTestDate);
    }
}
