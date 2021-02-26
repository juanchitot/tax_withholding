<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

abstract class FortnightSystem extends BaseSystem
{
    public function getFileName($reportName, $format = null): string
    {
        $q = $this->reportFortnightIdentifier ? '-q'.$this->reportFortnightIdentifier : '';
        $fileName = $this->reportMonthIdentifier.$q.'-'.$this->getBaseFilename($reportName);

        return $fileName.$this->getExtension($format);
    }
}
