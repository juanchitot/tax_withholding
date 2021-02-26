<?php

namespace GeoPagos\WithholdingTaxBundle\Contract;

use GeoPagos\WithholdingTaxBundle\Model\Dto\TaxAmountDto;

interface WithholdingTaxDetailInterface
{
    /**
     * @param int[] $saleIds
     *
     * @return TaxAmountDto[]
     */
    public function getFederalTaxesBySaleIds(array $saleIds): array;

    /**
     * @param int[] $saleIds
     *
     * @return TaxAmountDto[]
     */
    public function getProvincialTaxesBySaleIds(array $saleIds): array;
}
