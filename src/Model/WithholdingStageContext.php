<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class WithholdingStageContext
{
    /* @var float */
    protected $applicableRate = 0;

    /* @var float */
    protected $taxableIncomeCoefficient = 1;

    /** @var TaxCategory */
    protected $taxCategory;

    /** @var TaxCategory */
    protected $loggedTaxCategory;

    /** @var WithholdingTaxTypeEnum */
    protected $taxType;

    /** @var Transaction */
    protected $transaction;

    /** @var Account */
    protected $account;

    /** @var Subsidiary */
    protected $subsidiary;

    /** @var object */
    protected $transactionTaxingPointOfView;

    /** @var array */
    private $matchedWithholdingRule = [];

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->subsidiary = $transaction->getSubsidiary();
        $this->account = $this->subsidiary->getAccount();
        $this->setTaxCategory($this->subsidiary->getTaxCategory());
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }

    public function setTaxType(string $taxType): WithholdingStageContext
    {
        $this->taxType = $taxType;

        return $this;
    }

    public function getApplicableRate(): float
    {
        return $this->applicableRate;
    }

    public function setApplicableRate(float $applicableRate): WithholdingStageContext
    {
        $this->applicableRate = $applicableRate;

        return $this;
    }

    public function getTaxableIncomeCoefficient(): float
    {
        return $this->taxableIncomeCoefficient;
    }

    public function setTaxableIncomeCoefficient(float $taxableIncomeCoefficient): WithholdingStageContext
    {
        $this->taxableIncomeCoefficient = $taxableIncomeCoefficient;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTaxCategory(?TaxCategory $taxCategory)
    {
        $this->taxCategory = $taxCategory;

        return $this;
    }

    /**
     * @return Subsidiary
     */
    public function getSubsidiary()
    {
        return $this->subsidiary;
    }

    public function setTransactionTaxingPointOfView(object $transactionTaxingPointOfView)
    {
        $this->transactionTaxingPointOfView = $transactionTaxingPointOfView;

        return $this;
    }

    public function getTransactionTaxingPointOfView(): object
    {
        return $this->transactionTaxingPointOfView;
    }

    public function pushWithholdingMatchedRule(WithholdingTaxRuleInterface $rule)
    {
        $this->setApplicableRate($rule->getRate());
        array_push($this->matchedWithholdingRule, $rule);
    }

    public function popWithholdingMatchedRule(): ?WithholdingTaxRuleInterface
    {
        return array_pop($this->matchedWithholdingRule);
    }

    public function lastWithholdingMatchedRule(): ?WithholdingTaxRuleInterface
    {
        $rulesLength = count($this->matchedWithholdingRule);

        return ($rulesLength > 0) ? $this->matchedWithholdingRule[$rulesLength - 1] : null;
    }

    public function firstWithholdingMatchedRule(): ?WithholdingTaxRuleInterface
    {
        return $this->matchedWithholdingRule[0] ?? null;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getLoggedTaxCategory(): ?TaxCategory
    {
        return $this->loggedTaxCategory;
    }

    public function setLoggedTaxCategory(?TaxCategory $loggedTaxCategory): WithholdingStageContext
    {
        $this->loggedTaxCategory = $loggedTaxCategory;

        return $this;
    }

    public function __toString()
    {
        return sprintf(' CX{ TX: %s, SUB: %s }', $this->getTransaction()->getId(), $this->getSubsidiary()->getId());
    }
}
