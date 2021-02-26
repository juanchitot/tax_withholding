<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200701150141_add_use_registry_for_vat_rules extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add tax_type_ to withholding_tax_dynamic_rule';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE withholding_tax_rule  SET has_tax_registry = 1 WHERE type IN ('VAT','INCOME_TAX');");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE withholding_tax_rule  SET has_tax_registry = 0 WHERE type IN ('VAT','INCOME_TAX');");
    }
}
