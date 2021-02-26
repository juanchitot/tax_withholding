<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRuleProvinceRate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxDynamicRuleProvinceRateType extends AbstractFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isNew = AbstractFormType::ACTION_CREATE === $options['action'];

        $builder
            ->add('province', EntityType::class, [
                'label' => 'withholding_tax.dynamic_rule_province_rate.form.province',
                'required' => true,
                'class' => Province::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('rate', TextType::class, [
                'label' => 'withholding_tax.dynamic_rule_province_rate.form.rate',
                'required' => true,
            ])
            ->add('externalId', TextType::class, [
                'label' => 'withholding_tax.dynamic_rule_province_rate.form.external_id',
                'required' => true,
            ])
            ;
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class' => WithholdingTaxDynamicRuleProvinceRate::class,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'withholding_tax_dynamic_rule_province_rate';
    }
}
