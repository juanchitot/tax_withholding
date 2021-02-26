<?php

namespace GeoPagos\WithholdingTaxBundle\Exceptions;

use Exception;

class TaxGenerationStrategyNotFound extends Exception
{
    public function __construct(string $taxType)
    {
        parent::__construct(
            "No tax generation strategy was found for the tax '$taxType'"
        );
    }
}
