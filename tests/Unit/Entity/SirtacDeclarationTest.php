<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Unit\Entity;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\Tests\TestCase;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;

class SirtacDeclarationTest extends TestCase
{
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
        $sirtacDeclaration = new SirtacDeclaration();

        $province = $this->em->getReference(Province::class, 600);
        $taxCategory = $this->em->getReference(TaxCategory::class, 500);
        $taxConcept = $this->em->getReference(TaxConcept::class, 400);
        $certificate = $this->em->getReference(Certificate::class, 300);
        $subsidiary = $this->em->getReference(Subsidiary::class, 200);

        $now = Carbon::now();
        $sirtacDeclaration
            ->setId(1)
            ->setSubsidiary($subsidiary)
            ->setProvince($province)
            ->setTaxConcept($taxConcept)
            ->setControlNumber(16)
            ->setSettlementDate($now->toDateTime())
            ->setWithholdingDate($now->toDateTime())
            ->setCertificateNumber(0000001)
            ->setSettlementNumber(0000001)
            ->setTaxableIncome(500)
            ->setRate(1.5)
            ->setAmount(50)
            ->setTaxCategory($taxCategory)
            ->setCertificate($certificate)
            ->setStatus(WithholdingTaxStatus::CREATED);

        $this->assertEquals($sirtacDeclaration->getId(), 1);
        $this->assertEquals($sirtacDeclaration->getSubsidiary()->getId(), 200);
        $this->assertEquals($sirtacDeclaration->getProvince()->getId(), 600);
        $this->assertEquals($sirtacDeclaration->getTaxConcept()->getId(), 400);
        $this->assertEquals($sirtacDeclaration->getControlNumber(), 16);
        $this->assertEquals($sirtacDeclaration->getSettlementDate()->format('ymd'), $now->format('ymd'));
        $this->assertEquals($sirtacDeclaration->getWithholdingDate()->format('ymd'), $now->format('ymd'));
        $this->assertEquals($sirtacDeclaration->getSettlementNumber(), 0000001);
        $this->assertEquals($sirtacDeclaration->getCertificateNumber(), 0000001);
        $this->assertEquals($sirtacDeclaration->getTaxableIncome(), 500);
        $this->assertEquals($sirtacDeclaration->getRate(), 1.5);
        $this->assertEquals($sirtacDeclaration->getAmount(), 50);
        $this->assertEquals($sirtacDeclaration->getTaxCategory()->getId(), 500);
        $this->assertEquals($sirtacDeclaration->getCertificate()->getId(), 300);
        $this->assertEquals($sirtacDeclaration->getStatus(), WithholdingTaxStatus::CREATED);
    }
}
