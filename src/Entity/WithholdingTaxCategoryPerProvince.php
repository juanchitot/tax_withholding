<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;

class WithholdingTaxCategoryPerProvince
{
    public const PATH = '/iibb/wtcpp';

    /** @var int */
    private $id;

    /** @var TaxCategory */
    private $taxCategory;

    /** @var string */
    private $withholdingTaxNumber;

    /** @var string */
    private $withholdingTaxFile;

    /** @var string */
    private $withholdingTaxAttachment;

    /** @var Province */
    private $province;

    /** @var Subsidiary */
    private $subsidiary;

    /** @var Carbon */
    private $createdAt;

    /** @var Carbon */
    private $updatedAt;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return TaxCategory
     */
    public function getTaxCategory(): ?TaxCategory
    {
        return $this->taxCategory;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setTaxCategory(TaxCategory $taxCategory): self
    {
        $this->taxCategory = $taxCategory;

        return $this;
    }

    /**
     * @return string
     */
    public function getWithholdingTaxNumber(): ?string
    {
        return $this->withholdingTaxNumber;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setWithholdingTaxNumber(string $withholdingTaxNumber): self
    {
        $this->withholdingTaxNumber = $withholdingTaxNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getWithholdingTaxFile(): ?string
    {
        return $this->withholdingTaxFile;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setWithholdingTaxFile(?string $withholdingTaxFile): self
    {
        $this->withholdingTaxFile = $withholdingTaxFile;

        return $this;
    }

    /**
     * @param string|null $extension
     */
    public function generateWithholdingTaxFileName($extension = null): string
    {
        $generatedPath = self::PATH.'/'.md5(microtime(true)).($extension ? '.'.$extension : '');

        return $this->withholdingTaxFile = $generatedPath;
    }

    /**
     * @return string
     */
    public function getWithholdingTaxAttachment(): ?string
    {
        return $this->withholdingTaxAttachment;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setWithholdingTaxAttachment(?string $withholdingTaxAttachment): self
    {
        $this->withholdingTaxAttachment = $withholdingTaxAttachment;

        return $this;
    }

    /**
     * @return Province
     */
    public function getProvince(): ?Province
    {
        return $this->province;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setProvince(Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    /**
     * @return Subsidiary
     */
    public function getSubsidiary(): ?Subsidiary
    {
        return $this->subsidiary;
    }

    /**
     * @param Subsidiary $subsidiary
     *
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setSubsidiary(?Subsidiary $subsidiary): self
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setCreatedAt(Carbon $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @return WithholdingTaxCategoryPerProvince
     */
    public function setUpdatedAt(Carbon $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Gets triggered only on insert.
     */
    public function onPrePersist()
    {
        $this->createdAt = Carbon::now();
    }

    /**
     * Gets triggered every time on update.
     */
    public function onPreUpdate()
    {
        $this->updatedAt = Carbon::now();
    }
}
