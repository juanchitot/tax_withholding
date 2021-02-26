<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\Period;

class ProvinceWithholdingTaxTest extends TestCase
{
    use FactoriesTrait;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
        BusinessDay::enable('Carbon\Carbon');

        $this->em = self::$container->get(EntityManagerInterface::class);
    }

    /** @test */
    public function montlhy_and_semi_monthly_regeneration_will_work_as_expected(): void
    {
        $startOfMonth = Carbon::parse('2020-05-01');
        Carbon::setTestNow($startOfMonth);

        $monthlySetting = new ProvinceWithholdingTaxSetting();
        $monthlySetting->setPeriod(Period::MONTHLY);

        $semiMonthlySetting = new ProvinceWithholdingTaxSetting();
        $semiMonthlySetting->setPeriod(Period::SEMI_MONTHLY);
        $semiMonthlySetting->setLastPeriodStartDate($semiMonthlySetting->calculateLastPeriodStartDate());

        $monthlySetting->setupPeriodCertificateGeneration();
        $semiMonthlySetting->setupPeriodCertificateGeneration();

        // We simulate 10 last certificate last month for monthly and 5 for semi_monthly
        for ($i = 0; $i < 10; ++$i) {
            $monthlySetting->increaseAndGetLastCertificateNumber();
            if (0 === $i % 2) {
                $semiMonthlySetting->increaseAndGetLastCertificateNumber();
            }
        }

        // Validamos los valores que quedaron de certificateNumber
        $this->assertEquals(10, $monthlySetting->getLastCertificate());
        $this->assertEquals(5, $semiMonthlySetting->getLastCertificate());

        // PRIMERA EJECUCION DEL CRON QUINCENAL
        $halfOfMonth = Carbon::parse('2020-05-16');
        Carbon::setTestNow($halfOfMonth);

        $monthlySetting->setupPeriodCertificateGeneration();
        $semiMonthlySetting->setupPeriodCertificateGeneration();

        for ($i = 0; $i < 5; ++$i) {
            $semiMonthlySetting->increaseAndGetLastCertificateNumber();
        }

        // Validamos los valores que quedaron de certificateNumber
        $this->assertEquals(10, $monthlySetting->getLastCertificate());
        $this->assertEquals(10, $semiMonthlySetting->getLastCertificate());

        // SEGUNDA EJECUCION DEL CRON QUINCENAL, se regeneran los certificados quincenales nomas.
        $halfOfMonth = Carbon::parse('2020-05-16');
        Carbon::setTestNow($halfOfMonth);

        $monthlySetting->setupPeriodCertificateGeneration();
        $semiMonthlySetting->setupPeriodCertificateGeneration();

        for ($i = 0; $i < 5; ++$i) {
            $semiMonthlySetting->increaseAndGetLastCertificateNumber();
        }

        // Validamos los valores que quedaron de certificateNumber
        $this->assertEquals(10, $monthlySetting->getLastCertificate());
        $this->assertEquals(10, $semiMonthlySetting->getLastCertificate());
    }
}
