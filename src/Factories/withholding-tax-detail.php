<?php

use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
$fm->define(WithholdingTaxDetail::class)->setDefinitions([
    'transaction' => 'entity|'.Transaction::class,
    'withholding_tax' => 'entity|'.WithholdingTax::class,
    'type' => WithholdingTaxTypeEnum::TAX,
    'taxable_income' => 1000,
    'amount' => 105,
    'concept' => 'entity|'.TaxConcept::class,
]);
