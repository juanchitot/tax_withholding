<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class WithholdingTaxRuleRepository extends ServiceEntityRepository
{
    protected static $taxRuleCache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxRule::class);
    }

    public function findWithholdingTaxRuleForTax(Province $province): ?object
    {
        return $this->findOneBy([
            'province' => $province,
            'type' => WithholdingTaxTypeEnum::TAX,
        ]);
    }

    public function findWithholdingTaxRuleForVat(Province $province): object
    {
        return $this->findOneBy([
            'type' => WithholdingTaxTypeEnum::VAT,
            'enabled' => true,
        ]);
    }

    public function findWithholdingTaxRuleForIncomeTax(Province $province): object
    {
        return $this->findOneBy(['type' => WithholdingTaxTypeEnum::INCOME_TAX]);
    }

    public function findWithholdingTaxRuleForItbis(Province $province): object
    {
        return $this->findOneBy(['type' => WithholdingTaxTypeEnum::ITBIS]);
    }

    /** @return WithholdingTaxRule[] */
    public function getRulesSortedByTaxTypeAndProvince(array $enabledTaxTypes = null): array
    {
        $qb = $this->createQueryBuilder('wtr')
            ->addSelect('
                CASE WHEN(wtr.type LIKE :taxType)
                THEN 1
                ELSE 0
                END as HIDDEN taxType
            ')
            ->orderBy('taxType')
            ->addOrderBy('wtr.province');

        if (!empty($enabledTaxTypes)) {
            $qb->andWhere($qb->expr()->in('wtr.type', $enabledTaxTypes));
        }

        $qb->setParameters([
            'taxType' => WithholdingTaxTypeEnum::TAX,
        ]);

        return $qb->getQuery()->getResult();
    }
}
