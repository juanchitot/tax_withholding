<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class TaxRuleProvincesGroup
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /**
     * @var ArrayCollection
     */
    private $groupItems;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getGroupItems(): Collection
    {
        return $this->groupItems;
    }

    public function setGroupItems(Collection $groupItems): void
    {
        $this->groupItems = $groupItems;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
