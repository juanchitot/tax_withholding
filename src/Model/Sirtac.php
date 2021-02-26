<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class Sirtac implements RuleFileParserInterface
{
    private const SIRTAC = 'SIRTAC';
    /**
     * @var WithholdingTaxRuleFile
     */
    private $ruleFile;

    /**
     * @var TaxRuleProvincesGroup
     */
    private $sirtac;

    public function __construct(WithholdingTaxRuleFile $ruleFile, EntityManagerInterface $entityManager)
    {
        $this->ruleFile = $ruleFile;
        $this->sirtac = $entityManager->getRepository(TaxRuleProvincesGroup::class)->findOneBy(['name' => self::SIRTAC]);
    }

    public function parse(string $line): RuleLineDtoInterface
    {
        $dto = new SirtacRuleLineDto();
        $row = str_getcsv($line, ';');

        $dto->taxPayer = $row[0];
        $dto->businessName = $row[1];
        $dto->jurisdiction = $row[2];
        $dto->period = substr($row[3], -2).'-'.substr($row[3], 0, 4);
        $dto->crc = $row[4];
        $dto->rateKey = $row[5];
        $dto->provinceGroup = $this->sirtac->getId();

        //the last digit has to me removed in order to match the jurisdiction
        $dto->statusJur = rtrim($row[6], '0');

        return $dto;
    }

    public function skipFirstLine(): bool
    {
        return false;
    }

    public function getTaxTypeForDynamicRule(): int
    {
        return WithholdingTaxTypeEnum::TAX_SIRTAC_TYPE;
    }
}
