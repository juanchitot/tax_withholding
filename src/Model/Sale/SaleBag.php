<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Sale;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Exceptions\EmptyTransactionException;
use Money\Currency;
use Money\Money;

class SaleBag
{
    /** @var Money */
    private $netAmount;

    /** @var ArrayCollection */
    private $transaction;

    /** @var Carbon */
    private $availableDate;

    /** @var float */
    private $grossAmount;

    /** @var Currency */
    private $currency;

    /** @var array */
    private $withheldTaxes;

    /** @var string */
    private $idFiscal;

    public function __construct(array $transactions, Currency $currency, Carbon $availableDate)
    {
        if (empty($transactions)) {
            throw new EmptyTransactionException();
        }
        $this->transaction = new ArrayCollection($transactions);
        $this->availableDate = $availableDate;

        $this->grossAmount = 0;
        foreach ($transactions as $transaction) {
            $this->grossAmount += $transaction->getAmount();
        }

        $this->currency = $currency;

        $this->withheldTaxes = [];
        foreach (WithholdingTaxTypeEnum::getAvailableTaxes() as $taxType) {
            $this->withheldTaxes[$taxType] = [
                'taxableIncome' => 0,
                'amount' => 0,
            ];
        }
    }

    public function getNetAmount(): float
    {
        return $this->netAmount->getAmount() / 100;
    }

    public function setNetAmount($newDepositAmount): void
    {
        $this->netAmount = new Money((string) (round($newDepositAmount, 2) * 100), $this->currency);
    }

    public function getTransactions(): ArrayCollection
    {
        return $this->transaction;
    }

    public function getAvailableDate(): Carbon
    {
        return Carbon::instance($this->availableDate);
    }

    public function getGrossAmount(): float
    {
        return $this->grossAmount;
    }

    public function getWithheldTaxes(): array
    {
        return $this->withheldTaxes;
    }

    public function addWithheldTax(string $type, float $taxableIncome, float $amount): void
    {
        $this->withheldTaxes[$type]['taxableIncome'] += $taxableIncome;
        $this->withheldTaxes[$type]['amount'] += $amount;
    }

    public function getIdFiscal()
    {
        if (null == $this->idFiscal) {
            $this->idFiscal = $this->getTransactions()->first()->getSubsidiary()->getAccount()->getIdFiscal();
        }

        return $this->idFiscal;
    }
}
