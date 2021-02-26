<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Certificate;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\User;

class CreateRequest
{
    protected $subsidiary;
    protected $period;
    protected $fiscalId;
    /**
     * @var User|null
     */
    private $owner;
    /**
     * @var array
     */
    private $requestData = [];

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getSubsidiary()
    {
        return $this->subsidiary;
    }

    /**
     * @return CreateRequest
     */
    public function setSubsidiary($subsidiary)
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPeriod(): Carbon
    {
        return $this->period;
    }

    /**
     * @param mixed $period
     *
     * @return CreateRequest
     */
    public function setPeriod(Carbon $period)
    {
        $this->period = $period;

        return $this;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function getFiscalId()
    {
        return $this->fiscalId;
    }

    public function setFiscalId($fiscalId)
    {
        $this->fiscalId = $fiscalId;

        return $this;
    }

    public function setRequestData(array $dataToSend)
    {
        $this->requestData = $dataToSend;

        return $this;
    }

    public function getRequestData(): array
    {
        return $this->requestData;
    }
}
