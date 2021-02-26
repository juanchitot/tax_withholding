<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use DateTime;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class SirtacDeclaration
{
    const ALL_PAYMENT_METHODS = 'ALL';
    /** @var int */
    private $id;

    /** @var Subsidiary */
    private $subsidiary;

    /** @var Province */
    private $province;

    /** @var TaxConcept */
    private $taxConcept;

    /** @var int */
    private $controlNumber;

    /** @var Carbon */
    private $settlementDate;

    /** @var Carbon */
    private $withholdingDate;

    /** @var int */
    private $certificateNumber;

    /** @var int */
    private $settlementNumber;

    /** @var float */
    private $taxableIncome;

    /** @var float */
    private $rate;

    /** @var float */
    private $amount;

    /** @var TaxCategory */
    private $taxCategory;

    /** @var Certificate */
    private $certificate;

    /** @var string */
    private $status;

    /** @var int */
    private $salesCount;

    /** @var int */
    private $provinceJurisdiction;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): SirtacDeclaration
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): string
    {
        return WithholdingTaxTypeEnum::SIRTAC;
    }

    public function getDate()
    {
        return $this->getWithholdingDate();
    }

    public function getSubsidiary(): Subsidiary
    {
        return $this->subsidiary;
    }

    public function setSubsidiary(Subsidiary $subsidiary): SirtacDeclaration
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    public function getProvince(): Province
    {
        return $this->province;
    }

    public function setProvince(Province $province): SirtacDeclaration
    {
        $this->province = $province;

        return $this;
    }

    public function getTaxConcept(): TaxConcept
    {
        return $this->taxConcept;
    }

    public function setTaxConcept(TaxConcept $taxConcept): SirtacDeclaration
    {
        $this->taxConcept = $taxConcept;

        return $this;
    }

    public function getControlNumber(): int
    {
        return $this->controlNumber;
    }

    public function setControlNumber(int $controlNumber): SirtacDeclaration
    {
        $this->controlNumber = $controlNumber;

        return $this;
    }

    public function getSettlementDate(): Carbon
    {
        return Carbon::instance($this->settlementDate);
    }

    /**
     * @param Carbon $settlementDate
     */
    public function setSettlementDate(DateTime $settlementDate): SirtacDeclaration
    {
        $this->settlementDate = $settlementDate;

        return $this;
    }

    public function getWithholdingDate(): Carbon
    {
        return Carbon::instance($this->withholdingDate);
    }

    /**
     * @param Carbon $withholdingDate
     */
    public function setWithholdingDate(DateTime $withholdingDate): SirtacDeclaration
    {
        $this->withholdingDate = $withholdingDate;

        return $this;
    }

    public function getCertificateNumber(): ?int
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(?int $certificateNumber): SirtacDeclaration
    {
        $this->certificateNumber = $certificateNumber;

        return $this;
    }

    public function getSettlementNumber(): int
    {
        return $this->settlementNumber;
    }

    public function setSettlementNumber(int $settlementNumber): SirtacDeclaration
    {
        $this->settlementNumber = $settlementNumber;

        return $this;
    }

    public function getTaxableIncome(): float
    {
        return $this->taxableIncome;
    }

    public function setTaxableIncome(float $taxableIncome): SirtacDeclaration
    {
        $this->taxableIncome = $taxableIncome;

        return $this;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): SirtacDeclaration
    {
        $this->rate = $rate;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): SirtacDeclaration
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTaxCategory(): TaxCategory
    {
        return $this->taxCategory;
    }

    public function setTaxCategory(TaxCategory $taxCategory): SirtacDeclaration
    {
        $this->taxCategory = $taxCategory;

        return $this;
    }

    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    public function setCertificate(?Certificate $certificate): SirtacDeclaration
    {
        $this->certificate = $certificate;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): SirtacDeclaration
    {
        $this->status = $status;

        return $this;
    }

    public function getSalesCount(): int
    {
        return $this->salesCount;
    }

    public function setSalesCount(int $salesCount): SirtacDeclaration
    {
        $this->salesCount = $salesCount;

        return $this;
    }

    public function getProvinceJurisdiction(): int
    {
        return $this->provinceJurisdiction;
    }

    public function setProvinceJurisdiction(int $provinceJurisdiction): SirtacDeclaration
    {
        $this->provinceJurisdiction = $provinceJurisdiction;

        return $this;
    }

    public function getPaymentType(): string
    {
        return self::ALL_PAYMENT_METHODS;
    }
}
