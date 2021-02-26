<?php

namespace GeoPagos\WithholdingTaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class WithholdingTaxSimpleRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WithholdingTaxSimpleRule::class);
    }

    public function findIdByWithholdingTaxParams($simpleRuleParams, $type)
    {
        $simpleRule = $this->findByWithholdingTaxParams($simpleRuleParams, $type);
        if ($simpleRule) {
            return ['id' => $simpleRule->getId()];
        }
    }

    public function findByWithholdingTaxParams($simpleRuleParams, $type): ?WithholdingTaxSimpleRule
    {
        /* @var $builder QueryBuilder */
        $builder = $this->createQueryBuilder('s')
            ->select('s');
        $ands = $builder->expr()->andX($builder->expr()->eq('s.type',
            $builder->expr()->literal($type))
        );

        if (WithholdingTaxTypeEnum::SIRTAC == $type) {
            $ands->add($builder->expr()->eq('s.taxCategory', $simpleRuleParams['taxCategoryId']));
        } elseif (WithholdingTaxTypeEnum::TAX == $type) {
            $ands->add($builder->expr()->eq('s.province', $simpleRuleParams['provinceId']));
            $ands->add($builder->expr()->eq('s.taxCategory', $simpleRuleParams['taxCategoryId']));
        } else {
            $ands->add(
                $builder->expr()->orX(
                    $builder->expr()->eq('s.paymentMethodType',
                        $builder->expr()->literal($simpleRuleParams['paymentMethodType']->getType())
                    ),
                    $builder->expr()->isNull('s.paymentMethodType')
                )
            );
            if (isset($simpleRuleParams['taxConditionId'])) {
                $ands->add(
                    $builder->expr()->orX(
                        $builder->expr()->eq('s.taxCondition', $simpleRuleParams['taxConditionId']),
                        $builder->expr()->isNull('s.taxCondition')
                    )
                );
            }
            if (isset($simpleRuleParams['incomeTax'])) {
                $ands->add(
                    $builder->expr()->orX(
                        $builder->expr()->eq('s.incomeTax',
                            $builder->expr()->literal($simpleRuleParams['incomeTax'])),
                        $builder->expr()->isNull('s.incomeTax')
                    )
                );
            }
            if (isset($simpleRuleParams['classificationId'])) {
                $ands->add(
                    $builder->expr()->orX(
                        $builder->expr()->eq('s.classification', $simpleRuleParams['classificationId']),
                        $builder->expr()->isNull('s.classification')
                    )
                );
            }
        }
        $builder->where($ands)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(1);
        $data = $builder->getQuery()->getResult();
        if (count($data)) {
            return reset($data);
        }

        return null;
    }

    public function findRulesByFederalTaxRule(WithholdingTaxRule $withholdingTaxRule): array
    {
        return $this->createQueryBuilder('wtsr')
            ->where('wtsr.type = :taxType')
            ->setParameter('taxType', $withholdingTaxRule->getType())
            ->addOrderBy('wtsr.taxCategory')
            ->getQuery()
            ->getResult();
    }

    public function findRulesByProvincialTaxRule(WithholdingTaxRule $withholdingTaxRule): array
    {
        return $this->createQueryBuilder('wtsr')
            ->where('wtsr.type = :taxType')
            ->andWhere('wtsr.province = :province')
            ->setParameter('taxType', $withholdingTaxRule->getType())
            ->setParameter('province', $withholdingTaxRule->getProvince())
            ->addOrderBy('wtsr.taxCategory')
            ->getQuery()
            ->getResult();
    }
}
