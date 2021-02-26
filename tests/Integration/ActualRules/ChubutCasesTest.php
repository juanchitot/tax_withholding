<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\ActualRules;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\ApiAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxSystem;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;

class ChubutCasesTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;
    use WithholdingMocks;

    private const CHUBUT = 26;

    private const INSCRIPTO_LOCAL = 1;
    private const INSCRIPTO_CONVENIO_MULTILATERAL = 2;
    private const NO_INSCRIPTO = 3;
    private const REGIMEN_SIMPLIFICADO = 4;
    private const EXENTO = 5;

    private const CHUBUT_INSCRIPTO_LOCAL_RATE = 0;
    private const CHUBUT_INSCRIPTO_CM_RATE = 2;
    private const CHUBUT_HABITUALIDAD_RATE = 3;

    private const CHUBUT_JURISDICTION = '907';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Province */
    private $chubutProvince;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->configurationManager = $this->getMockedConfigurationManager(
            true,
            false,
            false,
            false
        );

        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);

        $this->chubutProvince = $this->entityManager->getRepository(Province::class)->find(self::CHUBUT);
    }

    private function enableRule(): void
    {
        $rule = $this->entityManager->getRepository(WithholdingTaxRule::class)->findBy([
            'taxType' => WithholdingTaxType,
        ]);
    }

    /** @test */
    public function system_is_sircar(): void
    {
        $this->assertEquals(
            WithholdingTaxSystem::SIRCAR,
            $this->entityManager->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
                'province' => $this->chubutProvince,
                'withholdingTaxType' => WithholdingTaxTypeEnum::TAX,
            ])->getWithholdingTaxSystem()
        );
    }

    /** @test */
    public function jurisdiction_is_equals_as_expected(): void
    {
        $this->assertEquals(
            self::CHUBUT_JURISDICTION,
            $this->entityManager->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
                'province' => $this->chubutProvince,
                'withholdingTaxType' => WithholdingTaxTypeEnum::TAX,
            ])->getCode()
        );
    }

    /** @test */
    public function has_inscripto_local_exclusion(): void
    {
        $baseAmount = 5000;
        $this->buildRuleTestScenario($this->chubutProvince, self::INSCRIPTO_LOCAL, 1, $baseAmount);

        /** @var WithholdingTaxDetail[] $withholdingTaxDetails */
        $withholdingTaxDetails = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();

        $this->assertCount(0, $withholdingTaxDetails);
    }

    /** @test */
    public function has_inscripto_cm_rule(): void
    {
        $baseAmount = 10000;

        $this->buildRuleTestScenario($this->chubutProvince, self::INSCRIPTO_CONVENIO_MULTILATERAL, 1, $baseAmount);

        /** @var WithholdingTaxDetail[] $withholdingTaxDetails */
        $withholdingTaxDetails = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();

        $taxableIncome = $baseAmount;
        $withholdedAmount = round($taxableIncome * (self::CHUBUT_INSCRIPTO_CM_RATE / 100), 2);

        $this->assertCount(1, $withholdingTaxDetails);
        $this->assertEquals($taxableIncome, $withholdingTaxDetails[0]->getTaxableIncome());
        $this->assertEquals($withholdedAmount, $withholdingTaxDetails[0]->getAmount());
    }

    /** @test */
    public function has_exento_exclusion_rule(): void
    {
        $baseAmount = 5000;
        $commission = 0.1;
        $commissionTax = 0.5;

        $saleBag = $this->buildRuleTestScenario($this->chubutProvince, self::EXENTO, 1, $baseAmount, $commission,
            $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = 0;

        $incomeNetAmount = ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($incomeNetAmount, $saleBag->getNetAmount());
    }

    /** @test */
    public function has_regimen_simplificado_rule(): void
    {
        $baseAmount = 5000;
        $commission = 0.1;
        $commissionTax = 0.5;

        $saleBag = $this->buildRuleTestScenario($this->chubutProvince, self::REGIMEN_SIMPLIFICADO,
            1, $baseAmount, $commission, $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = 0;

        $incomeNetAmount = ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($incomeNetAmount, $saleBag->getNetAmount());
    }

    /** @test */
    public function habituality_with_less_than_3_trx_and_7500_doesnt_withhold(): void
    {
        $transactionCount = 2;
        $baseAmount = 4000;
        $commission = 0.1;
        $commissionTax = 0.5;

        $saleBag = $this->buildRuleTestScenario($this->chubutProvince, self::NO_INSCRIPTO,
            $transactionCount, $baseAmount, $commission, $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = 0;

        $incomeNetAmount = $transactionCount * ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($incomeNetAmount, $saleBag->getNetAmount());
    }

    /** @test */
    public function habituality_with_3_or_more_trx_and_7500_does_withhold(): void
    {
        $transactionCount = 3;
        $baseAmount = 2500;
        $commission = 0.1;
        $commissionTax = 0.5;

        $saleBag = $this->buildRuleTestScenario($this->chubutProvince, self::NO_INSCRIPTO,
            $transactionCount, $baseAmount, $commission, $commissionTax);

        $commissionAmount = round($baseAmount * $commission, 2);
        $commissionTaxAmount = round($commissionAmount * $commissionTax, 2);
        $taxWithheld = round($baseAmount * self::CHUBUT_HABITUALIDAD_RATE / 100, 2);

        $incomeNetAmount = $transactionCount * ($baseAmount - $commissionAmount - $commissionTaxAmount - $taxWithheld);

        $this->assertEquals($incomeNetAmount, $saleBag->getNetAmount());
    }
}
