<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200406181319_update_province_withholding_tax_setting_caba_agip extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update Withholding tax simple Rules 20-03-2020';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update province_withholding_tax_setting set withholding_tax_system = 'AGIP', code = '028' where province_id = 2 and withholding_tax_type = 'TAX';");
    }

    public function down(Schema $schema): void
    {
        // no down migration
    }
}
