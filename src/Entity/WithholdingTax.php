<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;

class WithholdingTax
{
    /** @var int */
    private $id;

    /** @var Subsidiary */
    private $subsidiary;

    /** @var Province */
    private $province;

    /** @var int */
    private $certificateNumber = 0;

    /** @var Carbon */
    private $date;

    /** @var float */
    private $taxableIncome = 0;

    /** @var float */
    private $rate = 0;

    /** @var float */
    private $amount = 0;

    /** @var string */
    private $status;

    /** @var string */
    private $file;

    /** @var string */
    private $type;

    /** @var string */
    private $paymentType;

    /** @var TaxCategory */
    private $taxCategory;

    /** @var TaxCondition */
    private $taxCondition;

    /** @var Certificate */
    private $certificate;

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    public function setPaymentType(?string $paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getId(): ?int
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

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    public function getCertificateNumber(): int
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(int $certificateNumber): self
    {
        $this->certificateNumber = $certificateNumber;

        return $this;
    }

    public function getDate(): Carbon
    {
        return Carbon::instance($this->date);
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTaxableIncome(): ?float
    {
        return $this->taxableIncome;
    }

    public function addTaxableIncome(float $taxableIncome): self
    {
        $this->taxableIncome += $taxableIncome;

        return $this;
    }

    public function setTaxableIncome(float $taxableIncome): self
    {
        $this->taxableIncome = $taxableIncome;

        return $this;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function addAmount(float $amount): self
    {
        $this->amount += $amount;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        WithholdingTaxStatus::isValidValueOrThrowException($status);

        $this->status = $status;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setTaxCondition(TaxCondition $taxCondition): self
    {
        $this->taxCondition = $taxCondition;

        return $this;
    }

    public function getTaxCondition(): TaxCondition
    {
        return $this->taxCondition;
    }

    public function setTaxCategory(TaxCategory $taxCategory): self
    {
        $this->taxCategory = $taxCategory;

        return $this;
    }

    public function getTaxCategory(): TaxCategory
    {
        return $this->taxCategory;
    }

    public function setCertificate(Certificate $certificate): WithholdingTax
    {
        $this->certificate = $certificate;

        return $this;
    }

    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }
}
