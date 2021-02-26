<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Enum\TaxCategoryCode;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\SendWithholdingTaxesReportTaxAgenciesCommand;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroupItem;
use GeoPagos\WithholdingTaxBundle\Enum\TaxConceptEnum;
use GeoPagos\WithholdingTaxBundle\Enum\TaxRuleProvincesGroupEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Repository\SirtacDeclarationRepository;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\SirtacMocks;
use Symfony\Component\HttpFoundation\ParameterBag;

final class SirtacDeclarationCreationTest extends TestCase
{
    use FactoriesTrait;
    use SirtacMocks;

    /**
     * @var SirtacDeclarationRepository
     */
    private $declarationsRepository;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(null);
        BusinessDay::enable('Carbon\Carbon');

        $this->getMockedConfigurationManager(
            true,
            false,
            false,
            true,
            false,
            false,
            false
        );

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->declarationsRepository = $this->entityManager->getRepository(SirtacDeclaration::class);

        $this->entityManager->getFilters()->enable('enabled_rules');
    }

    /** @test */
    public function it_create_declarations(): void
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->day(16);

        $currentTestDate = Carbon::getTestNow();
        Carbon::setTestNow($transactionDate);

        $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            1,
            500,
            TaxCategoryCode::INSCRIPTO_LOCAL
        );

        $scene = $this->sceneBuilder()->getResult();

        $this->withhold($scene->getSaleBag());

        Carbon::setTestNow($currentTestDate);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var SirtacDeclaration[] $declarations */
        $declarations = $this->declarationsRepository->findAll();

        $this->assertCount(1, $declarations);

        $this->assertEquals($declarations[0]->getTaxConcept()->getId(), TaxConceptEnum::PENALTY_ID);
    }

    /** @test */
    public function settlement_number_is_sequential()
    {
        $count = 5;

        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->day(16);

        $currentTestDate = Carbon::getTestNow();
        Carbon::setTestNow($transactionDate);

        for ($i = 1; $i <= $count; ++$i) {
            $this->buildTrxScene(
                self::$A_SIRTAC_PROVINCE_ID,
                1,
                500,
                TaxCategoryCode::INSCRIPTO_LOCAL
            );
            $scene = $this->sceneBuilder()->getResult();
            $this->withhold($scene->getSaleBag());
        }

        Carbon::setTestNow($currentTestDate);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var SirtacDeclaration[] $declarations */
        $declarations = $this->declarationsRepository->findAll();

        $this->assertCount($count, $declarations);

        for ($i = 1; $i <= $count; ++$i) {
            $declaration = $declarations[$i - 1];
            $this->assertEquals($declaration->getSettlementNumber(), $i);
        }
    }

    /** @test */
    public function one_declaration_is_created_for_withholding_and_another_for_penalty()
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->day(16);

        $currentTestDate = Carbon::getTestNow();
        Carbon::setTestNow($transactionDate);

        $scene = $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            1,
            500
        );

        $this->addAccountToTaxRegistry(
            $scene,
            self::$SIRTAC_TAX_REGISTRY_RATE,
            true,
            self::$A_SIRTAC_PROVINCE_ID
        );

        $this->withhold($scene->getSaleBag());

        Carbon::setTestNow($currentTestDate);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var SirtacDeclaration[] $declarations */
        $declarations = $this->declarationsRepository->findAll();
        $this->assertCount(2, $declarations);

        $withholdingConcept = $this->entityManager->getReference(
            TaxConcept::class,
            TaxConceptEnum::WITHHOLDING_ID
        );

        $withholdingDeclaration = $this->declarationsRepository->findOneBy([
            'taxConcept' => $withholdingConcept,
        ]);

        $this->assertNotNull($withholdingDeclaration);

        $penaltyConcept = $this->entityManager->getReference(
            TaxConcept::class,
            TaxConceptEnum::PENALTY_ID
        );

        $penaltyDeclaration = $this->declarationsRepository->findOneBy([
            'taxConcept' => $penaltyConcept,
        ]);

        $this->assertNotNull($penaltyDeclaration);

        $this->assertEquals(
            $withholdingDeclaration->getSubsidiary()->getId(),
            $penaltyDeclaration->getSubsidiary()->getId()
        );
    }

    /** @test */
    public function one_declaration_is_created_for_every_province()
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->day(16);

        $currentTestDate = Carbon::getTestNow();
        Carbon::setTestNow($transactionDate);

        $sirtacGroup = $this->entityManager->getReference(
            TaxRuleProvincesGroup::class,
            TaxRuleProvincesGroupEnum::SIRTAC_ID
        );

        $province = $this->entityManager->getRepository(Province::class)->find(self::$A_SIRTAC_PROVINCE_ID);

        $parameterBag = new ParameterBag([
            'account.idFiscal' => self::$A_FISCAL_ID,
            'subsidiary.address.province' => $province,
            'subsidiary.taxCategoryId' => TaxCategoryCode::NO_INSCRIPTO,
        ]);

        $this->sceneBuilder()->reset()
            ->buildAccount($parameterBag)
            ->buildSubsidiary($parameterBag)
            ->buildDeposit($parameterBag);

        /** @var TaxRuleProvincesGroupItem[] $items */
        $items = $this->entityManager->getRepository(TaxRuleProvincesGroupItem::class)->findBy([
            'taxRuleProvincesGroup' => $sirtacGroup,
        ]);

        foreach ($items as $item) {
            for ($i = 0; $i < 10; ++$i) {
                $parameterBag = new ParameterBag([
                    'transaction.amount' => 10000,
                    'transaction.province' => $item->getProvince(),
                ]);

                $this->sceneBuilder()->buildTransaction($parameterBag);
            }
        }

        $this->sceneBuilder()->buildBag($parameterBag);
        $scene = $this->sceneBuilder()->getResult();

        $this->withhold($scene->getSaleBag());

        $this->entityManager->flush();

        Carbon::setTestNow($currentTestDate);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var SirtacDeclaration[] $declarations */
        $declarations = $this->declarationsRepository->findAll();
        $this->assertCount(count($items), $declarations);
    }

    /** @test */
    public function seller_account_is_excluded()
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->day(16);

        $currentTestDate = Carbon::getTestNow();
        Carbon::setTestNow($transactionDate);

        $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            3,
            5000,
            TaxCategoryCode::INSCRIPTO_LOCAL
        );

        $scene = $this->sceneBuilder()->getResult();

        $configurationManager = static::$container->get(ConfigurationManagerInterface::class);
        $configurationManager->set('ReadersSellAccount', $scene->getAccount());

        $this->withhold($scene->getSaleBag());

        $this->buildTrxScene(
            self::$A_SIRTAC_PROVINCE_ID,
            10,
            5000,
            TaxCategoryCode::INSCRIPTO_LOCAL
        );

        $scene = $this->sceneBuilder()->getResult();

        $this->withhold($scene->getSaleBag());

        Carbon::setTestNow($currentTestDate);

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var SirtacDeclaration[] $declarations */
        $declarations = $this->declarationsRepository->findAll();

        $this->assertCount(1, $declarations);

        $this->assertEquals($declarations[0]->getTaxConcept()->getId(), TaxConceptEnum::PENALTY_ID);
    }

    private function withhold(SaleBag $saleBag): void
    {
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($saleBag);

        $this->entityManager->flush();
    }
}
