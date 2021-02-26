<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Helper\WithholdingTaxPeriodHelper;

class ProvinceWithholdingTaxSettingRepository extends ServiceEntityRepository
{
    /** @var ConfigurationManager */
    private $configurationManager;

    public function __construct(ManagerRegistry $registry, ConfigurationManager $configurationManager)
    {
        parent::__construct($registry, ProvinceWithholdingTaxSetting::class);
        $this->configurationManager = $configurationManager;
    }

    /** @return ProvinceWithholdingTaxSetting[] */
    public function findActiveConfigurations()
    {
        $qb = $this->createQueryBuilder('pwts')
            ->andWhere('pwts.withholdingTaxSystem!=\'\'');

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
            [
                $startDate,
                $midDate,
                $endDate
            ] = WithholdingTaxPeriodHelper::getDateLimitsForPreviousMonth($executionDate);
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
            [$startDate, $endDate] = WithholdingTaxPeriodHelper::getDateLimitsForCurrentMonth($executionDate);

            $qb->andWhere('pwts.period = :semiMonthly', $dateField.' BETWEEN :startDate AND :endDate');
        }

        $parameters['startDate'] = $startDate;
        $parameters['endDate'] = $endDate;
        $parameters['semiMonthly'] = Period::SEMI_MONTHLY;
    }

    public function findSettingsForTaxType($taxType, $provinceId = null)
    {
        $camelizedTaxType = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($taxType))));
        if (method_exists($this, 'findSettingsForTaxType'.$camelizedTaxType)) {
            return $this->{'findSettingsForTaxType'.$camelizedTaxType}($provinceId);
        }

        return $this->findOneBy(['withholdingTaxType' => $taxType, 'province' => (int) $provinceId]);
    }
}
