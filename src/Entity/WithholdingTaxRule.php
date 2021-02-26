<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleAmountFieldEnum;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

class WithholdingTaxRule implements WithholdingTaxRuleInterface
{
    const UNPUBLISHED_DYNAMIC_RULE = 'No publicado en padrón';

    /**
     * @var int
     */
    private $id;

    /**
     * @var WithholdingTaxTypeEnum
     */
    private $type;

    /**
     * @var float
     *
     * @deprecated
     */
    private $rate;

    /**
     * @var TaxCategory
     */
    private $taxCategory;

    /**
     * @var Province
     */
    private $province;

    /**
     * @var float
     */
    private $unpublishRate;

    /**
     * @var float
     */
    private $minimumDynamicRuleAmount;

    /**
     * @var string
     */
    private $calculationBasis;

    /**
     * @var int
     */
    private $withholdOccasional;

    /**
     * @var bool
     */
    private $hasTaxRegistry;

    /**
     * @var string
     */
    private $period;

    /**
     * @var Carbon
     */
    private $downloadDateDb;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var Carbon
     */
    private $modifiedAt;

    /**
     * @var Carbon
     */
    private $createdAt;

    public function __construct()
    {
        $this->withholdOccasional = 0;
        $this->hasTaxRegistry = false;
        $this->rate = 0;
        $this->enabled = true;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    public function getUnpublishRate(): ?float
    {
        return $this->unpublishRate;
    }

    public function getMinimumDynamicRuleAmount(): ?float
    {
        return $this->minimumDynamicRuleAmount;
    }

    public function getCalculationBasis(): ?string
    {
        return $this->calculationBasis;
    }

    public function setCalculationBasis(string $calculationBasis): self
    {
        $this->calculationBasis = $calculationBasis;

        return $this;
    }

    public function getWithholdOccasional(): int
    {
        return $this->withholdOccasional;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function getDownloadDateDb(): ?Carbon
    {
        if (empty($this->downloadDateDb)) {
            return $this->downloadDateDb;
        }

        return Carbon::instance($this->downloadDateDb);
    }

    public function getModifiedAt(): ?Carbon
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(Carbon $modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeAsString()
    {
        return WithholdingTaxTypeEnum::getString($this->type);
    }

    public function getCalculationBasisAsString()
    {
        return WithholdingTaxRuleAmountFieldEnum::getString($this->calculationBasis);
    }

    public function __call($name, $arguments)
    {
        if (property_exists(self::class, $name)) {
            return $this->$name();
        }
    }

    public function getValueFromField(string $field)
    {
        return $this->$field;
    }

    public function hasTaxRegistry(): bool
    {
        return $this->hasTaxRegistry;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): WithholdingTaxRule
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function shouldSkipByMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): bool {
        return false;
    }

    public function calculateRequiredMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): float {
        return 0;
    }

    public function getRate()
    {
        return 0;
    }

    public function setHasTaxRegistry(bool $hasTaxRegistry): self
    {
        $this->hasTaxRegistry = $hasTaxRegistry;

        return $this;
    }

    public function getLogDescription(WithholdingTaxLog $log): string
    {
        return self::UNPUBLISHED_DYNAMIC_RULE;
    }
}
