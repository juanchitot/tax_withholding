<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210106170535_add_tax_concept_table extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tax_concept (
                id INT AUTO_INCREMENT NOT NULL,
                concept VARCHAR(250) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DROP TABLE tax_concept;
        ');
    }
}
