<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Align pokemon stats with Pokechill star ratings 1..6 (statToRating on prior raw BST values).
 */
final class Version20260403105000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'convert pokemon stats to star ratings 1..6 and tighten CHECK constraints';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pokemon DROP CONSTRAINT chk_pokemon_hp_positive');
        $this->addSql('ALTER TABLE pokemon DROP CONSTRAINT chk_pokemon_atk_positive');
        $this->addSql('ALTER TABLE pokemon DROP CONSTRAINT chk_pokemon_def_positive');
        $this->addSql('ALTER TABLE pokemon DROP CONSTRAINT chk_pokemon_satk_positive');
        $this->addSql('ALTER TABLE pokemon DROP CONSTRAINT chk_pokemon_sdef_positive');
        $this->addSql('ALTER TABLE pokemon DROP CONSTRAINT chk_pokemon_spe_positive');

        $expr = 'CASE WHEN %1$s BETWEEN 1 AND 6 THEN %1$s ELSE GREATEST(1, LEAST(6, ROUND((%1$s::numeric + 16) / 36)))::int END';
        $this->addSql(sprintf(
            'UPDATE pokemon SET
                hp = %1$s,
                atk = %2$s,
                "def" = %3$s,
                satk = %4$s,
                sdef = %5$s,
                spe = %6$s',
            sprintf($expr, 'hp'),
            sprintf($expr, 'atk'),
            sprintf($expr, '"def"'),
            sprintf($expr, 'satk'),
            sprintf($expr, 'sdef'),
            sprintf($expr, 'spe'),
        ));

        $this->addSql('ALTER TABLE pokemon ADD CONSTRAINT chk_pokemon_hp_stars CHECK (hp BETWEEN 1 AND 6)');
        $this->addSql('ALTER TABLE pokemon ADD CONSTRAINT chk_pokemon_atk_stars CHECK (atk BETWEEN 1 AND 6)');
        $this->addSql('ALTER TABLE pokemon ADD CONSTRAINT chk_pokemon_def_stars CHECK ("def" BETWEEN 1 AND 6)');
        $this->addSql('ALTER TABLE pokemon ADD CONSTRAINT chk_pokemon_satk_stars CHECK (satk BETWEEN 1 AND 6)');
        $this->addSql('ALTER TABLE pokemon ADD CONSTRAINT chk_pokemon_sdef_stars CHECK (sdef BETWEEN 1 AND 6)');
        $this->addSql('ALTER TABLE pokemon ADD CONSTRAINT chk_pokemon_spe_stars CHECK (spe BETWEEN 1 AND 6)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Star ratings 1..6 cannot be rolled back to the previous raw BST values.');
    }
}
