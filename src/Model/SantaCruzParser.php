<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class SantaCruzParser implements RuleFileParserInterface
{
    const CELL_TAX_ID_START = 0;
    const CELL_TAX_ID_LENGTH = 11;
    const CELL_BUSINESS_NAME_START = 0;
    const CELL_BUSINESS_NAME_LENGTH = 11;
    const CELL_YEAR_START = -7;
    const CELL_YEAR_LENGTH = 4;
    const CELL_MONTH_START = -3;
    const CELL_MONTH_LENGTH = 2;
    const CELL_LETTER_START = -1;
    const CELL_LETTER_LENGTH = 1;
    public static $RATE_LETTER_MAPPING = [
        'A' => 0,
        'B' => 0.2,
        'C' => 0.5,
        'D' => 0.75,
        'E' => 1,
        'F' => 1.25,
        'G' => 1.50,
        'H' => 2.0,
        'I' => 2.5,
        'J' => 3,
    ];

    /**
     * @var WithholdingTaxRuleFile
     */
    private $ruleFile;

    public function __construct(WithholdingTaxRuleFile $ruleFile)
    {
        $this->ruleFile = $ruleFile;
    }

    /**
     * @example '20180637681ALVAREZ  EDUARDO DANIEL                                                  202012G'
     */
    public function parse(string $line): RuleLineDtoInterface
    {
        $line = $this->removeEndLineChart($line);
        $dto = new RuleLineDto();
        $dto->taxPayer = substr($line, self::CELL_TAX_ID_START, self::CELL_TAX_ID_LENGTH);
        //substr($line, self::CELL_BUSINESS_NAME_START, self::CELL_BUSINESS_NAME_LENGTH);
        $dto->provinceIdentifier = $this->getProvinceIdentifier();
        $dto->rate = (float) $this->resolveRateFromLetter(substr($line, self::CELL_LETTER_START,
            self::CELL_LETTER_LENGTH));
        $dto->monthYear = sprintf('%s-%s',
            substr($line, self::CELL_MONTH_START, self::CELL_MONTH_LENGTH),
            substr($line, self::CELL_YEAR_START, self::CELL_YEAR_LENGTH)
        );
        $dto->withholdingTaxType = $this->getTaxTypeForDynamicRule();

        return $dto;
    }

    public function skipFirstLine(): bool
    {
        return false;
    }

    public function getTaxTypeForDynamicRule(): int
    {
        return WithholdingTaxTypeEnum::TAX_TYPE;
    }

    private function getProvinceIdentifier()
    {
        return $this->ruleFile->getProvince()->getId();
    }

    protected function resolveRateFromLetter(string $letter)
    {
        return self::$RATE_LETTER_MAPPING[$letter];
    }

    private function removeEndLineChart(string $line)
    {
        return str_replace(array("\r", "\n"), '', $line);
    }
}
