<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201109115535_add_sire_into_province_withholding_tax_system extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Inserts new system for VAT (SIRE)
        $this->addSql("
            INSERT INTO province_withholding_tax_setting (
                province_id, withholding_tax_type, withholding_tax_system, type, code, agent_subsidiary, last_certificate,
                period, min_amount, resolution, number, last_period_last_certificate, last_period_start_date
            ) 
            SELECT 
                province_id, withholding_tax_type, 'SIRE', type, code, agent_subsidiary, last_certificate,
                period, min_amount, 'Retención IVA AFIP RG 4622/2019', number, last_period_last_certificate, last_period_start_date
            FROM 
                province_withholding_tax_setting
            WHERE 
                withholding_tax_type = 'VAT' AND 
                withholding_tax_system = 'SICORE'
        ");

        // Deletes old system for VAT (SICORE)
        $this->addSql("
            DELETE FROM 
                province_withholding_tax_setting
            WHERE
                withholding_tax_type = 'VAT' AND 
                withholding_tax_system = 'SICORE'
        ");
    }

    public function down(Schema $schema): void
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
}
