<?php

namespace GeoPagos\WithholdingTaxBundle\Contract;

interface RuleLineDtoInterface
{
    public function getMonthYear();

    public function getProvinceIdentifier();

    public function getRate();

    public function getTaxType();

    public function getProvinceGroup();

    public function getStatusJurisdiction();

    public function getCrc();
}
