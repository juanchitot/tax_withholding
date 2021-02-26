<?php

namespace GeoPagos\WithholdingTaxBundle\Helper;

use GeoPagos\WithholdingTaxBundle\Exceptions\InvalidSirtacJurisdictionMappingException;

class SirtacJurisdictionsHelper
{
    /** @var array [provinceId => jurisdiction] */
    const PROVINCE_JURISDICTIONS = [
        2 => 901,
        6 => 902,
        10 => 903,
        14 => 904,
        18 => 905,
        22 => 906,
        26 => 907,
        30 => 908,
        34 => 909,
        38 => 910,
        42 => 911,
        46 => 912,
        50 => 913,
        54 => 914,
        58 => 915,
        62 => 916,
        66 => 917,
        70 => 918,
        74 => 919,
        78 => 920,
        82 => 921,
        86 => 922,
        94 => 923,
        90 => 924,
    ];

    /** @var array [provinceId => position] */
    const PROVINCE_MAPPING = [
        2 => 0, 6 => 1, 10 => 2, 14 => 3, 18 => 4, 22 => 5, 26 => 6, 30 => 7, 34 => 8,
        38 => 9, 42 => 10, 46 => 11, 50 => 12, 54 => 13, 58 => 14, 62 => 15, 66 => 16,
        70 => 17, 74 => 18, 78 => 19, 82 => 20, 86 => 21, 94 => 22, 90 => 23,
    ];

    const INSCRIPTION_VALUES = [1, 3, 4, 5];

    public static function isRegisteredInJurisdiction($statusJurisdiction, $saleProvinceId): bool
    {
        $statusJurisdictionLength = strlen($statusJurisdiction);
        $provinceMappingLength = count(self::PROVINCE_MAPPING);

        if ($statusJurisdictionLength !== $provinceMappingLength) {
            throw new InvalidSirtacJurisdictionMappingException(
                'Given Status Jurisdiction is not equals than mapping length. Did you forget to update mapping?'
            );
        }

        $valueForSaleProvince = $statusJurisdiction[self::PROVINCE_MAPPING[$saleProvinceId]];

        return in_array($valueForSaleProvince, self::INSCRIPTION_VALUES);
    }

    public static function getJurisdictionId($saleProvinceId): int
    {
        if (!isset(self::PROVINCE_JURISDICTIONS[$saleProvinceId])) {
            throw new InvalidSirtacJurisdictionMappingException(
                "Given provinceId ($saleProvinceId) is not mapped. Did you forget to update it?"
            );
        }

        return self::PROVINCE_JURISDICTIONS[$saleProvinceId];
    }
}
