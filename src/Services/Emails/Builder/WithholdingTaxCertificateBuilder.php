<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Emails\Builder;

use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;

class WithholdingTaxCertificateBuilder extends WithholdingTaxAbstractEmailBuilder implements WithholdingTaxCertificateBuilderInterface
{
    /** @var WithholdingTax */
    private $withholdingTax;

    /** @var ProvinceWithholdingTaxSetting */
    private $provinceWithholdingTaxSetting;

    /** @var array List of printables lines */
    private $details;

    private $showRate = false;

    private $showPaymentType = false;

    public function setWithholdingTax($withholdingTax): WithholdingTaxCertificateBuilderInterface
    {
        $this->withholdingTax = $withholdingTax;

        return $this;
    }

    public function setProvinceWithholdingTaxSettings(ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting
    ): WithholdingTaxCertificateBuilderInterface {
        $this->provinceWithholdingTaxSetting = $provinceWithholdingTaxSetting;

        return $this;
    }

    public function setDetails(array $details): WithholdingTaxCertificateBuilderInterface
    {
        $this->details = $details;

        return $this;
    }

    public function showRate(bool $show): WithholdingTaxCertificateBuilderInterface
    {
        $this->showRate = $show;

        return $this;
    }

    public function showPaymentType(bool $show): WithholdingTaxCertificateBuilderInterface
    {
        $this->showPaymentType = $show;

        return $this;
    }

    public function getShowPaymentType(): bool
    {
        return $this->showPaymentType;
    }

    protected function getTemplateName()
    {
        return 'withholding_tax_certificate';
    }

    protected function getEmailType()
    {
        return 'withholding_tax_certificate';
    }

    public function getWithholdingTax()
    {
        return $this->withholdingTax;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getShowRate()
    {
        return $this->showRate;
    }

    public function getProvinceWithholdingTaxSetting()
    {
        return $this->provinceWithholdingTaxSetting;
    }

    public function getTotalAmount()
    {
        $rtnValue = 0;
        if (count($this->details) > 0) {
            foreach ($this->details as $detail) {
                $rtnValue += $detail['amount'];
            }
        }

        return $rtnValue;
    }

    protected function getParametersToReplace()
    {
        return [
            'assetsBaseUrl' => $this->assetsBaseUrl,
            'withholdingTax' => $this->withholdingTax,
            'withholdingTaxAmount' => $this->getTotalAmount(),
            'withholdingTaxLines' => $this->details,
            'showRate' => $this->showRate,
            'showPaymentType' => $this->showPaymentType,
            'withholding_label' => $this->translator->trans('emails_builder.withholding_tax_certificate.label.'.$this->withholdingTax->getType()),
            'provinceWithholdingTaxSetting' => $this->provinceWithholdingTaxSetting,
            'doNotReply' => $this->translator->trans('emails.do_not_reply',
                ['%phone%' => $this->configurationManager->get('phone')]),
            'companyName' => $this->configurationManager->get('certificate_company_name'),
            'companyAddress' => $this->configurationManager->get('certificate_address'),
            'location' => $this->configurationManager->get('certificate_location'),
            'zipCode' => $this->configurationManager->get('certificate_zip_code'),
            'companyFiscalId' => $this->configurationManager->get('certificate_fiscal_id'),
            'show_certificate_number' => $this->configurationManager->get('certificate_should_show_number'),
            'show_sign' => $this->configurationManager->get('certificate_should_show_sign'),
            'signCertificateUrl' => $this->assetsBaseUrl.'withholding-tax-sign-certificate.png',
            'signCertificateName' => $this->configurationManager->get('certificate_sign_name'),
            'signCertificateFiscalId' => $this->configurationManager->get('certificate_sign_fiscal_id'),
        ];
    }
}
