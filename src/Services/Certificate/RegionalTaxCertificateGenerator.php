<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Services\Emails\Builder\AbstractEmailBuilder;
use GeoPagos\ApiBundle\Services\Emails\EmailSender;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\CreateCertificate;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegionalTaxCertificateGenerator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AbstractEmailBuilder
     */
    private $emailBuilder;
    /**
     * @var EmailSender
     */
    private $emailSender;
    /**
     * @var SubsidiaryDataAggregator
     */
    private $aggregator;
    /**
     * @var CreateCertificate
     */
    private $createCertificate;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ConfigurationManagerInterface
     */
    private $configurationManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        AbstractEmailBuilder $emailBuilder,
        EmailSender $emailSender,
        SubsidiaryDataAggregator $aggregator,
        CreateCertificate $createCertificate,
        LoggerInterface $logger,
        ConfigurationManagerInterface $configurationManager
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->emailBuilder = $emailBuilder;
        $this->emailSender = $emailSender;
        $this->aggregator = $aggregator;
        $this->createCertificate = $createCertificate;
        $this->logger = $logger;
        $this->configurationManager = $configurationManager;
    }

    public function generate($taxType, $data = [])
    {
        $groupedData = [];
        $certificates = [];
        foreach ($groupedData as $group) {
            $attachment_name = $this->translator->trans('emails_builder.withholding_tax_certificate.filename.'.$taxType);
            $attachment_name = $this->createCertificate->prependProvinceNameToFilename($attachment_name,
                $group[0]);
            $certificate = $this->createCertificate->createAndReturnPath($withholdingTaxes);
            $this->em->persist($certificate);

            foreach ($group as $withholdingTax) {
                // All withholdingTaxes in one report
                $withholdingTax->setCertificate($certificate);
                $this->em->persist($withholdingTax);
            }

            $certificates[$attachment_name] = $certificate;
        }

        return $certificates;
    }
}
