<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdableOperationInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdStageInterface;

class WithholdingProcessPipe
{
    protected $stages = [];

    public function pushStage(WithholdStageInterface $stage)
    {
        $this->stages[] = $stage;
    }

    /**
     * @param $operation
     * @param $income
     * @todo: type the $operation to WithholdableOperationInterface
     */
    public function pipe($operation, $income): bool
    {
        $processed = false;
        /* @var $currentStage WithholdStageInterface */
        foreach ($this->stages as $currentStage) {
            $processed |= $currentStage->process($income, $operation);
        }

        return $processed;
    }
}
