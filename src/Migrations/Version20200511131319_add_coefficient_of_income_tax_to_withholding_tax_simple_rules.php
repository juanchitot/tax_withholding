<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200511131319_add_coefficient_of_income_tax_to_withholding_tax_simple_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_simple_rule ADD COLUMN `taxable_amount_coefficient` decimal (6,2) DEFAULT 1 ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_simple_rule DROP COLUMN  `taxable_amount_coefficient` ');
    }
}
