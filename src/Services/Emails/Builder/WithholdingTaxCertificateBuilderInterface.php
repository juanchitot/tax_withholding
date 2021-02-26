<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Emails\Builder;

use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;

interface WithholdingTaxCertificateBuilderInterface
{
    public function setWithholdingTax(WithholdingTax $withholdingTax): self;

    public function setProvinceWithholdingTaxSettings(ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting): self;

    public function setDetails(array $details): self;

    public function showRate(bool $show): self;

    public function showPaymentType(bool $show): self;

    public function getWithholdingTax();

    public function getDetails(): array;

    public function getShowRate();

    public function getProvinceWithholdingTaxSetting();

    public function getTotalAmount();

    public function getHtml();
}
