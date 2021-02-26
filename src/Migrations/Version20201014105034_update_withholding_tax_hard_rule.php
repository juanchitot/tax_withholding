<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201014105034_update_withholding_tax_hard_rule extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $sql = '
            update withholding_tax_hard_rule set rule = replace(
	            rule,
	            \'{"type":"SELECT","field":"count","fieldFunction":"SUM(CASE WHEN type_id = \'\'ADJUSTMENT_DEBIT\'\' THEN (-1) WHEN type_id = \'\'REFUND\'\' THEN (-1) ELSE 1 END)","condition":\',
	            \'{"type":"SELECT","field":"count","fieldFunction":"SUM(CASE WHEN type_id = \'\'ADJUSTMENT_DEBIT\'\' THEN (-1) WHEN type_id = \'\'REFUND\'\' THEN (-1) WHEN type_id = \'\'WT_ADJUSTMENT_NET\'\' THEN (0) WHEN type_id = \'\'WT_ADJUSTMENT_GROSS\'\' THEN (0) ELSE 1 END)","condition":\'
            );
        ';
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $sql = '
            update withholding_tax_hard_rule set rule = replace(
	            rule,
	            \'{"type":"SELECT","field":"count","fieldFunction":"SUM(CASE WHEN type_id = \'\'ADJUSTMENT_DEBIT\'\' THEN (-1) WHEN type_id = \'\'REFUND\'\' THEN (-1) WHEN type_id = \'\'WT_ADJUSTMENT_NET\'\' THEN (0) WHEN type_id = \'\'WT_ADJUSTMENT_GROSS\'\' THEN (0) ELSE 1 END)","condition":\',
	            \'{"type":"SELECT","field":"count","fieldFunction":"SUM(CASE WHEN type_id = \'\'ADJUSTMENT_DEBIT\'\' THEN (-1) WHEN type_id = \'\'REFUND\'\' THEN (-1) ELSE 1 END)","condition":\'
            );
        ';
        $this->addSql($sql);
    }
}
