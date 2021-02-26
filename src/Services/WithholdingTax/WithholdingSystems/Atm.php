<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;

class Atm extends BaseSystem
{
    /** @var string */
    protected $grouperCuit;

    /** @var Province */
    protected $province;

    public function __construct(ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting, Carbon $executionDate, string $grouperCuit)
    {
        parent::__construct($provinceWithholdingTaxSetting, $executionDate);
        $this->province = $provinceWithholdingTaxSetting->getProvince();
        $this->grouperCuit = $grouperCuit;
    }

    public function getReportNames(): array
    {
        return ['withholding_taxes_atm'];
    }

    public function getFileName($reportName, $format = null): string
    {
        return
            'rr'.
            $this->grouperCuit.
            $this->dateFrom->format('Ym').
            $this->getExtension($format);
    }
}
