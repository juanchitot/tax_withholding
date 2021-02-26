<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\ActualRules\Country;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Classification;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\SceneBuilder;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\SceneBuilderBaseDirector;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Symfony\Component\HttpFoundation\ParameterBag;

class DominicRepublicITBISCasesTest extends TestCase
{
    use FactoriesTrait;
    use WithholdingMocks;

    const DOMINICAN_REPUBLIC = '57';

    private const NO_INSCRIPTO = 3;
    private const ITBIS_RATE_ON_CLASSIFICATION = 2;
    private const ITBIS_RATE_OUT_OF_CLASSIFICATION = 0;
    const DOMINICAN_REPUBLIC_CLASSIFICATION_4111 = 4111;
    const ANY_CLASSIFICATION_OUTSIDE_THE_TAXED_ONES = 1;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WithholdingTaxService
     */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->getMockedConfigurationManager(false, false, false, false, false, true);

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
    }

    /** @test * */
    public function a_transaction_made_of_dominican_republic_dont_withholds_ITBIS()
    {
        $commission = 0.1;
        $commissionTax = 0.5;

        $director = new SceneBuilderBaseDirector();
        $director->makeCompleteTransactionWithParam(
            $this->sceneBuilder(),
            new ParameterBag([
                'commission' => $commission,
                'commissionTax' => $commissionTax,
            ])
        );
        $scene = $this->sceneBuilder()->getResult();
        $this->withholdingTaxService->withhold($scene->getSaleBag());

        $commissionAmount = round(SceneBuilder::DEFAULT_TRANSACTIONS_AMOUNT * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round(SceneBuilder::DEFAULT_TRANSACTIONS_AMOUNT * self::ITBIS_RATE_OUT_OF_CLASSIFICATION / 100,
            2);
        $expectedAmount = (SceneBuilder::DEFAULT_TRANSACTIONS_AMOUNT - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $netAmount = $scene->getSaleBag()->getNetAmount();
        $this->assertEquals($expectedAmount, $scene->getSaleBag()->getNetAmount());
    }

    /** @test * */
    public function a_transaction_made_of_dominican_republic_it_withholds_if_has_classification_ITBIS()
    {
        $commission = 0.1;
        $commissionTax = 0.5;

        $director = new SceneBuilderBaseDirector();
        /* @var $classification Classification */
        $classification = $this->factory->create(Classification::class, ['code' => '4111']);
        $this->factory->create(WithholdingTaxSimpleRule::class, [
            'type' => 'ITBIS',
            'classification_id' => $classification->getId(),
            'rate' => 2.0,
            'taxable_amount_coefficient' => 1,
        ]);
        $director->makeCompleteTransactionWithParam(
            $this->sceneBuilder(),
            new ParameterBag([
                'commission' => $commission,
                'commissionTax' => $commissionTax,
                'account.classification' => $classification,
            ])
        );
        $scene = $this->sceneBuilder()->getResult();
        $this->withholdingTaxService->withhold($scene->getSaleBag());

        $commissionAmount = round(SceneBuilder::DEFAULT_TRANSACTIONS_AMOUNT * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round(SceneBuilder::DEFAULT_TRANSACTIONS_AMOUNT * self::ITBIS_RATE_ON_CLASSIFICATION / 100, 2);
        $expectedAmount = (SceneBuilder::DEFAULT_TRANSACTIONS_AMOUNT - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($expectedAmount, $scene->getSaleBag()->getNetAmount());
    }
}
