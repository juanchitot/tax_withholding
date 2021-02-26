<?php

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
$fm->define(WithholdingTaxSimpleRule::class)->setDefinitions([
    'minimun_amount' => 250,
    'rate' => 3.5,
    'created_at' => Carbon::now(),
    'type' => WithholdingTaxTypeEnum::TAX,
]);
