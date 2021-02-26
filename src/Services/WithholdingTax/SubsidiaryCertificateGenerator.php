<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Services\Emails\Builder\AbstractEmailBuilder;
use GeoPagos\ApiBundle\Services\Emails\EmailSender;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\CreateRequest;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use GeoPagos\WithholdingTaxBundle\Repository\SirtacDeclarationRepository;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\BuilderFactory;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\SubsidiaryDataAggregator;
use GeoPagos\WithholdingTaxBundle\Services\CountryWithholdableTaxes;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubsidiaryCertificateGenerator
{
    private const BATCH_LOG = 50;
    private const FLUSH_LOG = 300;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var AbstractEmailBuilder
     */
    private $emailBuilder;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var string
     */
    private $mailSender;

    /**
     * @var string
     */
    private $appName;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CreateCertificate
     */
    private $createCertificate;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected $month;

    /** @var bool */
    private $sendOldCertificates;
    /**
     * @var CountryWithholdableTaxes
     */
    private $countryWithholdableTaxes;
    /**
     * @var SubsidiaryDataAggregator
     */
    private $aggregator;
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        AbstractEmailBuilder $emailBuilder,
        EmailSender $emailSender,
        SubsidiaryDataAggregator $aggregator,
        CreateCertificate $createCertificate,
        LoggerInterface $logger,
        BuilderFactory $builderFactory,
        ConfigurationManagerInterface $configurationManager,
        CountryWithholdableTaxes $countryWithholdableTaxes
    ) {
        $this->em = $em;
        $this->countryWithholdableTaxes = $countryWithholdableTaxes;
        $this->emailBuilder = $emailBuilder;
        $this->emailSender = $emailSender;
        $this->translator = $translator;
        $this->createCertificate = $createCertificate;
        $this->logger = $logger;
        $this->mailSender = $configurationManager->get('mail_sender');
        $this->appName = $configurationManager->get('app_name');
        $this->month = Carbon::now()->format('Ym');
        $this->sendOldCertificates = false;
        $this->aggregator = $aggregator;
        $this->builderFactory = $builderFactory;
    }

    public function setMonth(string $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getMonth(): string
    {
        return $this->month;
    }

    public function getSendOldCertificates(): bool
    {
        return $this->sendOldCertificates;
    }

    public function process(): void
    {
        $period = Carbon::createFromFormat('Ym', $this->getMonth());
        $subsidiaryIds = $this->collectSubsidiaryIdsToProcess();

        $subsidiaryCount = 0;
        $this->logger->info('[withholdingTax] start send certificates to owners', [
            'subsidiaries-to-check' => count($subsidiaryIds),
        ]);
        $totalCertsSent = 0;
        foreach ($subsidiaryIds as $subsidiaryId) {
            try {
                $subsidiary = $this->em->getRepository(Subsidiary::class)->find($subsidiaryId);
                /* Subsidiary data for the period, all taxes */
                $dataToSend = $this->aggregator->collect($subsidiaryId, $period, $this->getSendOldCertificates());
                /* Prepare parameter for builder */
                $createRequest = $this->prepareCreateRequest($subsidiary, $dataToSend);
                $certificates = [];
                /* @var $withholdableTax WithholdableTaxInterface */
                foreach ($this->countryWithholdableTaxes->getAvailableTaxes() as $withholdableTax) {
                    /* Service fetch */
                    $withholdingTaxCertificateGenerator = $this->builderFactory->create(new $withholdableTax());
                    /* Certificates generation */
                    $certificates = array_merge($certificates,
                        $withholdingTaxCertificateGenerator->build($createRequest)
                    );
                }

                /* Create mails from pdf files */
                if ($this->createMailsFromPackages($createRequest, $certificates)) {
                    /* Flag certificate entities as SENT */
                    $this->flagPackagesAsSent($certificates);
                }

                $this->em->flush();
                $totalCertsSent += count($certificates);
                ++$subsidiaryCount;
                $this->logger->info('[CERTIFICATE SEND PROCESS]', [
                    'message' => sprintf('Subsidiary %d of %d procesed, %d certificates sent, %d total sent',
                        $subsidiaryCount, count($subsidiaryIds), count($certificates), $totalCertsSent),
                ]);
            } catch (\Throwable $exception) {
                $this->logger->error('[CERTIFICATE SEND PROCESS]', [
                    'message' => sprintf('Exception during the build process for Subsidiary id %d',
                        $subsidiaryId),
                    'exception' => $exception,
                ]);
            }
        }
        $this->logger->info('[CERTIFICATE SEND PROCESS] end send certificates to owners');
    }

    public function setSendOldCertificates(bool $sendOldCertificates): self
    {
        $this->sendOldCertificates = $sendOldCertificates;

        return $this;
    }

    private function collectSubsidiaryIdsToProcess()
    {
        $subsidiaryIds = $this->em->getRepository(WithholdingTax::class)->findWithActiveSubsidiariesWithCertificates(
            $this->getMonth(),
            $this->getSendOldCertificates(),
            ['status' => WithholdingTaxStatus::CREATED]
        );
        /* @var $sirtacDeclarationRepository SirtacDeclarationRepository */
        $sirtacDeclarationRepository = $this->em->getRepository(SirtacDeclaration::class);
        $subsidiaryIdsSirtac = $sirtacDeclarationRepository->findWithActiveSubsidiariesWithCertificates(
            $this->getMonth(),
            $this->getSendOldCertificates(),
            ['status' => WithholdingTaxStatus::CREATED]
        );

        return array_unique(
            array_map(function ($result) {
                return (int) current($result);
            }, array_merge($subsidiaryIds, $subsidiaryIdsSirtac)
            )
        );
    }

    protected function createMailsFromPackages(CreateRequest $createRequest, array $certificates): bool
    {
        $attachments = array_map(function (Package $package) {
            return $package->getCertificateEntity()->getFileName();
        }, $certificates);
        if ($createRequest->getOwner()) {
            $this->emailBuilder->setUser($createRequest->getOwner());

            $email = $this->emailSender
                ->setFrom($this->mailSender)
                ->setFromName($this->appName)
                ->setSubject($this->translator->trans('emails_builder.withholding_tax_certificate.subject'))
                ->setTo($createRequest->getOwner()->getEmail())
                ->setHtml($this->emailBuilder->getHtml())
                ->setAttachments($attachments)
                ->createWithAttachments();

            $this->em->persist($email);

            return true;
        }

        return false;
    }

    private function prepareCreateRequest(Subsidiary $subsidiary, array $dataToSend = []): ?CreateRequest
    {
        $account = $subsidiary->getAccount();

        $createRequest = new CreateRequest();
        $createRequest->setSubsidiary($subsidiary);
        $createRequest->setPeriod(Carbon::createFromFormat('Ym', $this->getMonth())->startOfMonth());
        $createRequest->setFiscalId($account->getIdFiscal());
        $createRequest->setRequestData($dataToSend);

        if ($owner = $account->getOwner()) {
            $createRequest->setOwner($owner);
        } else {
            $this->logger->alert("[withholdingTax] account haven't owner", [
                'id' => $account->getId(),
                'name' => $account->getLegalName(),
            ]);
        }

        return $createRequest;
    }

    protected function flagPackagesAsSent(array $certificatePackages)
    {
        /* @var $package Package */
        foreach ($certificatePackages as $package) {
            $certificate = $package->getCertificateEntity();
            $certificate->setStatus(Certificate::SENT);
            /* @var $item WithholdingTax */
            foreach ($package->getData() as $item) {
                $item->setStatus(WithholdingTaxStatus::SENT);
                $this->em->persist($item);
            }
            $this->em->persist($certificate);
        }
    }
}
