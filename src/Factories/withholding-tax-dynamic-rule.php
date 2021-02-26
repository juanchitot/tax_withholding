<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
$fm->define(WithholdingTaxDynamicRule::class)->setMaker(static function ($class, $attributes, Faker $faker, FactoryMuffin $fm) {
    return new $class(
        $attributes['id_fiscal'] ?? '',
        $attributes['province'] ?? null,
        $attributes['month_year'] ?? Carbon::now()->format('m-Y'),
        $attributes['rate'] ?? 3.5,
        $attributes['type'] ?? WithholdingTaxTypeEnum::TAX_TYPE,
        $attributes['created_at'] ?? Carbon::now(),
        $attributes['provinces_group'] ?? null,
        $attributes['status_jurisdictions'] ?? null,
        $attributes['crc'] ?? 16
    );
});
