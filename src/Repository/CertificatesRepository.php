<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;

class CertificatesRepository extends ServiceEntityRepository
{
    const SORT_COLUMNS = [
        'c.period',
        'c.type',
        'c.province',
        'c.fileName',
        'c.status',
    ];

    const ORDER = [
        'asc' => Criteria::ASC,
        'desc' => Criteria::DESC,
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Certificate::class);
    }

    public function findByPackageOrCreateTax(Package $package): Certificate
    {
        return $this->findOneBy([
                'subsidiary' => $package->getSubsidiary(),
                'type' => $package->getTaxType(),
                'province' => $package->getProvince(),
                'period' => $package->getPeriod(),
            ]) ?? (new Certificate())
                ->setSubsidiary($package->getSubsidiary())
                ->setProvince($package->getProvince())
                ->setType($package->getTaxType())
                ->setSequenceNumber($this->generateSequenceNumberFor($package))
                ->setStatus(Certificate::CREATED)
                ->setPeriod($package->getPeriod());
    }

    public function findByPackageOrCreate(Package $package): Certificate
    {
        $camelizedTaxType = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($package->getTaxType()))));
        if (method_exists($this, 'findByPackageOrCreate'.$camelizedTaxType)) {
            return $this->{'findByPackageOrCreate'.$camelizedTaxType}($package);
        }

        return $this->findOneBy([
                'subsidiary' => $package->getSubsidiary(),
                'type' => $package->getTaxType(),
                'period' => $package->getPeriod(),
            ]) ?? (new Certificate())
                ->setSubsidiary($package->getSubsidiary())
                ->setType($package->getTaxType())
                ->setSequenceNumber($this->generateSequenceNumberFor($package))
                ->setStatus(Certificate::CREATED)
                ->setPeriod($package->getPeriod());
    }

    public function generateSequenceNumberFor(Package $package): int
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select($qb->expr()->count('c'))
            ->andWhere($qb->expr()->eq('c.type', $qb->expr()->literal($package->getTaxType())))
            ->andWhere($qb->expr()->eq('YEAR(c.period)', $qb->expr()->literal($package->getPeriod()->year)));

        $query = $qb->getQuery();

        $certsCount = $query->getSingleScalarResult();

        return $certsCount + 1;
    }

    public function getPaginatedData(
        Account $account,
        ?int $offset,
        ?int $limit,
        array $orderCriteria = [],
        array $filterData = []
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('c')
            ->innerJoin('c.subsidiary', 's');

        $queryBuilder->andWhere('c.period BETWEEN :periodFrom AND :periodTo')->setParameters([
            'periodFrom' => $filterData['periodFrom'],
            'periodTo' => $filterData['periodTo'],
        ]);

        $queryBuilder->andWhere('s.account = :account')->setParameter(
            'account', $account
        );

        if (!empty($filterData['taxType'])) {
            $queryBuilder->andWhere('c.type LIKE :taxType')
                ->setParameter('taxType', $filterData['taxType']);
        }

        $this->addOrderByCriteria($queryBuilder, $orderCriteria);
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        return new Paginator($queryBuilder, false);
    }

    private function addOrderByCriteria(QueryBuilder $qb, array $orderCriteria)
    {
        if (0 === count($orderCriteria)) {
            return;
        }

        foreach ($orderCriteria as $criteria) {
            $qb->orderBy(self::SORT_COLUMNS[$criteria['column']], self::ORDER[$criteria['dir']]);
        }
    }
}
