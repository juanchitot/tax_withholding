<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200821184338_add_tax_category_and_condition_to_withholding_tax extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax ADD tax_condition_id INT DEFAULT NULL, ADD tax_category_id INT DEFAULT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax` DROP COLUMN `tax_condition_id`, `tax_category_id`;');
    }
}
