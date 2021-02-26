<?php

namespace GeoPagos\WithholdingTaxBundle\DependencyInjection\Compiler;

use GeoPagos\WithholdingTaxBundle\EventSubscriber\DoWithholdingTaxSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddWithholdingTaxSubscriberPass implements CompilerPassInterface
{
    const WITHHOLDING_TAX_FFS = [
        'features.process_iibb',
        'features.process_vat',
        'features.process_income_tax',
        'features.process_itbis',
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $isWithholdingTaxEnabledInProject = false;
        foreach (self::WITHHOLDING_TAX_FFS as $featureFlag) {
            if ($container->hasParameter($featureFlag) && (bool) $container->getParameter($featureFlag)) {
                $isWithholdingTaxEnabledInProject = true;

                break;
            }
        }

        if ($isWithholdingTaxEnabledInProject) {
            $container
                ->register('withholding_tax_subscriber', DoWithholdingTaxSubscriber::class)
                ->setAutowired(true)
                ->setAutoconfigured(false)
                ->addTag('kernel.event_subscriber');
        }
    }
}
