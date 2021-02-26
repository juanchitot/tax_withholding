<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxSystem;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Exceptions\InvalidPeriodicityException;
use GeoPagos\WithholdingTaxBundle\Helper\WithholdingTaxPeriodHelper;

class ProvinceWithholdingTaxSetting
{
    /** @var int */
    private $id;

    /** @var Province */
    private $province;

    /** @var string */
    private $withholdingTaxType;

    /** @var string */
    private $withholdingTaxSystem;

    /** @var WithholdingTaxTypeEnum */
    private $type;

    /** @var string */
    private $code;

    /** @var int */
    private $lastCertificate;

    /** @var int */
    private $lastPeriodLastCertificate;

    /** @var string */
    private $period;

    /** @var Carbon */
    private $lastPeriodStartDate;

    /**
     * @var float
     */
    private $minAmount;

    /** @var string */
    private $resolution;

    /** @var string */
    private $number;

    public function __construct($period = Period::MONTHLY)
    {
        $this->lastCertificate = 0;
        $this->lastPeriodLastCertificate = 0;
        $this->period = $period;
        $this->lastPeriodStartDate = $this->calculateLastPeriodStartDate(Carbon::now());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProvince(): Province
    {
        return $this->province;
    }

    public function setProvince(Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    public function getWithholdingTaxSystem(): string
    {
        return $this->withholdingTaxSystem;
    }

    public function setWithholdingTaxSystem(string $withholdingTaxSystem): self
    {
        WithholdingTaxSystem::isValidValueOrThrowException($withholdingTaxSystem);
        $this->withholdingTaxSystem = $withholdingTaxSystem;

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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getPeriod(): string
    {
        return $this->period;
    }

    public function setPeriod(string $period): self
    {
        Period::isValidValueOrThrowException($period);
        $this->period = $period;

        return $this;
    }

    public function getMinAmount(): float
    {
        return $this->minAmount;
    }

    public function setMinAmount(float $minAmount): self
    {
        $this->minAmount = $minAmount;

        return $this;
    }

    public function increaseAndGetLastCertificateNumber(): int
    {
        ++$this->lastCertificate;

        return $this->lastCertificate;
    }

    public function getResolution(): string
    {
        return $this->resolution;
    }

    public function setResolution(string $resolution): self
    {
        $this->resolution = $resolution;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function isSemiMonthly()
    {
        return Period::SEMI_MONTHLY === $this->period;
    }

    public function getWithholdingTaxType(): string
    {
        return $this->withholdingTaxType;
    }

    public function setWithholdingTaxType(string $withholdingTaxType): void
    {
        $this->withholdingTaxType = $withholdingTaxType;
    }

    public function getValueFromField(string $field)
    {
        return $this->$field;
    }

    public function getLastCertificate(): int
    {
        return $this->lastCertificate;
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public function setupPeriodCertificateGeneration(Carbon $now = null): void
    {
        $lastPeriodStartDate = $this->calculateLastPeriodStartDate(
            $now = WithholdingTaxPeriodHelper::getNewDateInstance($now)
        );

        if (Period::MONTHLY === $this->period && $now->day > 15) {
            return;
        }

        if ($lastPeriodStartDate != $this->lastPeriodStartDate) {
            $this->lastPeriodLastCertificate = $this->lastCertificate;
            $this->lastPeriodStartDate = $lastPeriodStartDate;
        }

        $this->lastCertificate = $this->lastPeriodLastCertificate;
    }

    public function getLastPeriodLastCertificate(): int
    {
        return $this->lastPeriodLastCertificate;
    }

    public function getLastPeriodStartDate(): ?Carbon
    {
        return $this->lastPeriodStartDate;
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public function calculateLastPeriodStartDate(Carbon $now = null): Carbon
    {
        try {
            return WithholdingTaxPeriodHelper::getLastPeriodStartDate($this->getPeriod(), $now);
        } catch (InvalidPeriodicityException $e) {
            throw new InvalidPeriodicityException('Province Withholding Tax Settings with incorrect Period. ID: '.
                $this->getId().', Period: '.$this->getPeriod()
            );
        }
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public function calculateLastPeriodEndDate(Carbon $now = null): Carbon
    {
        try {
            return WithholdingTaxPeriodHelper::getLastPeriodEndDate($this->getPeriod(), $now);
        } catch (InvalidPeriodicityException $e) {
            throw new InvalidPeriodicityException('Province Withholding Tax Settings with incorrect Period. ID: '.
                $this->getId().', Period: '.$this->getPeriod()
            );
        }
    }

    public function setLastPeriodStartDate(?Carbon $lastPeriodStartDate): self
    {
        $this->lastPeriodStartDate = $lastPeriodStartDate;

        return $this;
    }

    public function getLastPeriodStartAndEndDateInUTC(Carbon $executionDate)
    {
        return [
            WithholdingTaxPeriodHelper::getLastPeriodStartDate($this->getPeriod(), $executionDate)->copy()->setTimezone('UTC'),
            WithholdingTaxPeriodHelper::getLastPeriodEndDate($this->getPeriod(), $executionDate)->copy()->setTimezone('UTC'),
        ];
    }
}
