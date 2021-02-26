<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210129125802_add_settlement_date_to_withholding_tax_detail extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds SettlementDate Field to WithholdingTaxDetail table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_detail ADD COLUMN `settlement_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_detail DROP COLUMN  `settlement_date` ');
    }
}
