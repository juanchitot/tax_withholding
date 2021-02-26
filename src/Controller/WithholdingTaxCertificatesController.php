<?php

namespace GeoPagos\WithholdingTaxBundle\Controller;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Repository\AccountRepository;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Exceptions\BackOfficeUnauthorizedException;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxCertificatesFilterType;
use GeoPagos\WithholdingTaxBundle\Repository\CertificatesRepository;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

class WithholdingTaxCertificatesController extends BackOfficeController
{
    private const MODULE_NAME = 'withholding_tax_certificates';

    /** @var AccountRepository */
    private $accountRepository;

    /** @var CertificatesRepository */
    private $certificatesRepository;

    /** @var FilesystemInterface */
    private $filesystem;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ConfigurationManager $configurationManager,
        AccountRepository $accountRepository,
        CertificatesRepository $certificatesRepository,
        FilesystemInterface $filesystem
    ) {
        parent::__construct(
            $translator,
            $entityManager,
            $configurationManager
        );
        $this->accountRepository = $accountRepository;
        $this->certificatesRepository = $certificatesRepository;
        $this->filesystem = $filesystem;
    }

    public function getControllerBreadcrumb()
    {
        $breadcrumbHelper = new BreadcrumbHelper();
        $breadcrumbHelper->addPage(
            $this->translator->trans('modules.withholding_tax_certificates'),
            $this->generateUrl('withholding_tax_rule_backoffice_section')
        );

        return $breadcrumbHelper;
    }

    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

    public function indexAction(Request $request, $merchant_id)
    {
        $this->checkIfUserIsAllowedToMakeThisAction(StaticConstant::ROLE_ACTION_VIEW);

        $account = $this->accountRepository->find($merchant_id);

        $form = $this->createForm(WithholdingTaxCertificatesFilterType::class, null)->handleRequest($request);

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxCertificates/index.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $this->getControllerBreadcrumb()->getBreadcrumb(),
                'form' => $form->createView(),
                'merchant' => $account,
            ]
        ));
    }

    public function listWithholdingTaxCertificatesAction(Request $request, $merchant_id)
    {
        $this->checkIfUserIsAllowedToMakeThisAction(StaticConstant::ROLE_ACTION_VIEW);

        $offset = $request->query->get('start', 0);
        $limit = $request->query->get('length', 10);
        $order = $request->query->get('order', []);

        $form = $this->createForm(
            WithholdingTaxCertificatesFilterType::class,
            null
        )->handleRequest($request);
        $filterData = $this->mapFormFiltersToArray($form);

        $account = $this->accountRepository->find($merchant_id);

        $certificates = $this->certificatesRepository->getPaginatedData(
            $account,
            $offset,
            $limit,
            $order,
            $filterData
        );

        $response = [
            'data' => $this->convertCertificatesToCertificatesDto($certificates),
            'draw' => $request->query->get('draw', 1),
            'recordsFiltered' => $certificates->count(),
            'recordsTotal' => $certificates->count(),
        ];

        return new JsonResponse($response);
    }

    private function mapFormFiltersToArray(FormInterface $form): array
    {
        return collect($form->all())->map(function (Form $form) {
            return $form->getData();
        })->toArray();
    }

    private function convertCertificatesToCertificatesDto(Paginator $certificates): array
    {
        $certificatesDto = $dto = [];
        foreach ($certificates as $certificate) {
            /* @var Certificate $certificate */
            $dto['period'] = $certificate->getPeriod()->tz('UTC')->format('m-Y');
            $dto['type'] = $this->getTaxTypeTranslation($certificate->getType());
            $dto['province'] = $certificate->getProvince() ? $certificate->getProvince()->getName() : '';
            $dto['fileName'] = $this->getFilenameWithoutPath($certificate->getFileName());
            $dto['status'] = $this->getStatusTranslation($certificate->getStatus());
            $dto['actions'] = $this->getActions($certificate);
            $certificatesDto[] = $dto;
        }

        return $certificatesDto;
    }

    private function getFilenameWithoutPath($filepath): string
    {
        return substr($filepath, strrpos($filepath, DIRECTORY_SEPARATOR) + 1);
    }

    private function getTaxTypeTranslation($taxType): string
    {
        return $this->translator->trans($taxType);
    }

    private function getStatusTranslation($status): string
    {
        return $this->translator->trans('withholding_tax.certificates.statuses.'.strtolower($status));
    }

    private function getActions(Certificate $certificate): string
    {
        return $this->render(
            '@GeoPagosWithholdingTax/WithholdingTaxCertificates/certificate.actions.html.twig',
            [
                'certificate' => $certificate,
                'view_path' => $this->getCertificateFilepath($certificate),
            ]
        )->getContent();
    }

    private function getCertificateFilepath(Certificate $certificate): ?string
    {
        if ($this->filesystem->has($certificate->getFileName())) {
            return $certificate->getFileName();
        }

        return null;
    }

    public function downloadCertificateAction(Request $request, $certificate_id): Response
    {
        $this->checkIfUserIsAllowedToMakeThisAction(StaticConstant::ROLE_ACTION_DOWNLOAD);

        /** @var Certificate $certificate */
        $certificate = $this->certificatesRepository->find($certificate_id);

        $filepath = $this->getCertificateFilepath($certificate);
        if (null === $filepath) {
            throw new FileNotFoundException('The file does not exist');
        }

        $content = $this->filesystem->read($filepath);
        $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        $disposition .= ';filename="'.$this->getFilenameWithoutPath($filepath).'"';

        return $this->generateResponse(
            $content,
            'application/pdf',
            $disposition,
            mb_strlen($content, '8bit')
        );
    }

    public function viewCertificateAction(Request $request, $certificate_id): Response
    {
        $this->checkIfUserIsAllowedToMakeThisAction(StaticConstant::ROLE_ACTION_VIEW);

        /** @var Certificate $certificate */
        $certificate = $this->certificatesRepository->find($certificate_id);

        $filepath = $this->getCertificateFilepath($certificate);
        if (null === $filepath) {
            throw new FileNotFoundException('The file does not exist');
        }

        $content = $this->filesystem->read($filepath);

        return $this->generateResponse(
            $content,
            'application/pdf',
            ResponseHeaderBag::DISPOSITION_INLINE,
            mb_strlen($content, '8bit')
        );
    }

    public function downloadZipAction(Request $request, $merchant_id): Response
    {
        $this->checkIfUserIsAllowedToMakeThisAction(StaticConstant::ROLE_ACTION_DOWNLOAD);

        $account = $this->accountRepository->find($merchant_id);

        $filterData = [
            'periodFrom' => $request->query->get('periodFrom', null),
            'periodTo' => $request->query->get('periodTo', null),
            'taxType' => $request->query->get('taxType', null),
        ];

        if (empty($filterData['periodFrom']) || empty($filterData['periodTo'])) {
            throw new InvalidParameterException('"Period From" and "Period To" parameters are required');
        }

        $zipName = $this->createZip($account, $filterData);
        $content = file_get_contents($zipName);
        $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT.';filename="'.$zipName.'"';

        $response = $this->generateResponse(
            $content,
            'application/zip',
            $disposition,
            filesize($zipName)
        );

        @unlink($zipName);

        return $response;
    }

    private function createZip(Account $account, array $filterData): string
    {
        $zip = new ZipArchive();
        $zipName = $this->getZipFilename($filterData['periodFrom'], $filterData['periodTo'], $filterData['taxType']);
        $zip->open($zipName, ZipArchive::CREATE);

        $certificates = $this->certificatesRepository->getPaginatedData(
            $account,
            null,
            null,
            [],
            $filterData
        );

        foreach ($certificates as $certificate) {
            /** @var Certificate $certificate */
            $certificateFilename = $this->getTaxTypeTranslation($certificate->getType());
            $certificateFilename .= '_'.$this->getFilenameWithoutPath($certificate->getFileName());
            $content = $this->filesystem->read($certificate->getFileName());
            $zip->addFromString($certificateFilename, $content);
        }

        $zip->close();

        return $zipName;
    }

    private function getZipFilename($periodFrom, $periodTo, $taxType): string
    {
        $filename = '/tmp/'.$this->translator->trans('withholding_tax.certificates.zip.base_name');
        $filename .= '_'.Carbon::create($periodFrom)->format('Y-m');
        $filename .= '_'.Carbon::create($periodTo)->format('Y-m');
        if (empty($taxType)) {
            $filename .= '_'.$this->translator->trans('common.all');
        } else {
            $filename .= '_'.$this->translator->trans($taxType);
        }

        return $filename.'.zip';
    }

    private function generateResponse(string $content, $mime, $disposition, $length): Response
    {
        $response = new Response($content);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-length', $length);

        return $response;
    }

    /**
     * @throws BackOfficeUnauthorizedException
     */
    private function checkIfUserIsAllowedToMakeThisAction($action): void
    {
        $isFeatureDisabled = !$this->configurationManager->isFeatureEnabled('withholding_tax.certificates.show_in_backoffice');
        $isBackOfficeUserUnauthorized = !$this->getAuthorizer()->isAuthorize(self::MODULE_NAME, $action);
        if ($isFeatureDisabled && $isBackOfficeUserUnauthorized) {
            throw new BackOfficeUnauthorizedException(
                'Can`t make this action.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }
    }
}
