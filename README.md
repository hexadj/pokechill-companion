# Pokechill Companion (V1)

Projet d'aide a la recommandation de Pokemon (V1) : backend Symfony + PostgreSQL local.

## Prerequis

- PHP 8.4+
- Composer
- PostgreSQL local

## Demarrage du backend

1. Remplir la config locale :
   - copier `backend/.env.local.example` vers `backend/.env.local`
   - renseigner `APP_SECRET`
   - renseigner `DATABASE_URL`
   - renseigner `POKECHILL_SOURCE_URL` (URL raw ou chemin local pour l'import de reference)
2. Verifier que Symfony demarre :
   - `php backend/bin/console --version`
   - `php backend/bin/console cache:clear`
3. Initialiser la base locale si besoin :
   - `php backend/bin/console doctrine:migrations:migrate --no-interaction`
   - `php backend/bin/console doctrine:schema:validate`

## Parcours local documente

- Le parcours valide a ce stade repose sur une instance PostgreSQL locale pointee par `DATABASE_URL`.
- Les fichiers `backend/compose.yaml` et `backend/compose.override.yaml` proviennent du scaffold Symfony/Doctrine et ne constituent pas encore le parcours local documente de reference.

## Etat actuel

- Phase 1 terminee : Doctrine, les entites V1 et les migrations initiales sont en place.
- Prochaine etape : Phase 2 - seeders `type` / `type_effectiveness` et repositories de reference.
