<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class WithholdingTaxRepository extends ServiceEntityRepository
{
    /** @var ProvinceWithholdingTaxSettingRepository */
    private $provinceWithholdingTaxSettingRepository;

    public function __construct(
        ManagerRegistry $registry,
        ProvinceWithholdingTaxSettingRepository $provinceWithholdingTaxSettingRepository
    ) {
        parent::__construct($registry, WithholdingTax::class);
        $this->provinceWithholdingTaxSettingRepository = $provinceWithholdingTaxSettingRepository;
    }

    public function removeByReportExecutionDate(
        ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting,
        Carbon $executionDate
    ): int {
        [$startDate, $endDate] = $provinceWithholdingTaxSetting->getLastPeriodStartAndEndDateInUTC($executionDate);

        $qb = $this->createQueryBuilder('wt')
            ->delete()
            ->where('wt.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d'))
            ->setParameter('end', $endDate->format('Y-m-d'))
            ->andWhere('wt.type = :type')
            ->setParameter('type', $provinceWithholdingTaxSetting->getWithholdingTaxType());

        if (WithholdingTaxTypeEnum::TAX == $provinceWithholdingTaxSetting->getWithholdingTaxType()) {
            $qb->andWhere('wt.province = :province')
                ->setParameter('province', $provinceWithholdingTaxSetting->getProvince());
        }

        return $qb->getQuery()->execute();
    }

    public function findProvincesByReportExecutionDate(Carbon $executionDate)
    {
        $parameters = [
            'tax' => WithholdingTaxTypeEnum::TAX,
        ];

        $qb = $this->createQueryBuilder('wt')
            ->select(['p', 'count(wt.id) as withholds'])
            ->join(Subsidiary::class, 's', 'WITH', 'wt.subsidiary=s')
            ->join(Province::class, 'p', 'WITH', 'wt.province=p')
            ->join(ProvinceWithholdingTaxSetting::class, 'pwts', 'WITH', 'pwts.province=p');

        $this->setExecutionDateDependentConditions($executionDate, $qb, $parameters, 'wt.date');

        $qb
            ->andWhere('wt.type = :tax')
            ->setParameters($parameters)
            ->groupBy('p.id');

        return $qb->getQuery()->getResult();
    }

    public function findFederalTaxesToReportByExecutionDate(Carbon $executionDate)
    {
        $parameters = [
            'federal_tax_types' => WithholdingTaxTypeEnum::getFederalTaxTypes(),
        ];

        $qb = $this->createQueryBuilder('wt')
            ->select(['distinct(wt.type) as type', 'count(wt.id) as withholds'])
            ->join(ProvinceWithholdingTaxSetting::class, 'pwts', 'WITH', 'pwts.withholdingTaxType=wt.type');

        $this->provinceWithholdingTaxSettingRepository->setExecutionDateDependentConditions(
            $executionDate,
            $qb,
            $parameters,
            'wt.date'
        );

        $qb
            ->andWhere('wt.type IN(:federal_tax_types)')
            ->setParameters($parameters)
            ->groupBy('wt.type');

        return $qb->getQuery()->getResult();
    }

    public function setExecutionDateDependentConditions(
        Carbon $executionDate,
        QueryBuilder $qb,
        &$parameters,
        string $dateField
    ): void {
        $executionDay = (int) $executionDate->format('j');

        if ($executionDay <= 15) {
            // execution for previous whole month or second fortnight (depending on province withholding tax 'period' setting)
            [$startDate, $midDate, $endDate] = $this->getQueryDateLimitsForPreviousMonth($executionDate,
                't.availableDate' === $dateField);

            $qb->andWhere(
                $qb->expr()->orX(
                    'pwts.period = :semiMonthly and '.$dateField.' BETWEEN :midDate AND :endDate',
                    'pwts.period = :monthly and '.$dateField.' BETWEEN :startDate AND :endDate'
                )
            );

            $parameters['midDate'] = $midDate;
            $parameters['monthly'] = Period::MONTHLY;
        } else {
            // execution for current months' first fortnight (only for provinces with SEMI_MONTHLY 'period' setting)
            [$startDate, $endDate] = $this->getQueryDateLimitsForCurrentMonth($executionDate,
                't.availableDate' === $dateField);
            $qb->andWhere('pwts.period = :semiMonthly', $dateField.' BETWEEN :startDate AND :endDate');
        }

        $parameters['startDate'] = $startDate;
        $parameters['endDate'] = $endDate;
        $parameters['semiMonthly'] = Period::SEMI_MONTHLY;
    }

    private function getQueryDateLimitsForCurrentMonth(Carbon $executionDate, bool $calculateWithTimezone): array
    {
        if ($calculateWithTimezone) {
            $baseDate = $executionDate->copy()->hour(4)->setTimezone($this->configurationManager->get('timezone'));
            $startDate = $baseDate->copy()
                ->startOfMonth()
                ->setTimezone('UTC');
            $endDate = $baseDate->copy()
                ->day(15)->endOfDay()
                ->setTimezone('UTC');
        } else {
            $startDate = $executionDate->copy()->startOfMonth();
            $endDate = $executionDate->copy()->day(15)->endOfDay();
        }

        return [$startDate, $endDate];
    }

    private function getQueryDateLimitsForPreviousMonth(Carbon $executionDate, bool $calculateWithTimezone): array
    {
        if ($calculateWithTimezone) {
            $baseDate = $executionDate->copy()->hour(4)->setTimezone($this->configurationManager->get('timezone'));

            $startDate = $baseDate->copy()
                ->subMonth()
                ->startOfMonth()
                ->setTimezone('UTC');
            $midDate = $baseDate->copy()
                ->subMonth()
                ->day(16)->startOfDay()
                ->startOfDay()->setTimezone('UTC');
            $endDate = $baseDate->copy()
                ->subMonth()
                ->endOfMonth()
                ->setTimezone('UTC');
        } else {
            $startDate = $executionDate->copy()->subMonth()->startOfMonth();
            $midDate = $executionDate->copy()->subMonth()->day(16)->startOfDay();
            $endDate = $executionDate->copy()->subMonth()->endOfMonth();
        }

        return [$startDate, $midDate, $endDate];
    }

    public function findWithActiveSubsidiariesWithCertificates(
        $month,
        $getOldCertificates = false,
        ?array $parameters = []
    ): array {
        $startDate = Carbon::createFromFormat('Ymd', $month.'01')->startOfMonth();
        $endDate = Carbon::createFromFormat('Ymd', $month.'01')->endOfMonth();

        $qb = $this->createQueryBuilder('w')
            ->select('DISTINCT IDENTITY(w.subsidiary)')
            ->innerJoin(Subsidiary::class, 's', 'WITH', 'w.subsidiary=s.id')
            ->addOrderBy('w.date', 'ASC')
            ->addOrderBy('w.certificateNumber', 'ASC');

        if (!empty($parameters['status'])) {
            $qb->setParameter('status', $parameters['status'])
                ->andWhere('w.status like :status ');
        }

        $qb->andWhere('s.deletedAt is null');

        if ($getOldCertificates) {
            $qb->AndWhere('w.date <= :endDate')
                ->setParameter('endDate', $endDate);
        } else {
            $qb->AndWhere('w.date BETWEEN :startDate AND :endDate')
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
            ->addOrderBy('w.date', 'ASC')
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
            $qb->AndWhere('w.date <= :endDate')
                ->setParameter('endDate', $endDate);
        } else {
            $qb->AndWhere('w.date BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }
}
