<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210208170425_add_sequence_number_column_to_certificate_table extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_certificate ADD COLUMN sequence_number INT DEFAULT NULL;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_certificate DROP COLUMN  sequence_number;
        ');
    }
}
