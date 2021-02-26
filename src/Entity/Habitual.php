<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use DateTime;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;

class Habitual
{
    /** @var int */
    private $id;

    /** @var Subsidiary */
    private $subsidiary;

    /** @var string */
    private $taxType;

    /** @var Province */
    private $province;

    /** @var Carbon */
    private $since;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Habitual
    {
        $this->id = $id;

        return $this;
    }

    public function getSubsidiary(): Subsidiary
    {
        return $this->subsidiary;
    }

    public function setSubsidiary(Subsidiary $subsidiary): Habitual
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }

    public function setTaxType(string $taxType): Habitual
    {
        $this->taxType = $taxType;

        return $this;
    }

    public function getSince(): Carbon
    {
        return Carbon::instance($this->since);
    }

    public function setSince(DateTime $since): Habitual
    {
        $this->since = $since;

        return $this;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): Habitual
    {
        $this->province = $province;

        return $this;
    }
}
