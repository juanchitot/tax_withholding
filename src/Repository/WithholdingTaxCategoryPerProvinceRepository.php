<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxCategoryPerProvince;

class WithholdingTaxCategoryPerProvinceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxCategoryPerProvince::class);
    }

    public function findOneBySubsidiaryAndProvince(Subsidiary $subsidiary, Province $province)
    {
        return $this->findOneBy([
            'subsidiary' => $subsidiary,
            'province' => $province,
        ]);
    }
}
