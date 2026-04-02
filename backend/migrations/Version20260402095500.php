<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402095500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'align unique index names with doctrine naming strategy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX uq_pokemon_source_key RENAME TO UNIQ_62DC90F3DB64AEE8');
        $this->addSql('ALTER INDEX uq_type_code RENAME TO UNIQ_8CDE572977153098');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX UNIQ_62DC90F3DB64AEE8 RENAME TO uq_pokemon_source_key');
        $this->addSql('ALTER INDEX UNIQ_8CDE572977153098 RENAME TO uq_type_code');
    }
}

