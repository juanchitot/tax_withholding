<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit\Entity;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use PHPUnit\Framework\TestCase;

class ProvinceWithholdingTaxSettingTest extends TestCase
{
    /** @test */
    public function it_have_last_certificate_in_0_when_created()
    {
        $pwts = new ProvinceWithholdingTaxSetting();
        $this->assertEquals(0, $pwts->getLastCertificate());
    }

    /** @test */
    public function it_can_increment_the_last_certificate_number_and_return_it()
    {
        $pwts = new ProvinceWithholdingTaxSetting();
        $this->assertEquals(1, $pwts->increaseAndGetLastCertificateNumber());
        $this->assertEquals(2, $pwts->increaseAndGetLastCertificateNumber());
        $this->assertEquals(3, $pwts->increaseAndGetLastCertificateNumber());
    }

    /** @test */
    public function it_can_update_last_period_certificate_number_based_on_actual_date()
    {
        $now = Carbon::create(2020, 5, 1);
        Carbon::setTestNow($now);

        $pwts = new ProvinceWithholdingTaxSetting();
        $pwts->setupPeriodCertificateGeneration();

        $this->assertEquals(1, $pwts->increaseAndGetLastCertificateNumber());
        $this->assertEquals(2, $pwts->increaseAndGetLastCertificateNumber());
        $this->assertEquals(0, $pwts->getLastPeriodLastCertificate());

        $nextMonth = $now->addMonth();
        Carbon::setTestNow($nextMonth);

        $pwts->setupPeriodCertificateGeneration();

        $this->assertEquals(2, $pwts->getLastPeriodLastCertificate());
        $this->assertEquals(3, $pwts->increaseAndGetLastCertificateNumber());
        $this->assertEquals(4, $pwts->increaseAndGetLastCertificateNumber());
    }

    /** @test */
    public function it_can_provide_period_start_and_end_date_based_on_period()
    {
        $now = Carbon::create(2020, 5, 1);
        Carbon::setTestNow($now);

        $pwts = new ProvinceWithholdingTaxSetting();
        $pwts->setPeriod(Period::SEMI_MONTHLY);

        $lastPeriodStartDate = $now->copy()->subMonth()->day(16)->startOfDay();
        $lastPeriodEndDate = $now->copy()->subMonth()->endOfMonth()->endOfDay();

        $this->assertEquals(
            $lastPeriodStartDate->format('dmYHis'),
            $pwts->calculateLastPeriodStartDate()->format('dmYHis')
        );

        $this->assertEquals(
            $lastPeriodEndDate->format('dmYHis'),
            $pwts->calculateLastPeriodEndDate()->format('dmYHis')
        );
    }

    /** @test */
    public function it_can_provide_period_start_and_end_date_based_on_period_and_specific_date()
    {
        $now = Carbon::create(2020, 5, 16);

        $pwts = new ProvinceWithholdingTaxSetting();
        $pwts->setPeriod(Period::MONTHLY);

        $lastPeriodStartDate = $now->copy()->subMonth()->startOfMonth()->startOfDay();
        $lastPeriodEndDate = $now->copy()->subMonth()->endOfMonth()->endOfDay();

        $this->assertEquals(
            $lastPeriodStartDate->format('dmYHis'),
            $pwts->calculateLastPeriodStartDate($now)->format('dmYHis')
        );

        $this->assertEquals(
            $lastPeriodEndDate->format('dmYHis'),
            $pwts->calculateLastPeriodEndDate($now)->format('dmYHis')
        );
    }
}
