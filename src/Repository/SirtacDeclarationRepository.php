<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class SirtacDeclarationRepository extends ServiceEntityRepository
{
    /** @var  */
    private $provinceWithholdingTaxSettingRepository;

    public function __construct(
        ManagerRegistry $registry,
        ProvinceWithholdingTaxSettingRepository $provinceWithholdingTaxSettingRepository
    ) {
        parent::__construct($registry, SirtacDeclaration::class);
        $this->provinceWithholdingTaxSettingRepository = $provinceWithholdingTaxSettingRepository;
    }

    public function getLastSettlementNumber(): int
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->addSelect('MAX(sd.settlementNumber) as lastSettlementNumber')
            ->from(SirtacDeclaration::class, 'sd');

        $row = $qb->getQuery()->execute([], AbstractQuery::HYDRATE_SINGLE_SCALAR);

        if (!empty($row['lastSettlementNumber'])) {
            $lastSettlementNumber = (int) $row['lastSettlementNumber'];
        } else {
            $lastSettlementNumber = 0;
        }

        return $lastSettlementNumber;
    }

    /**
     * @return SirtacDeclaration[]
     */
    public function thereAreDeclarationsToReport(Carbon $date): bool
    {
        $parameters = [
            'taxType' => WithholdingTaxTypeEnum::SIRTAC,
        ];

        $qb = $this->createQueryBuilder('sd');

        $qb->join(ProvinceWithholdingTaxSetting::class, 'pwts', 'WITH', 'pwts.withholdingTaxType=:taxType');

        $this->provinceWithholdingTaxSettingRepository->setExecutionDateDependentConditions(
            $date,
            $qb,
            $parameters,
            'sd.withholdingDate'
        );

        $qb->setParameters($parameters);

        return !empty($qb->getQuery()->getResult());
    }

    public function findWithActiveSubsidiariesWithCertificates($month, $getOldCertificates = false, ?array $parameters = []): array
    {
        $startDate = Carbon::createFromFormat('Ymd', $month.'01')->startOfMonth();
        $endDate = Carbon::createFromFormat('Ymd', $month.'01')->endOfMonth();

        $qb = $this->createQueryBuilder('w')
            ->select('DISTINCT IDENTITY(w.subsidiary)')
            ->innerJoin(Subsidiary::class, 's', 'WITH', 'w.subsidiary=s.id')
            ->addOrderBy('w.withholdingDate', 'ASC')
            ->addOrderBy('w.certificateNumber', 'ASC');

        if (!empty($parameters['status'])) {
            $qb->setParameter('status', $parameters['status'])
                ->andWhere('w.status like :status ');
        }
        if (!empty($parameters['subsidiaryId'])) {
            $qb->setParameter('subsidiaryId', $parameters['subsidiaryId'])
                ->andWhere('IDENTITY(w.subsidiary) = :subsidiaryId');
        }

        $qb->andWhere('s.deletedAt is null');

        if ($getOldCertificates) {
            $qb->AndWhere('w.withholdingDate <= :endDate')
                ->setParameter('endDate', $endDate);
        } else {
            $qb->AndWhere('w.withholdingDate BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithActiveSubsidiaryBy($month, $getOldCertificates = false, ?array $parameters = []): array
    {
        $startDate = Carbon::createFromFormat('Ymd', $month.'01')->startOfMonth();
        $endDate = Carbon::createFromFormat('Ymd', $month.'01')->endOfMonth();

        $qb = $this->createQueryBuilder('w')
            ->select('w')
            ->innerJoin(Subsidiary::class, 's', 'WITH', 'w.subsidiary=s.id')
            ->addOrderBy('w.withholdingDate', 'ASC')
            ->addOrderBy('w.certificateNumber', 'ASC');

        if (!empty($parameters['status'])) {
            $qb->setParameter('status', $parameters['status'])
                ->andWhere('w.status like :status ');
        }
        if (!empty($parameters['subsidiaryId'])) {
            $qb->setParameter('subsidiaryId', $parameters['subsidiaryId'])
                ->andWhere('IDENTITY(w.subsidiary) = :subsidiaryId');
        }

        $qb->andWhere('s.deletedAt is null');

        if ($getOldCertificates) {
            $qb->AndWhere('w.withholdingDate <= :endDate')
                ->setParameter('endDate', $endDate);
        } else {
            $qb->AndWhere('w.withholdingDate BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function removeByReportExecutionDate(
        ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting,
        Carbon $executionDate
    ): int {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->from(SirtacDeclaration::class, 'sd')
            ->delete()
            ->where('sd.withholdingDate BETWEEN :start AND :end');

        [$startDate, $endDate] = $provinceWithholdingTaxSetting->getLastPeriodStartAndEndDateInUTC($executionDate);

        $qb->setParameters([
            'start' => $startDate,
            'end' => $endDate,
        ]);

        return $qb->getQuery()->execute();
    }
}
