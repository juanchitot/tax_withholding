<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\SendWithholdingTaxesReportTaxAgenciesCommand;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\SceneBuilderBaseDirector;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use Symfony\Component\HttpFoundation\ParameterBag;

class WithholdingTaxesCreationTest extends TestCase
{
    use FactoriesTrait;
    use WithholdingMocks;

    private const MOCK_CONTENT_OF_REPORT_SERVICE = [
        'iva' => 'AC',          // RESPONSABLE INSCRIPTO
        'monotributo' => 'NI',  // NO MONOTRIBUTO
        'incomeTax' => 'NI',    // NO GANANCIAS
    ];

    private const CORRIENTES = 18;
    private const SALTA = 66;

    private const TAX_CATEGORY_LOCAL_REGISTERED = 1;
    private const TAX_CATEGORY_EXENTO = 5;

    private const TAX_CONDITION_RESPONSABLE_INSCRIPTO = 1;
    private const TAX_CONDITION_NO_INSCRIPTO = 5;

    /** @var EntityManagerInterface */
    public $entityManager;

    /** @var ConfigurationManager */
    public $configurationManager;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(null);
        BusinessDay::enable('Carbon\Carbon');

        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->getMockedConfigurationManager(true, false, false, false);
    }

    /** @test */
    public function they_have_a_province_relation(): void
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        $this->generateWithholdedScenario(
            self::CORRIENTES,
            self::TAX_CATEGORY_LOCAL_REGISTERED,
            self::TAX_CONDITION_NO_INSCRIPTO,
            $transactionDate->copy()->addDay()
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var WithholdingTax[] $withholdingTaxes */
        $withholdingTaxes = $this->entityManager->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);
        $this->assertEquals(self::CORRIENTES, $withholdingTaxes[0]->getProvince()->getId());
    }

    /** @test */
    public function they_have_tax_category_and_tax_condition_relation_for_its_date(): void
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        // One SALTA Merchant
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->generateWithholdedScenario(
            self::CORRIENTES,
            self::TAX_CATEGORY_LOCAL_REGISTERED,
            self::TAX_CONDITION_RESPONSABLE_INSCRIPTO,
            $transactionDate
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        // CHANGE THE SUBSIDIARY CATEGORY AND CONDITION THEN RESET ENTITY MANAGER
        $subsidiary->setTaxCategory($this->entityManager->getReference(TaxCategory::class, self::TAX_CATEGORY_EXENTO));
        $subsidiary->setTaxCondition($this->entityManager->getReference(TaxCondition::class,
            self::TAX_CONDITION_NO_INSCRIPTO));
        $this->entityManager->flush();
        $this->entityManager->clear();

        /* @var WithholdingTax[] $withholdingTaxes */
        $withholdingTaxes = $this->entityManager->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);
        $this->assertEquals(self::TAX_CATEGORY_LOCAL_REGISTERED, $withholdingTaxes[0]->getTaxCategory()->getId());
        $this->assertEquals(self::TAX_CONDITION_RESPONSABLE_INSCRIPTO,
            $withholdingTaxes[0]->getTaxCondition()->getId());
    }

    /** @test */
    public function federal_taxes_doesnt_have_province_relation(): void
    {
        $this->getMockedConfigurationManager(false, true, false, false);
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->day(16);

        $this->generateWithholdedScenario(
            self::CORRIENTES,
            self::TAX_CATEGORY_LOCAL_REGISTERED,
            self::TAX_CONDITION_NO_INSCRIPTO,
            $transactionDate
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var WithholdingTax[] $withholdingTaxes */
        $withholdingTaxes = $this->entityManager->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);
        $this->assertNull($withholdingTaxes[0]->getProvince());
    }

    /** @test */
    public function a_sale_and_its_refund_shouldnt_generate_a_certificate(): void
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        // One SALTA Merchant
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->generateWithholdedScenarioWithRefunds(
            self::CORRIENTES,
            self::TAX_CATEGORY_LOCAL_REGISTERED,
            self::TAX_CONDITION_RESPONSABLE_INSCRIPTO,
            $transactionDate,
            null,
            1,
            1
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var WithholdingTax[] $withholdingTaxes */
        $withholdingTaxes = $this->entityManager->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(0, $withholdingTaxes);
    }

    /** @test */
    public function a_sale_and_its_refund_shouldnt_be_seen_in_the_certificate(): void
    {
        $transactionDate = Carbon::createFromFormat('d/m/Y H:i:s', '5/8/2020 15:30:00');
        $cronDate = $transactionDate->copy()->endOfMonth()->addDay();

        // One SALTA Merchant
        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->generateWithholdedScenarioWithRefunds(
            self::CORRIENTES,
            self::TAX_CATEGORY_LOCAL_REGISTERED,
            self::TAX_CONDITION_RESPONSABLE_INSCRIPTO,
            $transactionDate, null,
            2, 1
        );

        $this->runCommandWithParameters(SendWithholdingTaxesReportTaxAgenciesCommand::NAME, [
            '--date' => $cronDate->format('Y-m-d'),
        ]);

        /* @var WithholdingTax[] $withholdingTaxes */
        $withholdingTaxes = $this->entityManager->getRepository(WithholdingTax::class)->findAll();

        $this->assertCount(1, $withholdingTaxes);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearDirectoryForTest('./tests/../storage/sftp/mpos/Out/');
    }

    public function generateWithholdedScenario(
        $provinceId,
        $taxCategoryId,
        $taxConditionId,
        $transactionDate,
        $depositTransferredAt = null
    ) {
        $director = new SceneBuilderBaseDirector();
        $director->makeCompleteTransactionWithParam(
            $this->sceneBuilder(),
            new ParameterBag([
                'subsidiary.taxCategoryId' => $taxCategoryId,
                'subsidiary.address.provinceId' => $provinceId,
                'transaction.commission' => 0.1,
                'transaction.commissionTax' => 0.5,
                'transaction.amount' => 10000,
                'transaction.availableDate' => $transactionDate,
                'deposit.amount' => 10000,
                'deposit.transferredAt' => $depositTransferredAt,
            ])
        );
        $scene = $this->sceneBuilder()->getResult();
        $currentTestDate = Carbon::getTestNow();
        Carbon::setTestNow($transactionDate);
        $this->buildTaxInformation($scene->getAccount()->getIdFiscal(), self::MOCK_CONTENT_OF_REPORT_SERVICE);
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        Carbon::setTestNow($currentTestDate);

        return $scene->getAccount()->getSubsidiaries()->first();
    }

    public function generateWithholdedScenarioWithRefunds(
        $provinceId,
        $taxCategoryId,
        $taxConditionId,
        $transactionDate,
        $depositTransferredAt = null,
        $transactionCount = 1,
        $refundCount = 0,
        $amount = 5000,
        $commission = 0.5,
        $commissionTax = 0.1
    ) {
        $director = new SceneBuilderBaseDirector();
        $director->makeMultipleTransactionDepositAndRefundsWithParam(
            $this->sceneBuilder(),
            new ParameterBag([
                'subsidiary.taxCategoryId' => $taxCategoryId,
                'subsidiary.address.provinceId' => $provinceId,
                'transaction.commission' => $commission,
                'transaction.commissionTax' => $commissionTax,
                'transaction.amount' => $amount,
                'transaction.availableDate' => $transactionDate,
                'deposit.amount' => 10000,
                'deposit.transferredAt' => $depositTransferredAt,
                'transactions.count' => $transactionCount,
                'refunds.count' => $refundCount,
            ])
        );

        $scene = $this->sceneBuilder()->getResult();
        $currentTestDate = Carbon::getTestNow();
        Carbon::setTestNow($transactionDate);
        $this->buildTaxInformation($scene->getAccount()->getIdFiscal(), self::MOCK_CONTENT_OF_REPORT_SERVICE);
        $withholdingTaxService = self::$container->get(WithholdingTaxService::class);
        $withholdingTaxService->withhold($scene->getSaleBag());
        $this->entityManager->flush();

        Carbon::setTestNow($currentTestDate);

        return $scene->getAccount()->getSubsidiaries()->first();
    }
}
