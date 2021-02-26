<?php

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use League\FactoryMuffin\FactoryMuffin;

/* @var FactoryMuffin $fm */
$fm->define(WithholdingTaxRuleFile::class)->setDefinitions([
    'province' => null,
    'fileType' => WithholdingTaxRuleFile::MICRO_ENTERPRISE,
    'dbFile' => 'registry.txt',
    'status' => WithholdingTaxRuleFileStatus::PENDING,
    'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
    'createdAt' => Carbon::now(),
])->setCallback(function (WithholdingTaxRuleFile $entry) {
    $entry->generateDbFile(
        pathinfo($entry->getDbFile(), PATHINFO_EXTENSION)
    );
});
