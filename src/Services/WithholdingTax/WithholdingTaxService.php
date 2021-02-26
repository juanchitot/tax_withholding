<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

class WithholdingTaxService
{
    const NI = 'NI';

    /** @var EntityManagerInterface */
    private $em;

    /** @var WithholdingProcessPipe */
    private $withholdingProcessPipe;

    public function __construct(
        EntityManagerInterface $em,
        WithholdingProcessPipe $withholdingProcessPipe
    ) {
        $this->em = $em;
        $this->withholdingProcessPipe = $withholdingProcessPipe;
    }

    public function withhold(SaleBag $saleBag)
    {
        $netAmount = 0;
        /** @var Transaction $transaction */
        foreach ($saleBag->getTransactions()->toArray() as $transaction) {
            if ($transaction->isNotAnAdjustmentTransaction()) {
                if ($this->withholdingProcessPipe->pipe($transaction, $saleBag)) {
                    $transaction->setBalanceAmount();
                    $this->em->persist($transaction);
                }
            }

            $ba = $transaction->getBalanceAmount();

            if (!$transaction->isCredit()) {
                $ba = (-1) * $ba;
            }

            $netAmount += $ba;
        }
        $saleBag->setNetAmount($netAmount);

        return $saleBag;
    }
}
