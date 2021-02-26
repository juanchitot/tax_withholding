<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

final class Sirtac extends FortnightSystem
{
    private const SIRTAC_DECLARATIONS = 'sirtac_declarations';

    public function getReportNames(): array
    {
        return [
            self::SIRTAC_DECLARATIONS,
        ];
    }

    public function getFormats(): array
    {
        return ['txt'];
    }

    protected function getBaseFilename($reportName): string
    {
        return str_replace('_', '-', self::SIRTAC_DECLARATIONS);
    }
}
