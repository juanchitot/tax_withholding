<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit\Entity;

use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use PHPUnit\Framework\TestCase;

class WithholdingTaxDetailTest extends TestCase
{
    /** @test */
    public function it_have_amount_equals_0_when_created()
    {
        $withholdingTax = new WithholdingTaxDetail();
        $this->assertEquals(0, $withholdingTax->getAmount());
    }

    /** @test */
    public function it_can_increment_amount_by_number()
    {
        $withholdingTax = new WithholdingTaxDetail();
        $this->assertEquals(10, $withholdingTax->addAmount(10)->getAmount());
    }
}
