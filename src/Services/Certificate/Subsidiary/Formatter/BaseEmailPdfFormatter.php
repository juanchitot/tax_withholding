<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Formatter;

use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\ApiBundle\Services\Emails\Builder\AbstractEmailBuilder;
use GeoPagos\ApiBundle\Services\PDF\PDFConverter;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Builder;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BaseEmailPdfFormatter extends AbstractEmailBuilder
{
    public const PATH = '/certificate/';

    /**
     * @var PDFConverter
     */
    private $PDFConverter;
    /**
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var Package
     */
    protected $package;
    /**
     * @var Builder
     */
    protected $builder;

    public function __construct(
        Environment $templating,
        ConfigurationManager $configurationManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        PDFConverter $PDFConverter,
        FilesystemInterface $filesystem
    ) {
        parent::__construct($templating, $configurationManager, $translator, $logger);
        $this->PDFConverter = $PDFConverter;
        $this->filesystem = $filesystem;
    }

    public function getHtml()
    {
        return $this->templating->render(
            '@GeoPagosWithholdingTax/Emails/'.$this->getTemplateName().'.html.twig',
            $this->getParametersToReplace()
        );
    }

    protected function getTemplateName()
    {
        return 'withholding_tax_certificate';
    }

    protected function getEmailType()
    {
        return 'withholding_tax_certificate';
    }

    protected function getParametersToReplace()
    {
        $owner = $this->builder->getCreateRequest()->getOwner();
        $withholdingTax = $this->package->getData()[0];

        return [
            'certificate' => $this->package->getCertificateEntity(),
            'logoUrl' => $this->assetsBaseUrl.'logo-white.png',
            'voucher' => $this->assetsBaseUrl.'voucher.png',
            'name' => $owner ? $owner->getFirstName() : 'Dueño de la cuenta',
            'landingUrl' => $this->configurationManager->get('landing_url'),
            'app_name' => $this->configurationManager->get('app_name'),
            'domain' => $this->getDomain(),
            'assetsBaseUrl' => $this->assetsBaseUrl,
            'withholdingTax' => $withholdingTax,
            'withholdingTaxAmount' => $this->getTotalAmount(),
            'withholdingTaxLines' => $this->getDetails(),
            'showRate' => true,
            'showPaymentType' => false,
            'withholding_label' => $this->translator->trans('emails_builder.withholding_tax_certificate.label.'.$this->package->getTaxType()),
            'taxType' => $this->package->getTaxType(),
            'provinceWithholdingTaxSetting' => $this->package->getTaxSettings(),
            'doNotReply' => $this->translator->trans('emails.do_not_reply',
                ['%phone%' => $this->configurationManager->get('phone')]),
            'companyName' => $this->configurationManager->get('certificate_company_name'),
            'companyAddress' => $this->configurationManager->get('certificate_address'),
            'location' => $this->configurationManager->get('certificate_location'),
            'zipCode' => $this->configurationManager->get('certificate_zip_code'),
            'companyFiscalId' => $this->configurationManager->get('certificate_fiscal_id'),
            'show_certificate_number' => true || $this->configurationManager->get('certificate_should_show_number'),
            'show_sign' => $this->configurationManager->get('certificate_should_show_sign'),
            'signCertificateUrl' => $this->assetsBaseUrl.'withholding-tax-sign-certificate.png',
            'signCertificateName' => $this->configurationManager->get('certificate_sign_name'),
            'signCertificateFiscalId' => $this->configurationManager->get('certificate_sign_fiscal_id'),
        ];
    }

    public function format(Builder $builder, Package $package)
    {
        $path = $this->getPathFromPackage($package);
        $this->package = $package;
        $this->builder = $builder;
        if (!$this->filesystem->has($path)) {
            $pdf = $this->PDFConverter->htmlToPdf(
                $this->getHtml(),
                ['orientation' => 'landscape']
            );
            $this->filesystem->put($path, $pdf);
        }

        return $path;
    }

    protected function getPathFromPackage(Package $package): string
    {
        $directory = $package->getTaxType();

        return join('', [
            self::PATH,
            $directory,
            DIRECTORY_SEPARATOR,
            $package->getLocalFilename(),
        ]);
    }

    protected function getDetails(): array
    {
        /** @var WithholdingTax $taxWithholdRow */
        $details = [];
        foreach ($this->package->getData() as $taxWithholdRow) {
            $detail = [
                'date' => $taxWithholdRow->getDate(),
                'rate' => $taxWithholdRow->getRate(),
                'paymentType' => $taxWithholdRow->getPaymentType(),
                'taxableIncome' => $taxWithholdRow->getTaxableIncome(),
                'amount' => $taxWithholdRow->getAmount(),
            ];

            if (WithholdingTaxTypeEnum::SIRTAC === $taxWithholdRow->getType()) {
                $detail['certificateNumber'] = $taxWithholdRow->getSettlementNumber();
            } else {
                $detail['certificateNumber'] = $taxWithholdRow->getCertificateNumber();
            }

            $details[] = $detail;
        }

        return $details;
    }

    protected function getTotalAmount()
    {
        return array_reduce($this->package->getData(), function ($carry, $taxWithholdRow) {
            return $carry + $taxWithholdRow->getAmount();
        }, 0);
    }
}
