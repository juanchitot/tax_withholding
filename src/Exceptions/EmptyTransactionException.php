<?php

namespace GeoPagos\WithholdingTaxBundle\Exceptions;

class EmptyTransactionException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Income must have a collection of transaction');
    }
}
