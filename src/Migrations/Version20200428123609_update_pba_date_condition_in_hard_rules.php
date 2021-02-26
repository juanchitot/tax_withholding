<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200428123609_update_pba_date_condition_in_hard_rules extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update PBA Hard Rule 28-04-2020';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('update withholding_tax_hard_rule set
                                    rule = :jsonCondition 
                           where withholding_tax_rule_id = (select id from withholding_tax_rule where province_id = :provinceId)',
        [
            ':jsonCondition' => '[{"type":"SELECT","field":"count","fieldFunction":"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1) WHEN type_id = \'REFUND\' THEN (-1) ELSE 1 END)","condition":">=","value":"5"},{"type":"SELECT","field":"amount","fieldFunction":"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1 * _t.amount) WHEN type_id = \'REFUND\' THEN (-1 * _t.amount) ELSE _t.amount END)","condition":">=","value":"25000"},{"type":"WHERE","field":"_t.created_at","condition":"<="}, {"type":"WHERE","field":"_t.status","condition":"=","value":"APPROVED"} ]',
            ':provinceId' => $this->getProvinceId('Buenos Aires'),
        ]);
    }

    public function getProvinceId($provinceName)
    {
        return $this->connection->fetchColumn("SELECT * FROM province WHERE name LIKE '".$provinceName."' ORDER BY id ASC LIMIT 1");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
