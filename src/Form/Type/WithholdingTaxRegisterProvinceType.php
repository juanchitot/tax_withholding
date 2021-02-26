<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Type;

use GeoPagos\ApiBundle\Repository\ProvinceRepository;
use GeoPagos\CommonBackBundle\Form\Type\AbstractFormType;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Repository\TaxRuleProvincesGroupRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithholdingTaxRegisterProvinceType extends AbstractFormType
{
    public const REGIME_PREFIX = 'R';
    public const PROVINCE_PREFIX = 'P';

    /**
     * @var ProvinceRepository
     */
    private $provinceRepository;
    /**
     * @var TaxRuleProvincesGroupRepository
     */
    private $taxRuleProvincesGroupRepository;

    public function __construct(
        ProvinceRepository $provinceRepository,
        TaxRuleProvincesGroupRepository $taxRuleProvincesGroupRepository
    ) {
        $this->provinceRepository = $provinceRepository;
        $this->taxRuleProvincesGroupRepository = $taxRuleProvincesGroupRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isNew = AbstractFormType::ACTION_CREATE === $options['action'];
        $provinces = $taxGroups = [];

        $provs = $this->provinceRepository->getProvinceThatUseRegistry();

        $groups = $this->taxRuleProvincesGroupRepository->findAll();

        //create choices to display with a prefix to know
        //which  type of entity is
        foreach ($provs as $key => $prov) {
            $provinces[$provs[$key]->getName()] = self::PROVINCE_PREFIX.$provs[$key]->getId();
        }

        foreach ($groups as $key => $group) {
            $taxGroups[$groups[$key]->getName()] = self::REGIME_PREFIX.$groups[$key]->getId();
        }

        $builder
            ->add('origin', ChoiceType::class, [
                'placeholder' => 'Choose an option',
                'mapped' => false,
                'label' => 'withholdingRaxRegisterProvince.origin.label',
                'choices' => [
                    'withholdingRaxRegisterProvince.regime.label' => $taxGroups,
                    'withholdingRaxRegisterProvince.province.label' => $provinces,
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'common.upload_file',
                'attr' => [
                    'icon_class' => 'fa fa-dot-circle-o',
                    'class' => 'btn btn-success',
                ],
            ]);

        if ($isNew) {
            $builder->add('dbFile', FileType::class, [
                'data_class' => null,
                'label' => 'withholdingRaxRegisterProvince.dbFile.label',
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class' => WithholdingTaxRuleFile::class,
            'translation_domain' => 'messages',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'withholding_tax_register_province';
    }
}
