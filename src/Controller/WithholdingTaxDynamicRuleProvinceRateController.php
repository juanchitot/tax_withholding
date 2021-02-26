<?php

namespace GeoPagos\WithholdingTaxBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRuleProvinceRate;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxDynamicRuleProvinceRateType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class WithholdingTaxDynamicRuleProvinceRateController extends BackOfficeController
{
    private $module = 'withholding_tax_dynamic_rule_province_rate';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ConfigurationManager $configurationManager,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($translator, $entityManager, $configurationManager);
        $this->dispatcher = $dispatcher;
    }

    public function getControllerBreadcrumb()
    {
        $breadcrumbHelper = new BreadcrumbHelper();
        $breadcrumbHelper
            ->addPage(
                $this->translator->trans('modules.withholding_tax_dynamic_rule_province_rate'),
                $this->generateUrl('withholding_tax_register_province_backoffice_section')
            );

        return $breadcrumbHelper;
    }

    public function indexAction(Request $request, $withholdingTaxRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_VIEW);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        $withholdingTaxDynamicRulesProvinceRates = $this->em->getRepository(WithholdingTaxDynamicRuleProvinceRate::class)->findBy([
            'province' => $withholdingTaxRule->getProvince(),
        ], [
            'province' => 'ASC',
            'externalId' => 'ASC',
        ]);

        return $this->render('GeoPagosWithholdingTaxBundle:WithholdingTaxDynamicRuleProvinceRate:index.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $this->getControllerBreadcrumb()->getBreadcrumb(),
                'dynamic_rules_province_rates' => $withholdingTaxDynamicRulesProvinceRates,
                'withholdingTaxRule' => $withholdingTaxRule,
            ]
        ));
    }

    public function newAction(Request $request, $withholdingTaxRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_CREATE);

        /** @var WithholdingTaxDynamicRuleProvinceRate $dynamic_rule_province_rate */
        $dynamic_rule_province_rate = new WithholdingTaxDynamicRuleProvinceRate();

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        $form = $this->createWTRuleForm($dynamic_rule_province_rate, AbstractFormType::VALIDATION_GROUP_REGISTER);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dynamic_rule_province_rate = $form->getData();

            $this->em->persist($dynamic_rule_province_rate);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('withholding_tax.dynamic_rule_province_rate.new.success',
                    ['%name%' => $this->getIdentificationForRule($dynamic_rule_province_rate)])
            );

            return $this->redirect($this->generateUrl('common_back_withholding_tax_dynamic_rule_province_rate', ['withholdingTaxRuleId' => $withholdingTaxRuleId]));
        }

        $breadcrumbHelper = $this->getControllerBreadcrumb()
            ->addPage($this->translator->trans('withholding_tax.dynamic_rule_province_rate.new.title'));

        return $this->render('GeoPagosWithholdingTaxBundle:WithholdingTaxDynamicRuleProvinceRate:new.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'dynamic_rule_province_rate' => $dynamic_rule_province_rate,
                'form' => $form->createView(),
                'withholdingTaxRule' => $withholdingTaxRule,
            ]
        ));
    }

    public function editAction(Request $request, $dynamicRuleProvinceRateId, $withholdingTaxRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_EDIT);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        /** @var WithholdingTaxDynamicRuleProvinceRate $dynamic_rule_province_rate */
        $dynamic_rule_province_rate = $this->em->getRepository(WithholdingTaxDynamicRuleProvinceRate::class)->find($dynamicRuleProvinceRateId);

        if (!$dynamic_rule_province_rate) {
            throw $this->createNotFoundException('rule_not_found');
        }

        $form = $this->createWTRuleForm($dynamic_rule_province_rate, AbstractFormType::VALIDATION_GROUP_EDIT);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rule = $form->getData();

            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('withholding_tax.dynamic_rule_province_rate.edit.success', ['%name%' => $this->getIdentificationForRule($rule)]));

            return $this->redirect($this->generateUrl('common_back_withholding_tax_dynamic_rule_province_rate', ['withholdingTaxRuleId' => $withholdingTaxRuleId]));
        }

        $breadcrumbHelper = $this->getControllerBreadcrumb()
            ->addPage(
                $this->getIdentificationForRule($dynamic_rule_province_rate),
                $this->generateUrl('withholding_tax_rule_backoffice_section'))
            ->addPage($this->translator->trans('withholding_tax.simple_rules.edit.title'));

        return $this->render('GeoPagosWithholdingTaxBundle:WithholdingTaxDynamicRuleProvinceRate:edit.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'dynamic_rule_province_rate' => $dynamic_rule_province_rate,
                'form' => $form->createView(),
                'withholdingTaxRule' => $withholdingTaxRule,
            ]
        ));
    }

    private function createWTRuleForm(WithholdingTaxDynamicRuleProvinceRate $rule, string $validationGroup = ''): FormInterface
    {
        return $this->createForm(WithholdingTaxDynamicRuleProvinceRateType::class, $rule, [
            'method' => 'POST',
        ]);
    }

    public function deleteAction(Request $request, $dynamicRuleProvinceRateId, $withholdingTaxRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_DELETE);
        $em = $this->getDoctrine()->getManager();

        /** @var WithholdingTaxDynamicRuleProvinceRate $dynamic_rule_province_rate */
        $dynamic_rule_province_rate = $this->em->getRepository(WithholdingTaxDynamicRuleProvinceRate::class)->find($dynamicRuleProvinceRateId);
        $name = $this->getIdentificationForRule($dynamic_rule_province_rate);

        try {
            $em->remove($dynamic_rule_province_rate);
            $em->flush();

            $this->get('session')->getFlashBag()
                ->add('success',
                    $this->translator->trans(
                        'withholding_tax.dynamic_rule_province_rate.delete.success', [
                        '%name%' => $name,
                    ])
                );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $this->translator->trans(
                'withholding_tax.simple_rules.delete.error', [
                '%name%' => $name,
            ]));
        }

        return $this->redirect($this->generateUrl('common_back_withholding_tax_dynamic_rule_province_rate', ['withholdingTaxRuleId' => $withholdingTaxRuleId]));
    }

    public function getModuleName()
    {
        return 'Retenciones';
    }

    private function getIdentificationForRule(WithholdingTaxDynamicRuleProvinceRate $rule)
    {
        return $rule->getProvince()->getName().' - Categoria '.$rule->getExternalId();
    }
}
