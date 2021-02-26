<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleAmountFieldEnum;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxRuleType extends AbstractFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isNew = AbstractFormType::ACTION_CREATE === $options['action'];

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'withholding_tax.rules.form.type',
                'choices' => [
                    WithholdingTaxTypeEnum::getString(WithholdingTaxTypeEnum::TAX) => WithholdingTaxTypeEnum::TAX,
                    WithholdingTaxTypeEnum::getString(WithholdingTaxTypeEnum::VAT) => WithholdingTaxTypeEnum::VAT,
                    WithholdingTaxTypeEnum::getString(WithholdingTaxTypeEnum::INCOME_TAX) => WithholdingTaxTypeEnum::INCOME_TAX,
                ],
                'required' => true,
            ])
            ->add('province', EntityType::class, [
                'label' => 'withholdingRaxRegisterProvince.province.label',
                'required' => false,
                'placeholder' => 'Todas',
                'empty_data' => null,
                'class' => Province::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('has_tax_registry', ChoiceType::class, [
                'label' => 'Usa Padrón',
                'choices' => [
                    'SI' => true,
                    'no' => false,
                ],
                'required' => true,
            ])
            ->add('unpublish_rate', TextType::class, [
                'label' => 'Retención fuera del padrón',
                'required' => false,
            ])
            ->add('minimumDynamicRuleAmount', TextType::class, [
                'label' => 'withholding_tax.rules.form.minimum_amount_of_dynamic_rule',
                'required' => false,
            ])
            ->add('calculation_basis', ChoiceType::class, [
                'label' => 'Calculo sobre',
                'choices' => [
                    WithholdingTaxRuleAmountFieldEnum::getString(WithholdingTaxRuleAmountFieldEnum::GROSS_STRING) => WithholdingTaxRuleAmountFieldEnum::GROSS_STRING,
                    WithholdingTaxRuleAmountFieldEnum::getString(WithholdingTaxRuleAmountFieldEnum::NET_STRING) => WithholdingTaxRuleAmountFieldEnum::NET_STRING,
                    WithholdingTaxRuleAmountFieldEnum::getString(WithholdingTaxRuleAmountFieldEnum::NET_TAX_STRING) => WithholdingTaxRuleAmountFieldEnum::NET_TAX_STRING,
                    WithholdingTaxRuleAmountFieldEnum::getString(WithholdingTaxRuleAmountFieldEnum::NET_COMMISSION_STRING) => WithholdingTaxRuleAmountFieldEnum::NET_COMMISSION_STRING,
                ],
                'required' => true,
            ])
            ->add('withhold_occasional', ChoiceType::class, [
                'label' => '¿Retiene Ocasionales?',
                'choices' => [
                    'SI' => 1,
                    'no' => 0,
                ],
                'required' => false,
            ])
            ->add('period', TextType::class, [
                'label' => 'Period',
                'help' => 'This month',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class' => WithholdingTaxRule::class,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'withholding_tax_rule';
    }
}
