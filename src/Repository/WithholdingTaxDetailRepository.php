<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Enum\TaxConceptEnum;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class WithholdingTaxDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxDetail::class);
    }

    public function getFederalAmountBySaleIds(array $saleIds): array
    {
        return $this->createQueryBuilder('wtd')
            ->select([
                'wtd.type as name',
                'SUM(wtd.amount) as amount',
            ])
            ->where('wtd.transaction IN (:saleIds)')
            ->andWhere('wtd.type IN (:federalTaxTypes)')
            ->groupBy('wtd.type')
            ->setParameters([
                'saleIds' => $saleIds,
                'federalTaxTypes' => WithholdingTaxTypeEnum::getFederalTaxTypes(),
            ])
            ->getQuery()
            ->getScalarResult();
    }

    public function getProvincialAmountBySaleIds(array $saleIds): array
    {
        return $this->createQueryBuilder('wtd')
            ->select([
                'wtl.ruleApplied as name',
                'SUM(wtd.amount) as amount',
            ])
            ->join('wtd.withholdingTaxLog', 'wtl')
            ->where('wtd.transaction IN (:saleIds)')
            ->andWhere('wtd.type IN (:provincialTaxTypes)')
            ->andWhere('wtd.concept != :informativeConcept')
            ->groupBy('wtd.concept')
            ->addGroupBy('wtd.type')
            ->addGroupBy('wtl.province')
            ->setParameters([
                'saleIds' => $saleIds,
                'provincialTaxTypes' => [
                    WithholdingTaxTypeEnum::TAX,
                    WithholdingTaxTypeEnum::SIRTAC,
                ],
                'informativeConcept' => TaxConceptEnum::INFORMATIVE_ID,
            ])
            ->getQuery()
            ->getScalarResult();
    }
}
