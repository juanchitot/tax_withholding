<?php

namespace GeoPagos\WithholdingTaxBundle\Repository\Strategy\TaxGenerationStrategy;

use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\DepositBundle\Enum\DepositState;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Helper\SirtacJurisdictionsHelper;
use GeoPagos\WithholdingTaxBundle\Repository\SirtacDeclarationRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDynamicRuleRepository;
use Psr\Log\LoggerInterface;

final class SirtacDeclarationGenerator extends GenericDeclarationGenerationStrategy
{
    /** @var int */
    private $lastSettlementNumber;

    /** @var array */
    private $inMemoryTaxRegistryCache;

    /** @var SirtacDeclarationRepository */
    private $sirtacDeclarationRepository;

    /** @var WithholdingTaxDynamicRuleRepository */
    private $taxRegistryRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationManagerInterface $configurationManager,
        LoggerInterface $logger,
        SirtacDeclarationRepository $sirtacDeclarationRepository,
        WithholdingTaxDynamicRuleRepository $taxRegistryRepository
    ) {
        parent::__construct($entityManager, $configurationManager, $logger);
        $this->sirtacDeclarationRepository = $sirtacDeclarationRepository;
        $this->taxRegistryRepository = $taxRegistryRepository;
        $this->lastSettlementNumber = $this->sirtacDeclarationRepository->getLastSettlementNumber();
        $this->inMemoryTaxRegistryCache = [];
    }

    protected function setQueryForAvailableDateStrategy(
        QueryBuilder $qb,
        ProvinceWithholdingTaxSetting $setting,
        Carbon $startDate,
        Carbon $endDate
    ): void {
        $this->setBaseQuery($qb, $setting, $endDate);

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
        $this->setBaseQuery($qb, $setting, $endDate);

        $qb->innerJoin('t.deposits', 'd');

        $qb->andWhere('d.toDeposit between :startDate and :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        $qb->andWhere('d.state = :depositSuccessState and d.transferredAt is not null')
            ->setParameter('depositSuccessState', DepositState::SUCCESS);

        $qb->orderBy('d.toDeposit asc, t.id');
    }

    public function setBaseQuery(QueryBuilder $qb, ProvinceWithholdingTaxSetting $setting, Carbon $endDate)
    {
        $endPeriodicityDate = $endDate->copy()->startOfDay()->subSecond();

        $creditsCount = 'CASE WHEN t.typeId NOT IN (:credits) THEN -1 ELSE 1 END';

        $qb
            ->select([
                'a.idFiscal as accountIdFiscal',
                's.id as subsidiaryId',
                'p.id as provinceId',
                't_concept.id as taxConceptId',
                't_category.id as taxCategoryId',
                "'{$endPeriodicityDate}' as settlementDate",
                "'{$endPeriodicityDate}' as withholdingDate",
                "SUM(wtd.taxableIncome * ($creditsCount)) as taxableIncome",
                "(SUM(wtd.rate * ($creditsCount)) / SUM($creditsCount)) as rate",
                "SUM(wtd.amount * ($creditsCount)) as amount",
                "SUM($creditsCount) as salesCount",
            ])
            ->from(WithholdingTaxDetail::class, 'wtd')
            ->innerJoin('wtd.transaction', 't')
            ->innerJoin('wtd.withholdingTaxLog', 'wtl')
            ->innerJoin('wtd.concept', 't_concept')
            ->innerJoin('t.subsidiary', 's')
            ->innerJoin('wtl.province', 'p')
            ->innerJoin('wtl.taxCategory', 't_category')
            ->innerJoin('s.account', 'a')
            ->where('wtd.type = :type')
            ->addGroupBy('taxConceptId')
            ->addGroupBy('provinceId')
            ->addGroupBy('subsidiaryId')
            ->having('amount > 0');

        $qb->setParameters([
            'credits' => Transaction::CREDITS,
            'type' => $setting->getWithholdingTaxType(),
        ]);

        $readersSellerAccount = $this->configurationManager->get('ReadersSellAccount');
        if (null !== $readersSellerAccount) {
            $qb->andWhere('a != :readersSellerAccount');
            $qb->setParameter('readersSellerAccount', $readersSellerAccount);
        }
    }

    /** @returns SirtacDeclaration */
    protected function createDeclaration(ProvinceWithholdingTaxSetting $setting, array $row): object
    {
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->entityManager->getReference(
            Subsidiary::class,
            $row['subsidiaryId']
        );

        /** @var Province $province */
        $province = $this->entityManager->getReference(
            Province::class,
            $row['provinceId']
        );

        /** @var TaxConcept $taxConcept */
        $taxConcept = $this->entityManager->getReference(
            TaxConcept::class,
            $row['taxConceptId']
        );

        /** @var TaxCategory $taxCategory */
        $taxCategory = $this->entityManager->getReference(
            TaxCategory::class,
            $row['taxCategoryId']
        );

        $sirtacDeclaration = new SirtacDeclaration();

        $settlementDate = new DateTime($row['settlementDate']);
        $withholdingDate = new DateTime($row['withholdingDate']);

        $provinceJurisdiction = SirtacJurisdictionsHelper::getJurisdictionId(
            $province->getId()
        );

        $sirtacDeclaration
            ->setSubsidiary($subsidiary)
            ->setProvince($province)
            ->setTaxConcept($taxConcept)
            ->setTaxCategory($taxCategory)
            ->setSettlementDate($settlementDate)
            ->setWithholdingDate($withholdingDate)
            ->setTaxableIncome($row['taxableIncome'])
            ->setRate($row['rate'])
            ->setAmount($row['amount'])
            ->setSettlementNumber(++$this->lastSettlementNumber)
            ->setControlNumber($this->getControlNumber($row['accountIdFiscal'], $withholdingDate))
            ->setStatus(WithholdingTaxStatus::CREATED)
            ->setCertificate(null)
            ->setCertificateNumber(null)
            ->setSalesCount($row['salesCount'])
            ->setProvinceJurisdiction($provinceJurisdiction);

        return $sirtacDeclaration;
    }

    private function getControlNumber($idFiscal, $period)
    {
        if (!isset($this->inMemoryTaxRegistryCache[$idFiscal])) {
            $this->inMemoryTaxRegistryCache[$idFiscal] = $this->taxRegistryRepository->getSirtacRegisterByIdFiscalAndPeriod(
                $idFiscal,
                $period
            );
        }

        /** @var WithholdingTaxDynamicRule $sirtacRegister */
        $sirtacRegister = $this->inMemoryTaxRegistryCache[$idFiscal];

        return null !== $sirtacRegister ? $sirtacRegister->getCrc() : 00;
    }

    public function removeOld(ProvinceWithholdingTaxSetting $setting, Carbon $executionDate): int
    {
        return $this->sirtacDeclarationRepository->removeByReportExecutionDate(
            $setting,
            $executionDate
        );
    }
}
