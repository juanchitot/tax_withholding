<?php

/* @var FactoryMuffin $fm */

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\CreateRequest;
use League\FactoryMuffin\FactoryMuffin;

$faker = Faker\Factory::create();
$fm->define(CreateRequest::class)->setDefinitions([
'fiscalId' => $faker->numberBetween(10000, 99999),
'subsidiary' => 'entity|'.Subsidiary::class,
'period' => Carbon::now()->startOfMonth()->startOfDay(),
]);
