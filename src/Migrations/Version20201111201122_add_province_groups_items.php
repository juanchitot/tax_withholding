<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201111201122_add_province_groups_items extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE tax_rule_provinces_group_item (
                    id INT AUTO_INCREMENT NOT NULL, province_id INT NOT NULL,
                    tax_rule_provinces_group_id INT NOT NULL,PRIMARY KEY(id),
                    FOREIGN KEY (province_id) REFERENCES province (id),
                    FOREIGN KEY (tax_rule_provinces_group_id) 
                    REFERENCES tax_rule_provinces_group (id))
            ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table tax_rule_provinces_group_item');
    }
}
