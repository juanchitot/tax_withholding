<?php

namespace GeoPagos\WithholdingTaxBundle\Entity;

class TaxConcept
{
    /** @var int */
    private $id;

    /** @var string */
    private $concept;

    /** @var WithholdingTaxDetail[] */
    private $taxDetails;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): TaxConcept
    {
        $this->id = $id;

        return $this;
    }

    public function getConcept(): string
    {
        return $this->concept;
    }

    public function setConcept(string $concept): TaxConcept
    {
        $this->concept = $concept;

        return $this;
    }

    /**
     * @return WithholdingTaxDetail[]
     */
    public function getTaxDetails(): array
    {
        return $this->taxDetails;
    }

    /**
     * @param WithholdingTaxDetail[] $taxDetails
     */
    public function setTaxDetails(array $taxDetails): TaxConcept
    {
        $this->taxDetails = $taxDetails;

        return $this;
    }
}
