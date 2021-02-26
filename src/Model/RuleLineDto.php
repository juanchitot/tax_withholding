<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;

class RuleLineDto implements RuleLineDtoInterface
{
    public $taxPayer;
    public $provinceIdentifier;
    public $rate;
    public $monthYear;
    public $withholdingTaxType;

    public function getMonthYear()
    {
        return $this->monthYear;
    }

    public function getProvinceIdentifier()
    {
        return $this->provinceIdentifier;
    }

    public function getRate()
    {
        return $this->rate;
    }

    public function getTaxType()
    {
        return $this->withholdingTaxType;
    }

    public function getProvinceGroup()
    {
        return null;
    }

    public function getStatusJurisdiction()
    {
        return null;
    }

    public function getCrc()
    {
        return null;
    }
}
