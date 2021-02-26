<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;

class Agip extends BaseSystem
{
    public const REPORT_NAME = 'withholding_taxes_agip';
    public const BASE_FILENAME = 'agip';

    /** @var Province */
    protected $province;

    public function __construct(ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting, Carbon $executionDate)
    {
        parent::__construct($provinceWithholdingTaxSetting, $executionDate);
        $this->province = $provinceWithholdingTaxSetting->getProvince();
    }

    public function getReportNames(): array
    {
        return [self::REPORT_NAME];
    }

    public function getBaseFilename($reportName): string
    {
        return self::BASE_FILENAME;
    }
}
