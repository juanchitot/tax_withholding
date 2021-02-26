<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\ActualRules;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\ApiAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;

class CorrientesCasesTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;
    use WithholdingMocks;

    private const CORRIENTES = 18;

    private const INSCRIPTO_LOCAL = 1;
    private const INSCRIPTO_CONVENIO_MULTILATERAL = 2;
    private const NO_INSCRIPTO = 3;
    private const EXENTO = 4;
    private const REGIMEN_SIMPLIFICADO = 5;

    private const CORRIENTES_INSCRIPTO_LOCAL_RATE = 2;
    private const CORRIENTES_INSCRIPTO_CONVENIO_MULTILATERAL_RATE = 2;
    private const CORRIENTES_HABITUALIDAD_RATE = 2.5;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Province */
    private $corrientesProvince;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->configurationManager = $this->getMockedConfigurationManager(true, false, false, false);

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);

        $this->corrientesProvince = $this->entityManager->getRepository(Province::class)->find(self::CORRIENTES);
    }

    /** @test */
    public function has_inscripto_local_rules(): void
    {
        $baseAmount = 500;
        $this->buildRuleTestScenario($this->corrientesProvince, self::INSCRIPTO_LOCAL, 1, $baseAmount);

        /** @var WithholdingTaxDetail[] $withholdingTaxDetails */
        $withholdingTaxDetails = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();

        $taxableIncome = $baseAmount;
        $withholdedAmount = round($taxableIncome * (self::CORRIENTES_INSCRIPTO_LOCAL_RATE / 100), 2);

        $this->assertCount(1, $withholdingTaxDetails);
        $this->assertEquals($taxableIncome, $withholdingTaxDetails[0]->getTaxableIncome());
        $this->assertEquals($withholdedAmount, $withholdingTaxDetails[0]->getAmount());
    }

    /** @test */
    public function has_inscripto_cm_rules(): void
    {
        $baseAmount = 500;
        $this->buildRuleTestScenario($this->corrientesProvince, self::INSCRIPTO_CONVENIO_MULTILATERAL, 1, $baseAmount);

        /** @var WithholdingTaxDetail[] $withholdingTaxDetails */
        $withholdingTaxDetails = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();

        $taxableIncome = $baseAmount;
        $withholdedAmount = round($taxableIncome * (self::CORRIENTES_INSCRIPTO_CONVENIO_MULTILATERAL_RATE / 100), 2);

        $this->assertCount(1, $withholdingTaxDetails);
        $this->assertEquals($taxableIncome, $withholdingTaxDetails[0]->getTaxableIncome());
        $this->assertEquals($withholdedAmount, $withholdingTaxDetails[0]->getAmount());
    }

    /** @test */
    public function has_exento_exclusion_rule(): void
    {
        $baseAmount = 500;
        $commission = 0.1;
        $commissionTax = 0.5;

        $sale = $this->buildRuleTestScenario($this->corrientesProvince, self::EXENTO,
            1, $baseAmount, $commission, $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = 0;

        $saleNetAmount = ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($saleNetAmount, $sale->getNetAmount());
    }

    /** @test */
    public function has_regimen_simplificado_exclusion_rule(): void
    {
        $baseAmount = 500;
        $commission = 0.1;
        $commissionTax = 0.5;

        $sale = $this->buildRuleTestScenario($this->corrientesProvince, self::REGIMEN_SIMPLIFICADO,
            1, $baseAmount, $commission, $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = 0;

        $saleNetAmount = ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($saleNetAmount, $sale->getNetAmount());
    }

    /** @test */
    public function habituality_with_less_than_10_trx_and_20000_doesnt_withhold(): void
    {
        $transactionCount = 9;
        $baseAmount = 2000;
        $commission = 0.1;
        $commissionTax = 0.5;

        $sale = $this->buildRuleTestScenario($this->corrientesProvince, self::NO_INSCRIPTO,
            $transactionCount, $baseAmount, $commission, $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = 0;

        $saleNetAmount = $transactionCount * ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($saleNetAmount, $sale->getNetAmount());
    }

    /** @test */
    public function habituality_with_10_or_more_trx_and_20000_does_withhold(): void
    {
        $transactionCount = 10;
        $baseAmount = 2000;
        $commission = 0.1;
        $commissionTax = 0.5;

        $sale = $this->buildRuleTestScenario($this->corrientesProvince, self::NO_INSCRIPTO,
            $transactionCount, $baseAmount, $commission, $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round($baseAmount * self::CORRIENTES_HABITUALIDAD_RATE / 100, 2);

        $saleNetAmount = $transactionCount * ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($saleNetAmount, $sale->getNetAmount());
    }
}
