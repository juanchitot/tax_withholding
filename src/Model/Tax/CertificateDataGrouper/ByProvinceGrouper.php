<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Tax\CertificateDataGrouper;

use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;

class ByProvinceGrouper
{
    /**
     * @var string
     */
    private $taxType;

    public function __construct(string $taxType)
    {
        $this->taxType = $taxType;
    }

    public function group($data = [])
    {
        $result = [];
        /* @var $item WithholdingTax */
        foreach ($data as $item) {
            $province = $item->getProvince();
            if ($item->getType() != $this->taxType or empty($province)) {
                continue;
            }
            $provinceId = $item->getProvince()->getId();
            if (isset($data[$provinceId])) {
                $data[$provinceId] = $item;
            } else {
                $settings = $province->getProvinceWithholdingTaxSetting();
                if ($settings && $settings->getWithholdingTaxSystem()) {
                    $data[$provinceId][] = $item;
                }
            }
        }
    }
}
