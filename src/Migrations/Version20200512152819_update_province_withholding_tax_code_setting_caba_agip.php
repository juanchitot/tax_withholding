<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200512152819_update_province_withholding_tax_code_setting_caba_agip extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update province_withholding_tax_setting for AGIP code from 028 to 031';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update province_withholding_tax_setting set code = '031' where province_id = 2 and withholding_tax_type = 'TAX' and withholding_tax_system = 'AGIP';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update province_withholding_tax_setting set code = '028' where province_id = 2 and withholding_tax_type = 'TAX' and withholding_tax_system = 'AGIP';");
    }
}
