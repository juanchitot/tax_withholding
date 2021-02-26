<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class SirtacRuleLineDto implements RuleLineDtoInterface
{
    public $taxPayer;
    public $businessName;
    public $jurisdiction;
    public $period;
    public $crc;
    public $rateKey;
    public $provinceGroup;
    /**
     * Se lee de derecha '
     * a izquierda, y se debería descartar el primer carácter (que es el cero,.
     * e iría como valor siempre cero), para que del 1 al 24 coincida con la
     * identificación de las jurisdicciones.
     * .: Status_Jur(4) =”1”
     *  Adherida al SIRTAC dada de alta
     * .: Status_Jur(13) = “2”
     *  Adherida al SIRTAC no dada de alta
     * .: Status_Jur(16) = “3”
     *  Adherida al SIRTAC no dada de alta y con servicios presenciales
     * .: Status_Jur(1) =”4”
     *  No Adherida al SIRTAC dada de alta:
     * .: Status_Jur(24) = “5”
     *  No Adherida al SIRTAC no dada de alta:.
     */
    public $statusJur;

    private $rates = [
        'A' => 0.00,
        'B' => 0.01,
        'C' => 0.05,
        'D' => 0.10,
        'E' => 0.20,
        'F' => 0.30,
        'G' => 0.40,
        'H' => 0.50,
        'I' => 0.60,
        'J' => 0.70,
        'K' => 0.80,
        'L' => 0.90,
        'M' => 1.00,
        'N' => 1.10,
        'O' => 1.20,
        'P' => 1.30,
        'Q' => 1.40,
        'R' => 1.50,
        'S' => 1.70,
        'T' => 2.00,
        'U' => 2.50,
        'V' => 3.00,
        'W' => 3.50,
        'X' => 4.00,
        'Y' => 4.50,
        'Z' => 5.00,
    ];

    public function getMonthYear()
    {
        return $this->period;
    }

    public function getRate()
    {
        return $this->rates[$this->rateKey];
    }

    public function getTaxType()
    {
        //always the same type
        return WithholdingTaxTypeEnum::TAX_SIRTAC_TYPE;
    }

    public function getProvinceGroup()
    {
        return $this->provinceGroup;
    }

    public function getStatusJurisdiction()
    {
        return $this->statusJur;
    }

    public function getProvinceIdentifier()
    {
        return null;
    }

    public function getCrc()
    {
        return $this->crc;
    }
}
