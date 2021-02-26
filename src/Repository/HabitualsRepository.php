<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Exception;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\Habitual;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class HabitualsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Habitual::class);
    }

    public function findBySubjectTaxAndProvince(Subsidiary $subsidiary, string $taxType, ?Province $province): ?Habitual
    {
        $this->validateIfProvinceIsMandatory($taxType, $province);

        return $this->findOneBy([
            'subsidiary' => $subsidiary,
            'taxType' => $taxType,
            'province' => $province,
        ]);
    }

    public function markAsHabitual(Subsidiary $subsidiary, string $taxType, ?Province $province): ?Habitual
    {
        $this->validateIfProvinceIsMandatory($taxType, $province);

        $habitual = new Habitual();

        $habitual
            ->setSubsidiary($subsidiary)
            ->setProvince($province)
            ->setTaxType($taxType)
            ->setSince(Carbon::now());

        $this->_em->persist($habitual);
        $this->_em->flush();

        return $habitual;
    }

    private function validateIfProvinceIsMandatory(string $taxType, Province $province = null): void
    {
        if (WithholdingTaxTypeEnum::TAX === $taxType && null === $province) {
            throw new Exception("Province param is mandatory for $taxType type");
        }
    }
}
