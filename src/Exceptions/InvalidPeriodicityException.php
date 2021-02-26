<?php

namespace GeoPagos\WithholdingTaxBundle\Exceptions;

class InvalidPeriodicityException extends \Exception
{
    private const DEFAULT_MESSAGE = 'Incorrect periodicity for withholding tax setting.';

    public function __construct(?string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }
}
