<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Repository;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\Country;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;

class WithholdingTaxRepositoryTest extends TestCase
{
    use FactoriesTrait, WithholdingMocks;

    private const COUNTRY_ID = 10;
    private const BUENOS_AIRES = 6;
    private const INSCRIPTO_LOCAL = 1;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->loadFactories(self::$container->get('doctrine')->getManager());

        Carbon::setTestNow();

        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /** @test */
    public function it_must_get_all_withholding_tax_with_created_status_before_end_date_if_asked(): void
    {
        $account = $this->factory->create(Account::class);

        $address = $this->factory->create(Address::class, [
            'country' => $this->em->getRepository(Country::class)->find(self::COUNTRY_ID),
            'province' => $this->em->getRepository(Province::class)->find(self::BUENOS_AIRES),
        ]);

        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $account,
            'address' => $address,
            'taxCategory' => $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth()->subMonths(3),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth()->subMonths(2),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth()->subMonth(),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth(),
        ]);

        $month = Carbon::now()->startOfMonth()->subMonth()->format('Ym');

        $withholdingTaxes = $this->em
            ->getRepository(WithholdingTax::class)
            ->findWithActiveSubsidiaryBy(
                $month,
                true,
                [
                    'status' => WithholdingTaxStatus::CREATED,
                ]
            );

        $this->assertCount(3, $withholdingTaxes);
    }

    /** @test */
    public function it_must_get_withholding_tax_only_from_last_month_if_asked(): void
    {
        $account = $this->factory->create(Account::class);

        $address = $this->factory->create(Address::class, [
            'country' => $this->em->getRepository(Country::class)->find(self::COUNTRY_ID),
            'province' => $this->em->getRepository(Province::class)->find(self::BUENOS_AIRES),
        ]);

        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $account,
            'address' => $address,
            'taxCategory' => $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth()->subMonths(3),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth()->subMonths(2),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth()->subMonth(),
        ]);

        $this->factory->create(WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth(),
        ]);

        $month = Carbon::now()->startOfMonth()->subMonth()->format('Ym');

        $withholdingTaxes = $this->em
            ->getRepository(WithholdingTax::class)
            ->findWithActiveSubsidiaryBy(
                $month,
                false,
                [
                    'status' => WithholdingTaxStatus::CREATED,
                ]
            );

        $this->assertCount(1, $withholdingTaxes);
    }

    /** @test */
    public function it_should_find_enabled_rules(): void
    {
        $this->em->getFilters()->enable('enabled_rules');
        $rulesRepository = $this->em->getRepository(WithholdingTaxRule::class);

        $this->factory->create(WithholdingTaxRule::class, [
            'type' => self::$fakeTaxType,
        ]);

        /** @var WithholdingTaxRule $wtr */
        $wtr = $this->factory->create(WithholdingTaxRule::class, [
            'type' => self::$anotherFakeTaxType,
        ]);

        $wtr->setEnabled(false);

        $this->em->persist($wtr);
        $this->em->flush();

        $rules = $rulesRepository->getRulesSortedByTaxTypeAndProvince([
            self::$fakeTaxType,
            self::$anotherFakeTaxType,
        ]);
        $this->assertCount(1, $rules);
    }
}
