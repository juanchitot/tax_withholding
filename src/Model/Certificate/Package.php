<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Certificate;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;

class Package
{
    protected $certificateEntity;
    protected $attachmentFilename;
    protected $localFilename;
    protected $period;
    protected $data;
    protected $taxType;
    protected $provinceId;
    protected $fiscalId;
    protected $taxSettings;
    protected $subsidiary;
    /**
     * @var Province|null
     */
    private $province;

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function getLocalFilename()
    {
        return $this->localFilename;
    }

    /**
     * @return Package
     */
    public function setLocalFilename($localFilename)
    {
        $this->localFilename = $localFilename;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCertificateEntity(): Certificate
    {
        return $this->certificateEntity;
    }

    /**
     * @return Package
     */
    public function setCertificateEntity($certificateEntity)
    {
        $this->certificateEntity = $certificateEntity;

        return $this;
    }

    public function getAttachmentFilename()
    {
        return $this->attachmentFilename;
    }

    /**
     * @return Package
     */
    public function setAttachmentFilename($attachmentFilename)
    {
        $this->attachmentFilename = $attachmentFilename;

        return $this;
    }

    public function getPeriod(): Carbon
    {
        return $this->period;
    }

    /**
     * @return Package
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Package
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function pushData($item)
    {
        $this->data[] = $item;
    }

    public function setTaxType(string $taxType)
    {
        $this->taxType = $taxType;

        return $this;
    }

    public function getTaxType()
    {
        return $this->taxType;
    }

    /**
     * @return Package
     */
    public function setFiscalId($fiscalId)
    {
        $this->fiscalId = $fiscalId;

        return $this;
    }

    public function getFiscalId()
    {
        return $this->fiscalId;
    }

    /**
     * @return Package
     */
    public function setProvinceId($provinceId)
    {
        $this->provinceId = $provinceId;

        return $this;
    }

    public function getProvinceId()
    {
        return $this->provinceId;
    }

    public function setTaxSettings($taxSettings)
    {
        $this->taxSettings = $taxSettings;

        return $this;
    }

    public function getTaxSettings()
    {
        return $this->taxSettings;
    }

    public function getSubsidiary()
    {
        return $this->subsidiary;
    }

    public function setSubsidiary($subsidiary): Package
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    public function setProvince(?Province $province): Package
    {
        $this->province = $province;

        return $this;
    }
}
