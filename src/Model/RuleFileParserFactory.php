<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;

class RuleFileParserFactory
{
    private const BUENOS_AIRES_CAPITAL_ACRONYM = 'caba';
    private const BUENOS_AIRES_PROVINCE_ACRONYM = 'pba';
    private const CORDOBA_ACRONYM = 'cba';
    private const SANTA_CRUZ_ACRONYM = 'scruz';
    private static $entityManager;

    public static function setEntityManager($entityManager)
    {
        self::$entityManager = $entityManager;
    }

    public static function getParser(WithholdingTaxRuleFile $ruleFile): RuleFileParserInterface
    {
        switch (true) {
            case WithholdingTaxRuleFile::SIRTAC_TYPE === $ruleFile->getFileType():
                return new Sirtac($ruleFile, self::$entityManager);

            case WithholdingTaxRuleFile::GROSS_INCOME_TYPE === $ruleFile->getFileType()
                && self::BUENOS_AIRES_CAPITAL_ACRONYM === $ruleFile->getProvince()->getAcronym():
                return new CabaParser($ruleFile);

            case WithholdingTaxRuleFile::GROSS_INCOME_TYPE === $ruleFile->getFileType()
                && self::BUENOS_AIRES_PROVINCE_ACRONYM === $ruleFile->getProvince()->getAcronym():
                return new PbaParser($ruleFile, self::$entityManager);

            case WithholdingTaxRuleFile::MICRO_ENTERPRISE === $ruleFile->getFileType():
                return new MicroEnterpriseParser();

            case WithholdingTaxRuleFile::GROSS_INCOME_TYPE === $ruleFile->getFileType()
                && self::CORDOBA_ACRONYM === $ruleFile->getProvince()->getAcronym():
                return new CordobaParser($ruleFile);

            case WithholdingTaxRuleFile::GROSS_INCOME_TYPE === $ruleFile->getFileType()
                && self::SANTA_CRUZ_ACRONYM === $ruleFile->getProvince()->getAcronym():
                return new SantaCruzParser($ruleFile);

            default:
                throw new \Exception('Invalid rule file type');
        }
    }
}
