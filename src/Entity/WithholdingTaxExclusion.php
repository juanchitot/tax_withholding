<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Subsidiary;

class WithholdingTaxExclusion
{
    const PATH = '/iibb/wte';

    /**
     * @var int
     */
    private $id;

    /**
     * @var Subsidiary
     */
    private $subsidiary;

    /**
     * @var Carbon
     */
    private $dateFrom;

    /**
     * @var Carbon
     */
    private $dateTo;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $attachment;

    public function getId(): int
    {
        return $this->id;
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

    public function getDateFrom(): ?Carbon
    {
        return $this->dateFrom ? Carbon::instance($this->dateFrom) : null;
    }

    public function setDateFrom(\DateTime $dateFrom = null): self
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function getDateTo(): ?Carbon
    {
        return $this->dateTo ? Carbon::instance($this->dateTo) : null;
    }

    public function setDateTo(\DateTime $dateTo = null): self
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function generateFileName($extension = null)
    {
        $this->file = self::PATH.'/'.md5(microtime(true)).($extension ? '.'.$extension : '');

        return $this;
    }

    public function setFile(string $path): self
    {
        $this->file = $path;

        return $this;
    }
}
