<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\CreateRequest;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use GeoPagos\WithholdingTaxBundle\Repository\CertificatesRepository;
use GeoPagos\WithholdingTaxBundle\Repository\ProvinceWithholdingTaxSettingRepository;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Formatter\BaseEmailPdfFormatter;

class Builder implements SubsidiaryCertificateBuilderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    private $createCertificate;

    private $currentTaxType;
    private $grouper;
    private $taxStages = [];

    private $currentStageIndex = 0;
    /**
     * @var array
     */
    private $groupers = [];
    /**
     * @var CreateRequest
     */
    protected $createRequest;
    /**
     * @var ProvinceWithholdingTaxSettingRepository
     */
    private $provinceWithholdingTaxSettingRepository;
    /**
     * @var BaseEmailPdfFormatter
     */
    private $mailFormatter;
    private $certificatesRepository;
    /**
     * @var array
     */
    private $mailFormatters = [];

    public function __construct(
        EntityManagerInterface $em,
        CertificatesRepository $certificatesRepository,
        ProvinceWithholdingTaxSettingRepository $provinceWithholdingTaxSettingRepository
    ) {
        $this->em = $em;
        $this->certificatesRepository = $certificatesRepository;
        $this->provinceWithholdingTaxSettingRepository = $provinceWithholdingTaxSettingRepository;
    }

    public function setTax(WithholdableTaxInterface $tax)
    {
        $this->currentStageIndex = 0;
        $tax1 = $tax;
        $this->taxStages = get_class($tax)::availableWithholdingStages();
        $availableTaxTypes = array_map(function ($stage) {
            return $stage::getTaxType();
        }, $this->taxStages);
        $this->forwardCurrentTaxStage(0);
    }

    public function setGroupers($groupers)
    {
        $this->groupers = $groupers;
    }

    public function setFormatter($formatter)
    {
        $this->mailFormatter = $formatter;
    }

    public function build(CreateRequest $createRequest): array
    {
        $packages = [];
        $this->createRequest = $createRequest;
        foreach ($this->taxStages as $taxStage) {
            $stagePackages = $this->grouper->group($this,
                $this->filter($createRequest->getRequestData())
            );
            /* @var $package Package */
            foreach ($stagePackages as $package) {
                $package->setPeriod($createRequest->getPeriod())
                    ->setSubsidiary($createRequest->getSubsidiary())
                    ->setFiscalId($this->createRequest->getFiscalId())
                    ->setTaxSettings($this->getTaxSettings($package));
                $this->createCertificateEntity($package);
                $this->bindRegisters($package);
            }
            $packages = array_merge($packages, $stagePackages);
            $this->forwardCurrentTaxStage();
        }

        return $packages;
    }

    public function getCurrentTaxType(): string
    {
        return $this->currentTaxType;
    }

    public function forwardCurrentTaxStage($index = null)
    {
        if (is_null($index)) {
            $index = $this->currentStageIndex + 1;
        }
        if ($index < count($this->taxStages)) {
            $this->currentStageIndex = $index;

            $currentStage = $this->taxStages[$this->currentStageIndex];
            $this->currentTaxType = $currentStage::getTaxType();
        }

        if (isset($this->groupers[$this->currentStageIndex])) {
            $this->grouper = $this->groupers[$this->currentStageIndex];
        }

        if (isset($this->mailFormatters[$this->currentStageIndex])) {
            $this->mailFormatter = $this->mailFormatters[$this->currentStageIndex];
        }
    }

    public function getCreateRequest(): CreateRequest
    {
        return $this->createRequest;
    }

    private function filter(array $data)
    {
        return array_values(array_filter($data, function ($item) {
            /* @var  $item WithholdingTax */
            return $item->getType() == $this->currentTaxType;
        }));
    }

    private function bindRegisters(Package $package)
    {
        $this->em->persist($package->getCertificateEntity());
        foreach ($package->getData() as $withholdingTax) {
            $withholdingTax->setCertificate($package->getCertificateEntity());
            $this->em->persist($withholdingTax);
        }
    }

    private function getTaxSettings(Package $package)
    {
        return $this->provinceWithholdingTaxSettingRepository->findSettingsForTaxType(
            $package->getTaxType(),
            $package->getProvinceId()
        );
    }

    private function createCertificateEntity(Package $package)
    {
        $certificate = $this->certificatesRepository->findByPackageOrCreate($package);
        $package->setCertificateEntity($certificate);
        $certificate->setFileName($this->mailFormatter->format($this, $package));

        return $certificate;
    }

    public function setFormatters(array $formatters)
    {
        $this->mailFormatters = $formatters;
    }
}
