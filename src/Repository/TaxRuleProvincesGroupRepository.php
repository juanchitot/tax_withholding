<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;

class TaxRuleProvincesGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxRuleProvincesGroup::class);
    }
}
