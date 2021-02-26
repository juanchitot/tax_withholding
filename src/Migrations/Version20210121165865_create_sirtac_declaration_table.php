<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210121165865_create_sirtac_declaration_table extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE sirtac_declaration (
                id INT AUTO_INCREMENT NOT NULL,
                subsidiary_id INT NOT NULL,
                province_id INT NOT NULL,
                tax_category_id INT NOT NULL,
                tax_concept_id INT NOT NULL,
                certificate_id INT DEFAULT NULL,
                control_number INT NOT NULL,
                settlement_date DATE NOT NULL,
                withholding_date DATE NOT NULL,
                certificate_number INT DEFAULT NULL,
                settlement_number INT NOT NULL,
                taxable_income NUMERIC(12, 2) NOT NULL,
                rate NUMERIC(6, 5) NOT NULL,
                amount NUMERIC(12, 2) NOT NULL,
                status VARCHAR(30) NOT NULL,
                sales_count INT NOT NULL,
                province_jurisdiction INT NOT NULL,
                FOREIGN KEY (subsidiary_id) REFERENCES subsidiary (id),
                FOREIGN KEY (province_id) REFERENCES province (id),
                FOREIGN KEY (tax_category_id) REFERENCES tax_category (id),
                FOREIGN KEY (certificate_id) REFERENCES withholding_tax_certificate (id),
                FOREIGN KEY (tax_concept_id) REFERENCES tax_concept (id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DROP TABLE sirtac_declaration
        ');
    }
}
