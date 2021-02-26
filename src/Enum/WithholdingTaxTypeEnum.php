<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class WithholdingTaxTypeEnum extends BasicEnum
{
    const __default = self::TAX;

    const TAX = 'TAX'; //IIBB
    const VAT = 'VAT'; //IVA
    const INCOME_TAX = 'INCOME_TAX'; //GANANCIAS
    const ITBIS = 'ITBIS'; // Impuesto sobre Transferencias de Bienes Industrializados y Servicios - Republica Dominicana
    const SIRTAC = 'SIRTAC'; // IIBB SIRTAC

    const TAX_SIRTAC_TYPE = 8;
    const TAX_TYPE = 4;
    const VAT_TYPE = 2;
    const INCOME_TYPE = 1;

    public static function getString($type)
    {
        switch ($type) {
            case self::TAX:
                return 'IIBB';
            case self::VAT:
                return 'IVA';
            case self::INCOME_TAX:
                return 'GANANCIAS';
            case self::ITBIS:
                return 'ITBIS';
            case self::SIRTAC:
                return 'IIBB SIRTAC';
        }
    }

    public static function getBitFieldValue($type)
    {
        switch ($type) {
            case self::TAX:
                return self::TAX_TYPE;
            case self::VAT:
                return self::VAT_TYPE;
            case self::INCOME_TAX:
                return self::INCOME_TYPE;
            case self::ITBIS:
                return self::ITBIS;
            case self::SIRTAC:
                return self::TAX_SIRTAC_TYPE;
        }
    }

    public static function getProvincialTaxTypes(): array
    {
        return [self::TAX];
    }

    public static function getFederalTaxTypes(): array
    {
        return [self::INCOME_TAX, self::VAT];
    }

    public static function getAvailableTaxes(): array
    {
        return [
            self::TAX,
            self::VAT,
            self::INCOME_TAX,
            self::ITBIS,
            self::SIRTAC,
        ];
    }
}
