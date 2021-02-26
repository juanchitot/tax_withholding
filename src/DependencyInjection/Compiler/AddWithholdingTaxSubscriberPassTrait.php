<?php

namespace GeoPagos\WithholdingTaxBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

trait AddWithholdingTaxSubscriberPassTrait
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddWithholdingTaxSubscriberPass());
    }
}
