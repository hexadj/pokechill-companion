# Pokechill Companion (V1)

Projet d'aide a la recommandation de Pokemon (V1) : backend Symfony + PostgreSQL local.

## Prerequis

- PHP 8.4+
- Composer
- PostgreSQL local
- Node.js 20+ et npm (frontend)

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

1. Configurer l'environnement de test : copier ou editer `backend/.env.test` (et optionnellement `backend/.env.test.local`) pour que `DATABASE_URL` pointe vers une instance PostgreSQL valide. En `APP_ENV=test`, Doctrine ajoute le suffixe `_test` au nom de base (voir `backend/config/packages/doctrine.yaml`).
2. Appliquer les migrations sur la base de test :
   - PowerShell : `$env:APP_ENV="test"; php backend/bin/console doctrine:migrations:migrate --no-interaction"`
3. Lancer la suite :
   - `cd backend`
   - `composer test` (equivalent a `php bin/phpunit`)

Les tests **unitaires** s'executent sans base. Les tests **d'integration** et **fonctionnels** necessitent PostgreSQL et des migrations appliquees ; sinon ils sont ignores (`markTestSkipped`) avec un message explicite.

## Frontend (Vite + React + TypeScript)

1. Installer les dependances : `cd frontend` puis `npm install`.
2. Demarrer l'API Symfony en local (par defaut le proxy Vite attend `http://127.0.0.1:8000`, voir `frontend/vite.config.ts`).
3. Lancer l'UI : `npm run dev` (URL affichee par Vite, en general `http://localhost:5173`).
4. Build de production : `npm run build` ; variable optionnelle `VITE_API_BASE_URL` (voir `frontend/.env.example`) si l'API n'est pas servie derriere la meme origine / `/api`.

## Parcours complet V1 (ordre recommande)

Toutes les commandes ci-dessous partent de la racine du depot. Une instance **PostgreSQL** locale doit etre joignable via `DATABASE_URL` dans `backend/.env.local`.

1. **Configuration** : copier `backend/.env.local.example` vers `backend/.env.local`, renseigner `APP_SECRET`, `DATABASE_URL`, et `POKECHILL_SOURCE_URL` (URL raw GitHub ou chemin fichier local vers la source Pokechill).
2. **Migrations** : `php backend/bin/console doctrine:migrations:migrate --no-interaction` (la migration de phase 9 convertit les anciennes stats "BST bruts" en etoiles `1..6`, laisse intactes les lignes deja migrees, durcit les contraintes SQL, et doit etre consideree comme irreversible cote donnees ; en cas de doute sur l'historique d'un environnement, relancer ensuite l'import ci-dessous).
3. **Referentiels** (18 types + matrice d'efficacite) : `php backend/bin/console app:reference-data:seed`
4. **Import Pokemon** (optionnel en dry-run) : `php backend/bin/console app:pokechill:import-reference-data --dry-run` puis sans `--dry-run` pour ecrire en base (source de verite alignee jeu : ratings + types).
5. **API** : par exemple `php -S 127.0.0.1:8000 -t backend/public` (ou votre stack Symfony habituelle sur le meme port que le proxy Vite).
6. **Frontend** : `cd frontend`, `npm install` si besoin, `npm run dev` - le proxy envoie `/api` vers `http://127.0.0.1:8000` (voir `frontend/vite.config.ts`).

Les fichiers `backend/compose.yaml` et `backend/compose.override.yaml` viennent du scaffold Symfony ; ce n'est pas le parcours de reference documente ici tant qu'aucun service Compose dedie n'est decrit dans ce README.

### Verification rapide (API)

Apres l'import, un `GET` doit retourner au moins un Pokemon ; recuperez un `sourceKey` dans la reponse pour le `POST`.

**curl** (bash) :

```bash
curl -s "http://127.0.0.1:8000/api/v1/reference/pokemon?limit=1"
curl -s -X POST "http://127.0.0.1:8000/api/v1/recommendations" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"opponentSourceKeys\":[\"REMPLACEZ_PAR_UN_SOURCE_KEY_DU_GET\"],\"limit\":5}"
```

**PowerShell** :

```powershell
Invoke-RestMethod "http://127.0.0.1:8000/api/v1/reference/pokemon?limit=1"
$body = '{"opponentSourceKeys":["REMPLACEZ_PAR_UN_SOURCE_KEY_DU_GET"],"limit":5}'
Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:8000/api/v1/recommendations" `
  -ContentType "application/json; charset=utf-8" -Body $body
```

## Evolutions post-V1 (backlog)

Hors perimetre actuel : moves, abilities, items, IV/EV, niveaux, meteo, score defensif, historique, equipes sauvegardees, auth, import auto - voir [roadmap - Hors perimetre V1](project-management/implementation/roadmap.md).

Pistes naturelles ensuite : enrichir le moteur de score, filtres de candidats, equipes sauvegardees, trace d'import - voir [roadmap - Bascule vers les evolutions futures](project-management/implementation/roadmap.md).

## Etat actuel

- Backend : API V1 (`GET /api/v1/reference/pokemon` avec stats `1..6`, `bstSum`, `division` informatifs ; `POST /api/v1/recommendations` avec la meme forme pour `opponentTeam`), import Pokechill (`statToRating` en normalisation), moteur de recommandation, PHPUnit (unitaires + integration + fonctionnels API). Voir `backend/phpunit.dist.xml` et `project-management/implementation/progress-tracking.md`.
- Frontend : `frontend/` (Vite, React 19, TypeScript, TanStack Query, React Router), page `RecommendationPage` (equipe adverse, limite de resultats, recommandations + detail matchups, rappel ruleset V1 repliable, division informative affichee sur les slots adverses).
- Integration V1 : parcours local documente ci-dessus (Phase 8 roadmap) ; erreurs reseau / JSON cote client centralisees dans `frontend/src/shared/api/client.ts`.
