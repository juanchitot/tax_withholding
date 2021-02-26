<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit;

use GeoPagos\WithholdingTaxBundle\Helper\DiscretizationHelper;
use PHPUnit\Framework\TestCase;

class DiscretizationHelperTest extends TestCase
{
    /** @test * */
    public function truncate_to_n_digits_precision()
    {
        $this->assertEquals(11.33, DiscretizationHelper::truncate(11.333));
        $this->assertEquals(11.33, DiscretizationHelper::truncate(11.336));
        $this->assertEquals(11.999, DiscretizationHelper::truncate(11.9999, 3));
        $this->assertEquals(11.119, DiscretizationHelper::truncate(11.11999, 3));
        $this->assertEquals(11, DiscretizationHelper::truncate(11.11999, 0));
    }
}
