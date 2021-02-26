<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\ApiAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleCalculationBasisEnum;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\SceneBuilderBaseDirector;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Symfony\Component\HttpFoundation\ParameterBag;

class WithholdingTaxScenariosTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;
    use WithholdingMocks;

    const UNPUBLISH_RATE = 0.0175; // 1.75%

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->configurationManager = $this->getMockedConfigurationManager(true, false, false);

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
    }

    /** @test */
    public function naranja_doesnt_withhold_unpublish_rate_scenario()
    {
        $buenosAiresProvince = $this->entityManager->getRepository(Province::class)->find(6);

        $pbaRule = $this->entityManager->getRepository(WithholdingTaxRule::class)->findOneBy(['province' => $buenosAiresProvince]);
        $pbaRule->setCalculationBasis(WithholdingTaxRuleCalculationBasisEnum::GROSS);
        $this->entityManager->flush();

        $director = new SceneBuilderBaseDirector();
        $director->makeMultipleTransactionDepositWithParam(
            $this->sceneBuilder(),
            new ParameterBag([
                'subsidiary.address.province' => $buenosAiresProvince,
                'transactions.count' => $transactionCount = 10,
                'transactions.details' => [
                    [
                        'transaction.amount' => 64,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 1275,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 614,
                        'transaction.commission' => 0.0579,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 390,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 1090,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 680,
                        'transaction.commission' => 0.0579,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 1016,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 400,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 595,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                    [
                        'transaction.amount' => 630,
                        'transaction.commission' => 0.0329,
                        'transaction.commissionTax' => 0.21,
                    ],
                ],
            ])
        );

        $scene = $this->sceneBuilder()->getResult();
        $this->withholdingTaxService->withhold($scene->getSaleBag());

        $firstTransaction = $scene->getDeposit()->getTransactions()[0];
        $transactionAmount = $firstTransaction->getAmount();
        $commissionAmount = round($firstTransaction->getCommission() * $transactionAmount, 2);
        $commissionTaxAmount = round($firstTransaction->getCommissionTax() * $commissionAmount, 2);
        $taxWithheld = round($firstTransaction->getAmount() * self::UNPUBLISH_RATE, 2);

        $expectedFirstTransactionNetAmount = $transactionAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld;

        $totalWithheld = round($scene->getSaleBag()->getGrossAmount() * self::UNPUBLISH_RATE, 2);
        $values = (new ArrayCollection($scene->getWithholdingTaxDetails()))->map(function ($details) { return $details->getAmount(); })->getValues();

        $detail = $firstTransaction->getWithholdingTaxDetails()[0];
        $this->assertEquals($taxWithheld, $detail->getAmount());
        $this->assertEquals($expectedFirstTransactionNetAmount, $firstTransaction->getBalanceAmount());
        $this->assertCount(10, $scene->getWithholdingTaxDetails());
    }
}
