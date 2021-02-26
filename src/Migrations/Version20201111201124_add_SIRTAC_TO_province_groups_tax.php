<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201111201124_add_SIRTAC_TO_province_groups_tax extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add SIRTAC to tax_rule_provinces_group ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO tax_rule_provinces_group (id, name) VALUES (1, "SIRTAC")
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM tax_rule_provinces_group WHERE id = 1');
    }
}
