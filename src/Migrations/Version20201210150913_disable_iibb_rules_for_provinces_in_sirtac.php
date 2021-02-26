<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201210150913_disable_iibb_rules_for_provinces_in_sirtac extends AbstractMigration
{
    const PROVINCES = [
        66, // Salta
        86, // Santiago del Estero,
        78, // Santa Cruz
        14, // Córdoba
        38, // Jujuy
        46, // La Rioja
        94, // Tierra del Fuego
        22, // Chaco
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE
                withholding_tax_rule
            SET is_enabled = 0
            WHERE
                type = \'TAX\'
                AND province_id IN (
                    '.implode(',', self::PROVINCES).'
                )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            UPDATE
                withholding_tax_rule
            SET is_enabled = 1
            WHERE
                type = \'TAX\'
                AND province_id IN (
                    '.implode(',', self::PROVINCES).'
                )
        ');
    }
}
