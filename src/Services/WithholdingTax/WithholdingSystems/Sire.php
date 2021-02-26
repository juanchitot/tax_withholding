<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class Sire extends FortnightSystem
{
    private const REPORT_DETAILS = 'withholding_taxes_sire_details';

    public function getReportNames(): array
    {
        return [
            self::REPORT_DETAILS,
        ];
    }

    protected function getBaseFilename($reportName): string
    {
        $taxString = strtolower(WithholdingTaxTypeEnum::getString($this->provinceWithholdingTaxSettings->getWithholdingTaxType()));
        $system = strtolower($this->provinceWithholdingTaxSettings->getWithholdingTaxSystem());

        return  $system.'-'.$taxString.'-detalles';
    }

    public function getFormats(): array
    {
        return ['txt'];
    }
}
