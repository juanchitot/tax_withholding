<?php

namespace GeoPagos\WithholdingTaxBundle\Events;

use GeoPagos\WithholdingTaxBundle\Model\TaxInformation;
use Symfony\Contracts\EventDispatcher\Event;

class TaxInformationRequested extends Event
{
    public const NAME = 'identity_checker.tax_information_requested';

    /**
     * @var TaxInformation
     */
    private $taxInformation;

    public function __construct(TaxInformation $taxInformation)
    {
        $this->taxInformation = $taxInformation;
    }

    public function getTaxInformation(): TaxInformation
    {
        return $this->taxInformation;
    }
}
