<?php

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */

$fm->define(WithholdingTaxLog::class)->setDefinitions([
    'created_at' => Carbon::now(),
    'rule_applied' => 'IIBB Cordoba',
    'transaction' => 'entity|'.Transaction::class,
    'tax_category' => 'entity|'.TaxCategory::class,
    'tax_condition' => 'entity|'.TaxCondition::class,
    'withholding_tax_detail' => 'entity|'.WithholdingTaxDetail::class,
    'province' => 'entity|'.Province::class,
])->setMaker(static function ($class, $attributes) use ($fm) {
    $transaction = $attributes['transaction'] ?? $fm->instance(Transaction::class);
    $detail = $attributes['withholding_tax_detail'] ?? $fm->instance(WithholdingTaxDetail::class);
    $province = $attributes['province'] ?? $fm->instance(Province::class);

    return new WithholdingTaxLog($transaction, $detail, $province);
});
