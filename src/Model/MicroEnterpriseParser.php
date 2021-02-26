<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class MicroEnterpriseParser implements RuleFileParserInterface
{
    private $montOfYear = null;

    public function parse(string $line): RuleLineDtoInterface
    {
        $dto = new RuleLineDto();
        $dto->taxPayer = substr($line, 1, 11);
        $dto->provinceIdentifier = null;
        $dto->rate = 0;
        $dto->monthYear = $this->getMontOfYear($line);
        $dto->withholdingTaxType = $this->getTaxTypeForDynamicRule();

        return $dto;
    }

    private function getMontOfYear(string $line): string
    {
        if (null === $this->montOfYear) {
            $this->montOfYear = (false !== strpos($line, 'MEMPRE')) ? $this->getDateValues($line) : null;
        }

        return $this->montOfYear;
    }

    private function getDateValues(string $line): string
    {
        $monthYear = Carbon::createFromDate(substr($line, 7, 4), substr($line, 11, 2), 1);
        $monthYear->addMonth();

        return $monthYear->format('m-Y');
    }

    public function skipFirstLine(): bool
    {
        return true;
    }

    public function getTaxTypeForDynamicRule(): int
    {
        return WithholdingTaxTypeEnum::INCOME_TYPE + WithholdingTaxTypeEnum::VAT_TYPE;
    }
}
