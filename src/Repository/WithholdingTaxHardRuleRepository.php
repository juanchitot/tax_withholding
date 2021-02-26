<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;

class WithholdingTaxHardRuleRepository extends ServiceEntityRepository
{
    protected static $withholding_hard_rule_by_base_rule = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxHardRule::class);
    }

    public function findFirstBufferedHardRuleFromBaseRule(WithholdingTaxRule $taxRule)
    {
        if (!isset(self::$withholding_hard_rule_by_base_rule[$taxRule->getId()])) {
            $data = $this->findByWithholdingTaxRule($taxRule);
            if (count($data)) {
                self::$withholding_hard_rule_by_base_rule[$taxRule->getId()] = $data[0]->getId();

                return $data[0];
            }
        }
        if (!empty(self::$withholding_hard_rule_by_base_rule[$taxRule->getId()])) {
            return $this->find(self::$withholding_hard_rule_by_base_rule[$taxRule->getId()]);
        }

        return null;
    }
}
