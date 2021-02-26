<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Classification;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

class WithholdingTaxSimpleRule implements WithholdingTaxRuleInterface
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
     * @var WithholdingTaxTypeEnum
     */
    private $type;

    /**
     * @var TaxCategory
     */
    private $taxCategory;

    /**
     * @var float
     */
    private $rate;

    /**
     * @var float
     */
    private $minimunAmount;

    /**
     * @var string
     */
    private $paymentMethodType;

    /**
     * @var Classification
     */
    private $classification;

    /**
     * @var TaxCondition
     */
    private $taxCondition;

    /**
     * @var string
     */
    private $incomeTax;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var TaxRuleProvincesGroup
     */
    private $provincesGroup;
    /**
     * @var float
     */
    private $taxableAmountCoefficient;

    public function __construct()
    {
        $this->createdAt = Carbon::now();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province)
    {
        $this->province = $province;
    }

    public function getTaxCategory(): ?TaxCategory
    {
        return $this->taxCategory;
    }

    public function setTaxCategory(TaxCategory $taxCategory): void
    {
        $this->taxCategory = $taxCategory;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }

    public function getMinimunAmount(): ?float
    {
        return $this->minimunAmount;
    }

    public function setMinimunAmount(float $minimunAmount): void
    {
        $this->minimunAmount = $minimunAmount;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPaymentMethodType(): ?string
    {
        return $this->paymentMethodType;
    }

    public function setPaymentMethodType(?string $paymentMethodType): void
    {
        $this->paymentMethodType = $paymentMethodType;
    }

    public function getClassification(): ?Classification
    {
        return $this->classification;
    }

    public function setClassification(Classification $classification = null): void
    {
        $this->classification = $classification;
    }

    /**
     * @return string
     */
    public function getIncomeTax(): ?string
    {
        return $this->incomeTax;
    }

    public function setIncomeTax(string $incomeTax): void
    {
        $this->incomeTax = $incomeTax;
    }

    public function getTaxCondition(): ?TaxCondition
    {
        return $this->taxCondition;
    }

    public function setTaxCondition(TaxCondition $taxCondition): void
    {
        $this->taxCondition = $taxCondition;
    }

    public function getTypeAsString()
    {
        return WithholdingTaxTypeEnum::getString($this->type);
    }

    public function getTaxableAmountCoefficient(): float
    {
        return $this->taxableAmountCoefficient;
    }

    public function setTaxableAmountCoefficient(float $coefficient): self
    {
        $this->taxableAmountCoefficient = $coefficient;

        return $this;
    }

    public function shouldSkipByMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): bool {
        return $this->getMinimunAmount() > 0 && $saleBag->getGrossAmount() < $this->getMinimunAmount();
    }

    public function calculateRequiredMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): float {
        return $this->getMinimunAmount();
    }

    /**
     * @return TaxRuleProvincesGroup
     */
    public function getProvincesGroup(): ?TaxRuleProvincesGroup
    {
        return $this->provincesGroup;
    }

    public function setProvincesGroup(TaxRuleProvincesGroup $provincesGroup): void
    {
        $this->provincesGroup = $provincesGroup;
    }

    public function getLogDescription(WithholdingTaxLog $log): string
    {
        if (WithholdingTaxTypeEnum::TAX === $this->getType() && !empty($log->getTaxCategory())) {
            return $log->getTaxCategory()->getName();
        }

        if (WithholdingTaxTypeEnum::TAX !== $this->getType() && !empty($log->getTaxCondition())) {
            return $log->getTaxCondition()->getName();
        }

        return '';
    }
}
