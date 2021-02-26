<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use GeoPagos\ApiBundle\Entity\Province;

class TaxRuleProvincesGroupItem
{
    /** @var int */
    private $id;

    /** @var TaxRuleProvincesGroup */
    private $taxRuleProvincesGroup;

    /** @var Province */
    private $province;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProvince(): Province
    {
        return $this->province;
    }

    public function setProvince(Province $province): void
    {
        $this->province = $province;
    }

    /**
     * @return TaxRuleProvincesGroup
     */
    public function getTaxRuleProvincesGroup(): ?TaxRuleProvincesGroup
    {
        return $this->taxRuleProvincesGroup;
    }

    public function setTaxRuleProvincesGroup(TaxRuleProvincesGroup $taxRuleProvincesGroup): void
    {
        $this->taxRuleProvincesGroup = $taxRuleProvincesGroup;
    }
}
