<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxCategoryPerProvince;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxCategoryPerProvinceType extends AbstractFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('province', EntityType::class, [
                'label' => false,
                'class' => Province::class,
                'placeholder' => 'common.choose_one',
                'choice_label' => 'name',
                'expanded' => false,
                'required' => true,
            ])
            ->add('taxCategory', EntityType::class, [
                'label' => false,
                'class' => TaxCategory::class,
                'choice_label' => 'name',
                'expanded' => false,
                'required' => true,
            ])
            ->add('withholdingTaxNumber', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('withholdingTaxAttachment', FileType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('withholdingTaxFile', HiddenType::class, [
                'label' => false,
                'required' => false,
                'block_prefix' => 'download_button',
            ]);
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class' => WithholdingTaxCategoryPerProvince::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'WithholdingTaxCategoryPerProvinceType';
    }
}
