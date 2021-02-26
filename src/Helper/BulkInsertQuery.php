<?php

namespace GeoPagos\WithholdingTaxBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Identifier;

class BulkInsertQuery
{
    /** @var Connection */
    protected $connection;

    /** @var Identifier */
    protected $table;

    /** @var string[] */
    protected $columns = [];

    /** @var array[] */
    protected $valueSets = [];

    /** @var int[] PDO::PARAM_* */
    protected $types = [];

    /** @var int|null */
    protected $lastInsertId = null;

    /** @var int|null */
    protected $numInsertedRows = null;

    /**
     * BulkInsertQuery constructor.
     */
    public function __construct(
        Connection $connection,
        string $table
    ) {
        $this->connection = $connection;
        $this->table = new Identifier($table);
    }

    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return $this
     */
    public function setValues(array $valueSets, array $types = null): self
    {
        $this->valueSets = $valueSets;
        $this->types = $types;

        return $this;
    }

    public function execute(): self
    {
        $sql = $this->getSQL();
        $parameters = array_reduce($this->valueSets, function (array $flattenedValues, array $valueSet) {
            return array_merge($flattenedValues, array_values($valueSet));
        }, []);
        $this->connection->executeUpdate($sql, $parameters, $this->getPositionalTypes());
        $this->lastInsertId = $this->connection->lastInsertId();
        $this->numInsertedRows = count($this->valueSets);

        return $this;
    }

    public function getLastInsertIds(): array
    {
        $lastInsertIds = [];
        if (null !== $this->lastInsertId && $this->numInsertedRows > 0) {
            $lastInsertIds = range(
                $this->lastInsertId,
                $this->lastInsertId + $this->numInsertedRows - 1
            );
        }

        return $lastInsertIds;
    }

    protected function getSQL(): string
    {
        $platform = $this->connection->getDatabasePlatform();
        $escapedColumns = array_map(function ($column) use ($platform) {
            return (new Identifier($column))->getQuotedName($platform);
        }, $this->columns);
        // (id, name, ..., date)
        $columnString = empty($this->columns) ? '' : '('.implode(', ', $escapedColumns).')';
        // (?, ?, ?, ... , ?)
        $singlePlaceholder = '('.implode(', ', array_fill(0, count($this->columns), '?')).')';
        // (?, ?), ... , (?, ?)
        $placeholders = implode(', ', array_fill(0, count($this->valueSets), $singlePlaceholder));
        $sql = sprintf(
            'INSERT INTO %s %s VALUES %s;',
            $this->table->getQuotedName($platform),
            $columnString,
            $placeholders
        );

        return $sql;
    }

    /**
     * @return int[] PDO::PARAM_*
     */
    protected function getPositionalTypes(): array
    {
        if (empty($this->types)) {
            return [];
        }
        $types = array_values($this->types);
        $repeat = count($this->valueSets);
        $positionalTypes = [];
        for ($i = 1; $i <= $repeat; ++$i) {
            $positionalTypes = array_merge($positionalTypes, $types);
        }

        return $positionalTypes;
    }
}
