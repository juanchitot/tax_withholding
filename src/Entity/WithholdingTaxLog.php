<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Entity\Transaction;

class WithholdingTaxLog
{
    const MONTHLY = 'Habitualidad';
    const USE_DYNAMIC_RULE = 'usa padron';
    const UNPUBLISHED_DYNAMIC_RULE = 'usa padron pero no esta cargado';

    private $id;
    /**
     * @var Transaction
     */
    private $transaction;

    /** @var Province */
    private $province;

    /** @var TaxCondition */
    private $taxCondition;

    /** @var TaxCategory */
    private $taxCategory;

    /** @var Carbon */
    private $createdAt;

    /**
     * @var WithholdingTaxDetail
     */
    private $taxDetail;

    /** @var string */
    private $ruleApplied;

    public function __construct(
        Transaction $transaction,
        WithholdingTaxDetail $taxDetail,
        Province $province
    ) {
        $this->transaction = $transaction;
        $this->province = $province;
        $this->taxDetail = $taxDetail;
        $this->createdAt = Carbon::now();
        $this->province = $province;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getRuleApplied(): string
    {
        return $this->ruleApplied;
    }

    public function getTaxCondition(): TaxCondition
    {
        return $this->taxCondition;
    }

    public function getTaxCategory(): TaxCategory
    {
        return $this->taxCategory;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->ruleApplied;
    }

    public function getProvince(): Province
    {
        return $this->province;
    }

    public function setProvince(Province $province): WithholdingTaxLog
    {
        $this->province = $province;

        return $this;
    }

    public function setRuleApplied(string $ruleApplied): WithholdingTaxLog
    {
        $this->ruleApplied = $ruleApplied;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTaxCategory(TaxCategory $taxCategory): self
    {
        $this->taxCategory = $taxCategory;

        return $this;
    }

    public function setTaxCondition(TaxCondition $taxCondition): self
    {
        $this->taxCondition = $taxCondition;

        return $this;
    }

    public function getTaxDetail(): WithholdingTaxDetail
    {
        return $this->taxDetail;
    }
}
