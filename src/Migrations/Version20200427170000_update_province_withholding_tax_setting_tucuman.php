<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200427170000_update_province_withholding_tax_setting_tucuman extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update Withholding Tax Certificates Tucumán';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update province_withholding_tax_setting set withholding_tax_system = 'SIRCAR2'
            where province_id = (select max(id) from province where acronym = 'tucuman') and withholding_tax_type = 'TAX';");
    }

    public function down(Schema $schema): void
    {
        // no down migration
    }
}
