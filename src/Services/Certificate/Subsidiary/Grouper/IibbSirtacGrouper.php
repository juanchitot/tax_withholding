<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Grouper;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\SubsidiaryCertificateBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class IibbSirtacGrouper
{
    const LOCAL_FILE_EXTENSION = 'pdf';
    /**
     * @var SubsidiaryCertificateBuilderInterface
     */
    private $builder;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function group($builder, $data)
    {
        $this->builder = $builder;
        $groups = [];
        /* @var $withholdingTax SirtacDeclaration */
        foreach ($data as $withholdingTax) {
            $province = $withholdingTax->getProvince();
            $provinceId = $province->getId();
            $groupKey = sprintf('%d-%d', $provinceId, $withholdingTax->getTaxConcept()->getId());

            if (isset($groups[$groupKey])) {
                $groups[$groupKey]->pushData($withholdingTax);

                continue;
            }

            $settings = $province->getProvinceWithholdingTaxSetting();
            if ($settings && $settings->getWithholdingTaxSystem()) {
                $package = (new Package())
                    ->setProvinceId($provinceId)
                    ->setAttachmentFilename($this->prepareAttachmentFilename($province))
                    ->setLocalFilename($this->prepareLocalFilename($province))
                    ->setData([$withholdingTax])
                    ->setTaxType($this->builder->getCurrentTaxType());
                $groups[$groupKey] = $package;
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

    private function prepareLocalFilename(Province $province)
    {
        $createRequest = $this->builder->getCreateRequest();
        $idFiscal = $createRequest->getFiscalId();
        $period = $createRequest->getPeriod()->format('Ym');

        return join('-', [$province->getAcronym(), $idFiscal, $period]).'.'.self::LOCAL_FILE_EXTENSION;
    }
}
