<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;

class TaxConceptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxConcept::class);
    }
}
