<?php

namespace GeoPagos\WithholdingTaxBundle\Repository\Strategy\TaxGenerationStrategy;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;

interface DeclarationGenerationStrategy
{
    /** @return WithholdingTax[]|SirtacDeclaration[] */
    public function generate(ProvinceWithholdingTaxSetting $setting, Carbon $startDate, Carbon $endDate): array;

    public function removeOld(ProvinceWithholdingTaxSetting $setting, Carbon $executionDate): int;
}
