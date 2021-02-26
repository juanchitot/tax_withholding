<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\ApiBundle\Enum\TaxConditionAfipRelationEnum;

class TaxInformation
{
    const NI = 'NI';
    /* @var string */
    private $idFiscal;
    /* @var */
    private $taxCondition;
    /* @var string */
    private $incomeTax = self::NI;

    private $iva;

    /**
     * @return TaxInformation
     *
     * @throws \GeoPagos\ApiBundle\Exceptions\BasicEnumException
     */
    public static function createFromResponse(\stdClass $response)
    {
        $information = new self();
        $information->setIva($response->iva);
        $information->setIdFiscal($response->id);
        $information->setIncomeTax($response->incomeTax ?? self::NI);
        $information->setTaxCondition(self::resolveTaxConditionFromData($response));

        return $information;
    }

    /**
     * @throws \GeoPagos\ApiBundle\Exceptions\BasicEnumException
     */
    public static function resolveTaxConditionFromData(\stdClass $response): int
    {
        $taxCondition = TaxConditionAfipRelationEnum::NI;
        if (self::NI !== $response->monotributo) {
            $taxCondition = TaxConditionAfipRelationEnum::MT;
        } else {
            $taxCondition = TaxConditionAfipRelationEnum::getValue(strtoupper($response->iva));
        }

        return $taxCondition;
    }

    /**
     * Only For Testing usage.
     */
    public static function reverseTaxConditionToData(int $taxConditionId): array
    {
        $data = [];
        if (TaxConditionAfipRelationEnum::MT == $taxConditionId) {
            $data['monotributo'] = 'A';
        } elseif (TaxConditionAfipRelationEnum::NI == $taxConditionId) {
            $data['monotributo'] = 'NI';
            $data['monotributo'] = 'AC';
        } else {
            $data['iva'] = TaxConditionAfipRelationEnum::getConstFromValue($taxConditionId);
        }

        return $data;
    }

    public function getTaxCondition(): int
    {
        return $this->taxCondition;
    }

    public function getIncomeTax(): string
    {
        return $this->incomeTax;
    }

    public function setTaxCondition(int $taxCondition)
    {
        $this->taxCondition = $taxCondition;
    }

    public function setIncomeTax($incomeTax)
    {
        $this->incomeTax = $incomeTax;
    }

    public function getIdFiscal()
    {
        return $this->idFiscal;
    }

    public function setIdFiscal(string $idFiscal): void
    {
        $this->idFiscal = $idFiscal;
    }

    public function setIva($iva = null)
    {
        $this->iva = $iva;
    }

    public function getIva()
    {
        return $this->iva;
    }
}
