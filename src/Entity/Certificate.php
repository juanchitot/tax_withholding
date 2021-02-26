<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;

class Certificate
{
    public const CREATED = 'CREATED';
    public const SENT = 'SENT';
    public const FAILED = 'FAILED';
    /**
     * @var int
     */
    private $id;
    /**
     * @var Subsidiary
     */
    private $subsidiary;
    /**
     * @var DateTime
     */
    private $period;
    /**
     * @var ArrayCollection
     */
    private $withholdingTaxes;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $status;
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var Province
     */
    private $province;

    /** @var int */
    private $sequenceNumber;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getSubsidiary(): Subsidiary
    {
        return $this->subsidiary;
    }

    public function setSubsidiary(Subsidiary $subsidiary): self
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getWithholdingTaxes(): ArrayCollection
    {
        return $this->withholdingTaxes;
    }

    public function setWithholdingTaxes(array $taxes): self
    {
        $this->withholdingTaxes = new ArrayCollection($taxes);

        return $this;
    }

    public function getPeriod(): Carbon
    {
        return Carbon::createFromFormat('Ymd', $this->period->format('Ymd'));
    }

    public function setPeriod(DateTime $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber($sequenceNumber): Certificate
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }
}
