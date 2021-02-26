<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200824144540_set_default_value_to_tax_category_in_withholding_tax extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE withholding_tax wt
                                INNER JOIN subsidiary s ON wt.subsidiary_id = s.id
                            SET wt.tax_condition_id = s.tax_condition_id , wt.tax_category_id = s.tax_category_id
                            where
                                wt.tax_condition_id is null and wt.tax_category_id is null;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
