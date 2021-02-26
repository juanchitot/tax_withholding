<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class SaleBagTest extends TestCase
{
    /** @test * */
    public function it_can_round_amount_from_deposit()
    {
        $saleBag = new SaleBag(
            [new Transaction()],
            new Currency('032'),
            Carbon::now()
        );

        $saleBag->setNetAmount(0.3565);

        $this->assertEquals(
            0.36,
            $saleBag->getNetAmount());
    }
}
