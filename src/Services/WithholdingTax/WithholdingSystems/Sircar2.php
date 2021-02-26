<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;

class Sircar2 extends BaseSystem
{
    private const DATA_REPORT = 'withholding_taxes_sircar2_data';
    private const SUBJECTS_REPORT = 'withholding_taxes_sircar2_subjects';

    public const DATA_REPORT_BASE_FILENAME = 'DATOS';
    public const SUBJECTS_REPORT_BASE_FILENAME = 'RETPER';

    /** @var Province */
    protected $province;

    public function __construct(ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting, Carbon $executionDate)
    {
        parent::__construct($provinceWithholdingTaxSetting, $executionDate);
        $this->province = $provinceWithholdingTaxSetting->getProvince();
    }

    public function getReportNames(): array
    {
        return [self::DATA_REPORT, self::SUBJECTS_REPORT];
    }

    protected function getBaseFilename($reportName): string
    {
        $baseFilename = '';

        switch ($reportName) {
            case self::SUBJECTS_REPORT:
                $baseFilename = self::SUBJECTS_REPORT_BASE_FILENAME;

                break;
            case self::DATA_REPORT:
                $baseFilename = self::DATA_REPORT_BASE_FILENAME;

                break;
        }

        return $baseFilename;
    }

    public function getFileName($reportName, $format = null): string
    {
        $fileName = $this->getBaseFilename($reportName).'-'.$this->reportMonthIdentifier;

        return $fileName.$this->getExtension($format);
    }
}
