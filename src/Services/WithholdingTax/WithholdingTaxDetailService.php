<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxDetailInterface;
use GeoPagos\WithholdingTaxBundle\Model\Dto\TaxAmountDto;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDetailRepository;

class WithholdingTaxDetailService implements WithholdingTaxDetailInterface
{
    /** @var WithholdingTaxDetailRepository */
    private $detailRepository;

    public function __construct(WithholdingTaxDetailRepository $detailRepository)
    {
        $this->detailRepository = $detailRepository;
    }

    /** {@inheritdoc} */
    public function getFederalTaxesBySaleIds(array $saleIds): array
    {
        return $this->convertScalarArrayTaxAmountDtos(
            $this->detailRepository->getFederalAmountBySaleIds($saleIds)
        );
    }

    /** {@inheritdoc} */
    public function getProvincialTaxesBySaleIds(array $saleIds): array
    {
        return $this->convertScalarArrayTaxAmountDtos(
            $this->detailRepository->getProvincialAmountBySaleIds($saleIds)
        );
    }

    /**
     * @param mixed[] $scalarDetails
     *
     * @return TaxAmountDto[]
     */
    private function convertScalarArrayTaxAmountDtos(array $scalarDetails): array
    {
        if (empty($scalarDetails)) {
            return [];
        }

        $transferObjects = [];
        foreach ($scalarDetails as $scalarDetail) {
            $transferObjects[] = new TaxAmountDto(
                $scalarDetail['name'],
                $scalarDetail['amount']
            );
        }

        return $transferObjects;
    }
}
