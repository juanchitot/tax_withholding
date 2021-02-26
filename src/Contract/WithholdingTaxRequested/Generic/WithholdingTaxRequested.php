<?php

namespace GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic;

use Carbon\Carbon;
use Money\Currency;

abstract class WithholdingTaxRequested
{
    /** @var array */
    private $transactions;

    /** @var Carbon */
    private $availableDate;

    /** @var Currency */
    private $currency;

    /** @var WithholdingTaxRequestedResponse */
    private $response;

    public function __construct(array $transactions, Currency $currency, Carbon $availableDate)
    {
        $this->transactions = $transactions;
        $this->availableDate = $availableDate;
        $this->currency = $currency;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function getAvailableDate(): Carbon
    {
        return $this->availableDate;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getResponse(): ?WithholdingTaxRequestedResponse
    {
        return $this->response;
    }

    public function setResponse(WithholdingTaxRequestedResponse $response): WithholdingTaxRequested
    {
        $this->response = $response;

        return $this;
    }
}
