<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210106170536_add_tax_concept_registers extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO tax_concept (id, concept) VALUES (
                1, \'Retención\'
            ), (
                2, \'Informativo\'
            ), (
                3, \'Excluido\'
            ), (
                4, \'No inscripto\'
            ), (
                5, \'Sobretasa\'
            );
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            TRUNCATE TABLE tax_concept;
        ');
    }
}
