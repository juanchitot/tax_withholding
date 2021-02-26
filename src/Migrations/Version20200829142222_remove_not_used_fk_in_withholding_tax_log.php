<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200829142222_remove_not_used_fk_in_withholding_tax_log extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            alter table withholding_tax_log drop FOREIGN KEY FK_E3B3F2211847302E;
            ');
    }

    public function down(Schema $schema): void
    {
        // TODO: Implement down() method.
    }
}
