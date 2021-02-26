<?php

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
$fm->define(WithholdingTaxHardRule::class)->setDefinitions([
    'rate' => 1.5,
    'created_at' => Carbon::now(),
    'verification_date' => 'today',
    'rule' => json_encode([
        [
            'type' => 'SELECT',
            'field' => 'count',
            'fieldFunction' => 'COUNT(1)',
            'condition' => '>=',
            'value' => '3',
        ],
        [
            'type' => 'SELECT',
            'field' => 'amount',
            'fieldFunction' => 'SUM(_t.amount)',
            'condition' => '>=',
            'value' => '3000',
        ],
        [
            'type' => 'WHERE',
            'field' => '_t.createdAt',
            'condition' => '>=',
        ],
    ]),
]);
