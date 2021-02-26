<?php

namespace GeoPagos\WithholdingTaxBundle\Contract;

interface RuleFileParserInterface
{
    public function parse(string $line): RuleLineDtoInterface;

    public function skipFirstLine(): bool;

    public function getTaxTypeForDynamicRule(): int;
}
