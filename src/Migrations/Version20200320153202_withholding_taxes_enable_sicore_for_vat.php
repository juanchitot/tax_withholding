<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200320153202_withholding_taxes_enable_sicore_for_vat extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update withholding_tax_system from GANANCIAS to SICORE and enable IVA to generate his report with it';
    }

    public function up(Schema $schema): void
    {
        // Update INCOME TAX System to SICORE
        $this->addSql("update province_withholding_tax_setting set withholding_tax_system = 'SICORE' where withholding_tax_type='INCOME_TAX';");

        // Enable VAT System to render with SICORE
        $this->addSql("update province_withholding_tax_setting set withholding_tax_system = 'SICORE' where withholding_tax_type='VAT';");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
