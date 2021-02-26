<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes;

use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

class Scene
{
    /**
     * @var Account
     */
    protected $account;

    /**
     * @var array
     */
    protected $transactions;
    /**
     * @var Deposit
     */
    private $deposit;
    /**
     * @var SaleBag
     */
    protected $saleBag;

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function getDeposit(): Deposit
    {
        return $this->deposit;
    }

    public function setDeposit(Deposit $deposit): void
    {
        $this->deposit = $deposit;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @param array
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function setSaleBag(SaleBag $saleBag)
    {
        $this->saleBag = $saleBag;
    }

    public function getSaleBag(): SaleBag
    {
        return $this->saleBag;
    }

    public function addTransaction(Transaction $transaction)
    {
        $this->transactions[] = $transaction;
    }

    public function getWithholdingTaxDetails()
    {
        $rtnValue = [];
        /* @var Transaction $trasaction */
        foreach ($this->transactions as $transaction) {
            foreach ($transaction->getWithholdingTaxDetails() as $withholdingTaxDetail) {
                $rtnValue[] = $withholdingTaxDetail;
            }
        }

        return $rtnValue;
    }
}
