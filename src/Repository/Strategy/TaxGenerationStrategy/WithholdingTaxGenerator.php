<?php

namespace GeoPagos\WithholdingTaxBundle\Repository\Strategy\TaxGenerationStrategy;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\DepositBundle\Enum\DepositState;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRepository;
use Psr\Log\LoggerInterface;

final class WithholdingTaxGenerator extends GenericDeclarationGenerationStrategy
{
    /** @var WithholdingTaxRepository */
    private $wihholdingTaxRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationManagerInterface $configurationManager,
        LoggerInterface $logger,
        WithholdingTaxRepository $wihholdingTaxRepository
    ) {
        parent::__construct($entityManager, $configurationManager, $logger);
        $this->wihholdingTaxRepository = $wihholdingTaxRepository;
    }

    protected function setQueryForAvailableDateStrategy(
        QueryBuilder $qb,
        ProvinceWithholdingTaxSetting $setting,
        Carbon $startDate,
        Carbon $endDate
    ): void {
        $this->setBaseQuery($qb, $setting);

        $qb->addSelect('date_format(CONVERT_TZ(wtd.settlementDate,\'+00:00\',\'-03:00\'),\'%d-%m-%Y\') as withholdingTaxDate');

        $qb->andWhere('wtd.settlementDate between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        $qb->orderBy('wtd.settlementDate asc, t.id');
    }

    protected function setQueryForDepositToDepositStrategy(
        QueryBuilder $qb,
        ProvinceWithholdingTaxSetting $setting,
        Carbon $startDate,
        Carbon $endDate
    ): void {
        $this->setBaseQuery($qb, $setting);

        $qb->addSelect('date_format(d.toDeposit, \'%d-%m-%Y\') as withholdingTaxDate');

        $qb->innerJoin('t.deposits', 'd');

        $qb->andWhere('d.toDeposit between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        $qb->andWhere('d.state = :depositSuccessState and d.transferredAt is not null')
            ->setParameter('depositSuccessState', DepositState::SUCCESS);

        $qb->orderBy('d.toDeposit asc, t.id');
    }

    public function setBaseQuery(QueryBuilder $qb, ProvinceWithholdingTaxSetting $setting)
    {
        $qb->select([
            's.id as subsidiaryId',
            'wtd.type as type',
            'sum(wtd.taxableIncome * (CASE WHEN t.typeId in (:debitTransactions) THEN -1 ELSE 1 END) ) as taxableIncome',
            'min(wtd.rate) as rate',
            'sum(wtd.amount * (CASE WHEN t.typeId in (:debitTransactions) THEN -1 ELSE 1 END)) as amount',
        ]);

        $qb->from(WithholdingTaxDetail::class, 'wtd');
        $qb->innerJoin('wtd.transaction', 't');
        $qb->innerJoin('t.subsidiary', 's');

        if (WithholdingTaxTypeEnum::TAX != $setting->getWithholdingTaxType()) {
            $qb->innerJoin('t.transactionDetail', 'td');
            $qb->innerJoin('td.paymentMethod', 'pm');
            $qb->innerJoin('pm.type', 'pmType');
            $qb->addSelect('pmType.type as paymentType');
        } else {
            $qb->addSelect('\'ALL\' as paymentType');
        }

        if ($this->appliesProvinceFromBuyer()) {
            $qb->innerJoin('wtd.withholdingTaxLog', 'wtl');
            $qb->innerJoin('wtl.taxCategory', 'tcat_wtd');
            $qb->innerJoin('wtl.taxCondition', 'tcond_wtd');
            $qb->addSelect(
                'tcat_wtd.id as taxCategoryId',
                'tcond_wtd.id as taxConditionId'
            );
        } else {
            $qb->innerJoin('s.taxCategory', 'tcat_s');
            $qb->innerJoin('s.taxCondition', 'tcond_s');
            $qb->innerJoin('s.address', 'a');
            $qb->addSelect(
                'tcat_s.id as taxCategoryId',
                'tcond_s.id as taxConditionId'
            );
        }

        $qb->addGroupBy('withholdingTaxDate, s.id');
        $qb->having('amount > 0');

        $qb->setParameter('debitTransactions', [
            Transaction::TYPE_ADJUSTMENT_DEBIT,
            Transaction::TYPE_REFUND,
        ]);

        if (WithholdingTaxTypeEnum::TAX === $setting->getWithholdingTaxType()) {
            $qb->andWhere('wtd.type = :type')->setParameter('type', WithholdingTaxTypeEnum::TAX);

            if ($this->appliesProvinceFromBuyer()) {
                $qb->andWhere('wtl.province = :province')->setParameter('province', $setting->getProvince());
            } else {
                $qb->andWhere('a.province = :province')->setParameter('province', $setting->getProvince());
            }
        } else {
            $qb->andWhere('wtd.type = :type')->setParameter('type', $setting->getWithholdingTaxType());

            $qb->addGroupBy('pm.type');
        }
    }

    /** @returns WithholdingTax */
    protected function createDeclaration(ProvinceWithholdingTaxSetting $setting, array $row): object
    {
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->entityManager->getReference(
            Subsidiary::class,
            $row['subsidiaryId']
        );

        /** @var TaxCategory $taxCategory */
        $taxCategory = $this->entityManager->getReference(
            TaxCategory::class,
            $row['taxCategoryId']
        );

        /** @var TaxCondition $taxCondition */
        $taxCondition = $this->entityManager->getReference(
            TaxCondition::class,
            $row['taxConditionId']
        );

        $province = null;
        if (WithholdingTaxTypeEnum::TAX === $setting->getWithholdingTaxType()) {
            $province = $setting->getProvince();
        }

        return (new WithholdingTax())
            ->setStatus(WithholdingTaxStatus::CREATED)
            ->setSubsidiary($subsidiary)
            ->setDate(Carbon::createFromFormat('d-m-Y', $row['withholdingTaxDate']))
            ->setType($row['type'])
            ->setPaymentType($row['paymentType'])
            ->setTaxableIncome($row['taxableIncome'])
            ->setRate($row['rate'])
            ->setAmount($row['amount'])
            ->setCertificateNumber($setting->increaseAndGetLastCertificateNumber())
            ->setTaxCategory($taxCategory)
            ->setTaxCondition($taxCondition)
            ->setProvince($province);
    }

    private function appliesProvinceFromBuyer(): bool
    {
        return $this->configurationManager->isFeatureEnabled('is_iibb_configurable_per_province');
    }

    public function removeOld(ProvinceWithholdingTaxSetting $setting, Carbon $executionDate): int
    {
        return $this->wihholdingTaxRepository->removeByReportExecutionDate(
            $setting,
            $executionDate
        );
    }
}
