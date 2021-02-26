<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201111201120_add_province_groups extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tax_rule_provinces_group (
                id INT AUTO_INCREMENT NOT NULL,
                name varchar(32),
                PRIMARY KEY(id),
                UNIQUE (name)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table tax_rule_provinces_group');
    }
}
