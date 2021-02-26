<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200624183923_rename_withholding_tax_register_province_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename withholding_tax_register_province to withholding_tax_rule_file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax_register_province` RENAME TO  `withholding_tax_rule_file` ;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax_rule_file` RENAME TO  `withholding_tax_register_province` ;');
    }
}
