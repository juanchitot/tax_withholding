<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\ActualRules;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Enum\TaxCategoryCode;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Enum\TaxConceptEnum;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxSystem;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\SirtacMocks;
use GeoPagos\WithholdingTaxBundle\Tests\Utils\WithholdingTaxCalculator;

class SirtacCasesTest extends TestCase
{
    use FactoriesTrait;
    use SirtacMocks;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    public function setUp(): void
    {
        parent::setUp();

        $this->initializeDependencies();
        $this->entityManager->getFilters()->enable('enabled_rules');
    }

    protected function tearDown(): void
    {
        $this->entityManager->getFilters()->disable('enabled_rules');
    }

    private function initializeDependencies(): void
    {
        $this->getMockedConfigurationManager(
            true,
            false,
            false,
            true,
            false,
            false,
            false
        );
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->withholdingTaxService = self::$container->get(WithholdingTaxService::class);
    }

    /** @test */
    public function system_is_sirtac(): void
    {
        $this->assertEquals(
            WithholdingTaxSystem::SIRTAC,
            $this->entityManager->getRepository(ProvinceWithholdingTaxSetting::class)->findOneBy([
                'withholdingTaxType' => WithholdingTaxTypeEnum::SIRTAC,
            ])->getWithholdingTaxSystem()
        );
    }

    /**
     * @test
     *
     * Situación 1:
     * CUIT dentro de padron SIRTAC
     * 1,3,4,5 en el campo 7
     * Solo alicuota
     */
    public function withhold_rate_from_tax_registry_if_subject_is_in_tax_registry_as_registered()
    {
        $baseAmount = 5000;

        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            1,
            $baseAmount
        );
        $this->addAccountToTaxRegistry($scene, self::$SIRTAC_TAX_REGISTRY_RATE);

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        $withheldAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_TAX_REGISTRY_RATE
        );

        // Must exists one withholding concept (Registered)
        $details = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();
        $this->assertCount(1, $details);

        /** @var WithholdingTaxDetail $detail */
        $detail = $details[0];

        // Withholding amount must be equals than expected
        $this->assertEquals($baseAmount, $detail->getTaxableIncome());
        $this->assertEquals($withheldAmount, $detail->getAmount());
        $this->assertEquals(
            TaxConceptEnum::WITHHOLDING_ID,
            $detail->getConcept()->getId()
        );

        // Log must be equals than expected
        $log = $detail->getWithholdingTaxLog();
        $this->assertEquals(
            'Retención Impuesto Ingresos Brutos SIRTAC',
            $log->getRuleApplied()
        );

        // Net amount must be Grouper Commission - Withholding Concept
        $saleBagNetAmount = WithholdingTaxCalculator::calculateNetAmount(
            $baseAmount,
            self::$SIRTAC_TAX_REGISTRY_RATE
        );
        $this->assertEquals($saleBag->getNetAmount(), $saleBagNetAmount);
    }

    /**
     * @test
     *
     * Situación 1: (Rate 0 en padron)
     * CUIT dentro de padron SIRTAC
     * 1,3,4,5 en el campo 7
     * Solo alicuota
     */
    public function inform_empty_rate_if_subject_is_in_tax_registry_as_registered()
    {
        $baseAmount = 5000;

        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            1,
            $baseAmount
        );
        $this->addAccountToTaxRegistry($scene, self::$SIRTAC_INFORMATIVE_RATE);

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must exists one withholding concept (Informative)
        $details = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();
        $this->assertCount(1, $details);

        /** @var WithholdingTaxDetail $detail */
        $detail = $details[0];

        // Informative amount must be equals than expected
        $this->assertEquals($baseAmount, $detail->getTaxableIncome());
        $this->assertEquals(0, $detail->getAmount());
        $this->assertEquals(
            TaxConceptEnum::INFORMATIVE_ID,
            $detail->getConcept()->getId()
        );

        // Log must be equals than expected
        $log = $detail->getWithholdingTaxLog();
        $this->assertEquals(
            'Retención Informativa por alicuota en padrón en 0',
            $log->getRuleApplied()
        );

        // Net amount must be Grouper Commission
        $saleBagNetAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount);
        $this->assertEquals($saleBag->getNetAmount(), $saleBagNetAmount);
    }

    /**
     * @test
     *
     * Situación 2:
     * CUIT dentro de padron SIRTAC
     * 2 en el campo 7
     * Alicuota + Penalidad
     */
    public function withhold_rate_from_tax_registry_and_penalty_if_subject_is_in_tax_registry_as_unregistered()
    {
        $baseAmount = 5000;

        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            1,
            $baseAmount
        );
        $this->addAccountToTaxRegistry(
            $scene,
            self::$SIRTAC_TAX_REGISTRY_RATE,
            true,
            self::$A_SIRTAC_PROVINCE_ID
        );

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must exists two withholding concepts (Withholding + Penalty)
        $detailRepository = $this->entityManager->getRepository(WithholdingTaxDetail::class);
        $allDetails = $detailRepository->findAll();
        $this->assertCount(2, $allDetails);

        $withholdingConcept = $this->entityManager->getReference(
            TaxConcept::class,
            TaxConceptEnum::WITHHOLDING_ID
        );

        /** @var WithholdingTaxDetail $withholdingConceptDetail */
        $withholdingConceptDetail = $detailRepository->findOneBy([
            'concept' => $withholdingConcept,
        ]);

        $this->assertEquals(
            TaxConceptEnum::WITHHOLDING_ID,
            $withholdingConceptDetail->getConcept()->getId()
        );

        $withholdingConceptAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_TAX_REGISTRY_RATE
        );

        // Withholding amount must be equals than expected
        $this->assertEquals($withholdingConceptAmount, $withholdingConceptDetail->getAmount());

        // Withholding Log must be equals than expected
        $log = $withholdingConceptDetail->getWithholdingTaxLog();
        $this->assertEquals(
            'Retención Impuesto Ingresos Brutos SIRTAC',
            $log->getRuleApplied()
        );

        $penaltyConcept = $this->entityManager->getReference(
            TaxConcept::class,
            TaxConceptEnum::PENALTY_ID
        );

        $penaltyConceptDetail = $detailRepository->findOneBy([
            'concept' => $penaltyConcept,
        ]);
        $this->assertEquals(TaxConceptEnum::PENALTY_ID, $penaltyConceptDetail->getConcept()->getId());

        $penaltyConceptAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_PENALTY_RATE
        );

        // Penalty amount must be equals than expected
        $this->assertEquals($penaltyConceptAmount, $penaltyConceptDetail->getAmount());

        // Penalty log must be equals than expected
        $province = $this->entityManager->getRepository(Province::class)->find(self::$A_SIRTAC_PROVINCE_ID);
        $log = $penaltyConceptDetail->getWithholdingTaxLog();
        $this->assertEquals(
            "Retención Impuesto Ingresos Brutos por falta de alta [{$province->getName()}]",
            $log->getRuleApplied()
        );

        // Net amount must be Grouper Commission - Withholding Concept - Penalty Concept
        $netAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount);
        $netAmount -= $withholdingConceptAmount;
        $netAmount -= $penaltyConceptAmount;

        $this->assertEquals($saleBag->getNetAmount(), $netAmount);
    }

    /**
     * @test
     *
     * Situación 3:
     * CUIT dentro de padron SIRTAC pero la venta se hizo en una provincia que no es SIRTAC y tiene regimen propio
     */
    public function withhold_rate_from_tax_registry_and_iibb_if_subject_is_in_tax_registry_and_sale_is_in_another_province()
    {
        $baseAmount = 5000;

        $scene = $this->buildTrxScene(
            self::$A_IIBB_PROVINCE_ID,
            1,
            $baseAmount
        );
        $this->addAccountToTaxRegistry($scene, self::$SIRTAC_TAX_REGISTRY_RATE);

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must exists two withholding concept (SIRTAC Withholding + IIBB Withholding)
        $details = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();
        $this->assertCount(2, $details);

        $sirtacWithheldAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_TAX_REGISTRY_RATE
        );

        $sirtacDetail = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findOneBy([
            'type' => WithholdingTaxTypeEnum::SIRTAC,
        ]);

        $this->assertEquals(
            TaxConceptEnum::WITHHOLDING_ID,
            $sirtacDetail->getConcept()->getId()
        );

        // SIRTAC Withholding amount must be equals than expected
        $this->assertEquals($baseAmount, $sirtacDetail->getTaxableIncome());
        $this->assertEquals($sirtacWithheldAmount, $sirtacDetail->getAmount());

        // SIRTAC log must be equals than expected
        $log = $sirtacDetail->getWithholdingTaxLog();
        $this->assertEquals(
            'Retención Impuesto Ingresos Brutos SIRTAC',
            $log->getRuleApplied()
        );

        $taxWithheldAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$A_IIBB_PROVINCE_RATE
        );

        $taxDetail = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findOneBy([
            'type' => WithholdingTaxTypeEnum::TAX,
        ]);

        $this->assertEquals(
            TaxConceptEnum::WITHHOLDING_ID,
            $taxDetail->getConcept()->getId()
        );

        // IIBB Withholding amount must be equals than expected
        $this->assertEquals($baseAmount, $taxDetail->getTaxableIncome());
        $this->assertEquals($taxWithheldAmount, $taxDetail->getAmount());

        // Net amount must be Grouper Commission - SIRTAC Withholding Concept - IIBB Withholding Concept
        $netAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount);
        $netAmount -= $sirtacWithheldAmount;
        $netAmount -= $taxWithheldAmount;

        $this->assertEquals($saleBag->getNetAmount(), $netAmount);
    }

    /**
     * @test
     *
     * Situación 4:
     * CUIT dentro de padron SIRTAC como no registrado dentro de la jurisdiccion
     * pero la venta se hizo en una provincia que no es SIRTAC y tiene regimen propio
     */
    public function withhold_rate_from_tax_registry_and_penalty_and_iibb_if_subject_is_in_tax_registry_as_unregistered_and_sale_is_in_another_province()
    {
        $baseAmount = 5000;

        $scene = $this->buildTrxScene(
            self::$A_IIBB_PROVINCE_ID,
            1,
            $baseAmount
        );
        $this->addAccountToTaxRegistry(
            $scene,
            self::$SIRTAC_TAX_REGISTRY_RATE,
            true,
            self::$A_IIBB_PROVINCE_ID
        );

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must exists three withholding concept (SIRTAC Withholding + IIBB Withholding + Penalty Concept)
        $details = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findAll();
        $this->assertCount(3, $details);

        $sirtacWithheldAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_TAX_REGISTRY_RATE
        );
        $sirtacDetail = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findOneBy([
            'type' => WithholdingTaxTypeEnum::SIRTAC,
        ]);

        $this->assertEquals(
            TaxConceptEnum::WITHHOLDING_ID,
            $sirtacDetail->getConcept()->getId()
        );

        // SIRTAC Withholding amount must be equals than expected
        $this->assertEquals($baseAmount, $sirtacDetail->getTaxableIncome());
        $this->assertEquals($sirtacWithheldAmount, $sirtacDetail->getAmount());

        // SIRTAC log must be equals than expected
        $log = $sirtacDetail->getWithholdingTaxLog();
        $this->assertEquals(
            'Retención Impuesto Ingresos Brutos SIRTAC',
            $log->getRuleApplied()
        );

        $penaltyConceptAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_PENALTY_RATE
        );

        $penaltyConcept = $this->entityManager->getReference(
            TaxConcept::class,
            TaxConceptEnum::PENALTY_ID
        );

        $penaltyConceptDetail = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findOneBy([
            'type' => WithholdingTaxTypeEnum::SIRTAC,
            'concept' => $penaltyConcept,
        ]);

        $this->assertEquals(
            TaxConceptEnum::PENALTY_ID,
            $penaltyConceptDetail->getConcept()->getId()
        );

        // Penalty amount must be equals than expected
        $this->assertEquals($baseAmount, $penaltyConceptDetail->getTaxableIncome());
        $this->assertEquals($penaltyConceptAmount, $penaltyConceptDetail->getAmount());

        // Penalty log must be equals than expected
        $province = $this->entityManager->getRepository(Province::class)->find(self::$A_IIBB_PROVINCE_ID);
        $log = $penaltyConceptDetail->getWithholdingTaxLog();
        $this->assertEquals(
            "Retención Impuesto Ingresos Brutos por falta de alta [{$province->getName()}]",
            $log->getRuleApplied()
        );

        $iibbDetail = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$A_IIBB_PROVINCE_RATE
        );

        $taxDetail = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findOneBy([
            'type' => WithholdingTaxTypeEnum::TAX,
        ]);

        $this->assertEquals(
            TaxConceptEnum::WITHHOLDING_ID,
            $taxDetail->getConcept()->getId()
        );

        // IIBB Withholding amount must be equals than expected
        $this->assertEquals($baseAmount, $taxDetail->getTaxableIncome());
        $this->assertEquals($iibbDetail, $taxDetail->getAmount());

        // Net amount must be Grouper Commission - SIRTAC Withholding Concept - IIBB Withholding Concept - Penalty
        $netAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount);
        $netAmount -= $sirtacWithheldAmount;
        $netAmount -= $penaltyConceptAmount;
        $netAmount -= $iibbDetail;

        $this->assertEquals($saleBag->getNetAmount(), $netAmount);
    }

    /**
     * @test
     *
     * Situación 5:
     * CUIT no esta en padron SIRTAC, pero hace una venta dentro de una provincia SIRTAC y
     * además NO Esta incripto en ninguna provincia de las 24 provincias
     */
    public function withhold_habituality_rate_if_subject_is_not_in_tax_registry_and_sale_is_in_a_sirtac_province_and_subject_is_unregistered_in_all_provinces()
    {
        $trxCount = 10;
        $baseAmount = 6000;

        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            $trxCount,
            $baseAmount,
            TaxCategoryCode::NO_INSCRIPTO
        );
        $this->entityManager->flush();

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must exists one withholding concept (Habituality)
        $detailRepository = $this->entityManager->getRepository(WithholdingTaxDetail::class);
        $allDetails = $detailRepository->findAll();
        $this->assertCount($trxCount, $allDetails);

        $habitualityAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_HABITUALITY_RATE
        );

        $province = $this->entityManager->getRepository(Province::class)->find(self::$A_SIRTAC_PROVINCE_ID);
        foreach ($allDetails as $habitualityDetail) {
            // Habituality amount must be equals than expected
            $this->assertEquals($habitualityAmount, $habitualityDetail->getAmount());

            // Habituality concept must be equals than expected
            $this->assertEquals(
                TaxConceptEnum::UNREGISTERED_ID,
                $habitualityDetail->getConcept()->getId()
            );

            // Habituality log must be equals than expected
            $log = $habitualityDetail->getWithholdingTaxLog();
            $this->assertEquals(
                "Retención Impuesto Ingresos Brutos no inscripto [{$province->getName()}]",
                $log->getRuleApplied()
            );
        }

        // Net amount must be Grouper Commission - Habituality Concept
        $netAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount) * $trxCount;
        $netAmount -= ($habitualityAmount * $trxCount);

        $this->assertEquals($saleBag->getNetAmount(), $netAmount);
    }

    /**
     * @test
     *
     * Situación 5 (No cumple habitualidad por monto):
     * CUIT no esta en padron SIRTAC, pero hace una venta dentro de una provincia SIRTAC y
     * además NO Esta incripto en ninguna provincia de las 24 provincias
     */
    public function does_not_withhold_habituality_if_not_meet_by_amount(): void
    {
        $trxCount = 10;
        $baseAmount = 4000;
        $this->do_unsuccessful_habituality_scenario($trxCount, $baseAmount);
    }

    /**
     * @test
     *
     * Situación 5 (No cumple habitualidad por cantidad de trx):
     * CUIT no esta en padron SIRTAC, pero hace una venta dentro de una provincia SIRTAC y
     * además NO Esta incripto en ninguna provincia de las 24 provincias
     */
    public function does_not_withhold_habituality_if_not_meet_by_trx_quantity(): void
    {
        $trxCount = 9;
        $baseAmount = 10000;
        $this->do_unsuccessful_habituality_scenario($trxCount, $baseAmount);
    }

    private function do_unsuccessful_habituality_scenario(int $trxCount, int $baseAmount)
    {
        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            $trxCount,
            $baseAmount,
            TaxCategoryCode::NO_INSCRIPTO
        );
        $this->entityManager->flush();

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must not exists any concept
        $detailRepository = $this->entityManager->getRepository(WithholdingTaxDetail::class);
        $allDetails = $detailRepository->findAll();
        $this->assertCount(0, $allDetails);

        // Net amount must be Grouper Commission
        $netAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount);
        $netAmount *= $trxCount;

        $this->assertEquals($saleBag->getNetAmount(), $netAmount);
    }

    /**
     * @test
     *
     * Situación 6:
     * CUIT no esta en padron SIRTAC,
     * pero hace una venta dentro de una provincia SIRTAC y esta inscipto en alguna de las 24 provinicias
     */
    public function withhold_penalty_rate_if_subject_is_not_in_tax_registry_and_sale_is_in_a_sirtac_province_and_subject_is_registered_in_any_province()
    {
        $baseAmount = 5000;

        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            1,
            $baseAmount,
            TaxCategoryCode::INSCRIPTO_LOCAL
        );

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must exists one withholding concept (Penalty)
        $detailRepository = $this->entityManager->getRepository(WithholdingTaxDetail::class);
        $allDetails = $detailRepository->findAll();
        $this->assertCount(1, $allDetails);

        $penaltyConcept = $this->entityManager->getReference(
            TaxConcept::class,
            TaxConceptEnum::PENALTY_ID
        );

        $penaltyConceptDetail = $detailRepository->findOneBy([
            'concept' => $penaltyConcept,
        ]);

        $this->assertEquals(
            TaxConceptEnum::PENALTY_ID,
            $penaltyConceptDetail->getConcept()->getId()
        );

        $penaltyConceptAmount = WithholdingTaxCalculator::withhold(
            $baseAmount,
            self::$SIRTAC_PENALTY_RATE
        );

        // Penalty amount must be equals than expected
        $this->assertEquals($penaltyConceptAmount, $penaltyConceptDetail->getAmount());

        // Penalty log must be equals than expected
        $province = $this->entityManager->getRepository(Province::class)->find(self::$A_SIRTAC_PROVINCE_ID);
        $log = $penaltyConceptDetail->getWithholdingTaxLog();
        $this->assertEquals(
            "Retención Impuesto Ingresos Brutos por falta de alta [{$province->getName()}]",
            $log->getRuleApplied()
        );

        // Net amount must be Grouper Commission - Penalty Concept
        $netAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount);
        $netAmount -= $penaltyConceptAmount;

        $this->assertEquals($saleBag->getNetAmount(), $netAmount);
    }

    /**
     * @test
     *
     * Situación 6: (Inscripto como Excento)
     *
     * CUIT no esta en padron SIRTAC,
     * pero hace una venta dentro de una provincia SIRTAC y esta inscipto en alguna de las 24 provinicias
     */
    public function inform_if_subject_is_not_in_tax_registry_and_sale_is_in_a_sirtac_province_and_subject_is_exempt()
    {
        $baseAmount = 5000;

        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            1,
            $baseAmount,
            TaxCategoryCode::EXENTO
        );

        $saleBag = $this->withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        // Must not exists any concept
        $detailRepository = $this->entityManager->getRepository(WithholdingTaxDetail::class);
        $allDetails = $detailRepository->findAll();
        $this->assertCount(0, $allDetails);

        $this->entityManager->getReference(
            TaxConcept::class,
            TaxConceptEnum::INFORMATIVE_ID
        );

        // Net amount must be Grouper Commission
        $netAmount = WithholdingTaxCalculator::calculateNetAmount($baseAmount);

        $this->assertEquals($saleBag->getNetAmount(), $netAmount);
    }
}
