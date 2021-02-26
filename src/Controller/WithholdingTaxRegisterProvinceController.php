<?php

namespace GeoPagos\WithholdingTaxBundle\Controller;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxRegisterProvinceType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class WithholdingTaxRegisterProvinceController extends BackOfficeController
{
    public function getControllerBreadcrumb()
    {
        $breadcrumbHelper = new BreadcrumbHelper();
        $breadcrumbHelper
            ->addPage(
                $this->translator->trans('modules.withholding_tax_registry'),
                $this->generateUrl('withholding_tax_register_province_backoffice_section')
            );

        return $breadcrumbHelper;
    }

    public function indexAction()
    {
        $this->getAuthorizer()->checkAuthorize($this->getModuleName(), StaticConstant::ROLE_ACTION_VIEW);

        $withholdingTaxRuleFiles = $this->em->getRepository(WithholdingTaxRuleFile::class)->findBy([
            'fileType' => [
                WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
                WithholdingTaxRuleFile::SIRTAC_TYPE,
            ],
        ], ['id' => 'desc']);

        $data = array_merge($this->getParametersForView(), [
            'breadcrumb' => $this->getControllerBreadcrumb()->getBreadcrumb(),
            'withholdingTaxRuleFiles' => $withholdingTaxRuleFiles,
        ]);

        return $this->render('GeoPagosWithholdingTaxBundle:TaxRegistries\WithholdingTaxRegisterProvince:withholding_tax_register_province.html.twig',
            $data);
    }

    public function newAction(Request $request)
    {
        $this->getAuthorizer()->checkAuthorize($this->getModuleName(), StaticConstant::ROLE_ACTION_CREATE);

        $withholdingTaxRuleFile = new WithholdingTaxRuleFile();
        $form = $this->createForm(WithholdingTaxRegisterProvinceType::class, $withholdingTaxRuleFile, [
            'method' => 'POST',
            'action' => AbstractFormType::ACTION_CREATE,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $choiceId = $form->get('origin')->getData();
            $prefix = substr($choiceId, 0, 1);
            $objectID = ltrim($choiceId, $prefix);
            switch ($prefix) {
                case WithholdingTaxRegisterProvinceType::PROVINCE_PREFIX:
                    $withholdingTaxRuleFile->setFileType(WithholdingTaxRuleFile::GROSS_INCOME_TYPE);
                    $province = $this->em->getRepository(Province::class)->find($objectID);
                    $withholdingTaxRuleFile->setProvince($province);

                    break;
                case WithholdingTaxRegisterProvinceType::REGIME_PREFIX:
                    $withholdingTaxRuleFile->setFileType(WithholdingTaxRuleFile::SIRTAC_TYPE);

                    break;
            }
            $withholdingTaxRuleFile->setStatus(WithholdingTaxRuleFileStatus::PENDING);
            $withholdingTaxRuleFile->setDate(Carbon::now()->endOfMonth()->addDay()->format('m-Y'));

            $this->uploadDbFile($withholdingTaxRuleFile, AbstractFormType::ACTION_CREATE);

            $this->em->persist($withholdingTaxRuleFile);

            $this->em->flush();

            $name = '';

            if ($withholdingTaxRuleFile->getProvince()) {
                $name = $withholdingTaxRuleFile->getProvince()->getName();
            }

            $this->addFlash('success',
                $this->translator->trans('withholdingRaxRegisterProvince.new.success', [
                    '%name%' => $name.' - '.$withholdingTaxRuleFile->getDate(),
                ]));

            return $this->redirect($this->generateUrl('withholding_tax_register_province_backoffice_section'));
        }

        $breadcrumbHelper = $this->getControllerBreadcrumb()
            ->addPage($this->translator->trans('withholdingRaxRegisterProvince.new.title'));

        $data = array_merge($this->getParametersForView(), [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
            'nextMonth' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
        ]);

        return $this->render('GeoPagosWithholdingTaxBundle:TaxRegistries\WithholdingTaxRegisterProvince:withholding_tax_register_province_editable.html.twig',
            $data);
    }

    public function deleteAction(WithholdingTaxRuleFile $withholdingTaxRuleFile)
    {
        $this->getAuthorizer()->checkAuthorize($this->getModuleName(), StaticConstant::ROLE_ACTION_DELETE);

        $name = $withholdingTaxRuleFile->getProvince() ? $withholdingTaxRuleFile->getProvince()->getName().' - ' : '';
        $name .= $withholdingTaxRuleFile->getDate();
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

    private function uploadDbFile(WithholdingTaxRuleFile $withholdingTaxRuleFile, $type): void
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
}
