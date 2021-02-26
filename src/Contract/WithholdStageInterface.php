<?php

namespace GeoPagos\WithholdingTaxBundle\Contract;

use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

interface WithholdStageInterface
{
    public function process(
        SaleBag $saleBag,
        Transaction $transaction
    ): bool;
}
