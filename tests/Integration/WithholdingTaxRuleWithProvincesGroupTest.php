<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;

class WithholdingTaxRuleWithProvincesGroupTest extends TestCase
{
    use FactoriesTrait;

    /** @test * */
    public function a_simple_rule_with_provinces_group()
    {
        $simpleRule = $this->factory->create(WithholdingTaxSimpleRule::class, [
            'type' => 'TEST',
            'provincesGroup' => $this->factory->create(TaxRuleProvincesGroup::class),
        ]);
        $this->assertInstanceOf(TaxRuleProvincesGroup::class, $simpleRule->getProvincesGroup());
    }

    /** @test * */
    public function a_dynamic_rule_with_provinces_group()
    {
        $simpleRule = $this->factory->create(WithholdingTaxDynamicRule::class, [
            'type' => 'TEST',
            'provincesGroup' => $this->factory->create(TaxRuleProvincesGroup::class),
        ]);
        $this->assertInstanceOf(TaxRuleProvincesGroup::class, $simpleRule->getProvincesGroup());
    }
}
