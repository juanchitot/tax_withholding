<?php

use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
$fm->define(ProvinceWithholdingTaxSetting::class)->setDefinitions([
    'withholding_tax_system' => 'ARBA',
    'type' => 0,
    'code' => 0,
    'agent_subsidiary' => 1,
    'lastCertificate' => 0,
    'period' => 'MONTHLY',
    'minAmount' => 0,
    'resolution' => 'RG (Prov. BA) RN 19/2019',
    'number' => '30-71065725-0',
]);
