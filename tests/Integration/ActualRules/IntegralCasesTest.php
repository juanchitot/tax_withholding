<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\ActualRules;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Entity\TransactionDetail;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\ApiAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Money\Currency;

class IntegralCasesTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;
    use WithholdingMocks;

    private const BUENOS_AIRES = 6;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->configurationManager = $this->getMockedConfigurationManager(true, true, true, false);

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
    }

    /** @test */
    public function hold_registry_rate_correctly_when_all_taxes_are_enabled(): void
    {
        $now = Carbon::create(2020, 11, 2);
        Carbon::setTestNow($now);

        /** @var Province $pba */
        $pba = $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES);

        $accountIdFiscal = '34621139088';
        $baseAmount = 500;
        $commission = 0.03;
        $commissionTax = 0.21;
        $registryRate = 0.02;

        /** @var User $owner */
        $owner = $this->factory->create(User::class);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $this->factory->create(Account::class,
                ['idFiscal' => $accountIdFiscal]
            ),
        ])->setAddress($this->factory->create(Address::class, ['province' => $pba]));
        // Account->getSubsidiaries() isnt populated with factory muffin
        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);
        $account->setOwner($owner);

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
            'amount' => $baseAmount,
            'availableDate' => Carbon::now(),
        ]);

        // Needed for VAT and Income Tax processing
        /* @var $transactionDetail TransactionDetail */
        $transactionDetail = $this->factory->create(TransactionDetail::class,
            [
                'paymentMethod' => $this->entityManager->getRepository(PaymentMethod::class)->find(1),
                'account' => $account,
                'subsidiary' => $subsidiary,
            ]
        );

        $this->entityManager->persist($transactionDetail);
        $aSaleTransaction->setTransactionDetail($transactionDetail);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $subsidiary->getAccount(),
            'availableDate' => Carbon::now(),
        ])->addTransaction($aSaleTransaction);

        // Add account to this month registry
        $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => Carbon::now()->format('m-Y'),
            'province' => $subsidiary->getAddress()->getProvince(),
            'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
            'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            'imported' => 1,
        ]);
        $this->factory->create(WithholdingTaxDynamicRule::class, [
            'id_fiscal' => $accountIdFiscal,
            'month_year' => Carbon::now()->format('m-Y'),
            'rate' => $registryRate * 100,
            'province' => $subsidiary->getAddress()->getProvince(),
            'type' => WithholdingTaxTypeEnum::TAX_TYPE,
        ]);

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate());

        $this->buildTaxInformation($account->getIdFiscal());
        $saleBag = $this->withholdingTaxService->withhold($saleBag);

        $aDeposit->setAmount($saleBag->getNetAmount());

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round(($baseAmount - $commissionAmount - $commissionTaxAmount) * ($registryRate), 2);

        $depositAmount = ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertCount(1, $aSaleTransaction->getWithholdingTaxDetails());
        $this->assertEquals($depositAmount, $aDeposit->getAmount());
    }
}
