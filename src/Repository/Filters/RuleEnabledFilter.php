<?php

namespace GeoPagos\WithholdingTaxBundle\Repository\Filters;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;

class RuleEnabledFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (WithholdingTaxRule::class !== $targetEntity->getName()) {
            return '';
        }

        return sprintf('%s.is_enabled = 1', $targetTableAlias);
    }
}
