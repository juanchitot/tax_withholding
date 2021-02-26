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
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Money\Currency;

class VATCasesTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;
    use WithholdingMocks;

    private const BUENOS_AIRES = 6;
    private const MUNRO = 4789;

    private const INSCRIPTO_LOCAL = 1;
    private const NO_INSCRIPTO = 3;

    private const VAT_CREDIT_RESPONSABLE_INSCRIPTO_RATE = 0.03;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->configurationManager = $this->getMockedConfigurationManager(false, true, false, false);

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
    }

    /** @test */
    public function hold_if_a_merchant_is_responsable_inscripto_and_card_is_credit(): void
    {
        /** @var Province $province */
        $province = $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES);

        $baseAmount = 4.32;
        $commission = 0.1;
        $commissionTax = 0.5;

        /** @var User $aMerchantOwner */
        $aMerchantOwner = $this->factory->create(User::class);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $this->factory->create(Account::class,
                ['idFiscal' => $this->generateRandomIdFiscal()]
            ),
        ])
            ->setAddress($this->factory->create(Address::class, ['province' => $province]));
        // Account->getSubsidiaries() isnt populated with factory muffin
        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);
        $account->setOwner($aMerchantOwner);

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
            'amount' => $baseAmount,
        ]);

        /* @var $aTransactionDetail TransactionDetail */
        $aTransactionDetail = $this->factory->create(TransactionDetail::class,
            [
                'paymentMethod' => $this->entityManager->getRepository(PaymentMethod::class)->find(1),
                'account' => $account,
                'subsidiary' => $subsidiary,
            ]
        );

        $aSaleTransaction->setTransactionDetail($aTransactionDetail);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, [
            'account' => $subsidiary->getAccount(),
        ])->addTransaction($aSaleTransaction);

        $this->entityManager->flush();

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate());

        $this->buildTaxInformation($account->getIdFiscal(),
            [
                'iva' => 'AC',  // RESPONSABLE INSCRIPTO
                'monotributo' => 'NI', // NO MONOTRIBUTO
                'incomeTax' => 'NI', // NO GANANCIAS
            ]);
        $saleBag = $this->withholdingTaxService->withhold($saleBag);

        $aDeposit->setAmount($saleBag->getNetAmount());

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round(($baseAmount - $commissionAmount - $commissionTaxAmount) * self::VAT_CREDIT_RESPONSABLE_INSCRIPTO_RATE,
            2);
        $aSaleTransaction->getAmountWithoutCommissionAndFinancialCost();
        $financialCost = $aSaleTransaction->getFinancialCostAmount();
        $financialCostTax = $aSaleTransaction->getFinancialCostTaxAmount();

        $amountToDeposit = ($baseAmount - $commissionAmount - $commissionTaxAmount - $financialCost - $financialCostTax - $taxWithheld);

        $this->assertEquals($amountToDeposit, $aDeposit->getAmount());
    }

    /** @test */
    public function dont_hold_if_a_merchant_has_a_dynamic_rule_of_exclusion_for_vat_and_income(): void
    {
        /** @var Province $province */
        $province = $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES);

        $baseAmount = 4.32;
        $commission = 0.1;
        $commissionTax = 0.5;

        /** @var User $aMerchantOwner */
        $aMerchantOwner = $this->factory->create(User::class);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class)
            ->setAddress($this->factory->create(Address::class, ['province' => $province]));

        // Account->getSubsidiaries() isnt populated with factory muffin
        $account = $subsidiary->getAccount();
        $account->addSubsidiary($subsidiary);
        $account->setOwner($aMerchantOwner);

        /** @var Transaction $aSaleTransaction */
        $aSaleTransaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => $commission,
            'commissionTax' => $commissionTax,
            'amount' => $baseAmount,
        ]);

        /* @var $aTransactionDetail TransactionDetail */
        $aTransactionDetail = $this->factory->create(TransactionDetail::class,
            [
                'paymentMethod' => $this->entityManager->getRepository(PaymentMethod::class)->find(1),
                'account' => $account,
                'subsidiary' => $subsidiary,
            ]
        );

        $aSaleTransaction->setTransactionDetail($aTransactionDetail);

        /** @var Deposit $aDeposit */
        $aDeposit = $this->factory->create(Deposit::class, ['account' => $subsidiary->getAccount()])
            ->addTransaction($aSaleTransaction);

        $this->factory->create(WithholdingTaxDynamicRule::class, [
            'id_fiscal' => $account->getIdFiscal(),
            'month_year' => Carbon::now()->format('m-Y'),
            'rate' => 0,
            'province' => $subsidiary->getAddress()->getProvince(),
            'type' => WithholdingTaxTypeEnum::VAT_TYPE + WithholdingTaxTypeEnum::INCOME_TYPE,
        ]);

        $saleBag = new SaleBag(
            $aDeposit->getTransactions()->toArray(),
            new Currency($aDeposit->getCurrencyCode()),
            $aDeposit->getAvailableDate());

        $saleBag = $this->withholdingTaxService->withhold($saleBag);

        $aDeposit->setAmount($saleBag->getNetAmount());

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);

        $taxWithheld = 0;

        $financialCost = $aSaleTransaction->getFinancialCost();
        $financialCostTax = $aSaleTransaction->getFinancialCostTax();

        $amountToDeposit = ($baseAmount - $commissionAmount - $commissionTaxAmount - $financialCost - $financialCostTax - $taxWithheld);

        $this->assertEquals($amountToDeposit, $aDeposit->getAmount());
    }

    private function mockGuzzleResponses(array $responses)
    {
        $mock = new MockHandler();
        foreach ($responses as $response) {
            $mock->append($response);
        }
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        static::$container->set(ClientInterface::class, $client);

        return $client;
    }

    private function mockTaxIdentityChecker()
    {
        return $this->mockGuzzleResponses([
            new Response(200, [],
                json_encode([
                        'iva' => 'AC',  // RESPONSABLE INSCRIPTO
                        'monotributo' => 'NI', // NO MONOTRIBUTO
                        'incomeTax' => 'NI', // NO GANANCIAS
                    ]
                )
            ),
        ]);
    }
}
