<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200324183357_withholding_taxes_enable_atm_file_generation extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update withholding_tax_system to ATM in Mendoza Province';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update province_withholding_tax_setting set withholding_tax_system = 'ATM' where province_id=:provinceId;",
            [
                'provinceId' => $this->getProvinceId('Mendoza'),
            ]);
    }

    public function down(Schema $schema): void
    {
    }

    public function getProvinceId($provinceName)
    {
        return $this->connection->fetchColumn("SELECT * FROM province WHERE name LIKE '".$provinceName."' ORDER BY id ASC LIMIT 1");
    }
}
