<?php

namespace GeoPagos\WithholdingTaxBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxRuleType;
use GeoPagos\WithholdingTaxBundle\Services\EnabledTaxesInProjectTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class WithholdingTaxRuleController extends BackOfficeController
{
    use EnabledTaxesInProjectTrait;

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
                $this->translator->trans('withholding_tax.rules.index.title'),
                $this->generateUrl('withholding_tax_rule_backoffice_section')
            );

        return $breadcrumbHelper;
    }

    public function indexAction(Request $request)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_VIEW);

        $sortCondition = ['province' => 'ASC', 'taxCategory' => 'ASC'];
        $withholding_tax_rules = $this->em->getRepository(WithholdingTaxRule::class)->findBy([
            'type' => $this->getEnabledTaxesInProject($this->configurationManager),
            'enabled' => true,
        ], $sortCondition);

        $response = $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/index.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $this->getControllerBreadcrumb()->getBreadcrumb(),
                'withholdingTaxRules' => $withholding_tax_rules,
                'canCreateRules' => $this->isInSymfonyDevMode(),
            ]
        ));

        $sectionCookie = new Cookie('wtr_version', 'v1');
        $response->headers->setCookie($sectionCookie);

        return $response;
    }

    public function newAction(Request $request)
    {
        if (!$this->isInSymfonyDevMode()) {
            // CREATE Only from local environment
            return $this->redirect($this->generateUrl('withholding_tax_rule_backoffice_section'));
        }

        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_CREATE);
        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = new WithholdingTaxRule();
        $form = $this->createWTRuleForm($withholdingTaxRule, AbstractFormType::VALIDATION_GROUP_REGISTER);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $withholdingTaxRule = $form->getData();

            $this->em->persist($withholdingTaxRule);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('withholding_tax.rules.new.success',
                    ['%name%' => $withholdingTaxRule->getId()]
                )
            );

            return $this->redirect($this->generateUrl(
                'withholding_tax_rule_backoffice_section_edit',
                ['withholdingTaxRuleId' => $withholdingTaxRule->getId()]
            ));
        }

        $breadcrumbHelper = $this->getControllerBreadcrumb()
            ->addPage($this->translator->trans('withholding_tax.rules.new.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/new.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'form' => $form->createView(),
            ]
        ));
    }

    public function editAction($withholdingTaxRuleId, Request $request)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_EDIT);

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);

        if (!$withholdingTaxRule) {
            throw $this->createNotFoundException('rule_not_found');
        }

        $form = $this->createWTRuleForm($withholdingTaxRule, AbstractFormType::VALIDATION_GROUP_EDIT);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rule = $form->getData();
            $this->em->persist($rule);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('withholding_tax.rules.edit.success', ['%name%' => $rule->getId()]));

            return $this->redirect($this->generateUrl('withholding_tax_rule_backoffice_section'));
        }

        $breadcrumbHelper = $this->getControllerBreadcrumb()
            ->addPage(
                $this->getIdentificationForRule($withholdingTaxRule),
                $this->generateUrl('withholding_tax_rule_backoffice_section'))
            ->addPage($this->translator->trans('withholding_tax.rules.edit.title'));

        return $this->render('@GeoPagosWithholdingTax/WithholdingTaxRule/edit.html.twig', array_merge(
            $this->getParametersForView(),
            [
                'breadcrumb' => $breadcrumbHelper->getBreadcrumb(),
                'withholdingTaxRule' => $withholdingTaxRule,
                'form' => $form->createView(),
            ]
        ));
    }

    private function createWTRuleForm(WithholdingTaxRule $rule, string $validationGroup = ''): Form
    {
        return $this->createForm(WithholdingTaxRuleType::class, $rule, [
            'method' => 'POST',
        ]);
    }

    public function deleteAction(Request $request, $withholdingTaxRuleId)
    {
        $this->getAuthorizer()->checkAuthorize($this->module, StaticConstant::ROLE_ACTION_DELETE);
        $em = $this->getDoctrine()->getManager();

        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $this->em->getRepository(WithholdingTaxRule::class)->find($withholdingTaxRuleId);
        $name = $this->getIdentificationForRule($withholdingTaxRule);

        try {
            $em->remove($withholdingTaxRule);
            $em->flush();

            $this->get('session')->getFlashBag()
                ->add('success',
                    $this->translator->trans(
                        'withholding_tax.rules.delete.success', [
                            '%name%' => $name,
                        ])
                );
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $this->translator->trans(
                'withholding_tax.rules.delete.error', [
                '%name%' => $name,
            ]));
        }

        return $this->redirect($this->generateUrl('withholding_tax_rule_backoffice_section'));
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
