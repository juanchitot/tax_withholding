<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210201130202_create_habituals_table extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE habituals (
                id INT AUTO_INCREMENT NOT NULL,
                tax_type VARCHAR(255) NOT NULL,
                subsidiary_id INT NOT NULL,
                province_id INT DEFAULT NULL,
                since DATETIME NOT NULL,
                FOREIGN KEY (subsidiary_id) REFERENCES subsidiary (id),
                FOREIGN KEY (province_id) REFERENCES province (id),
                UNIQUE INDEX subsidiary_province_tax_type_unique_idx (subsidiary_id, province_id, tax_type),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE habituals');
    }
}
