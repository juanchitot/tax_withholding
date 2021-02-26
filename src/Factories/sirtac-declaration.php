<?php

/* @var FactoryMuffin $fm */

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;

$fm->define(SirtacDeclaration::class)->setDefinitions([
    'subsidiary' => 'entity|'.Subsidiary::class,
    'certificate_number' => 1,
    'date' => Carbon::now(),
    'taxable_income' => 1000,
    'rate' => 1.5,
    'amount' => 105,
    'province' => 'entity|'.Province::class,
    'taxCategory' => 'entity|'.TaxCategory::class,
    'taxConcept' => 'entity|'.TaxConcept::class,
    'file' => null,
    'salesCount' => rand(1, 5),
    'control_number' => rand(1, 100),
    'settlement_date' => Carbon::now(),
    'settlement_number' => rand(1, 100),
    'withholding_date' => Carbon::now(),
    'min_amount' => 0,
    'payment_type' => 'ALL',
    'provinceJurisdiction' => rand(1, 10),
    'status' => WithholdingTaxStatus::CREATED,
]);
