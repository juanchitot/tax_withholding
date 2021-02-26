<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystemInterface;
use ReflectionClass;

abstract class BaseSystem implements WithholdingSystemInterface
{
    /** @var Carbon */
    protected $executionDate;

    /** @var string */
    protected $reportMonthIdentifier;

    /** @var int */
    protected $reportFortnightIdentifier;

    /** @var Carbon */
    protected $dateFrom;

    /** @var Carbon */
    protected $dateTo;

    /** @var ProvinceWithholdingTaxSetting */
    protected $provinceWithholdingTaxSettings;

    public function __construct(ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting, Carbon $executionDate)
    {
        $this->provinceWithholdingTaxSettings = $provinceWithholdingTaxSetting;
        $this->executionDate = $executionDate;

        $this->dateFrom = $this->provinceWithholdingTaxSettings->calculateLastPeriodStartDate($this->executionDate);
        $this->dateTo = $this->provinceWithholdingTaxSettings->calculateLastPeriodEndDate($this->executionDate);

        $this->reportMonthIdentifier = $this->dateFrom->format('mY');
        if (Period::SEMI_MONTHLY === $this->provinceWithholdingTaxSettings->getPeriod()) {
            $this->reportFortnightIdentifier = $this->dateFrom->day > 15 ? 2 : 1;
        }
    }

    public function getFormats(): array
    {
        return ['xls', 'txt'];
    }

    public function getDateFrom(): Carbon
    {
        return $this->dateFrom;
    }

    public function getDateTo(): Carbon
    {
        return $this->dateTo;
    }

    public function getFileName($reportName, $format = null): string
    {
        $fileName = $this->reportMonthIdentifier.'-'.$this->getBaseFilename($reportName).'-'.$this->province->getAcronym();

        if ($this->provinceWithholdingTaxSettings->isSemiMonthly()) {
            $fileName .= '-q'.$this->reportFortnightIdentifier;
        }

        return $fileName.$this->getExtension($format);
    }

    protected function getBaseFilename($reportName): string
    {
        return $reportName;
    }

    protected function getExtension($format): string
    {
        if (!$format) {
            return '';
        }

        return '.'.$format;
    }

    public function getFileType(): string
    {
        return strtolower((new ReflectionClass($this))->getShortName());
    }
}
