<?php

namespace GeoPagos\WithholdingTaxBundle\Controller;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxHardRuleType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class WithholdingTaxHardRuleController extends BackOfficeController
{
    private $module = 'withholding_tax_rule';

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
                $this->generateUrl('withholding_tax_rule_backoffice_section_edit', ['withholdingTaxRuleId' => $withholdingTaxRule->getId()]));
    }

    public function indexAction(Request $request, $withholdingTaxRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_VIEW);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        $withholdingTaxHardRules = $this->em->getRepository(WithholdingTaxHardRule::class)->findBy([
            'withholdingTaxRule' => $withholdingTaxRule,
        ], [
            'createdAt' => 'ASC',
        ]);

        $breadcrumbHelper = $this->getBreadcrumb($withholdingTaxRule)
            ->addPage($this->translator->trans('withholding_tax.hard_rules.index.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/hard_rules.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'withholdingTaxHardRules' => $withholdingTaxHardRules,
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

        $withholdingTaxHardRule = new WithholdingTaxHardRule();
        $form = $this->createWTHardRuleForm($withholdingTaxHardRule, AbstractFormType::VALIDATION_GROUP_REGISTER);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WithholdingTaxHardRule $wt_hard_rule */
            $withholdingTaxHardRule = $form->getData();
            $withholdingTaxHardRule->setWithholdingTaxRule($withholdingTaxRule);
            $withholdingTaxHardRule->setCreatedAt(Carbon::now());

            $this->em->persist($withholdingTaxHardRule);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('withholding_tax.hard_rules.new.success', ['%rate%' => $withholdingTaxHardRule->getRate()])
            );

            return $this->redirect($this->generateUrl(
                'common_back_withholding_tax_hard_rule',
                ['withholdingTaxRuleId' => $withholdingTaxRule->getId()]
            ));
        }

        $breadcrumbHelper = $this->getBreadcrumb($withholdingTaxRule)
            ->addPage($this->translator->trans('withholding_tax.hard_rules.new.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/hard_rules_new.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'form' => $form->createView(),
            ]
        ));
    }

    public function editAction(Request $request, $withholdingTaxRuleId, $withholdingTaxHardRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_EDIT);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);
        /** @var WithholdingTaxHardRule $withholdingTaxHardRule */
        $withholdingTaxHardRule = $this->em->getRepository(WithholdingTaxHardRule::class)->find($withholdingTaxHardRuleId);

        $form = $this->createWTHardRuleForm($withholdingTaxHardRule, AbstractFormType::VALIDATION_GROUP_EDIT);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $withholdingTaxHardRule = $form->getData();
            $this->em->persist($withholdingTaxHardRule);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('withholding_tax.hard_rules.edit.success', ['%rate%' => $withholdingTaxHardRule->getRate()]));

            return $this->redirect($this->generateUrl('common_back_withholding_tax_hard_rule', ['withholdingTaxRuleId' => $withholdingTaxRuleId]));
        }

        $breadcrumbHelper = $this->getBreadcrumb($withholdingTaxRule)
            ->addPage($this->translator->trans('withholding_tax.hard_rules.edit.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/hard_rules_edit.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'withholdingTaxHardRule' => $withholdingTaxHardRule,
                'form' => $form->createView(),
            ]
        ));
    }

    private function createWTHardRuleForm(WithholdingTaxHardRule $hardRule, string $validationGroup = ''): Form
    {
        return $this->createForm(WithholdingTaxHardRuleType::class, $hardRule, [
            'method' => 'POST',
        ]);
    }

    public function deleteAction(Request $request, $withholdingTaxRuleId, $withholdingTaxHardRuleId)
    {
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_DELETE);
        $em = $this->getDoctrine()->getManager();

        try {
            $withholdingTaxHardRule = $this->em->getRepository(WithholdingTaxHardRule::class)->find($withholdingTaxHardRuleId);
            $em->remove($withholdingTaxHardRule);
            $em->flush();

            $this->get('session')->getFlashBag()
                ->add('success', $this->translator->trans('withholding_tax.hard_rules.delete.success', ['rate' => $withholdingTaxHardRule->getRate().'%']));
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $this->translator->trans('withholding_tax.hard_rules.delete.error', ['rate' => $withholdingTaxHardRule->getRate().'%']));
        }

        return $this->redirect($this->generateUrl('common_back_withholding_tax_hard_rule',
            ['withholdingTaxRuleId' => $withholdingTaxRule->getId()]));
    }

    public function getModuleName()
    {
        return 'Comercios ';
    }

    private function getIdentificationForRule(WithholdingTaxRule $rule)
    {
        return $rule->getProvince() ? $rule->getProvince()->getName() : $rule->getType();
    }
}
