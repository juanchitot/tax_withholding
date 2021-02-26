<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use GeoPagos\FileManagementBundle\Contract\FileGenerationInterface;

interface WithholdingSystemInterface extends FileGenerationInterface
{
    public function getReportNames(): array;

    public function getFormats(): array;

    public function getDateFrom(): Carbon;

    public function getDateTo(): Carbon;

    public function getFileName($reportName, $format = null);
}
