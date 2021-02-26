<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200602140622_add_imported_to_withholding_tax_register_province extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax_register_province` ADD COLUMN `imported` bigint DEFAULT 0; ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_register_province DROP COLUMN `imported`; ');
    }
}
