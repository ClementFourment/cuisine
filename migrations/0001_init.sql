-- Schéma initial (migration depuis MariaDB, septembre 2026)

CREATE TABLE recette (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  type          TEXT    NOT NULL CHECK (type IN ('apero', 'entree', 'plat', 'dessert')),
  nom           TEXT    NOT NULL,
  -- JSON : [{ "ingredient": "farine", "qty": "250g" }, ...]
  ingredients   TEXT    NOT NULL DEFAULT '[]',
  -- JSON : [{ "action": "Mélanger..." }, ...]
  preparation   TEXT    NOT NULL DEFAULT '[]',
  temps_prep    INTEGER NOT NULL DEFAULT 0,
  temps_cuisson INTEGER NOT NULL DEFAULT 0,
  difficulte    TEXT    NOT NULL DEFAULT 'Facile' CHECK (difficulte IN ('Facile', 'Moyen', 'Difficile')),
  nb_personne   INTEGER NOT NULL DEFAULT 4,
  -- NULL = pas de photo ; sinon incrémenté à chaque nouvelle photo (sert à invalider le cache)
  photo_version INTEGER,
  created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX recette_type ON recette (type, id);

-- Les photos sont à part pour que la liste des recettes reste légère.
CREATE TABLE photo (
  recette_id INTEGER PRIMARY KEY REFERENCES recette (id) ON DELETE CASCADE,
  mime       TEXT NOT NULL,
  data       BLOB NOT NULL
);

CREATE TABLE user (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  login         TEXT NOT NULL UNIQUE,
  -- pbkdf2$<itérations>$<sel base64>$<hash base64>
  password_hash TEXT NOT NULL,
  firstname     TEXT NOT NULL DEFAULT '',
  lastname      TEXT NOT NULL DEFAULT ''
);

CREATE TABLE session (
  -- SHA-256 du jeton envoyé dans le cookie (le jeton lui-même n'est jamais stocké)
  id         TEXT    PRIMARY KEY,
  user_id    INTEGER NOT NULL REFERENCES user (id) ON DELETE CASCADE,
  expires_at INTEGER NOT NULL
);

-- Échecs de connexion récents, pour limiter les essais de mots de passe.
-- Les lignes de plus de 15 minutes sont effacées au fil de l'eau.
CREATE TABLE login_attempt (
  ip TEXT    NOT NULL,
  at INTEGER NOT NULL
);

CREATE INDEX login_attempt_ip ON login_attempt (ip, at);
