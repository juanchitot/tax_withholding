<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Rules\Habituality;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

abstract class HabitualityRule implements WithholdingTaxRuleInterface
{
    /** @var float */
    private $rate;

    /** @var float */
    private $minimumAmount;

    /** @var int */
    private $nonTaxableCount;

    /** @var float */
    private $nonTaxableAmount;

    /** @var string */
    private $periodicity;

    public function __construct(
        float $rate,
        float $minimumAmount,
        int $nonTaxableCount,
        float $nonTaxableAmount,
        string $periodicity
    ) {
        $this->rate = $rate;
        $this->minimumAmount = $minimumAmount;
        $this->nonTaxableCount = $nonTaxableCount;
        $this->nonTaxableAmount = $nonTaxableAmount;
        $this->periodicity = $periodicity;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getMinimumAmount(): float
    {
        return $this->minimumAmount;
    }

    public function getNonTaxableCount(): int
    {
        return $this->nonTaxableCount;
    }

    public function getNonTaxableAmount(): float
    {
        return $this->nonTaxableAmount;
    }

    public function getPeriodicity(): string
    {
        return $this->periodicity;
    }

    public function shouldSkipByMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): bool {
        return $this->getMinimumAmount() > 0 && $taxableAmountFromTransactions < $this->getMinimumAmount();
    }

    public function calculateRequiredMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): float {
        return $this->getMinimumAmount();
    }

    public function getLogDescription(WithholdingTaxLog $log): string
    {
        return WithholdingTaxHardRule::MONTHLY;
    }
}
