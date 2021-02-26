<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200511121500_update_province_withholding_tax_setting_sicore_semi_monthly extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update Withholding Tax Setting for SICORE system to "SEMI_MONTHLY"';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update province_withholding_tax_setting set period = 'SEMI_MONTHLY'
            where withholding_tax_system = 'SICORE' and withholding_tax_type IN ('VAT', 'INCOME_TAX');");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update province_withholding_tax_setting set period = 'MONTHLY'
            where withholding_tax_system = 'SICORE' and withholding_tax_type IN ('VAT', 'INCOME_TAX');");
    }
}
