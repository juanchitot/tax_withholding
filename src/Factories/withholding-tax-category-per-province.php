<?php

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxCategoryPerProvince;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
$fm->define(WithholdingTaxCategoryPerProvince::class)->setDefinitions([
    'subsidiary' => 'entity|'.Subsidiary::class,
    'taxCategory' => 'entity|'.TaxCategory::class,
    'withholdingTaxNumber' => 'numberBetween|1;200',
    'province' => 'entity|'.Province::class,
]);
