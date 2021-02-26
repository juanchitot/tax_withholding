<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201113130534_enable_currently_used_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE
              withholding_tax_rule wtr
              LEFT JOIN withholding_tax_simple_rule wtsr ON (
                (
                  wtr.province_id = wtsr.province_id
                  AND BINARY wtr.type = BINARY wtsr.type
                  AND BINARY wtr.type = BINARY \'TAX\'
                  AND wtsr.rate > 0
                ) OR (
                  BINARY wtr.type = BINARY wtsr.type
                  AND wtr.type IN (\'INCOME_TAX\', \'VAT\', \'ITBIS\')
                  AND wtsr.rate > 0
                )
              )
              LEFT JOIN withholding_tax_hard_rule wthr ON (
                wtr.id = wthr.withholding_tax_rule_id
                AND wthr.rate > 0
              )
            SET wtr.is_enabled = 1 
            WHERE
              wtsr.id IS NOT NULL
              OR wthr.id IS NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            UPDATE withholding_tax_rule SET is_enabled = 0;
        ');
    }
}
