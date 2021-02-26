<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Repository;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\Habitual;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Repository\HabitualsRepository;

class HabitualsRepositoryTest extends TestCase
{
    use FactoriesTrait;

    private const BUENOS_AIRES = 6;
    private const MENDOZA = 50;

    /** @var HabitualsRepository */
    private $habitualsRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->habitualsRepository = self::$container->get(HabitualsRepository::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    /** @test */
    public function it_can_mark_and_fetch_habituals()
    {
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class);

        $aProvince = $this->entityManager->find(Province::class, self::BUENOS_AIRES);
        $anotherProvince = $this->entityManager->find(Province::class, self::MENDOZA);

        $this->habitualsRepository->markAsHabitual($subsidiary, WithholdingTaxTypeEnum::VAT, null);
        $this->habitualsRepository->markAsHabitual($subsidiary, WithholdingTaxTypeEnum::INCOME_TAX, null);
        $this->habitualsRepository->markAsHabitual($subsidiary, WithholdingTaxTypeEnum::ITBIS, null);
        $this->habitualsRepository->markAsHabitual($subsidiary, WithholdingTaxTypeEnum::TAX, $aProvince);
        $this->habitualsRepository->markAsHabitual($subsidiary, WithholdingTaxTypeEnum::TAX, $anotherProvince);

        $this->assertNotNull(
            $this->habitualsRepository->findBySubjectTaxAndProvince(
                $subsidiary,
                WithholdingTaxTypeEnum::VAT,
                null
            )
        );

        $this->assertNotNull(
            $this->habitualsRepository->findBySubjectTaxAndProvince(
                $subsidiary,
                WithholdingTaxTypeEnum::INCOME_TAX,
                null
            )
        );

        $this->assertNotNull(
            $this->habitualsRepository->findBySubjectTaxAndProvince(
                $subsidiary,
                WithholdingTaxTypeEnum::ITBIS,
                null
            )
        );

        $this->assertNotNull(
            $this->habitualsRepository->findBySubjectTaxAndProvince(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $aProvince
            )
        );

        $this->assertNotNull(
            $this->habitualsRepository->findBySubjectTaxAndProvince(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $anotherProvince
            )
        );

        /** @var Habitual[] $habituals */
        $habituals = $this->habitualsRepository->findAll();

        foreach ($habituals as $habitual) {
            $this->assertEquals($habitual->getSubsidiary()->getId(), $subsidiary->getId());
            $this->assertNotNull($habitual->getSince());
        }

        $this->expectException(UniqueConstraintViolationException::class);

        $this->habitualsRepository->markAsHabitual($subsidiary, WithholdingTaxTypeEnum::TAX, $aProvince);
    }
}
