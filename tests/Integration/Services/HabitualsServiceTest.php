<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Services;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\HabitualsService;

class HabitualsServiceTest extends TestCase
{
    use FactoriesTrait;

    private const BUENOS_AIRES = 6;

    /** @var HabitualsService */
    private $habitualsService;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->habitualsService = self::$container->get(HabitualsService::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    /** @test */
    public function a_subsidiary_can_be_marked_as_habitual()
    {
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class);
        $aProvince = $this->entityManager->find(Province::class, self::BUENOS_AIRES);

        $this->assertFalse(
            $this->habitualsService->isSubjectMarkedAsHabitual($subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $aProvince
            )
        );

        $this->assertNotNull(
            $this->habitualsService->markSubjectAsHabitual(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $aProvince
            )
        );

        $this->assertTrue(
            $this->habitualsService->isSubjectMarkedAsHabitual($subsidiary, WithholdingTaxTypeEnum::TAX, $aProvince)
        );

        $this->assertNull(
            $this->habitualsService->markSubjectAsHabitual(
                $subsidiary,
                WithholdingTaxTypeEnum::TAX,
                $aProvince
            )
        );
    }
}
