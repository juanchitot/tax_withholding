<?php

namespace GeoPagos\WithholdingTaxBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Repository\AccountRepository;
use GeoPagos\ApiBundle\Repository\SubsidiaryRepository;
use GeoPagos\ApiBundle\Repository\TaxConditionRepository;
use GeoPagos\WithholdingTaxBundle\Events\TaxInformationRequested;
use GeoPagos\WithholdingTaxBundle\Model\TaxInformation;
use Psr\Log\LoggerInterface;

class SubsidiaryTaxInformationUpdateListener
{
    const TAX_INFORMATION_LISTENER = 'TAX_INFORMATION_LISTENER';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SubsidiaryRepository
     */
    private $subsidiaryRepository;
    /**
     * @var TaxConditionRepository
     */
    private $taxConditionRepository;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * SubsidiaryTaxInformationUpdateListener constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        AccountRepository $accountRepository,
        TaxConditionRepository $taxConditionRepository
    ) {
        $this->logger = $logger;
        $this->taxConditionRepository = $taxConditionRepository;
        $this->em = $em;
        $this->accountRepository = $accountRepository;
    }

    public function onTaxInformationRequested(TaxInformationRequested $taxInformationRequested)
    {
        $taxInformation = $taxInformationRequested->getTaxInformation();

        try {
            $this->updateAccountIdFiscal($taxInformation);
        } catch (\Throwable $e) {
            $this->logger->error(self::TAX_INFORMATION_LISTENER, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function updateAccountIdFiscal(TaxInformation $taxInformation)
    {
        /* @var $account Account */
        $account = $this->accountRepository->findOneBy(['idFiscal' => $taxInformation->getIdFiscal()]);
        if ($account) {
            $taxCondition = $this->taxConditionRepository->find($taxInformation->getTaxCondition());
            /* @var $subsidiary Subsidiary */
            foreach ($account->getSubsidiaries() as $subsidiary) {
                $subsidiary->setTaxCondition($taxCondition);
            }
            $this->em->flush($account);
        } else {
            $this->logger->error(self::TAX_INFORMATION_LISTENER, [
                'message' => 'Id Fiscal '.$taxInformation->getIdFiscal().' not found ',
            ]);
        }
    }
}
