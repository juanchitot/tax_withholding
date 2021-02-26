<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use JsonSerializable;

class WithholdingTaxHardRule implements JsonSerializable, WithholdingTaxRuleInterface
{
    const MONTHLY = 'Habitualidad';

    /**
     * @var int
     */
    private $id;

    /**
     * @var WithholdingTaxRule
     */
    private $withholdingTaxRule;

    /**
     * @var float
     */
    private $rate;

    /**
     * @var string
     */
    private $rule;

    /**
     * @var string
     */
    private $verificationDate;

    /**
     * @var Carbon
     */
    private $modifiedAt;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var float
     */
    private $minimunAmount = 0;

    /**
     * @var TaxRuleProvincesGroup
     */
    private $provincesGroup;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWithholdingTaxRule(): WithholdingTaxRule
    {
        return $this->withholdingTaxRule;
    }

    public function setWithholdingTaxRule(WithholdingTaxRule $withholdingTaxRule): void
    {
        $this->withholdingTaxRule = $withholdingTaxRule;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(string $rate): void
    {
        $this->rate = $rate;
    }

    public function getRule(): ?string
    {
        return $this->rule;
    }

    public function setRule(string $rule): void
    {
        $this->rule = $rule;
    }

    public function getVerificationDate(): ?string
    {
        return $this->verificationDate;
    }

    public function setVerificationDate(string $verificationDate): void
    {
        $this->verificationDate = $verificationDate;
    }

    public function getModifiedAt(): ?Carbon
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(Carbon $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'withholding_tax_rule_id' => $this->getWithholdingTaxRule()->getId(),
            'rate' => $this->getRate(),
            'rule' => $this->getRule(),
            'minimun_amount' => $this->getMinimunAmount(),
            'verification_date' => $this->getVerificationDate(),
            'created_at' => $this->getCreatedAt(),
            'modified_at' => $this->getModifiedAt(),
        ];
    }

    public function getMinimunAmount(): float
    {
        return $this->minimunAmount;
    }

    /**
     * @return WithholdingTaxHardRule
     */
    public function setMinimunAmount(float $minimunAmount): self
    {
        $this->minimunAmount = $minimunAmount;

        return $this;
    }

    public function shouldApplyToday(): bool
    {
        $today = Carbon::now()->toDateString();
        $date = Carbon::parse($this->getVerificationDate() ?? 'today')->format('Y-m-d');

        return $today == $date;
    }

    public function shouldSkipByMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): bool {
        return $this->getMinimunAmount() > 0 && $taxableAmountFromTransactions < $this->getMinimunAmount();
    }

    public function calculateRequiredMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): float {
        return $this->getMinimunAmount();
    }

    public function getLogDescription(WithholdingTaxLog $log): string
    {
        return self::MONTHLY;
    }
}
