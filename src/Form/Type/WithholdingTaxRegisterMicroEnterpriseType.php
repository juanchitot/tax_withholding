<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxRegisterMicroEnterpriseType extends AbstractFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isNew = AbstractFormType::ACTION_CREATE === $options['action'];

        if ($isNew) {
            $builder->add('dbFile', FileType::class, [
                'data_class' => null,
                'label' => 'WithholdingTaxRegisterMicroEnterprise.file.label',
                'required' => true,
            ]);
        }
        $builder
            ->add('save', SubmitType::class, [
                'label' => 'common.upload_file',
                'attr' => [
                    'icon_class' => 'fa fa-dot-circle-o',
                    'class' => 'btn btn-success',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class' => WithholdingTaxRuleFile::class,
            'translation_domain' => 'messages',
        ]);
    }

    public function getName()
    {
        return 'withholding_tax_register_micro_enterprise';
    }
}
