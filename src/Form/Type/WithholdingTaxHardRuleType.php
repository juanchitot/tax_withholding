<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxHardRuleType extends AbstractFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isNew = AbstractFormType::ACTION_CREATE === $options['action'];

        $builder
            ->add('verification_date', TextType::class, [
                'label' => 'withholding_tax.hard_rules.form.verification_date',
                'required' => false,
            ])
            ->add('rate', TextType::class, [
                'label' => 'withholding_tax.hard_rules.form.rate',
                'required' => false,
            ])
            ->add('rule', TextareaType::class, [
                'label' => 'withholding_tax.hard_rules.form.rule',
                'required' => true,
                'attr' => array('rows' => '8'),
            ])
            ->add('minimun_amount', TextType::class, [
                'label' => 'withholding_tax.rules.form.minimum_amount',
                'required' => false,
            ])

            ;
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class' => WithholdingTaxHardRule::class,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'withholding_tax_hard_rule';
    }
}
