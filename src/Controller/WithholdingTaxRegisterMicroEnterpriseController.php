<?php

namespace GeoPagos\WithholdingTaxBundle\Controller;

use Carbon\Carbon;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxRegisterMicroEnterpriseType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class WithholdingTaxRegisterMicroEnterpriseController extends BackOfficeController
{
    public function getControllerBreadcrumb()
    {
        $breadcrumbHelper = new BreadcrumbHelper();
        $breadcrumbHelper
            ->addPage(
                $this->translator->trans('modules.withholding_tax_registry'),
                $this->generateUrl('withholding_tax_register_micro_enterprise_index')
            );

        return $breadcrumbHelper;
    }

    public function indexAction()
    {
        $this->getCheckAuthorize(StaticConstant::ROLE_ACTION_VIEW);

        $withholdingTaxRuleFiles = $this->em->getRepository(WithholdingTaxRuleFile::class)->findBy([
            'fileType' => WithholdingTaxRuleFile::MICRO_ENTERPRISE,
        ], ['id' => 'desc']);

        $data = array_merge($this->getParametersForView(), [
            'breadcrumb' => $this->getControllerBreadcrumb()->getBreadcrumb(),
            'withholdingTaxRuleFiles' => $withholdingTaxRuleFiles,
        ]);

        return $this->render('GeoPagosWithholdingTaxBundle:TaxRegistries\WithholdingTaxRegisterMicroEnterprise:index.html.twig',
            $data);
    }

    public function newAction(Request $request)
    {
        $this->getCheckAuthorize(StaticConstant::ROLE_ACTION_CREATE);

        $withholdingTaxRuleFile = new WithholdingTaxRuleFile();

        $form = $this->createForm(WithholdingTaxRegisterMicroEnterpriseType::class, $withholdingTaxRuleFile, [
            'method' => 'POST',
            'action' => AbstractFormType::ACTION_CREATE,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $withholdingTaxRuleFile->setFileType(WithholdingTaxRuleFile::MICRO_ENTERPRISE);
            $withholdingTaxRuleFile->setStatus(WithholdingTaxRuleFileStatus::PENDING);
            $withholdingTaxRuleFile->setDate(Carbon::now()->endOfMonth()->addDay()->format('m-Y'));

            $this->uploadFile($withholdingTaxRuleFile, AbstractFormType::ACTION_CREATE);

            $this->em->persist($withholdingTaxRuleFile);

            $this->em->flush();

            $this->addFlash('success',
                $this->translator->trans('WithholdingTaxRegisterMicroEnterprise.new.success',
                    [
                        '%name%' => $withholdingTaxRuleFile->getDbFile().' - '.$withholdingTaxRuleFile->getDate(),
                    ]));

            return $this->redirect($this->generateUrl('withholding_tax_register_micro_enterprise_index'));
        }

        $breadcrumbHelper = $this->getControllerBreadcrumb()->addPage($this->translator->trans('WithholdingTaxRegisterMicroEnterprise.new.title'));

        $data = array_merge($this->getParametersForView(),
            [
                'form' => $form->createView(),
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'nextMonth' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            ]);

        return $this->render('GeoPagosWithholdingTaxBundle:TaxRegistries\WithholdingTaxRegisterMicroEnterprise:new.html.twig', $data);
    }

    public function deleteAction(WithholdingTaxRuleFile $withholdingTaxRuleFile)
    {
        $this->getCheckAuthorize(StaticConstant::ROLE_ACTION_DELETE);

        $name = 'para - '.$withholdingTaxRuleFile->getDate();
        if (WithholdingTaxRuleFileStatus::PENDING != $withholdingTaxRuleFile->getStatus()) {
            $this->get('session')->getFlashBag()
                ->add('error',
                    'Se produjo un error al eliminar el padrón "'.$name."''");

            return $this->redirect($this->generateUrl('withholding_tax_register_province_backoffice_section'));
        }

        try {
            $withholdingTaxRuleFile->setDeletedAt(Carbon::now());
            $this->em->flush();

            $this->get('session')->getFlashBag()
                ->add('success',
                    'Se eliminó correctamente el padrón "'.$name.'"');
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()
                ->add('error',
                    'Se produjo un error al eliminar el padrón "'.$name."''");
        }

        return $this->redirect($this->generateUrl('withholding_tax_register_province_backoffice_section'));
    }

    public function getModuleName()
    {
        return 'withholding_tax_register_province';
    }

    private function uploadFile(WithholdingTaxRuleFile $withholdingTaxRuleFile, $type): void
    {
        $attachment = $withholdingTaxRuleFile->getDbFile();

        if (!$attachment) {
            return;
        }
        if (AbstractFormType::ACTION_CREATE == $type) {
            $uploadedFile = new UploadedFile($attachment, '');
            $withholdingTaxRuleFile->generateDbFile($uploadedFile->guessExtension());

            $this->filesystem = $this->get('public_filesystem');

            if (!$this->filesystem->write($withholdingTaxRuleFile->getDbFile(),
                file_get_contents($attachment))) {
                return;
            }
        } else {
            $withholdingTaxRuleFile->setDbFile($withholdingTaxRuleFile->getDbFile());
        }
    }

    protected function getCheckAuthorize(string $action): void
    {
        $this->getAuthorizer()->checkAuthorize($this->getModuleName(), $action);
    }
}
