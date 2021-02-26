<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200812185307_add_withholding_tax_category_per_province_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds withholding_tax_category_per_province table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE withholding_tax_category_per_province (id INT AUTO_INCREMENT NOT NULL, subsidiary_id INT NOT NULL, province_id INT NOT NULL, tax_category_id INT NOT NULL, withholding_tax_number VARCHAR(40) DEFAULT NULL, withholding_tax_file VARCHAR(255) DEFAULT NULL, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_F5892833D4A7BDA2 (subsidiary_id), INDEX IDX_F5892833E946114A (province_id), INDEX IDX_F58928339DF894ED (tax_category_id), UNIQUE INDEX subsidiary_province_idx (subsidiary_id, province_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE withholding_tax_category_per_province ADD CONSTRAINT FK_F5892833D4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES subsidiary (id)');
        $this->addSql('ALTER TABLE withholding_tax_category_per_province ADD CONSTRAINT FK_F5892833E946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('ALTER TABLE withholding_tax_category_per_province ADD CONSTRAINT FK_F58928339DF894ED FOREIGN KEY (tax_category_id) REFERENCES tax_category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP TABLE withholding_tax_category_per_province');
    }
}
