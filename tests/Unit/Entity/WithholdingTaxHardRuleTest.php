<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit\Entity;

use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use PHPUnit\Framework\TestCase;

class WithholdingTaxHardRuleTest extends TestCase
{
    /** @test */
    public function it_have_minimun_amount_equals_0_when_created()
    {
        $withholdingTaxHardRule = new WithholdingTaxHardRule();
        $this->assertEquals(0, $withholdingTaxHardRule->getMinimunAmount());
    }
}
