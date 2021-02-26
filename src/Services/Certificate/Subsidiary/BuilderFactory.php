<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Ganancias;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Iibb;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Iva;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Formatter\BaseEmailPdfFormatter;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Formatter\FederalPdfFormatter;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Grouper\FederalGrouper;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Grouper\IibbSirtacGrouper;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Grouper\ProvinceGrouper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class BuilderFactory implements ServiceSubscriberInterface
{
    private $locator;
    /**
     * @var array
     */
    private $createdServices;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
        $this->createdServices = [];
    }

    public static function getSubscribedServices(): array
    {
        return [
            Builder::class => Builder::class,
            FederalGrouper::class => FederalGrouper::class,
            ProvinceGrouper::class => ProvinceGrouper::class,
            IibbSirtacGrouper::class => IibbSirtacGrouper::class,
            /*Formatters*/
            BaseEmailPdfFormatter::class => BaseEmailPdfFormatter::class,
            FederalPdfFormatter::class => FederalPdfFormatter::class,
        ];
    }

    public function create(WithholdableTaxInterface $tax): SubsidiaryCertificateBuilderInterface
    {
        /* @var $service SubsidiaryCertificateBuilderInterface */
        switch (get_class($tax)) {
            case Iva::class:
            case Ganancias::class:
                $service = $this->locator->get(Builder::class);
                $service->setGroupers([$this->locator->get(FederalGrouper::class)]);
                $service->setFormatters([$this->locator->get(FederalPdfFormatter::class)]);

                break;
            case Iibb::class:
                $service = $this->locator->get(Builder::class);
                $service->setGroupers([
                    $this->locator->get(ProvinceGrouper::class),
                    $this->locator->get(FederalGrouper::class),
                ]);
                $service->setFormatters([$this->locator->get(BaseEmailPdfFormatter::class)]);

                break;
            default:
                throw new \Exception(sprintf('Builder for tax with class %s not found', get_class($tax)));
        }
        $service->setFormatter($this->locator->get(BaseEmailPdfFormatter::class));
        $service->setTax($tax);

        return $service;
    }
}
