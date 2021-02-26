<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201214182644_rollback_sire_into_province_withholding_tax_system extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore SICORE';
    }

    public function up(Schema $schema): void
    {
        // Restores old system for VAT (SICORE)
        $this->addSql("
            INSERT INTO province_withholding_tax_setting (
                province_id, withholding_tax_type, withholding_tax_system, type, code, agent_subsidiary, last_certificate,
                period, min_amount, resolution, number, last_period_last_certificate, last_period_start_date
            )
            SELECT
                province_id, withholding_tax_type, 'SICORE', type, code, agent_subsidiary, last_certificate,
                period, min_amount, 'Retención IVA RG 4622 AFIP', number, last_period_last_certificate, last_period_start_date
            FROM
                province_withholding_tax_setting
            WHERE
                withholding_tax_type = 'VAT' AND
                withholding_tax_system = 'SIRE'
        ");

        // Deletes new system for VAT (SIRE)
        $this->addSql("
            DELETE FROM
                province_withholding_tax_setting
            WHERE
                withholding_tax_type = 'VAT' AND
                withholding_tax_system = 'SIRE'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
