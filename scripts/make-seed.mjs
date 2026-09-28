// Génère data/seed.sql à partir de l'export de l'ancienne base MariaDB.
//
//   CUISINE_LOGIN=philae CUISINE_PASSWORD=... npm run seed:make
//
// Entrée : data/recettes-export.json (SELECT JSON_ARRAYAGG(...) FROM recette)
// Sortie : data/seed.sql, à appliquer avec
//   npx wrangler d1 execute cuisine --local|--remote --file data/seed.sql

import { pbkdf2Sync, randomBytes } from "node:crypto";
import { readFileSync, writeFileSync } from "node:fs";

const login = process.env.CUISINE_LOGIN;
const password = process.env.CUISINE_PASSWORD;
if (!login || !password) {
  console.error("Définir CUISINE_LOGIN et CUISINE_PASSWORD.");
  process.exit(1);
}

// Même format que worker/auth.ts
const ITERATIONS = 100_000;
const salt = randomBytes(16);
const hash = pbkdf2Sync(password, salt, ITERATIONS, 32, "sha256");
const passwordHash = `pbkdf2$${ITERATIONS}$${salt.toString("base64")}$${hash.toString("base64")}`;

const q = (v) => (typeof v === "number" ? String(v) : `'${String(v).replace(/'/g, "''")}'`);

const recettes = JSON.parse(readFileSync("data/recettes-export.json", "utf8"));
const lignes = recettes.map((r) => {
  // Vérifie que les colonnes JSON sont valides et les normalise.
  const ingredients = JSON.stringify(JSON.parse(r.ingredients || "[]"));
  const preparation = JSON.stringify(JSON.parse(r.preparation || "[]"));
  const vals = [r.id, r.type, r.nom, ingredients, preparation, r.temps_prep, r.temps_cuisson, r.difficulte, r.nb_personne];
  return `INSERT INTO recette (id, type, nom, ingredients, preparation, temps_prep, temps_cuisson, difficulte, nb_personne) VALUES (${vals.map(q).join(", ")});`;
});

const firstname = process.env.CUISINE_FIRSTNAME ?? "";
const lastname = process.env.CUISINE_LASTNAME ?? "";

const sql = [
  "DELETE FROM photo;",
  "DELETE FROM recette;",
  "DELETE FROM session;",
  "DELETE FROM user;",
  ...lignes,
  `INSERT INTO user (login, password_hash, firstname, lastname) VALUES (${[login, passwordHash, firstname, lastname].map(q).join(", ")});`,
  "",
].join("\n");

writeFileSync("data/seed.sql", sql);
console.log(`data/seed.sql : ${lignes.length} recettes, 1 utilisateur (${login}).`);
