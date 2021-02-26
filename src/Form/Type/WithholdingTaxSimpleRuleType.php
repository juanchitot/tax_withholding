<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\ApiBundle\Entity\Classification;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxSimpleRuleType extends AbstractFormType
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
            ->add('tax_category', EntityType::class, [
                'label' => 'withholding_tax.rules.form.tax_category',
                'required' => false,
                'placeholder' => '-',
                'empty_data' => null,
                'class' => TaxCategory::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('province', EntityType::class, [
                'label' => 'withholdingRaxRegisterProvince.province.label',
                'required' => false,
                'placeholder' => '-',
                'empty_data' => null,
                'class' => Province::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('rate', TextType::class, [
                'label' => 'Rate %',
                'required' => false,
            ])
            ->add('minimun_amount', TextType::class, [
                'label' => 'withholding_tax.rules.form.minimum_amount',
                'required' => false,
            ])

            ->add('payment_method_type', ChoiceType::class, [
                'label' => 'withholding_tax.simple_rules.form.payment_method_type',
                'choices' => [
                    '-' => null,
                    'Crédito' => 'CREDIT',
                    'Débito' => 'DEBIT',
                ],
                'required' => true,
            ])

            ->add('classification', EntityType::class, [
                'label' => 'withholding_tax.simple_rules.form.classification',
                'required' => false,
                'placeholder' => '-',
                'empty_data' => null,
                'class' => Classification::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
            ])

            ->add('tax_condition', EntityType::class, [
                'label' => 'withholding_tax.simple_rules.form.tax_condition',
                'required' => false,
                'placeholder' => '-',
                'empty_data' => null,
                'class' => TaxCondition::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
            ])

            ->add('income_tax', ChoiceType::class, [
                'label' => 'withholding_tax.simple_rules.form.income_tax',
                'choices' => [
                    'NI' => 'NI',
                    'AC' => 'AC',
                    'EX' => 'EX',
                    'NC' => 'NC',
                ],
                'required' => false,
                'placeholder' => '-',
                'empty_data' => null,
            ]);
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class' => WithholdingTaxSimpleRule::class,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'withholding_tax_simple_rule';
    }
}
