<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\FileManagementBundle\Tests\TestHelperTrait;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\BackOfficeAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRuleRepository;
use GeoPagos\WithholdingTaxBundle\Services\EnabledTaxesInProjectTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WithholdingTaxRulesV2ControllerTest extends TestCase
{
    use BackOfficeAuthenticationTrait;

    use FactoriesTrait;

    use TestHelperTrait;

    use EnabledTaxesInProjectTrait;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var WithholdingTaxRuleRepository */
    private $rulesRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = self::$container->get(UrlGeneratorInterface::class);
        $this->rulesRepository = self::$container->get(WithholdingTaxRuleRepository::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->loginIntoBackoffice($this->client);
    }

    /** @test */
    public function a_cookie_is_created_when_user_visits_new_section(): void
    {
        $this->client->request(
            'GET',
            $this->router->generate('withholding_tax_rule_backoffice_section_v2')
        );

        $cookie = $this->client->getCookieJar()->get('wtr_version');
        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals($cookie->getValue(), 'v2');
        $this->assertNotEquals($cookie->getValue(), 'v1');
    }

    /** @test */
    public function a_cookie_is_created_when_user_visits_old_section(): void
    {
        $this->client->request(
            'GET',
            $this->router->generate('withholding_tax_rule_backoffice_section')
        );

        $cookie = $this->client->getCookieJar()->get('wtr_version');
        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals($cookie->getValue(), 'v1');
        $this->assertNotEquals($cookie->getValue(), 'v2');
    }

    /** @test */
    public function it_can_list_enabled_rules(): void
    {
        $configurationManager = $this->injectConfigurationManager();
        $crawler = $this->client->request(
            'GET',
            $this->router->generate('withholding_tax_rule_backoffice_section_v2')
        );

        $this->assertResponseStatusCodeSame(200);

        $enabledRules = $this->getEnabledRules($configurationManager);

        $crawler = $crawler->filter('.masonry div.panel');
        $this->assertEquals(sizeof($enabledRules), $crawler->count());
    }

    /** @test */
    public function it_cant_list_disabled_rules(): void
    {
        $this->entityManager->getFilters()->enable('enabled_rules');
        $configurationManager = $this->injectConfigurationManager();
        $enabledRules = $this->getEnabledRules($configurationManager);

        $enabledRules[0]->setEnabled(false);
        $this->entityManager->persist($enabledRules[0]);
        $this->entityManager->flush();

        $crawler = $this->client->request(
            'GET',
            $this->router->generate('withholding_tax_rule_backoffice_section_v2')
        );

        $this->assertResponseStatusCodeSame(200);

        $crawler = $crawler->filter('.masonry div.panel');
        $this->assertEquals(sizeof($enabledRules) - 1, $crawler->count());
    }

    /** @test */
    public function province_filter_must_contain_only_enabled_provinces(): void
    {
        $configurationManager = $this->injectConfigurationManager();
        $crawler = $this->client->request(
            'GET',
            $this->router->generate('withholding_tax_rule_backoffice_section_v2')
        );

        $this->assertResponseStatusCodeSame(200);

        $crawler = $crawler->filter("select#province-select option:not([value='0'])");
        $this->assertEquals(
            $this->getEnabledProvincesCount($configurationManager),
            $crawler->count()
        );
    }

    /**
     * @return WithholdingTaxRule[]
     */
    private function getEnabledRules(ConfigurationManager $configurationManager): array
    {
        return $this->rulesRepository->getRulesSortedByTaxTypeAndProvince(
            $this->getEnabledTaxesInProject($configurationManager)
        );
    }

    private function injectConfigurationManager(): ConfigurationManager
    {
        $configurationManager = $this->getMockedConfigurationManager([
            'process_iibb' => true,
            'process_vat' => true,
            'process_income_tax' => true,
        ]);
        self::$container->set(ConfigurationManager::class, $configurationManager);

        return $configurationManager;
    }

    private function getEnabledProvincesCount(ConfigurationManager $configurationManager): int
    {
        $uniqueProvinces = [];
        $enabledRules = $this->getEnabledRules($configurationManager);
        foreach ($enabledRules as $enabledRule) {
            if (null !== $enabledRule->getProvince() &&
                !in_array($enabledRule->getProvince()->getId(), $uniqueProvinces)) {
                $uniqueProvinces[] = $enabledRule->getProvince()->getId();
            }
        }

        return count($uniqueProvinces);
    }
}
