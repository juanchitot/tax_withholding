<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Enum\TaxConditionAfipRelationEnum;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Adapter\TaxInformationAdapter;
use GuzzleHttp\Psr7\Response;

class SubsidiaryTaxInformationUpdatedTest extends TestCase
{
    private const TEST_ID_FISCAL = '111111';
    use FactoriesTrait;

    /** @var TaxInformationAdapter */
    protected $taxInformationAdapter;
    /** @var GuzzleHttp\Handler\MockHandler */
    protected $guzzleHandler;
    /** @var EntityManagerInterface */
    private $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->taxInformationAdapter = self::$container->get(TaxInformationAdapter::class);
        $this->guzzleHandler = self::$container->get('guzzle.mocked_handler');

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    public function cases(): array
    {
        return [
            [
                'http_response' => new Response(200),
                'original_tax_condition_id' => TaxConditionAfipRelationEnum::AC,
                'expected_tax_condition_id' => TaxConditionAfipRelationEnum::NI,
            ],
            [
                'http_response' => new Response(404),
                'original_tax_condition_id' => TaxConditionAfipRelationEnum::AC,
                'expected_tax_condition_id' => TaxConditionAfipRelationEnum::NI,
            ],
            [
                'http_response' => new Response(200, [],
                    '{"id":"'.self::TEST_ID_FISCAL.'","denomination":"LEONCZYK CARLA LUCIANA        ","incomeTax":"NI","iva":"AC","monotributo":"NI","soc":"N","employer":"N","activity":"8"}'
                ),
                'original_tax_condition_id' => TaxConditionAfipRelationEnum::EX,
                'expected_tax_condition_id' => TaxConditionAfipRelationEnum::AC,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider cases
     */
    public function listener_update_subsidiary_tax_condition($httpResponse, $originalTaxCondition, $expectedTaxCondition): void
    {
        /** @var TaxCondition $taxConditionOriginal */
        $taxConditionOriginal = $this->entityManager->getRepository(TaxCondition::class)->find($originalTaxCondition);

        /** @var Subsidiary $subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $this->factory->create(Account::class,
                ['idFiscal' => self::TEST_ID_FISCAL]
            ),
        ]);
        $subsidiary->setTaxCondition($taxConditionOriginal);
        $this->entityManager->persist($subsidiary);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->guzzleHandler->append($httpResponse);

        $taxInformation = $this->taxInformationAdapter->taxInformation(self::TEST_ID_FISCAL);

        $account = $this->entityManager->getRepository(Account::class)->findOneBy(['idFiscal' => self::TEST_ID_FISCAL]);
        $subsidiary = $account->getSubsidiaries()[0];

        $this->assertEquals(self::TEST_ID_FISCAL, $subsidiary->getAccount()->getIdFiscal());
        $this->assertEquals($expectedTaxCondition, $taxInformation->getTaxCondition());
        $this->assertEquals($expectedTaxCondition, $subsidiary->getTaxCondition()->getId());
    }
}
