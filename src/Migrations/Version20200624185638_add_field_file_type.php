<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200624185638_add_field_file_type extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add field fiel_type to withholding_tax_rule_file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_rule_file ADD file_type SMALLINT NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax_rule_file` DROP COLUMN `file_type`;');
    }
}
