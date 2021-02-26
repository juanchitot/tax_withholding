<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NoResultException;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Enum\TaxRuleProvincesGroupEnum;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class WithholdingTaxDynamicRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxDynamicRule::class);
    }

    public function getRuleByType(
        $type,
        $idFiscal,
        Province $province,
        Carbon $availableDate
    ): ?WithholdingTaxDynamicRule {
        $qb = $this->getRules($idFiscal, $availableDate, $type);

        if (WithholdingTaxTypeEnum::TAX === $type) {
            $qb->andWhere('w.province = :province')
                ->setParameter('province', $province->getId());
        }

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $noResultException) {
            return null;
        }
    }

    public function deleteByMonthYear($date, $province, $taxType)
    {
        $em = $this->getEntityManager();
        $queryString = 'DELETE FROM GeoPagosWithholdingTaxBundle:WithholdingTaxDynamicRule _d1
            WHERE _d1.province = :province and _d1.monthYear = :monthYear and _d1.taxType = :taxType';

        $query = $em->createQuery($queryString);
        $query->setParameter('monthYear', $date);
        $query->setParameter('province', $province);
        $query->setParameter('taxType', $taxType);

        $query->getResult();
    }

    public function deleteByMonthYearWithNullProvince($date, $taxType)
    {
        $em = $this->getEntityManager();
        $queryString = 'DELETE FROM GeoPagosWithholdingTaxBundle:WithholdingTaxDynamicRule _d1
            WHERE _d1.province is null and _d1.monthYear = :monthYear and _d1.taxType = :taxType';

        $query = $em->createQuery($queryString);
        $query->setParameter('monthYear', $date);
        $query->setParameter('taxType', $taxType);

        $query->getResult();
    }

    public function cleanOldTaxRegisters(Carbon $now)
    {
        $actualMonth = $now->startOfMonth()->format('m-Y');
        $nextMonth = $now->startOfMonth()->addMonth()->format('m-Y');

        $qb = $this->createQueryBuilder('wtdr')
            ->select(' distinct(wtdr.monthYear) as monthYear')
            ->where('wtdr.monthYear not in (:thisMonthAndNextOne)')
            ->setParameter('thisMonthAndNextOne', [
                $actualMonth,
                $nextMonth,
            ]);

        $result = $qb->getQuery()->getResult();

        $eliminated = 0;

        foreach ($result as $monthYearToClean) {
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                'delete from withholding_tax_dynamic_rule
                                where id_fiscal not in ( select id_fiscal collate utf8_bin from account where id_fiscal is not null ) and
                                    month_year = :monthYear'
            );
            $stmt->bindParam(':monthYear', $monthYearToClean['monthYear']);
            $stmt->execute();
            $eliminated += $stmt->rowCount();
        }

        return $eliminated;
    }

    public function getRules($idFiscal, Carbon $availableDate, $type)
    {
        $qb = $this->createQueryBuilder('w');

        return $qb->andWhere('w.idFiscal =:idFiscal')
            ->andWhere('w.monthYear = :monthYear')
            ->andWhere('BIT_AND(w.taxType, :taxType) > 0 ')
            ->setParameter('idFiscal', $idFiscal)
            ->setParameter('monthYear', $availableDate->format('m-Y'))
            ->setParameter('taxType', WithholdingTaxTypeEnum::getBitFieldValue($type));
    }

    public function getSirtacRegisterByIdFiscalAndPeriod($idFiscal, $period)
    {
        $qb = $this->createQueryBuilder('w');

        $qb
            ->andWhere('w.idFiscal =:idFiscal')
            ->andWhere('w.monthYear = :monthYear')
            ->andWhere('BIT_AND(w.taxType, :taxType) > 0 ')
            ->andWhere('w.provincesGroup = :sirtacProvinceGroup')
            ->setParameter('idFiscal', $idFiscal)
            ->setParameter('monthYear', $period->format('m-Y'))
            ->setParameter(
                'taxType',
                WithholdingTaxTypeEnum::getBitFieldValue(WithholdingTaxTypeEnum::SIRTAC)
            )->setParameter(
                'sirtacProvinceGroup',
                $this->_em->getReference(TaxRuleProvincesGroup::class, TaxRuleProvincesGroupEnum::SIRTAC_ID)
            );

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $noResultException) {
            return null;
        }
    }
}
