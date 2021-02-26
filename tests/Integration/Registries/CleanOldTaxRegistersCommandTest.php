<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Registries;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\CleanOldTaxRegistersCommand;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;

class CleanOldTaxRegistersCommandTest extends TestCase
{
    use FactoriesTrait;

    const ID_FISCAL_NOT_FOUND_IN_ANY_ACCOUNT = '10101010';

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
        BusinessDay::enable('Carbon\Carbon');

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    /** @test */
    public function it_clean_old_registries(): void
    {
        $this->createDynamicRule(
            self::ID_FISCAL_NOT_FOUND_IN_ANY_ACCOUNT,
            Carbon::now()->startOfMonth()->subMonth()->format('m-Y')
        );

        $this->entityManager->clear();

        $this->runCommandWithParameters(CleanOldTaxRegistersCommand::getDefaultName(), []);

        $total = $this->entityManager->getRepository(WithholdingTaxDynamicRule::class)->findAll();

        $this->assertCount(0, $total);
    }

    /** @test */
    public function it_doesnt_clean_old_registries_from_accounts(): void
    {
        /** @var Account $account */
        $account = $this->factory->create(Account::class);

        $this->createDynamicRule(
            $account->getIdFiscal(),
            Carbon::now()->startOfMonth()->subMonth()->format('m-Y')
        );

        $this->entityManager->clear();

        $this->runCommandWithParameters(CleanOldTaxRegistersCommand::getDefaultName(), []);

        $total = $this->entityManager->getRepository(WithholdingTaxDynamicRule::class)->findAll();

        $this->assertCount(1, $total);
    }

    /** @test */
    public function it_doesnt_clean_registries_from_this_month_even_if_we_do_not_have_that_account(): void
    {
        /** @var Account $account */
        $account = $this->factory->create(Account::class);

        $this->createDynamicRule(
            $account->getIdFiscal(),
            Carbon::now()->startOfMonth()->format('m-Y')
        );
        $this->createDynamicRule(
            self::ID_FISCAL_NOT_FOUND_IN_ANY_ACCOUNT,
            Carbon::now()->startOfMonth()->format('m-Y')
        );

        $this->entityManager->clear();

        $this->runCommandWithParameters(CleanOldTaxRegistersCommand::getDefaultName(), []);

        $total = $this->entityManager->getRepository(WithholdingTaxDynamicRule::class)->findAll();

        $this->assertCount(2, $total);
    }

    /** @test */
    public function it_doesnt_clean_registries_from_next_even_if_we_do_not_have_that_account(): void
    {
        /** @var Account $account */
        $account = $this->factory->create(Account::class);

        $this->createDynamicRule(
            $account->getIdFiscal(),
            Carbon::now()->startOfMonth()->addMonth()->format('m-Y')
        );
        $this->createDynamicRule(
            self::ID_FISCAL_NOT_FOUND_IN_ANY_ACCOUNT,
            Carbon::now()->startOfMonth()->addMonth()->format('m-Y')
        );

        $this->entityManager->clear();

        $this->runCommandWithParameters(CleanOldTaxRegistersCommand::getDefaultName(), []);

        $total = $this->entityManager->getRepository(WithholdingTaxDynamicRule::class)->findAll();

        $this->assertCount(2, $total);
    }

    protected function createDynamicRule($idFiscal, $monthYear)
    {
        return $this->factory->create(WithholdingTaxDynamicRule::class,
            [
                'id_fiscal' => $idFiscal,
                'month_year' => $monthYear,
            ]
        );
    }
}
