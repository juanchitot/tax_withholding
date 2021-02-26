<?php

use Doctrine\Common\Collections\ArrayCollection;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroupItem;
use League\FactoryMuffin\FactoryMuffin;
use League\FactoryMuffin\Faker\Facade as Faker;

/* @var FactoryMuffin $fm */
$fm->define(TaxRuleProvincesGroupItem::class)->setDefinitions([
    'province' => 'entity|'.Province::class,
]);

$fm->define(TaxRuleProvincesGroup::class)->setDefinitions([
    'name' => Faker::regexify('[A-Z]{20}'),
    'groupItems' => function (TaxRuleProvincesGroup $object, $saved) use ($fm) {
        return new ArrayCollection([
            $fm->instance(TaxRuleProvincesGroupItem::class,
                [
                    'province' => $fm->create(Province::class),
                    'taxRuleProvincesGroup' => $object,
                ]),
        ]);
    },
]);
