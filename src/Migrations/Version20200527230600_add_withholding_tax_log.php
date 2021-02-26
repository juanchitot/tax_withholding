<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200527230600_add_withholding_tax_log extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE withholding_tax_log
                        (
                            id             INT AUTO_INCREMENT NOT NULL,
                            transaction_id INT DEFAULT NULL,
                            created_at     DATETIME(6)   NOT NULL COMMENT \'(DC2Type:datetime)\',
                            tax_condition_id INT DEFAULT NULL,
                            tax_category_id INT DEFAULT NULL,
                            rule_applied VARCHAR(255)  NOT NULL,
                            tax_detail_id INT DEFAULT NULL,
                            UNIQUE INDEX UNIQ_E3B3F2212FC0CB0F (transaction_id),
                            UNIQUE INDEX UNIQ_E3B3F221B2A824D8 (tax_detail_id),
                            CONSTRAINT FK_E3B3F2212FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id),
                            CONSTRAINT FK_E3B3F2211847302E FOREIGN KEY (tax_condition_id) REFERENCES tax_condition (id),
                            CONSTRAINT FK_E3B3F2219DF894ED FOREIGN KEY (tax_category_id) REFERENCES tax_category (id),
                            PRIMARY KEY (id)
                        ) DEFAULT CHARACTER SET utf8
                          COLLATE `utf8_unicode_ci`
                          ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSQL('DROP TABLE  `withholding_tax_log`');
    }
}
