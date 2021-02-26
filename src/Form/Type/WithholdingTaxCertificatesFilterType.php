<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Form\Transformer\DateToPeriodTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class WithholdingTaxCertificatesFilterType extends AbstractFormType
{
    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var DateToPeriodTransformer */
    private $periodTransformer;

    public function __construct(
        ConfigurationManager $configurationManager,
        DateToPeriodTransformer $periodTransformer
    ) {
        $this->configurationManager = $configurationManager;
        $this->periodTransformer = $periodTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('periodFrom', DateType::class, [
            'label' => 'withholding_tax.certificates.form.period_from',
            'widget' => 'single_text',
            'html5' => false,
            'attr' => [
                'class' => 'js-datepicker',
                'label_width' => 12,
                'input_width' => 12,
                'container_class' => 'no-gutter',
            ],
            'label_attr' => [
                'style' => 'text-align: left !important',
            ],
            'required' => true,
            'error_bubbling' => true,
            'mapped' => false,
            'constraints' => [
                new NotBlank(),
                new DateTime(),
            ],
            'view_timezone' => $this->configurationManager->get('timezone'),
        ])->add('periodTo', DateType::class, [
            'label' => 'withholding_tax.certificates.form.period_to',
            'widget' => 'single_text',
            'html5' => false,
            'attr' => [
                'class' => 'js-datepicker',
                'label_width' => 12,
                'input_width' => 12,
                'container_class' => 'no-gutter',
            ],
            'label_attr' => [
                'style' => 'text-align: left !important',
            ],
            'required' => true,
            'mapped' => false,
            'error_bubbling' => true,
            'constraints' => [
                new NotBlank(),
                new DateTime(),
                new GreaterThan([
                    'propertyPath' => 'parent.all[periodFrom].data',
                    'message' => 'comparison.end_date_greater_than',
                ]),
            ],
            'view_timezone' => $this->configurationManager->get('timezone'),
        ])->add('taxType', ChoiceType::class, [
            'choices' => [
                'common.all' => '0',
                'VAT' => WithholdingTaxTypeEnum::VAT,
                'INCOME_TAX' => WithholdingTaxTypeEnum::INCOME_TAX,
                'TAX' => WithholdingTaxTypeEnum::TAX,
                'SIRTAC' => WithholdingTaxTypeEnum::SIRTAC,
            ],
            'attr' => [
                'label_width' => 12,
                'input_width' => 12,
                'class' => 'form-control',
                'container_class' => 'no-gutter',
            ],
            'label_attr' => [
                'style' => 'text-align: left !important',
            ],
            'multiple' => false,
            'expanded' => false,
            'label' => 'withholding_tax.certificates.form.tax_type',
            'required' => true,
            'mapped' => false,
            'placeholder' => 'common.all',
        ]);
        $builder->get('periodFrom')->addModelTransformer($this->periodTransformer);
        $builder->get('periodTo')->addModelTransformer($this->periodTransformer);
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
