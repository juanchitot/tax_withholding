<?php

namespace GeoPagos\WithholdingTaxBundle\Adapter;

use GeoPagos\ApiBundle\Enum\TaxConditionAfipRelationEnum;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\WithholdingTaxBundle\Events\TaxInformationRequested;
use GeoPagos\WithholdingTaxBundle\Model\TaxInformation;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaxInformationAdapter
{
    const NI = 'NI';

    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var LoggerInterface
     */
    private $logger;
    private $taxIdentityChecker;
    /**
     * @var ConfigurationManager
     */
    private $configurationManager;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ConfigurationManager $configurationManager,
        EventDispatcherInterface $eventDispatcher,
        ClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->configurationManager = $configurationManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * @return TaxInformation
     */
    public function taxInformation(string $idFiscal): ?TaxInformation
    {
        $url = $this->generateRequestUrl($idFiscal);

        try {
            $request = new \GuzzleHttp\Psr7\Request('GET', $url);
            $response = $this->httpClient->send($request, [RequestOptions::HTTP_ERRORS => true]);
            $response = json_decode($response->getBody()->getContents(), false);
            if (empty($response)) {
                throw new  ClientException('Empty response', $request);
            }
            $information = TaxInformation::createFromResponse($response);
            $event = new TaxInformationRequested($information);
            $this->eventDispatcher->dispatch($event, 'identity_checker.tax_information_requested');

            return $information;
        } catch (ClientException $e) { // Case when idFiscal is not found.
            $this->logger->alert('[tax-identity.checker]', [
                'endpoint' => $url,
                'response' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            $information = new TaxInformation();
            $information->setIdFiscal($idFiscal);
            $information->setIncomeTax(TaxInformation::NI);
            $information->setTaxCondition(TaxConditionAfipRelationEnum::NI);
            $information->setIva(TaxConditionAfipRelationEnum::NI);
            $event = new TaxInformationRequested($information);
            $this->eventDispatcher->dispatch($event, 'identity_checker.tax_information_requested');

            return $information;
        } catch (\Throwable  $e) {
            $this->logger->alert('[tax-identity.checker]', [
                'endpoint' => $url,
                'response' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }

        return null;
    }

    /**
     * @param $idFiscal
     *
     * @return string
     */
    private function generateRequestUrl($idFiscal)
    {
        $this->taxIdentityChecker = $this->taxIdentityChecker ?? $this->configurationManager->get('api_tax-identity-checker_url');

        return $this->taxIdentityChecker.'person/'.$idFiscal;
    }
}
