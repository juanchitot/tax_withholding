<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201111201125_add_provinces_to_SIRTAC_grouper extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add provincies to SIRCAR in tax_rule_provinces_group ';
    }

    public function up(Schema $schema): void
    {
        /*
         * 66 - Salta
         * 86 - Santiago del Estero,
         * 78 - Santa Cruz
         * 14 - Córdoba
         * 38 - Jujuy
         * 46 - La Rioja
         * 94 - Tierra del Fuego
         * 22 - Chaco
        */
        $this->addSql('
            INSERT INTO tax_rule_provinces_group_item (province_id, tax_rule_provinces_group_id)
            VALUES (66, 1), (86, 1), (78, 1), (14, 1), (38, 1), (46, 1), (94, 1), (22, 1)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DELETE FROM tax_rule_provinces_group_item WHERE tax_rule_provinces_group_id = "1"
        ');
    }
}
