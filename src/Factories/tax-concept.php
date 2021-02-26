<?php

/* @var FactoryMuffin $fm */

use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Enum\TaxConceptEnum;
use League\FactoryMuffin\FactoryMuffin;

$fm->define(TaxConcept::class)->setDefinitions([
    'id' => TaxConceptEnum::WITHHOLDING_ID,
    'concept' => 'Retención',
]);
