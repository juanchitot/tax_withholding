<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210106170537_add_concept_id_to_withholding_tax_detail extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_detail ADD concept_id INT NOT NULL DEFAULT 1 AFTER rate;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_detail DROP concept_id
        ');
    }
}
