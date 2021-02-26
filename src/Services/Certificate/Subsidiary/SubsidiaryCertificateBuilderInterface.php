<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\CreateRequest;

interface SubsidiaryCertificateBuilderInterface
{
    public function setTax(WithholdableTaxInterface $tax);

    public function build(CreateRequest $createRequest): array;

    public function getCurrentTaxType(): string;

    public function setGroupers(array $groupers);

    public function setFormatters(array $formatters);

    public function getCreateRequest(): CreateRequest;
}
