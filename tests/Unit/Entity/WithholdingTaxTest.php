<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit\Entity;

use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use PHPUnit\Framework\TestCase;

class WithholdingTaxTest extends TestCase
{
    /** @test */
    public function it_have_amount_equals_0_when_created()
    {
        $withholdingTax = new WithholdingTax();
        $this->assertEquals(0, $withholdingTax->getAmount());
    }

    /** @test */
    public function it_have_taxable_income_equals_0_when_created()
    {
        $withholdingTax = new WithholdingTax();
        $this->assertEquals(0, $withholdingTax->getTaxableIncome());
    }

    /** @test */
    public function it_can_increment_taxable_income_by_number()
    {
        $withholdingTax = new WithholdingTax();

        $this->assertEquals(10, $withholdingTax->addTaxableIncome(10)->getTaxableIncome());
    }

    /** @test */
    public function it_can_increment_amount_by_number()
    {
        $withholdingTax = new WithholdingTax();
        $this->assertEquals(10, $withholdingTax->addAmount(10)->getAmount());
    }
}
