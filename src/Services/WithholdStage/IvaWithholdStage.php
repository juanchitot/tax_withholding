<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdStage;

use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\Generic\GenericFederalStage;

final class IvaWithholdStage extends GenericFederalStage
{
    public static function getTaxType(): string
    {
        return WithholdingTaxTypeEnum::VAT;
    }

    protected function isSubjectConfiguredAsExcluded(WithholdingStageContext $stageContext): bool
    {
        return (bool) $stageContext->getAccount()->isExcludeVat();
    }
}
