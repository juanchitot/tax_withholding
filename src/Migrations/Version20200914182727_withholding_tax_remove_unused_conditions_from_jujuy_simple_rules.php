<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200914182727_withholding_tax_remove_unused_conditions_from_jujuy_simple_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update withholding_tax_simple_rule wtsr 
                                inner join province p on wtsr.province_id = p.id 
                            set wtsr.tax_condition_id = null, classification_id = null
                           where p.acronym='jujuy';");
    }

    public function down(Schema $schema): void
    {
        // NO DOWN MIGRATION
    }
}
