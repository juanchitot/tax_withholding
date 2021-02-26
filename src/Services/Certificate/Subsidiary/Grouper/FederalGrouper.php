<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Grouper;

use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use Symfony\Contracts\Translation\TranslatorInterface;

class FederalGrouper
{
    const LOCAL_FILE_EXTENSION = 'pdf';
    /**
     * @var TranslatorInterface
     */
    private $translator;
    private $builder;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function group($builder, $data)
    {
        $this->builder = $builder;
        $packages = [];
        if (count($data)) {
            $packages[] = (new Package())
                ->setAttachmentFilename($this->translator->trans('emails_builder.withholding_tax_certificate.filename.'.$builder->getCurrentTaxType()))
                ->setLocalFilename($this->prepareLocalFilename())
                ->setTaxType($builder->getCurrentTaxType())
                ->setData($data);
        }

        return $packages;
    }

    private function prepareLocalFilename()
    {
        $createRequest = $this->builder->getCreateRequest();
        $idFiscal = $createRequest->getFiscalId();
        $period = $createRequest->getPeriod()->format('Ym');

        return join('-', [$idFiscal, $period]).'.'.self::LOCAL_FILE_EXTENSION;
    }
}
