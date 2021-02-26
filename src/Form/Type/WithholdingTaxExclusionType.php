<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxExclusion;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxExclusionType extends AbstractFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateFrom', DateType::class, [
                'label' => 'withholdingTaxExclusion.dateFrom.label',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'js-datepicker',
                ],
                'required' => false,
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'withholdingTaxExclusion.dateTo.label',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'js-datepicker',
                ],
                'required' => false,
            ])
            ->add('attachment', FileType::class, [
                'label' => 'withholdingTaxExclusion.attachment.label',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WithholdingTaxExclusion::class,
        ]);
    }
}
