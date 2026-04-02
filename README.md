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

## Tests backend (PHPUnit)

1. Configurer l’environnement de test : copier ou éditer `backend/.env.test` (et optionnellement `backend/.env.test.local`) pour que `DATABASE_URL` pointe vers une instance PostgreSQL valide. En `APP_ENV=test`, Doctrine ajoute le suffixe `_test` au nom de base (voir `backend/config/packages/doctrine.yaml`).
2. Appliquer les migrations sur la base de test :
   - PowerShell : `$env:APP_ENV="test"; php backend/bin/console doctrine:migrations:migrate --no-interaction"`
3. Lancer la suite :
   - `cd backend`
   - `composer test` (équivalent à `php bin/phpunit`)

Les tests **unitaires** s’exécutent sans base. Les tests **d’intégration** et **fonctionnels** nécessitent PostgreSQL et des migrations appliquées ; sinon ils sont ignorés (`markTestSkipped`) avec un message explicite.

## Parcours local documente

- Le parcours valide a ce stade repose sur une instance PostgreSQL locale pointee par `DATABASE_URL`.
- Les fichiers `backend/compose.yaml` et `backend/compose.override.yaml` proviennent du scaffold Symfony/Doctrine et ne constituent pas encore le parcours local documente de reference.

## Etat actuel

- Backend : API V1 (`GET /api/v1/reference/pokemon`, `POST /api/v1/recommendations`), import Pokechill, moteur de recommandation, PHPUnit (unitaires + intégration + fonctionnels API). Voir `backend/phpunit.dist.xml` et `project-management/implementation/progress-tracking.md`.
- Frontend React : non implémenté (phase roadmap suivante).
