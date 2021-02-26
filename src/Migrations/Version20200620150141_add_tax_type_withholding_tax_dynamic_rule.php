<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200620150141_add_tax_type_withholding_tax_dynamic_rule extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add tax_type_ to withholding_tax_dynamic_rule';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_dynamic_rule  ADD tax_type VARBINARY(255) NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_dynamic_rule  DROP COLUMN tax_type');
    }
}
