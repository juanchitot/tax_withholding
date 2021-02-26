<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdStage;

use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\Generic\GenericFederalStage;

final class ItbisWithholdStage extends GenericFederalStage
{
    public static function getTaxType(): string
    {
        return WithholdingTaxTypeEnum::ITBIS;
    }

    protected function isSubjectConfiguredAsExcluded(WithholdingStageContext $stageContext): bool
    {
        return false;
    }
}
