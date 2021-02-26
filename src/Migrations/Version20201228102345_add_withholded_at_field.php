<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201228102345_add_withholded_at_field extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds withholdedAt Field to WithholdingTaxDetail table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_detail ADD COLUMN `withholded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_detail DROP COLUMN  `withholded_at` ');
    }
}
