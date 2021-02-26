<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\BackOfficeAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;

class WithholdingTaxRulesBackendSectionTest extends TestCase
{
    private const BUENOS_AIRES = 6;

    use BackOfficeAuthenticationTrait;

    use FactoriesTrait;

    /**
     * @var WithholdingTaxRule
     */
    private $withholdingTaxRuleRepository;
    /**
     * @var WithholdingTaxHardRule
     */
    private $withholdingTaxHardRule;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get('doctrine')->getManager();

        $this->withholdingTaxRuleRepository = $entityManager->getRepository(WithholdingTaxRule::class);
        $this->withholdingTaxHardRule = $entityManager->getRepository(WithholdingTaxHardRule::class);
    }

    /** @test */
    public function shows_withholding_tax_rules_sections()
    {
        $this->loginIntoBackoffice($this->client);

        $basAS = $this->withholdingTaxRuleRepository->findOneBy([
            'province' => self::BUENOS_AIRES,
        ]);

        $this->assertNotEmpty($basAS);

        $this->client->request('GET', '/withholding-tax-rule/');

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', "/withholding-tax-rule/{$basAS->getId()}", [
            'withholdingTaxRuleId' => $basAS->getId(),
        ]);

        $this->assertResponseIsSuccessful();
    }

    /** @test * */
    public function shows_withholding_tax_simple_rules_section()
    {
        $this->loginIntoBackoffice($this->client);

        $basAS = $this->withholdingTaxRuleRepository->findOneBy([
            'province' => self::BUENOS_AIRES,
        ]);

        $this->assertNotEmpty($basAS);

        $this->client->request('GET', '/withholding-tax-rule/');

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', "/withholding-tax-rule/{$basAS->getId()}/simple-rules/");

        $this->assertResponseIsSuccessful();
    }

    /** @test * */
    public function shows_withholding_tax_hard_rules_section()
    {
        $this->loginIntoBackoffice($this->client);

        /** @var WithholdingTaxRule $basAS */
        $basAS = $this->withholdingTaxRuleRepository->findOneBy([
            'province' => self::BUENOS_AIRES,
        ]);

        $this->assertNotEmpty($basAS);

        $this->client->request('GET', '/withholding-tax-rule/');

        $this->assertResponseIsSuccessful();
    }

    /** @test * */
    public function it_can_create_a_withholding_tax_hard_rule()
    {
        /** @var WithholdingTaxRule $BsAsWithholdingTaxRule */
        $BsAsWithholdingTaxRule = $this->withholdingTaxRuleRepository->findOneBy([
            'province' => self::BUENOS_AIRES,
        ]);

        $this->client->request('GET', "/withholding-tax-rule/{$BsAsWithholdingTaxRule->getId()}/hard-rules/");

        $this->assertResponseIsSuccessful();

        /** @var WithholdingTaxHardRule $basAS */
        $basASHardRule = $this->withholdingTaxHardRule->findOneBy([
            'withholdingTaxRule' => $BsAsWithholdingTaxRule->getID(),
        ]);

        $this->client->request('POST', "/withholding-tax-rule/{$BsAsWithholdingTaxRule->getId()}/hard-rules/new", [
            'withholding_tax_hard_rule' => [
                'verification_date' => '01-01-2021',
                'rate' => '6.66',
                'minimun_amount' => '666',
                'rule' => $BsAsWithholdingTaxRule->getId(),
            ],
        ]);

        $this->assertResponseRedirects();
    }

    /** @test * */
    public function shows_withholding_tax_dynamic_rule_province_rate_section()
    {
        $this->loginIntoBackoffice($this->client);

        $basAS = $this->withholdingTaxRuleRepository->findOneBy([
            'province' => self::BUENOS_AIRES,
        ]);

        $this->client->request('GET', "/withholding-tax-rule/{$basAS->getId()}/tax-registry/");

        $this->assertResponseIsSuccessful();
    }

    /** @test * */
    public function it_can_create_a_withholding_tax_dynamic_rule_proving_rate()
    {
        $this->loginIntoBackoffice($this->client);

        $basAS = $this->withholdingTaxRuleRepository->findOneBy([
            'province' => self::BUENOS_AIRES,
        ]);

        $this->client->request('POST', "/withholding-tax-rule/{$basAS->getId()}/tax-registry/new", [
            'withholding_tax_dynamic_rule_province_rate' => [
                'province' => $basAS->getId(),
                'externalId' => '66',
                'rate' => '6.6',
            ],
        ]);

        $this->assertResponseRedirects();

        $this->client->request('GET', "/withholding-tax-rule/{$basAS->getId()}/tax-registry/1");

        $this->assertResponseIsSuccessful();
    }
}
