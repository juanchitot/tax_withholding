<?php

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use League\FactoryMuffin\FactoryMuffin;

$faker = Faker\Factory::create();

/* @var FactoryMuffin $fm */
$fm->define(WithholdingTax::class)->setDefinitions([
    'subsidiary' => 'entity|'.Subsidiary::class,
    'type' => WithholdingTaxTypeEnum::TAX,
    'certificate_number' => $faker->randomNumber(3),
    'date' => Carbon::now()->startOfDay()->startOfMonth(),
    'taxable_income' => $faker->randomFloat(4, 500, 3000),
    'rate' => $faker->randomFloat(2, 1, 30),
    'amount' => $faker->randomFloat(2, 100, 3000),
    'file' => null,
    'min_amount' => 0,
    'payment_type' => 'ALL',
    'status' => WithholdingTaxStatus::CREATED,
]);
