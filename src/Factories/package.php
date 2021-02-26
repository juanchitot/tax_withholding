<?php

/* @var FactoryMuffin $fm */

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use League\FactoryMuffin\FactoryMuffin;

$faker = Faker\Factory::create();
$fm->define(Package::class)->setDefinitions([
    'fiscalId' => $faker->numberBetween(10000, 99999),
    'subsidiary' => 'entity|'.Subsidiary::class,
    'taxSettings' => Carbon::now(),
    'taxType' => $faker->randomElement(\GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum::getAvailableTaxes()),
    'period' => Carbon::now()->startOfMonth()->startOfDay(),
    'province' => 'entity|'.Province::class,
    'localFilename' => $faker->md5,
    'certificateEntity' => null,
]);
