<?php

namespace GeoPagos\WithholdingTaxBundle\Repository\Strategy\TaxGenerationStrategy;

use Carbon\Carbon;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\TaxGenerationStrategyEnum;
use Psr\Log\LoggerInterface;

abstract class GenericDeclarationGenerationStrategy implements DeclarationGenerationStrategy
{
    /** @var ConfigurationManagerInterface */
    protected $configurationManager;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var string see */
    private $dateStrategy;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationManagerInterface $configurationManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->configurationManager = $configurationManager;
        $this->logger = $logger;
        $this->initializeDateStrategy();
    }

    private function initializeDateStrategy(): void
    {
        $dateStrategy = null;

        try {
            $dateStrategy = $this->configurationManager->get('tax_generation_strategy');
        } catch (Exception $e) {
            $message = 'There is no configuration for key "tax_generation_strategy", using default date strategy instead.';
            $this->logger->info($message);
        }

        if (null === $dateStrategy) {
            $dateStrategy = (new TaxGenerationStrategyEnum())->getDefault();
        }

        TaxGenerationStrategyEnum::isValidValueOrThrowException($dateStrategy);

        $this->dateStrategy = $dateStrategy;
    }

    /** {@inheritdoc} */
    public function generate(ProvinceWithholdingTaxSetting $setting, Carbon $startDate, Carbon $endDate): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        if (TaxGenerationStrategyEnum::TRANSACTION_AVAILABLE_AT === $this->dateStrategy) {
            $this->setQueryForAvailableDateStrategy($qb, $setting, $startDate, $endDate);
        } else {
            $this->setQueryForDepositToDepositStrategy($qb, $setting, $startDate, $endDate);
        }

        $generatedWithholdingTaxes = [];

        $rows = $qb->getQuery()->execute([], AbstractQuery::HYDRATE_SCALAR);

        foreach ($rows as $row) {
            $generatedWithholdingTaxes[] = $this->createDeclaration($setting, $row);
        }

        return $generatedWithholdingTaxes;
    }

    abstract protected function setQueryForAvailableDateStrategy(
        QueryBuilder $qb,
        ProvinceWithholdingTaxSetting $setting,
        Carbon $startDate,
        Carbon $endDate
    ): void;

    abstract protected function setQueryForDepositToDepositStrategy(
        QueryBuilder $qb,
        ProvinceWithholdingTaxSetting $setting,
        Carbon $startDate,
        Carbon $endDate
    ): void;

    abstract protected function createDeclaration(ProvinceWithholdingTaxSetting $setting, array $row): object;
}
