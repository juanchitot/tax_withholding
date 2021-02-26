<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class Sicore extends FortnightSystem
{
    private const REPORT_SUBJECTS = 'withholding_taxes_sicore_subjects';
    private const REPORT_DETAILS = 'withholding_taxes_sicore_details';

    public function getReportNames(): array
    {
        return [
            self::REPORT_SUBJECTS,
            self::REPORT_DETAILS,
        ];
    }

    protected function getBaseFilename($reportName): string
    {
        $baseFilename = '';

        $taxString = strtolower(WithholdingTaxTypeEnum::getString($this->provinceWithholdingTaxSettings->getWithholdingTaxType()));

        $system = strtolower($this->provinceWithholdingTaxSettings->getWithholdingTaxSystem());
        switch ($reportName) {
            case self::REPORT_SUBJECTS:
                $baseFilename = $system.'-'.$taxString.'-destinatarios';

                break;
            case self::REPORT_DETAILS:
                $baseFilename = $system.'-'.$taxString.'-detalles';

                break;
        }

        return $baseFilename;
    }
}
