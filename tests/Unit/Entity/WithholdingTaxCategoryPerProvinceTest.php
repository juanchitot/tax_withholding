<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit\Entity;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\Tests\TestCase;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxCategoryPerProvince;

class WithholdingTaxCategoryPerProvinceTest extends TestCase
{
    private const BUENOS_AIRES = 6;
    private const INSCRIPTO_LOCAL = 1;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::$container->get(EntityManagerInterface::class);
    }

    /** @test */
    public function it_have_working_getters_and_setters()
    {
        $categoryPerProvince = new WithholdingTaxCategoryPerProvince();
        $province = $this->em->getRepository(Province::class)->find(self::BUENOS_AIRES);
        $taxCategory = $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL);
        $now = Carbon::now();
        $categoryPerProvince
            ->setId(1)
            ->setProvince($province)
            ->setTaxCategory($taxCategory)
            ->setWithholdingTaxFile('file')
            ->setWithholdingTaxAttachment('attachment')
            ->setWithholdingTaxNumber(123)
            ->setSubsidiary(null)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $this->assertEquals(1, $categoryPerProvince->getId());
        $this->assertEquals($province, $categoryPerProvince->getProvince());
        $this->assertEquals($taxCategory, $categoryPerProvince->getTaxCategory());
        $this->assertEquals('file', $categoryPerProvince->getWithholdingTaxFile());
        $this->assertEquals('attachment', $categoryPerProvince->getWithholdingTaxAttachment());
        $this->assertEquals(123, $categoryPerProvince->getWithholdingTaxNumber());
        $this->assertEquals(null, $categoryPerProvince->getSubsidiary());
        $this->assertEquals($now, $categoryPerProvince->getCreatedAt());
        $this->assertEquals($now, $categoryPerProvince->getUpdatedAt());
    }
}
