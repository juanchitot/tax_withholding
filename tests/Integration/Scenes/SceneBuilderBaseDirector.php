<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes;

use GeoPagos\ApiBundle\Entity\Transaction;
use Symfony\Component\HttpFoundation\ParameterBag;

class SceneBuilderBaseDirector
{
    public function makeCompleteTransactionWithParam(
        SceneBuilder $sceneBuilder,
        ParameterBag $param
    ) {
        $sceneBuilder->reset()
            ->buildAccount($param)
            ->buildSubsidiary($param)
            ->buildDeposit($param)
            ->buildTransaction($param)
            ->buildBag($param);
    }

    public function makeMultipleTransactionDepositWithParam(
        SceneBuilder $sceneBuilder,
        ParameterBag $param
    ) {
        $sceneBuilder->reset()
            ->buildAccount($param)
            ->buildSubsidiary($param)
            ->buildDeposit($param);

        $transactionCount = $param->get('transactions.count', 1);
        $transactionDetails = $param->get('transactions.details', []);

        for ($i = 0; $i < $transactionCount; ++$i) {
            if (isset($transactionDetails[$i])) {
                $clonedParameterBag = new ParameterBag($param->all());
                $clonedParameterBag->replace($transactionDetails[$i]);
                $sceneBuilder->buildTransaction($clonedParameterBag);
            } else {
                $sceneBuilder->buildTransaction($param);
            }
        }
        $sceneBuilder->buildBag($param);
    }

    public function makeMultipleTransactionDepositAndRefundsWithParam(
        SceneBuilder $sceneBuilder,
        ParameterBag $param
    ) {
        $this->makeMultipleTransactionDepositWithParam($sceneBuilder, $param);
        $refundsCount = $param->get('refunds.count', 1);
        $refundsDetails = $param->get('refunds.details', []);

        for ($i = 0; $i < $refundsCount; ++$i) {
            if (isset($refundsDetails[$i])) {
                $clonedParameterBag = new ParameterBag($param->all());
                $clonedParameterBag->replace($refundsDetails[$i]);
                $clonedParameterBag->set('transaction.typeId', Transaction::TYPE_REFUND);
                $sceneBuilder->buildTransaction($clonedParameterBag);
            } else {
                $param->set('transaction.typeId', Transaction::TYPE_REFUND);
                $sceneBuilder->buildTransaction($param);
            }
        }
        $sceneBuilder->buildBag($param);
    }
}
