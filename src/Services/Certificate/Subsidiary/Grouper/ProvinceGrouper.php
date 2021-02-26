<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Grouper;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\SubsidiaryCertificateBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProvinceGrouper
{
    const LOCAL_FILE_EXTENSION = 'pdf';
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var SubsidiaryCertificateBuilderInterface
     */
    private $builder;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function group($builder, $data)
    {
        $this->builder = $builder;
        $groups = [];
        /* @var $withholdingTax WithholdingTax */
        foreach ($data as $withholdingTax) {
            $provinceId = null;
            $province = $withholdingTax->getProvince();
            if ($province) {
                $provinceId = $province->getId();
            }

            if (isset($groups[$provinceId])) {
                $groups[$provinceId]->pushData($withholdingTax);

                continue;
            }

            $settings = $province->getProvinceWithholdingTaxSetting();
            if ($settings && $settings->getWithholdingTaxSystem()) {
                $package = (new Package())
                    ->setProvinceId($provinceId)
                    ->setProvince($province)
                    ->setAttachmentFilename($this->prepareAttachmentFilename($province))
                    ->setLocalFilename($this->prepareLocalFilename($province))
                    ->setData([$withholdingTax])
                    ->setTaxType($this->builder->getCurrentTaxType());
                $groups[$provinceId] = $package;
            }
        }

        return $groups;
    }

    protected function prepareAttachmentFilename(Province $province)
    {
        return sprintf('%s-%s',
            $province->getAcronym(),
            $this->translator->trans('emails_builder.withholding_tax_certificate.filename.'.$this->builder->getCurrentTaxType())
        );
    }

    private function prepareLocalFilename(?Province $province)
    {
        $createRequest = $this->builder->getCreateRequest();
        $idFiscal = $createRequest->getFiscalId();
        $period = $createRequest->getPeriod()->format('Ym');

        return join('-', [$province->getAcronym(), $idFiscal, $period]).'.'.self::LOCAL_FILE_EXTENSION;
    }
}
