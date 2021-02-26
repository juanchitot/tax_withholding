<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxDetailStatus;

class WithholdingTaxDetail
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var float
     */
    private $amount = 0;

    /**
     * @var float
     */
    private $taxableIncome;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var WithholdingTax
     */
    private $withholdingTax;

    /** @var \DateTime */
    private $withholdedAt;

    /** @var \DateTime */
    private $settlementDate;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $type;

    /**
     * @var float
     */
    private $rate;

    private $withholdingTaxLog;

    /**
     * @var TaxConcept
     */
    private $concept;

    public function __construct()
    {
        $this->withholdedAt = Carbon::now();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;

        if ($transaction->getAvailableDate()->gt($this->getWithholdedAt())) {
            $settlementDate = $transaction->getAvailableDate();
        } else {
            $settlementDate = $this->getWithholdedAt();
        }

        $this->setSettlementDate($settlementDate);

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function addAmount(float $amount): self
    {
        $this->amount += $amount;

        return $this;
    }

    public function getWithholdedAt(): Carbon
    {
        return Carbon::instance($this->withholdedAt);
    }

    public function setWithholdedAt(\DateTime $withholdedAt): self
    {
        $this->withholdedAt = new Carbon($withholdedAt);

        return $this;
    }

    public function getSettlementDate(): Carbon
    {
        return Carbon::instance($this->settlementDate);
    }

    public function setSettlementDate(\DateTime $settlementDate): self
    {
        $this->settlementDate = $settlementDate;

        return $this;
    }

    public function getWithholdingTax(): ?WithholdingTax
    {
        return $this->withholdingTax;
    }

    public function setWithholdingTax(?WithholdingTax $withholdingTax): self
    {
        $this->withholdingTax = $withholdingTax;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        WithholdingTaxDetailStatus::isValidValueOrThrowException($status);

        $this->status = $status;

        return $this;
    }

    public function getTaxableIncome(): ?float
    {
        return $this->taxableIncome;
    }

    public function setTaxableIncome(float $taxableIncome): self
    {
        $this->taxableIncome = $taxableIncome;

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

    public function getAppliedRate(): float
    {
        return round(abs($this->amount) / $this->taxableIncome * 100, 2);
    }

    public function originIsRefund(): bool
    {
        return $this->getTransaction() &&
            Transaction::TYPE_REFUND === $this->getTransaction()->getTypeId();
    }

    public function getSignedTaxableIncome(): float
    {
        return $this->originIsRefund() ? abs($this->getTaxableIncome()) * (-1) : abs($this->getTaxableIncome());
    }

    public function getSignedAmount(): float
    {
        return $this->originIsRefund() ? abs($this->getAmount()) * (-1) : abs($this->getAmount());
    }

    public function getPaymentType(): ?string
    {
        return $this->getTransaction()->getTransactionDetail()->getPaymentMethod()->getType()->getType() ?? null;
    }

    public function paymentTypeIs(string $paymentType): float
    {
        return $this->getPaymentType() === $paymentType;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }

    public function getWithholdingTaxLog(): ?WithholdingTaxLog
    {
        return $this->withholdingTaxLog;
    }

    public function setWithholdingTaxLog(WithholdingTaxLog $withholdingTaxLog): self
    {
        $this->withholdingTaxLog = $withholdingTaxLog;

        return $this;
    }

    public function getConcept(): TaxConcept
    {
        return $this->concept;
    }

    public function setConcept(TaxConcept $concept): WithholdingTaxDetail
    {
        $this->concept = $concept;

        return $this;
    }
}
