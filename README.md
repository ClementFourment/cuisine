# Ma Cuisine

Livre de recettes de famille : cuisine.clementfourment.fr

- Interface : React + TypeScript (Vite), dans `src/`
- API : Cloudflare Worker + Hono, dans `worker/`
- Base de données : Cloudflare D1 (SQLite), schéma dans `migrations/`
- Code commun aux deux (types, validation) : `shared/`

Tout tourne sur l'offre gratuite de Cloudflare, sans carte bancaire.
Les photos sont compressées dans le navigateur (WebP, 1200 px max) et stockées dans D1.

## Développement

```sh
npm install
npm run db:migrate:local      # crée la base locale (.wrangler/)
npm run dev                   # http://localhost:5173
npm run check                 # vérification TypeScript
```

## Mise en ligne

```sh
npm run db:migrate:remote     # seulement si une nouvelle migration a été ajoutée
npm run deploy
```

## Reprise des données de l'ancienne version PHP

`data/` (ignoré par git) contient l'export JSON des recettes. Pour régénérer le fichier SQL :

```sh
CUISINE_LOGIN=... CUISINE_PASSWORD=... npm run seed:make
npx wrangler d1 execute cuisine --remote --file data/seed.sql
```

⚠️ `seed.sql` commence par vider les tables.
