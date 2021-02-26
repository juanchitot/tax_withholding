<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200325130337_withholding_taxes_enable_catamarca_sircar_and_certificate_creation extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable Catamarca to generate SIRCAR file';
    }

    public function up(Schema $schema): void
    {
        // Update INCOME TAX System to SICORE
        $this->addSql("INSERT INTO `province_withholding_tax_setting`
            (`province_id`, `withholding_tax_type`, `withholding_tax_system`, `type`, `code`, `agent_subsidiary`, 
             `last_certificate`, `period`, `min_amount`, `resolution`, `number`)
            VALUES
            (:provinceId,'TAX','SIRCAR','901','903',NULL,0,'MONTHLY',2500.00,'RG (Catamarca) 34/2016 RG','XX-XXXXXXXX-X')",
            [
                'provinceId' => $this->getProvinceId('Catamarca'),
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    public function getProvinceId($provinceName)
    {
        return $this->connection->fetchColumn("SELECT * FROM province WHERE name LIKE '".$provinceName."' ORDER BY id ASC LIMIT 1");
    }
}
