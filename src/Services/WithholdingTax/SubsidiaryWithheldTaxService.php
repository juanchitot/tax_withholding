<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\SubsidiaryWithheldTaxes;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Repository\SubsidiaryWithheldTaxesRepository;

class SubsidiaryWithheldTaxService
{
    /**
     * @var SubsidiaryWithheldTaxesRepository
     */
    private $subsidiaryWitheldTaxesRepository;

    public function __construct(SubsidiaryWithheldTaxesRepository $subsidiaryWitheldTaxesRepository)
    {
        $this->subsidiaryWitheldTaxesRepository = $subsidiaryWitheldTaxesRepository;
    }

    public function getSubsidiaryWithheldTaxes(Subsidiary $subsidiary): ?SubsidiaryWithheldTaxes
    {
        return $this->subsidiaryWitheldTaxesRepository->findOneBy(['subsidiary' => $subsidiary]);
    }

    public function checkIfTaxWithheld(string $tax, Subsidiary $subsidiary): bool
    {
        /* @var $subsidiaryWitheldTaxes SubsidiaryWithheldTaxes */
        $subsidiaryWitheldTaxes = $this->getSubsidiaryWithheldTaxes($subsidiary);

        if (!$subsidiaryWitheldTaxes) {
            return false;
        }

        // TODO: use withheld date instead of boolean to check if taxes were withheld for CURRENT fiscal period
        switch ($tax) {
            case WithholdingTaxTypeEnum::VAT:
                return (bool) $subsidiaryWitheldTaxes->getVatLastWithheldd();
            case WithholdingTaxTypeEnum::INCOME_TAX:
                return (bool) $subsidiaryWitheldTaxes->getEarningsTaxLastWithheld();
            case WithholdingTaxTypeEnum::TAX:
                return (bool) $subsidiaryWitheldTaxes->getGrossIncomeTaxLastWithheld();
            case WithholdingTaxTypeEnum::SIRTAC:
                return (bool) $subsidiaryWitheldTaxes->getSirtacTaxLastWithheld();
        }

        return false;
    }

    public function updateTaxWithheld(string $tax, Subsidiary $subsidiary): SubsidiaryWithheldTaxes
    {
        WithholdingTaxTypeEnum::isValidValueOrThrowException($tax);

        /* @var $subsidiaryWithheldTaxes SubsidiaryWithheldTaxes */
        $subsidiaryWithheldTaxes = $this->getSubsidiaryWithheldTaxes($subsidiary);

        if (!$subsidiaryWithheldTaxes) {
            $subsidiaryWithheldTaxes = new SubsidiaryWithheldTaxes($subsidiary);
        }

        switch ($tax) {
            case WithholdingTaxTypeEnum::VAT:
                $subsidiaryWithheldTaxes->setVatLastWithheldd(Carbon::now());

                break;
            case WithholdingTaxTypeEnum::INCOME_TAX:
                $subsidiaryWithheldTaxes->setEarningsTaxLastWithheld(Carbon::now());

                break;
            case WithholdingTaxTypeEnum::TAX:
                $subsidiaryWithheldTaxes->setGrossIncomeTaxLastWithheld(Carbon::now());

                break;
            case WithholdingTaxTypeEnum::SIRTAC:
                $subsidiaryWithheldTaxes->setSirtacTaxLastWithheld(Carbon::now());

                break;
        }

        return $subsidiaryWithheldTaxes;
    }
}
