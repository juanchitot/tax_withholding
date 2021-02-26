<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Repository;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Repository\ProvinceRepository;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxSimpleRuleRepository;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;

class WithholdingTaxSimpleRuleRepositoryTest extends TestCase
{
    private const BUENOS_AIRES = 6;

    use FactoriesTrait, WithholdingMocks;

    /**
     * @var WithholdingTaxSimpleRuleRepository
     */
    private $simpleRuleRepository;

    /**
     * @var ProvinceRepository
     */
    private $provinceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleRuleRepository = self::$container->get(WithholdingTaxSimpleRuleRepository::class);
        $this->provinceRepository = self::$container->get(ProvinceRepository::class);
    }

    /** @test */
    public function it_can_find_federal_rules(): void
    {
        /** @var WithholdingTaxRule $wtr */
        $wtr = $this->factory->create(WithholdingTaxRule::class, [
            'type' => self::$fakeTaxType,
        ]);

        $this->factory->create(WithholdingTaxSimpleRule::class, [
            'type' => self::$fakeTaxType,
        ]);

        $this->factory->create(WithholdingTaxSimpleRule::class, [
            'type' => self::$anotherFakeTaxType,
        ]);

        $simpleRules = $this->simpleRuleRepository->findRulesByFederalTaxRule($wtr);
        $this->assertCount(1, $simpleRules);
    }

    /** @test */
    public function it_can_find_provincial_rules(): void
    {
        /** @var Province $pba */
        $pba = $this->provinceRepository->find(self::BUENOS_AIRES);

        /** @var WithholdingTaxRule $wtr */
        $wtr = $this->factory->create(WithholdingTaxRule::class, [
            'type' => self::$fakeTaxType,
            'province' => $pba,
        ]);

        $this->factory->create(WithholdingTaxSimpleRule::class, [
            'type' => self::$fakeTaxType,
            'province' => $pba,
        ]);

        $this->factory->create(WithholdingTaxSimpleRule::class, [
            'type' => self::$fakeTaxType,
        ]);

        $simpleRules = $this->simpleRuleRepository->findRulesByProvincialTaxRule($wtr);
        $this->assertCount(1, $simpleRules);
    }
}
