<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200814180752_update_province_from_withholding_tax extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update withholding_tax wt inner join subsidiary s on wt.subsidiary_id = s.id 
                           inner join address a on s.address_id = a.id set wt.province_id = a.province_id
                           where wt.type = 'TAX';");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
