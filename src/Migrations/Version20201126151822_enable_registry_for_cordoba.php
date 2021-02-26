<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201126151822_enable_registry_for_cordoba extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add registry use for Cordoba';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql('update withholding_tax_rule set has_tax_registry = 1 where province_id = 14 ');
        $this->addSql('DELETE FROM withholding_tax_simple_rule where id in ( 45 , 44) ');
    }

    /**
     * {@inheritdoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('update withholding_tax_rule set has_tax_registry = 0 where province_id = 14 ');
    }
}
