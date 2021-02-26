<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210202130202_drop_old_habituals_table extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            DROP TABLE `subsidiary_withheld_taxes`;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `subsidiary_withheld_taxes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `subsidiary_id` int(11) NOT NULL,
              `vat_last_withheld` datetime DEFAULT NULL,
              `earnings_tax_last_withheld` datetime DEFAULT NULL,
              `gross_income_tax_last_withheld` datetime DEFAULT NULL,
              `sirtac_tax_last_withheld` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `fk_subsidiary_withheld_tax` (`subsidiary_id`),
              CONSTRAINT `fk_subsidiary_withheld_tax` FOREIGN KEY (`subsidiary_id`) REFERENCES `subsidiary` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
        ');
    }
}
