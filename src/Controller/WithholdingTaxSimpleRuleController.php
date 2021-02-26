<?php

namespace GeoPagos\WithholdingTaxBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxSimpleRuleType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class WithholdingTaxSimpleRuleController extends BackOfficeController
{
    private $module = 'withholding_tax_simple_rule';

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
                $this->translator->trans('modules.withholding_tax_rule'),
                $this->generateUrl('withholding_tax_rule_backoffice_section')
            );

        return $breadcrumbHelper;
    }

    public function getBreadcrumb(WithholdingTaxRule $withholdingTaxRule)
    {
        return $this->getControllerBreadcrumb()
            ->addPage(
                $this->getIdentificationForRule($withholdingTaxRule),
                $this->generateUrl('common_back_withholding_tax_simple_rule', ['withholdingTaxRuleId' => $withholdingTaxRule->getId()]));
    }

    public function indexAction(Request $request, $withholdingTaxRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_VIEW);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        $filters = ['type' => $withholdingTaxRule->getType()];

        if (WithholdingTaxTypeEnum::TAX == $withholdingTaxRule->getType()) {
            $filters['province'] = $withholdingTaxRule->getProvince();
        }

        $withholdingTaxSimpleRules = $this->em->getRepository(WithholdingTaxSimpleRule::class)->findBy($filters, [
            'type' => 'ASC',
            'taxCategory' => 'ASC',
            'province' => 'ASC',
            'taxCondition' => 'ASC',
        ]);

        $breadcrumbHelper = $this->getBreadcrumb($withholdingTaxRule)
            ->addPage($this->translator->trans('withholding_tax.simple_rules.index.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/simple_rules.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'withholdingTaxSimpleRules' => $withholdingTaxSimpleRules,
                'canCreateRules' => $this->isInSymfonyDevMode(),
            ]
        ));
    }

    public function newAction(Request $request, $withholdingTaxRuleId)
    {
        if (!$this->isInSymfonyDevMode()) {
            // CREATE Only from local environment
            return $this->redirect($this->generateUrl('withholding_tax_rule_backoffice_section'));
        }

        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_CREATE);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        /** @var WithholdingTaxSimpleRule $withholdingTaxSimpleRule */
        $withholdingTaxSimpleRule = new WithholdingTaxSimpleRule();

        $withholdingTaxSimpleRule->setType($withholdingTaxRule->getType());            // FIXED
        $withholdingTaxSimpleRule->setProvince($withholdingTaxRule->getProvince());    // FIXED

        $form = $this->createWTRuleForm($withholdingTaxSimpleRule, AbstractFormType::VALIDATION_GROUP_REGISTER);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $withholdingTaxSimpleRule = $form->getData();

            $this->em->persist($withholdingTaxSimpleRule);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('withholding_tax.simple_rules.new.success',
                    ['%name%' => $withholdingTaxSimpleRule->getId()])
            );

            return $this->redirect($this->generateUrl('common_back_withholding_tax_simple_rule_edit',
                [
                    'withholdingTaxSimpleRuleId' => $withholdingTaxSimpleRule->getId(),
                    'withholdingTaxRuleId' => $withholdingTaxRule->getId(),
                ]));
        }

        $breadcrumbHelper = $this->getControllerBreadcrumb()
            ->addPage($this->translator->trans('withholding_tax.simple_rules.new.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/simple_rules_new.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'form' => $form->createView(),
            ]
        ));
    }

    public function editAction(Request $request, $withholdingTaxRuleId, $withholdingTaxSimpleRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_EDIT);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        /** @var WithholdingTaxSimpleRule $withholdingTaxSimpleRule */
        $withholdingTaxSimpleRule = $this->em->getRepository(WithholdingTaxSimpleRule::class)->find($withholdingTaxSimpleRuleId);

        if (!$withholdingTaxSimpleRule) {
            throw $this->createNotFoundException('rule_not_found');
        }

        $form = $this->createWTRuleForm($withholdingTaxSimpleRule, AbstractFormType::VALIDATION_GROUP_EDIT);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rule = $form->getData();

            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('withholding_tax.simple_rules.edit.success',
                ['%name%' => $withholdingTaxSimpleRule->getId()])
            );

            return $this->redirect(
                $this->generateUrl('common_back_withholding_tax_simple_rule',
                    [
                        'withholdingTaxRuleId' => $withholdingTaxRule->getId(),
                    ]
                )
            );
        }

        $breadcrumbHelper = $this->getBreadcrumb($withholdingTaxRule)
            ->addPage($this->translator->trans('withholding_tax.simple_rules.edit.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/simple_rules_edit.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'withholdingTaxSimpleRule' => $withholdingTaxSimpleRule,
                'canCreateRules' => $this->isInSymfonyDevMode(),
                'form' => $form->createView(),
            ]
        ));
    }

    private function createWTRuleForm(WithholdingTaxSimpleRule $rule, string $validationGroup = ''): Form
    {
        return $this->createForm(WithholdingTaxSimpleRuleType::class, $rule, [
            'method' => 'POST',
        ]);
    }

    public function deleteAction(Request $request, $withholdingTaxRuleId, $withholdingTaxSimpleRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_DELETE);
        $em = $this->getDoctrine()->getManager();

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        /** @var WithholdingTaxSimpleRule $withholdingTaxSimpleRule */
        $withholdingTaxSimpleRule = $this->em->getRepository(WithholdingTaxSimpleRule::class)->find($withholdingTaxSimpleRuleId);
        $name = $this->getIdentificationForRule($withholdingTaxRule);

        try {
            $em->remove($withholdingTaxSimpleRule);
            $em->flush();

            $this->get('session')->getFlashBag()
                ->add('success',
                    $this->translator->trans(
                        'withholding_tax.simple_rules.delete.success', [
                            '%name%' => $name,
                        ])
                );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $this->translator->trans(
                'withholding_tax.simple_rules.delete.error', [
                '%name%' => $name,
            ]));
        }

        return $this->redirect($this->generateUrl('common_back_withholding_tax_simple_rule', ['withholdingTaxRuleId' => $withholdingTaxRule->getId()]));
    }

    public function getModuleName()
    {
        return 'Retenciones';
    }

    private function getIdentificationForRule(WithholdingTaxRule $rule)
    {
        return $rule->getProvince() ? $rule->getProvince()->getName() : $rule->getType();
    }
}
