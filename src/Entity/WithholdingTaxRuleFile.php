<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Entity\Province;
use Symfony\Component\Validator\Constraints as Assert;

class WithholdingTaxRuleFile
{
    const DB_FILE_PATH = 'storage/withholding_tax/register_province';
    const GROSS_INCOME_TYPE = 1;
    const MICRO_ENTERPRISE = 2;
    const SIRTAC_TYPE = 3;

    /**
     * @var int
     */
    private $id;
    /**
     * @var Province
     */
    private $province;

    /**
     * @var string
     * @Assert\File(
     *   mimeTypes={"application/x-rar", "application/x-rar-compressed", "application/zip", "application/octet-stream", "application/x-zip-compressed", "multipart/x-zip", "text/plain"},
     *   mimeTypesMessage="Formato de archivo inválido"
     *   )
     */
    private $dbFile;

    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $status;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var Carbon
     */
    private $modifiedAt;

    /**
     * @var Carbon
     */
    private $deletedAt;
    /**
     * @var int
     */
    private $imported = 0;
    /**
     * @var int
     */
    private $fileType;

    public function getId(): int
    {
        return $this->id;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): void
    {
        $this->province = $province;
    }

    public function getDbFile(): ?string
    {
        return $this->dbFile;
    }

    public function setDbFile(?string $dbFile): self
    {
        $this->dbFile = $dbFile;

        return $this;
    }

    public function generateDbFile(?string $extension)
    {
        $this->dbFile = self::DB_FILE_PATH.'/'.md5(microtime(true)).($extension ? '.'.$extension : '');

        return $this->dbFile;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate($date): self
    {
        $this->date = $date;
        if (null === $this->getCreatedAt()) {
            $this->setCreatedAt(Carbon::now());
        } else {
            $this->setModifiedAt(Carbon::now());
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt ? Carbon::instance($this->createdAt) : null;
    }

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getModifiedAt(): Carbon
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(Carbon $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }

    public function getDeletedAt(): Carbon
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(Carbon $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function setImported(int $imported): self
    {
        $this->imported = $imported;

        return $this;
    }

    public function getFileType(): int
    {
        return $this->fileType;
    }

    public function setFileType(int $fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }
}
