<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Subsidiary;

class SubsidiaryWithheldTaxes
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Subsidiary
     */
    private $subsidiary;

    /**
     * @var Carbon
     */
    private $vatLastWithheld;

    /**
     * @var Carbon
     */
    private $earningsTaxLastWithheld;

    /**
     * @var Carbon
     */
    private $grossIncomeTaxLastWithheld;

    /**
     * @var Carbon
     */
    private $sirtacTaxLastWithheld;

    public function __construct(Subsidiary $subsidiary)
    {
        $this->subsidiary = $subsidiary;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubsidiary(): Subsidiary
    {
        return $this->subsidiary;
    }

    public function setSubsidiary(Subsidiary $subsidiary): self
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    public function getVatLastWithheldd(): ?Carbon
    {
        return $this->vatLastWithheld;
    }

    public function setVatLastWithheldd(Carbon $vatLastWithheld): self
    {
        $this->vatLastWithheld = $vatLastWithheld;

        return $this;
    }

    public function getEarningsTaxLastWithheld(): ?Carbon
    {
        return $this->earningsTaxLastWithheld;
    }

    public function setEarningsTaxLastWithheld(Carbon $earningsTaxLastWithheld): self
    {
        $this->earningsTaxLastWithheld = $earningsTaxLastWithheld;

        return $this;
    }

    public function getGrossIncomeTaxLastWithheld(): ?Carbon
    {
        return $this->grossIncomeTaxLastWithheld;
    }

    public function setGrossIncomeTaxLastWithheld(Carbon $grossIncomeTaxLastWithheld): self
    {
        $this->grossIncomeTaxLastWithheld = $grossIncomeTaxLastWithheld;

        return $this;
    }

    public function getSirtacTaxLastWithheld(): ?Carbon
    {
        return $this->sirtacTaxLastWithheld;
    }

    public function setSirtacTaxLastWithheld(Carbon $sirtacTaxLastWithheld): SubsidiaryWithheldTaxes
    {
        $this->sirtacTaxLastWithheld = $sirtacTaxLastWithheld;

        return $this;
    }
}
