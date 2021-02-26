<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use GeoPagos\ApiBundle\Enum\TaxConditionAfipRelationEnum;
use GeoPagos\Tests\TestCase;
use GeoPagos\WithholdingTaxBundle\Adapter\TaxInformationAdapter;
use GeoPagos\WithholdingTaxBundle\Model\TaxInformation;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

class TaxInformationAdapterTest extends TestCase
{
    /**
     * @var TaxInformationAdapter
     */
    protected $taxInformationAdapter;
    /**
     * @var GuzzleHttp\Handler\MockHandler
     */
    protected $guzzleHandler;

    public function setUp(): void
    {
        parent::setUp();
        $this->taxInformationAdapter = self::$container->get(TaxInformationAdapter::class);
        $this->guzzleHandler = self::$container->get('guzzle.mocked_handler');
    }

    /**
     * @test
     */
    public function test_empty_reponse_from_identity_checker(): void
    {
        $idFiscal = '1111111';
        $emptyResponse = new Response(200);
        $this->guzzleHandler->append($emptyResponse);
        /* @var $taxInformation TaxInformation */
        $taxInformation = $this->taxInformationAdapter->taxInformation($idFiscal);
        $this->assertEquals($idFiscal, $taxInformation->getIdFiscal());
        $this->assertEquals(TaxConditionAfipRelationEnum::NI, $taxInformation->getTaxCondition());
        $this->assertEquals(TaxConditionAfipRelationEnum::NI, $taxInformation->getIva());
    }

    /** @test */
    public function test_not_found_reponse_from_identity_checker(): void
    {
        $idFiscal = '1111111';
        $emptyResponse = new Response(404);
        $this->guzzleHandler->append(function ($request) {
            return new ClientException('Not Found', $request);
        });
        /* @var $taxInformation TaxInformation */
        $taxInformation = $this->taxInformationAdapter->taxInformation($idFiscal);
        $this->assertEquals($idFiscal, $taxInformation->getIdFiscal());
        $this->assertEquals(TaxConditionAfipRelationEnum::NI, $taxInformation->getTaxCondition());
        $this->assertEquals(TaxConditionAfipRelationEnum::NI, $taxInformation->getIva());
    }

    /** @test */
    public function test_found_reponse_from_identity_checker(): void
    {
        $idFiscal = '1111111';
        $json = <<<EOD
{"id":"$idFiscal","denomination":"LEONCZYK CARLA LUCIANA        ","incomeTax":"NI","iva":"NI","monotributo":"A ","soc":"N","employer":"N","activity":"8"}
EOD;
        $response = new Response(200, [], $json);
        $this->guzzleHandler->append($response);
        /* @var $taxInformation TaxInformation */
        $taxInformation = $this->taxInformationAdapter->taxInformation($idFiscal);
        $this->assertEquals($idFiscal, $taxInformation->getIdFiscal());
        $this->assertEquals(TaxConditionAfipRelationEnum::MT, $taxInformation->getTaxCondition());
        $this->assertEquals(TaxInformation::NI, $taxInformation->getIva());
    }
}
