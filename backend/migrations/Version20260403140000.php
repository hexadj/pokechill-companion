<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Pokechill obtainability (phase 10): persist import-time tags aligned with setSearchTags().
 */
final class Version20260403140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add pokemon.is_obtainable and pokemon.obtainability_code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pokemon ADD is_obtainable BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE pokemon ADD obtainability_code VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pokemon DROP obtainability_code');
        $this->addSql('ALTER TABLE pokemon DROP is_obtainable');
    }
}
