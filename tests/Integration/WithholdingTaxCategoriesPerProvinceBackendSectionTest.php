<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\City;
use GeoPagos\ApiBundle\Entity\Country;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Role;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\BackOfficeAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;

class WithholdingTaxCategoriesPerProvinceBackendSectionTest extends TestCase
{
    use BackOfficeAuthenticationTrait;

    use FactoriesTrait;

    use WithholdingMocks;

    private const ARGENTINA = 10;
    private const BUENOS_AIRES = 6;
    private const MAR_DEL_PLATA = 4629;
    private const INSCRIPTO_LOCAL = 1;
    private const RESPONSABLE_INSCRIPTO = 1;
    private const ROLE_ADMIN = 3;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::$container->get(EntityManagerInterface::class);
    }

    /** @test */
    public function it_shouldnt_show_categories_per_province_form_in_new_action_if_feature_is_disabled()
    {
        $this->getMockedConfigurationManager(true, false, false, false);

        $this->loginIntoBackoffice($this->client);

        $account = $this->buildAccount();
        $this->assertNotEmpty($account);

        $merchantId = $account->getId();
        $crawler = $this->client->request('GET', "/merchants/{$merchantId}/subsidiaries/new");
        $this->assertResponseIsSuccessful();

        $crawler = $crawler->filter('#subsidiary_withholdingTaxCategoriesPerProvince');
        $this->assertNull($crawler->getNode(0));
    }

    /** @test */
    public function it_should_show_categories_per_province_form_in_new_action_if_feature_is_disabled()
    {
        $this->getMockedConfigurationManager(true, false, false, true);

        $this->loginIntoBackoffice($this->client);

        $account = $this->buildAccount();
        $this->assertNotEmpty($account);

        $merchantId = $account->getId();
        $crawler = $this->client->request('GET', "/merchants/{$merchantId}/subsidiaries/new");
        $this->assertResponseIsSuccessful();

        $crawler = $crawler->filter('#subsidiary_withholdingTaxCategoriesPerProvince');
        $this->assertNotNull($crawler->getNode(0));
    }

    /** @test */
    public function it_shouldnt_show_categories_per_province_form_in_edit_action_if_feature_is_disabled()
    {
        $this->loginIntoBackoffice($this->client);

        $account = $this->buildAccount();
        $this->assertNotEmpty($account);

        $merchantId = $account->getId();
        $subsidiaryId = $account->getSubsidiaries()->first()->getId();
        $crawler = $this->client->request('GET', "/merchants/{$merchantId}/subsidiaries/{$subsidiaryId}/edit");
        $this->assertResponseIsSuccessful();

        $crawler = $crawler->filter('#subsidiary_withholdingTaxCategoriesPerProvince');
        $this->assertNull($crawler->getNode(0));
    }

    private function buildAccount(): Account
    {
        /** @var Country $country */
        $country = $this->em->getRepository(Country::class)->find(self::ARGENTINA);
        /** @var Province $province */
        $province = $this->em->getRepository(Province::class)->find(self::BUENOS_AIRES);
        /** @var City $city */
        $city = $this->em->getRepository(City::class)->find(self::MAR_DEL_PLATA);
        /** @var TaxCategory $tax_category */
        $taxCategory = $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL);
        /** @var Role $role */
        $role = $this->em->getRepository(Role::class)->find(self::ROLE_ADMIN);
        /** @var TaxCondition $taxCondition */
        $taxCondition = $this->em->getRepository(TaxCondition::class)->find(self::RESPONSABLE_INSCRIPTO);

        /** @var Address $address */
        $address = $this->factory->instance(Address::class, [
            'country' => $country,
            'province' => $province,
            'city' => $city,
        ]);
        $this->em->persist($address);

        /** @var Account $account */
        $account = $this->factory->create(Account::class, ['country' => $country]);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class,
            [
                'account' => $account,
                'address' => $address,
                'taxCondition' => $taxCondition,
                'taxCategory' => $taxCategory,
            ]
        );

        $account->addSubsidiary($subsidiary);
        /** @var User $user */
        $user = $this->factory->create(User::class, [
            'account' => $account,
            'role' => $role,
            'defaultSubsidiary' => $subsidiary,
        ]);

        $user->addSubsidiary($subsidiary);
        $this->em->persist($user);

        $this->em->flush();

        return $account;
    }
}
