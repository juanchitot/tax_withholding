<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use GeoPagos\ApiBundle\Entity\Province;
use JsonSerializable;

class WithholdingTaxDynamicRuleProvinceRate implements JsonSerializable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Province
     */
    private $province;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $rate;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(Province $province): void
    {
        $this->province = $province;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function setRate(string $rate): void
    {
        $this->rate = $rate;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'province_id' => $this->getProvince() ? $this->getProvince()->getId() : null,
            'external_id' => $this->getExternalId(),
            'rate' => $this->getRate(),
        ];
    }
}
