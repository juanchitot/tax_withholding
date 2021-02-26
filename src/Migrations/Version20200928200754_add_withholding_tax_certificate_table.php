<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200928200754_add_withholding_tax_certificate_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add the table to persist the certificates generated ';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('
        CREATE TABLE withholding_tax_certificate
            (
                id            INT AUTO_INCREMENT NOT NULL, 
                subsidiary_id INT DEFAULT NULL,
                province_id   INT DEFAULT NULL,
                fileName      VARCHAR(255)       NOT NULL,
                date_period   DATE NOT NULL,
                type    ENUM(\'TAX\',\'VAT\',\'INCOME_TAX\') DEFAULT \'TAX\' NOT NULL, 
                status  ENUM(\'CREATED\',\'SENT\',\'FAILED\') DEFAULT \'CREATED\'  NOT NULL,
                CONSTRAINT FK_580E964CD4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES subsidiary (id),
                CONSTRAINT FK_580E964CE946114A FOREIGN KEY (province_id) REFERENCES province (id),      
                UNIQUE INDEX subsidiary_province_certificate_date_idx (subsidiary_id, province_id, date_period),
                PRIMARY KEY (id)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
