<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Repository\SirtacDeclarationRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRepository;

class SubsidiaryDataAggregator
{
    /**
     * @var WithholdingTaxRepository
     */
    private $repository;

    /**
     * @var array|string[]
     */
    protected $availableTaxes;
    private $sirtacRepo;

    public function __construct(
        WithholdingTaxRepository $repository,
        SirtacDeclarationRepository $sirtacRepo
    ) {
        $this->repository = $repository;
        $this->sirtacRepo = $sirtacRepo;
    }

    /**
     * @TODO: $includeOldCerts should be deprecated.
     *
     * @param bool $includeOldCerts
     */
    public function collect(int $subsidiaryId, Carbon $period, $includeOldCerts = false)
    {
        $rows = $this->repository->findWithActiveSubsidiaryBy(
            $period->format('Ym'),
            $includeOldCerts,
            ['status' => WithholdingTaxStatus::CREATED, 'subsidiaryId' => $subsidiaryId]
        );
        $sirtacRows = $this->sirtacRepo->findWithActiveSubsidiaryBy(
            $period->format('Ym'),
            $includeOldCerts,
            ['status' => WithholdingTaxStatus::CREATED, 'subsidiaryId' => $subsidiaryId]
        );

        return array_merge($sirtacRows, $rows);
    }
}
