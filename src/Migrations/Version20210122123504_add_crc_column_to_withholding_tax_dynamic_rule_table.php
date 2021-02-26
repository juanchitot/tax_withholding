<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210122123504_add_crc_column_to_withholding_tax_dynamic_rule_table extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_dynamic_rule ADD COLUMN crc INT NULL;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_dynamic_rule DROP COLUMN crc;
        ');
    }
}
