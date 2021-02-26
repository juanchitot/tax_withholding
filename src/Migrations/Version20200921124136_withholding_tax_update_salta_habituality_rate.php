<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200921124136_withholding_tax_update_salta_habituality_rate extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("update withholding_tax_hard_rule wtsr
                                inner join withholding_tax_rule wtr on wtsr.withholding_tax_rule_id = wtr.id
                                inner join province p on wtr.province_id = p.id
                            set wtsr.rate=3.6
                            where p.acronym='salta';");
    }

    public function down(Schema $schema): void
    {
        // no down migration
    }
}
