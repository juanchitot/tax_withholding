<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRuleProvinceRate;

class WithholdingTaxDynamicRuleProvinceRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxDynamicRuleProvinceRate::class);
    }

    public function findAllRates(Province $province)
    {
        $qb = $this->createQueryBuilder('whdrpr')
            ->select('whdrpr.externalId, whdrpr.rate')
            ->where('whdrpr.province=:province')
            ->setParameter('province', $province);

        $result = $qb->getQuery()->getResult();

        $returnArray = [];
        foreach ($result as $row) {
            $returnArray[$row['externalId']] = $row['rate'];
        }

        return $returnArray;
    }
}
