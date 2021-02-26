<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\ActualRules;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\ApiAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Money\Currency;

class PBACasesTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;
    use WithholdingMocks;

    private const BUENOS_AIRES = 6;
    private const MUNRO = 4789;

    private const INSCRIPTO_LOCAL = 1;
    private const NO_INSCRIPTO = 3;
    private const PBA_UNPUBLISH_RATE = 1.75;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->configurationManager = $this->getMockedConfigurationManager(true, false, false, false, false);

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
    }

    /** @test */
    public function doesnt_hold_if_user_meet_habituality_this_month(): void
    {
        $this->markTestSkipped();
        /** @var Province $pba */
        $pba = $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES);
        $noInscripto = $this->entityManager->getRepository(TaxCategory::class)->find(self::NO_INSCRIPTO);

        $transactionCount = 8;
        $baseAmount = 4800;
        $commission = 0.1;
        $commissionTax = 0.5;

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class,
            [
                'account' => $this->factory->create(Account::class,
                    ['idFiscal' => $this->generateRandomIdFiscal()]
                ),
            ])
            ->setAddress($this->factory->create(Address::class, [
                'province' => $pba,
            ])
            )
            ->setTaxCategory($noInscripto);
        // This isnt populated with factory muffin
        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);

        $availableDate = Carbon::now();
        // This is for having registry of month loaded so PBA doesnt apply 1.75%
        $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => $availableDate->format('m-Y'),
            'province' => $pba,
            'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
            'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            'imported' => 1,
        ]);

        /** @var Transaction[] $salesTransactions */
        $salesTransactions = $this->factory->seed($transactionCount, Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
            'amount' => $baseAmount,
            'availableDate' => $availableDate,
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $subsidiary->getAccount(),
        ]);

        foreach ($salesTransactions as $aSaleTransaction) {
            $aDeposit->addTransaction($aSaleTransaction);
        }

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate());

        $this->buildTaxInformation($account->getIdFiscal());
        $saleBag = $this->withholdingTaxService->withhold($saleBag);

        $aDeposit->setAmount($saleBag->getNetAmount());
        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = 0;

        $depositAmount = $transactionCount * ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($depositAmount, $aDeposit->getAmount());
    }

    /** @test */
    public function hold_if_user_meet_habituality_last_month(): void
    {
        $now = Carbon::create(2020, 11, 2);
        Carbon::setTestNow($now);

        /** @var Province $pba */
        $pba = $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES);
        $noInscripto = $this->entityManager->getRepository(TaxCategory::class)->find(self::NO_INSCRIPTO);

        $transactionCount = 6;
        $baseAmount = 4800;
        $commission = 0.1;
        $commissionTax = 0.5;

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class,
            [
                'account' => $this->factory->create(Account::class,
                    ['idFiscal' => $this->generateRandomIdFiscal()]
                ),
            ])
            ->setAddress($this->factory->create(Address::class, ['province' => $pba]))
            ->setTaxCategory($noInscripto);
        // Account->getSubsidiaries() isnt populated with factory muffin
        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);

        // Last month transactions.
        $this->factory->seed($transactionCount, Transaction::class, [
            'created_at' => Carbon::now()->subMonth()->startOfMonth(),
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
            'amount' => $baseAmount,
        ]);

        $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => Carbon::now()->format('m-Y'),
            'province' => $pba,
            'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
            'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            'imported' => 1,
        ]);

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
            'amount' => $baseAmount,
            'availableDate' => Carbon::now(),
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $subsidiary->getAccount(),
            'availableDate' => Carbon::now(),
        ])->addTransaction($aSaleTransaction);

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate());

        $this->buildTaxInformation($account->getIdFiscal());
        $saleBag = $this->withholdingTaxService->withhold($saleBag);

        $aDeposit->setAmount($saleBag->getNetAmount());
        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round(($baseAmount - $commissionAmount - $commissionTaxAmount) * 0.035, 2);

        $depositAmount = ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($depositAmount, $aDeposit->getAmount());
    }

    /** @test */
    public function withhold_unpublish_rate_if_registry_isnt_uploaded(): void
    {
        /** @var Province $pba */
        $pba = $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES);
        $noInscripto = $this->entityManager->getRepository(TaxCategory::class)->find(self::NO_INSCRIPTO);

        $transactionCount = 1;
        $baseAmount = 4800;
        $commission = 0.1;
        $commissionTax = 0.5;

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class)
            ->setAddress($this->factory->create(Address::class, [
                'province' => $pba,
            ])
            )
            ->setTaxCategory($noInscripto);
        // This isnt populated with factory muffin
        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);

        /** @var Transaction[] $salesTransactions */
        $salesTransactions = $this->factory->seed($transactionCount, Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
            'amount' => $baseAmount,
        ]);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $subsidiary->getAccount(),
        ]);

        foreach ($salesTransactions as $aSaleTransaction) {
            $aDeposit->addTransaction($aSaleTransaction);
        }

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate());

        $this->buildTaxInformation($account->getIdFiscal());
        $saleBag = $this->withholdingTaxService->withhold($saleBag);

        $aDeposit->setAmount($saleBag->getNetAmount());
        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round(($baseAmount - $commissionAmount - $commissionTaxAmount) * self::PBA_UNPUBLISH_RATE / 100, 2);

        $depositAmount = $transactionCount * ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($depositAmount, $aDeposit->getAmount());
    }
}
