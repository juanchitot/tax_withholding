<?php

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleCalculationBasisEnum;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
    $fm->define(WithholdingTaxRule::class)->setDefinitions([
    'unpublish_rate' => 5,
    'minimum_amount' => 200,
    'calculation_basis' => WithholdingTaxRuleCalculationBasisEnum::NET,
    'download_date_db' => Carbon::now(),
    'withhold_occasional' => true,
    'has_tax_registry' => false,
    'period' => 'This Month',
    'rate' => 3.5,
    'is_enabled' => true,
]);
