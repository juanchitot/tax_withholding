<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Services\PDF\PDFConverter;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use GeoPagos\WithholdingTaxBundle\Repository\CertificatesRepository;
use GeoPagos\WithholdingTaxBundle\Services\Emails\Builder\WithholdingTaxCertificateBuilderInterface;
use League\Flysystem\FilesystemInterface;

class CreateCertificate
{
    public const PATH = '/certificate/';

    /** @var WithholdingTaxCertificateBuilderInterface */
    private $certificateBuilder;

    /** @var PDFConverter */
    private $PDFConverter;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var EntityManagerInterface */
    private $em;

    /** @var CertificatesRepository */
    private $certificatesRepository;

    public function __construct(
        CertificatesRepository $certificatesRepository,
        WithholdingTaxCertificateBuilderInterface $certificateBuilder,
        PDFConverter $PDFConverter,
        FilesystemInterface $filesystem,
        EntityManagerInterface $em
    ) {
        $this->certificatesRepository = $certificatesRepository;
        $this->certificateBuilder = $certificateBuilder;
        $this->PDFConverter = $PDFConverter;
        $this->filesystem = $filesystem;
        $this->em = $em;
    }

    public function create(Package $package)
    {
        $withholdingTax = $package->getData()[0];
        $path = $this->getPathFromPackage($package);
        if (!$this->filesystem->has($path)) {
            $isFederalTaxType = in_array($withholdingTax->getType(), WithholdingTaxTypeEnum::getFederalTaxTypes(),
                true);
            $showDistinctPaymentTypesAndRates = $isFederalTaxType &&
                in_array($withholdingTax->getPaymentType(), [PaymentMethod::TYPE_DEBIT, PaymentMethod::TYPE_CREDIT],
                    true);

            $showRate = !$isFederalTaxType || $showDistinctPaymentTypesAndRates;

            $html = $this->certificateBuilder
                ->setProvinceWithholdingTaxSettings($package->getTaxSettings())
                ->setWithholdingTax($withholdingTax)
                ->setDetails($this->getDetails($package->getData()))
                ->showRate($showRate)
                ->showPaymentType($showDistinctPaymentTypesAndRates)
                ->getHtml();

            $pdf = $this->PDFConverter->htmlToPdf(
                $html,
                ['orientation' => 'landscape']
            );

            $this->filesystem->put($path, $pdf);
        }

        $certificate = $this->certificatesRepository->findOneByCriteriaOrCreate(
            $package->getSubsidiary(),
            $path,
            $withholdingTax->getDate()->startOfMonth()->startOfDay()
        );

        return $certificate
            ->setStatus(Certificate::CREATED)
            ->setProvince($withholdingTax->getProvince())
            ->setType($withholdingTax->getType());
    }

    private function getDetails(array $withholdingTaxes)
    {
        /** @var WithholdingTax $withholdingTax */
        $rtnValue = [];
        foreach ($withholdingTaxes as $withholdingTax) {
            $rtnValue[] = [
                'date' => $withholdingTax->getDate(),
                'certificateNumber' => $withholdingTax->getCertificateNumber(),
                'rate' => $withholdingTax->getRate(),
                'paymentType' => $withholdingTax->getPaymentType(),
                'taxableIncome' => $withholdingTax->getTaxableIncome(),
                'amount' => $withholdingTax->getAmount(),
            ];
        }

        return $rtnValue;
    }

    private function getPathFromPackage(Package $package): string
    {
        $directory = $package->getTaxType();

        return join('', [
            self::PATH,
            $directory,
            DIRECTORY_SEPARATOR,
            $package->getSubsidiary()->getId(),
            '-',
            $package->getLocalFilename(),
        ]);
    }
}
