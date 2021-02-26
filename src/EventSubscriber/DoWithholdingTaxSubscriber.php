<?php

namespace GeoPagos\WithholdingTaxBundle\EventSubscriber;

use Exception;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Adapter\TaxInformationAdapter;
use GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\WithholdingTaxRequestedAdapterCreator;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Deposit\AddSaleToDepositRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Deposit\DepositTransferRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\DigitalAccount\DebitAdjustmentRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\DirectPayment\DirectPaymentRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\WithholdingTaxRequested;
use GeoPagos\WithholdingTaxBundle\Exceptions\EmptyTransactionException;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DoWithholdingTaxSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var WithholdingTaxService */
    private $withholdingTaxService;

    /** @var WithholdingTaxRequestedAdapterCreator */
    private $adapterCreator;

    /** @var TaxInformationAdapter */
    private $taxInformationAdapter;

    /** @var ConfigurationManagerInterface */
    private $configurationManager;

    public function __construct(
        LoggerInterface $logger,
        WithholdingTaxService $withholdingTaxService,
        WithholdingTaxRequestedAdapterCreator $adapterCreator,
        TaxInformationAdapter $taxInformationAdapter,
        ConfigurationManagerInterface $configurationManager
    ) {
        $this->logger = $logger;
        $this->withholdingTaxService = $withholdingTaxService;
        $this->adapterCreator = $adapterCreator;
        $this->taxInformationAdapter = $taxInformationAdapter;
        $this->configurationManager = $configurationManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AddSaleToDepositRequested::class => 'doWithholdingTax',
            DepositTransferRequested::class => 'doWithholdingTax',
            DirectPaymentRequested::class => 'doWithholdingTax',
            DebitAdjustmentRequested::class => 'doWithholdingTax',
        ];
    }

    /**
     * @throws EmptyTransactionException
     */
    public function doWithholdingTax(WithholdingTaxRequested $request): void
    {
        $this->logger->info('[WITHHOLDING-TAX] Start do Withhold');

        try {
            $adapter = $this->adapterCreator->getAdapter($request);
            $saleBag = $adapter->adaptRequest($request);

            if (!$this->configurationManager->isFeatureEnabled('withholding_tax.skip_tax_identity_checker_validation')) {
                $this->taxInformationAdapter->taxInformation($saleBag->getIdFiscal());
            }

            $saleBag = $this->withholdingTaxService->withhold($saleBag);
            $response = $adapter->adaptResponse($saleBag);
            $request->setResponse($response);
        } catch (EmptyTransactionException | Exception $e) {
            $this->logger->error('[WITHHOLDING-TAX] Process failed: '.$e->getMessage());

            throw $e;
        }

        $this->logger->info('[WITHHOLDING-TAX] End do Withhold');
    }
}
