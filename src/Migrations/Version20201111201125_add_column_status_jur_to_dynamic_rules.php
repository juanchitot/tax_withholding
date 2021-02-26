<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201111201125_add_column_status_jur_to_dynamic_rules extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add SIRCAR to tax_rule_provinces_group ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax_dynamic_rule` ADD COLUMN `status_jurisdictions` varchar(24) default NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_dynamic_rule DROP COLUMN  `status_jurisdictions` ');
    }
}
