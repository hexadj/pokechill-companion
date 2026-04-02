# Pokechill Companion (V1)

Projet d'aide à la recommandation de Pokemon (V1) : backend Symfony + PostgreSQL local.

## Prérequis

- PHP (8.1+)
- Composer
- PostgreSQL local

## Démarrage du backend

1. Remplir la config locale :
   - copier `backend/.env.local.example` vers `backend/.env.local`
   - renseigner :
     - `APP_SECRET`
     - `DATABASE_URL`
     - `POKECHILL_SOURCE_URL` (URL raw ou chemin local pour l'import de référence)

2. Vérifier que Symfony démarre :
   - `php backend/bin/console --version`
   - `php backend/bin/console cache:clear`

## Prochaine étape

Phase 1 : ajouter Doctrine + PostgreSQL (entites, migration, constraints) pour créer la base de référence applicative.

