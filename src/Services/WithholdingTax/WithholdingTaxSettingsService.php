<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class WithholdingTaxSettingsService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ArrayCollection */
    private $provinceWithholdingTaxSettings;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function getProvinceWithholdingTaxSettingParams(Province $province, $type): array
    {
        $params = ['withholdingTaxType' => $type];

        if (in_array($type, WithholdingTaxTypeEnum::getProvincialTaxTypes(), true)) {
            $params['province'] = $province;
        }

        return $params;
    }

    public function getSettings($force = false): ArrayCollection
    {
        if (!$this->provinceWithholdingTaxSettings || $force) {
            $this->provinceWithholdingTaxSettings = new ArrayCollection(
                $this->em->getRepository(ProvinceWithholdingTaxSetting::class)->findAll()
            );
        }

        return $this->provinceWithholdingTaxSettings;
    }

    public function getProvinceWithholdingTaxSetting(
        Province $province,
        string $type
    ): ?ProvinceWithholdingTaxSetting {
        $provinceWithholdingTaxSettingParams = $this->getProvinceWithholdingTaxSettingParams($province, $type);

        $collection = $this->getSettings()->filter(
            static function (ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting) use ($provinceWithholdingTaxSettingParams) {
                foreach ($provinceWithholdingTaxSettingParams as $field => $value) {
                    if ($value !== $provinceWithholdingTaxSetting->getValueFromField($field)) {
                        return null;
                    }
                }

                return true;
            });

        if (!$collection->isEmpty()) {
            return $collection->first();
        }

        return null;
    }

    public function setupPeriodCertificateGeneration(Carbon $executionDate): void
    {
        foreach ($this->getSettings() as $provinceWithholdingTaxSetting) {
            /* @var $provinceWithholdingTaxSetting ProvinceWithholdingTaxSetting */
            $provinceWithholdingTaxSetting->setupPeriodCertificateGeneration($executionDate);
            $this->em->persist($provinceWithholdingTaxSetting);
        }
    }

    public function getMostRecentLastPeriodStartDate(): ?Carbon
    {
        $mostRecentLastPeriodStartDate = null;

        foreach ($this->getSettings() as $provinceWithholdingTaxSetting) {
            /* @var $provinceWithholdingTaxSetting ProvinceWithholdingTaxSetting */
            $settingLastPeriodStartDate = $provinceWithholdingTaxSetting->getLastPeriodStartDate();

            if ($settingLastPeriodStartDate && (!$mostRecentLastPeriodStartDate || $settingLastPeriodStartDate->isAfter($mostRecentLastPeriodStartDate))) {
                $mostRecentLastPeriodStartDate = $settingLastPeriodStartDate;
            }
        }

        return $mostRecentLastPeriodStartDate;
    }
}
