<?php

namespace GeoPagos\WithholdingTaxBundle;

use GeoPagos\WithholdingTaxBundle\DependencyInjection\Compiler\AddWithholdingTaxSubscriberPassTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GeoPagosWithholdingTaxBundle extends Bundle
{
    use AddWithholdingTaxSubscriberPassTrait;
}