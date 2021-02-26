<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

class WithholdingTaxDynamicRule implements WithholdingTaxRuleInterface
{
    const USE_DYNAMIC_RULE = 'En padrón';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $idFiscal;

    /**
     * @var Province
     */
    private $province;

    /**
     * @var string
     */
    private $rate;

    /**
     * string.
     */
    private $monthYear;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var int
     */
    private $taxType;
    /**
     * @var TaxRuleProvincesGroup
     */
    private $provincesGroup;

    /** @var string */
    private $statusJurisdictions;

    /** @var int */
    private $crc;

    public function __construct($idFiscal, ?Province $province, $monthYear, $rate, $taxType)
    {
        $this->idFiscal = $idFiscal;
        $this->province = $province;
        $this->monthYear = $monthYear;
        $this->rate = $rate;
        $this->taxType = $taxType;
        $this->createdAt = Carbon::now();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdFiscal(): string
    {
        return $this->idFiscal;
    }

    public function setIdFiscal(string $idFiscal): self
    {
        $this->idFiscal = $idFiscal;

        return $this;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function setRate(string $rate): self
    {
        $this->rate = $rate;
        $this->setCreatedAt(Carbon::now());

        return $this;
    }

    public function getMonthYear()
    {
        return $this->monthYear;
    }

    public function setMonthYear($monthYear): self
    {
        $this->monthYear = $monthYear;

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTaxType()
    {
        return $this->taxType;
    }

    public function setTaxType($taxType)
    {
        $this->taxType = $taxType;

        return $this;
    }

    public function shouldSkipByMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): bool {
        return $withholdingTaxRule->getMinimumDynamicRuleAmount() > 0 && $taxableAmountFromTransactions < $withholdingTaxRule->getMinimumDynamicRuleAmount();
    }

    public function calculateRequiredMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): float {
        return $withholdingTaxRule->getMinimumDynamicRuleAmount();
    }

    /**
     * @return TaxRuleProvincesGroup
     */
    public function getProvincesGroup(): ?TaxRuleProvincesGroup
    {
        return $this->provincesGroup;
    }

    public function setProvincesGroup(?TaxRuleProvincesGroup $provincesGroup): void
    {
        $this->provincesGroup = $provincesGroup;
    }

    public function getStatusJurisdictions(): ?string
    {
        return $this->statusJurisdictions;
    }

    public function setStatusJurisdictions(string $statusJurisdictions): self
    {
        $this->statusJurisdictions = $statusJurisdictions;

        return $this;
    }

    public function getLogDescription(WithholdingTaxLog $log): string
    {
        return self::USE_DYNAMIC_RULE;
    }

    public function getCrc(): int
    {
        return $this->crc;
    }

    public function setCrc(int $crc): WithholdingTaxDynamicRule
    {
        $this->crc = $crc;

        return $this;
    }
}
