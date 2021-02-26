<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxExclusion;

class WithholdingTaxExclusionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxExclusion::class);
    }

    public function findExclusionsBy(Subsidiary $subsidiary)
    {
        $today = Carbon::now()->setTimezone($subsidiary->getTimezone());

        $qb = $this->createQueryBuilder('w')
            ->andWhere('w.subsidiary=:subsidiary')
            ->setParameter('subsidiary', $subsidiary)
            ->andWhere(':today >=w.dateFrom')
            ->andWhere(':today<=w.dateTo')
            ->setParameter('today', $today);

        return $qb->getQuery()->getResult();
    }
}
