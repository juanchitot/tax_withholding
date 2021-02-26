<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Emails\Builder;

use GeoPagos\ApiBundle\Services\Emails\Builder\AbstractEmailBuilder;

abstract class WithholdingTaxAbstractEmailBuilder extends AbstractEmailBuilder
{
    public function getHtml()
    {
        return $this->templating->render(
            '@GeoPagosWithholdingTax/Emails/'.$this->getTemplateName().'.html.twig',
            $this->getParametersToReplace()
        );
    }
}
