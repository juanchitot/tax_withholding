<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Ganancias;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Iibb;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Itbis;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Iva;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class WithholdingPipeFactory implements ServiceSubscriberInterface
{
    private $serviceLocator;

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function __invoke(): WithholdingProcessPipe
    {
        $pipe = new WithholdingProcessPipe();
        /* @var $taxClass WithholdableTaxInterface */
        foreach (self::registeredWithholdableTaxes() as $taxClass) {
            $taxClass::registerWithholdingStages($this->serviceLocator, $pipe);
        }

        return $pipe;
    }

    protected static function registeredWithholdableTaxes(): array
    {
        return [
            Iibb::class,
            Iva::class,
            Itbis::class,
            Ganancias::class,
        ];
    }

    public static function getSubscribedServices()
    {
        $requiredClasses = array_merge(...array_values(
                array_map(
                    function ($className) {
                        return $className::availableWithholdingStages();
                    }, self::registeredWithholdableTaxes())
            )
        );
        $requiredClasses[] = ConfigurationManagerInterface::class;

        return $requiredClasses;
    }
}
