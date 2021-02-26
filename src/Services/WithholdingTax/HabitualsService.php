<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\WithholdingTaxBundle\Entity\Habitual;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Repository\HabitualsRepository;

class HabitualsService
{
    /** @var HabitualsRepository */
    private $habitualsRepository;

    public function __construct(HabitualsRepository $habitualsRepository)
    {
        $this->habitualsRepository = $habitualsRepository;
    }

    public function isSubjectMarkedAsHabitual(Subsidiary $subsidiary, string $taxType, ?Province $province): bool
    {
        if (WithholdingTaxTypeEnum::TAX === $taxType) {
            return null !== $this->habitualsRepository->findBySubjectTaxAndProvince($subsidiary, $taxType, $province);
        } else {
            return null !== $this->habitualsRepository->findBySubjectTaxAndProvince($subsidiary, $taxType, null);
        }
    }

    public function markSubjectAsHabitual(Subsidiary $subsidiary, string $taxType, ?Province $province): ?Habitual
    {
        if ($this->isSubjectMarkedAsHabitual($subsidiary, $taxType, $province)) {
            return null;
        }

        $province = WithholdingTaxTypeEnum::TAX === $taxType ? $province : null;

        return $this->habitualsRepository->markAsHabitual($subsidiary, $taxType, $province);
    }
}
