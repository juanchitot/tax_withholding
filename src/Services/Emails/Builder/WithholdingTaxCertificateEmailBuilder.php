<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Emails\Builder;

use GeoPagos\ApiBundle\Entity\User;

class WithholdingTaxCertificateEmailBuilder extends WithholdingTaxAbstractEmailBuilder
{
    /** @var User */
    protected $user;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    protected function getTemplateName()
    {
        return 'withholding_tax_certificate_email';
    }

    protected function getEmailType()
    {
        return 'withholding_tax_certificate_email';
    }

    protected function getParametersToReplace()
    {
        return [
            'assetsBaseUrl' => $this->assetsBaseUrl,
            'logoUrl' => $this->assetsBaseUrl.'logo-white.png',
            'voucher' => $this->assetsBaseUrl.'voucher.png',
            'name' => $this->user ? $this->user->getFirstName() : 'Dueño de la cuenta',
            'landingUrl' => $this->configurationManager->get('landing_url'),
            'app_name' => $this->configurationManager->get('app_name'),
            'domain' => $this->getDomain(),
            'doNotReply' => $this->translator->trans('emails.do_not_reply', ['%phone%' => $this->configurationManager->get('phone')]),
        ];
    }
}
