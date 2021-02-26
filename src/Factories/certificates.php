<?php

/* @var FactoryMuffin $fm */

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use League\FactoryMuffin\FactoryMuffin;

$fm->define(Certificate::class)->setDefinitions([
    'subsidiary' => 'entity|'.Subsidiary::class,
    'type' => WithholdingTaxTypeEnum::TAX,
    'period' => Carbon::now()->startOfMonth()->startOfDay(),
    'taxes' => null,
    'fileName' => 'unique:text',
    'province' => null,
    'status' => 'CREATED',
])->setMaker(static function ($class, $attributes) use ($fm) {
    $subsidiary = $attributes['subsidiary'] ?? $fm->instance(Subsidiary::class);
    $status = $attributes['status'] ?? Certificate::CREATED;
    $fileName = $attributes['filename'] ?? 'Factory Certificate filename '.Carbon::now()->toDateString().'.pdf';
    $period = $attributes['period'] ?? Carbon::now()->startOfMonth()->startOfDay();
    $province = $attributes['province'] ?? $fm->instance(Province::class);
    $type = $attributes['type'] ?? WithholdingTaxTypeEnum::TAX;
    $taxes = $attributes['taxes'] ?? [];

    return (new Certificate())
        ->setSubsidiary($subsidiary)
        ->setStatus($status)
        ->setFileName($fileName)
        ->setPeriod($period)
        ->setProvince($province)
        ->setType($type)
        ->setWithholdingTaxes($taxes);
});
