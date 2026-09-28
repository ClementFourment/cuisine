import { Hono, type MiddlewareHandler } from "hono";
import { deleteCookie, getCookie, setCookie } from "hono/cookie";
import { LIMITES, validerRecette, type Recette, type RecetteInput, type User } from "../shared/types";
import { newSessionToken, sessionId, verifyPassword } from "./auth";

type Env = { DB: D1Database };
type SessionUser = User & { id: number };
type App = { Bindings: Env; Variables: { user: SessionUser | null } };

const SESSION_JOURS = 30;
const ESSAIS_MAX = 10;
const ESSAIS_FENETRE_MS = 15 * 60 * 1000;

const app = new Hono<App>().basePath("/api");

// ── Recettes : conversion ligne SQL → objet ─────────────────────────────────
interface RecetteRow {
  id: number;
  type: string;
  nom: string;
  ingredients: string;
  preparation: string;
  temps_prep: number;
  temps_cuisson: number;
  difficulte: string;
  nb_personne: number;
  photo_version: number | null;
}

const COLONNES =
  "id, type, nom, ingredients, preparation, temps_prep, temps_cuisson, difficulte, nb_personne, photo_version";

function toRecette(r: RecetteRow): Recette {
  return {
    id: r.id,
    type: r.type as Recette["type"],
    nom: r.nom,
    ingredients: JSON.parse(r.ingredients),
    preparation: JSON.parse(r.preparation),
    temps_prep: r.temps_prep,
    temps_cuisson: r.temps_cuisson,
    difficulte: r.difficulte as Recette["difficulte"],
    nb_personne: r.nb_personne,
    photo: r.photo_version === null ? null : `/api/photos/${r.id}?v=${r.photo_version}`,
  };
}

function valeurs(r: RecetteInput) {
  return [
    r.type,
    r.nom,
    JSON.stringify(r.ingredients),
    JSON.stringify(r.preparation),
    r.temps_prep,
    r.temps_cuisson,
    r.difficulte,
    r.nb_personne,
  ];
}

function parseId(s: string): number | null {
  const id = Number(s);
  return Number.isSafeInteger(id) && id > 0 ? id : null;
}

// ── Middlewares ─────────────────────────────────────────────────────────────

// Refuse les requêtes qui modifient des données depuis un autre site (CSRF).
app.use(async (c, next) => {
  if (c.req.method !== "GET" && c.req.method !== "HEAD") {
    const origin = c.req.header("Origin");
    if (origin && origin !== new URL(c.req.url).origin) {
      return c.json({ error: "Origine refusée." }, 403);
    }
  }
  await next();
});

// Charge l'utilisateur connecté à partir du cookie de session.
app.use(async (c, next) => {
  c.set("user", null);
  const token = getCookie(c, "session", "host");
  if (token) {
    const user = await c.env.DB.prepare(
      `SELECT user.id, user.login, user.firstname FROM session
       JOIN user ON user.id = session.user_id
       WHERE session.id = ? AND session.expires_at > ?`,
    )
      .bind(await sessionId(token), Date.now())
      .first<SessionUser>();
    c.set("user", user);
  }
  await next();
});

const requireAuth: MiddlewareHandler<App> = async (c, next) => {
  if (!c.get("user")) return c.json({ error: "Connexion requise." }, 401);
  await next();
};

// ── Connexion ───────────────────────────────────────────────────────────────

app.get("/me", (c) => {
  const user = c.get("user");
  return c.json({ user: user && { login: user.login, firstname: user.firstname } });
});

app.post("/login", async (c) => {
  const db = c.env.DB;
  const ip = c.req.header("CF-Connecting-IP") ?? "local";
  const now = Date.now();

  await db.prepare("DELETE FROM login_attempt WHERE at < ?").bind(now - ESSAIS_FENETRE_MS).run();
  const essais = await db
    .prepare("SELECT COUNT(*) AS n FROM login_attempt WHERE ip = ?")
    .bind(ip)
    .first<number>("n");
  if ((essais ?? 0) >= ESSAIS_MAX) {
    return c.json({ error: "Trop d'essais. Réessayez dans quelques minutes." }, 429);
  }

  const body = await c.req.json().catch(() => null);
  const login = typeof body?.login === "string" ? body.login.trim() : "";
  const password = typeof body?.password === "string" ? body.password : "";

  const user = await db
    .prepare("SELECT id, login, firstname, password_hash FROM user WHERE login = ?")
    .bind(login)
    .first<SessionUser & { password_hash: string }>();

  if (!(await verifyPassword(password, user?.password_hash ?? null)) || !user) {
    await db.prepare("INSERT INTO login_attempt (ip, at) VALUES (?, ?)").bind(ip, now).run();
    return c.json({ error: "Identifiants incorrects. Veuillez réessayer." }, 401);
  }

  const token = newSessionToken();
  const expires = now + SESSION_JOURS * 24 * 3600 * 1000;
  await db.batch([
    db.prepare("DELETE FROM session WHERE expires_at < ?").bind(now),
    db.prepare("INSERT INTO session (id, user_id, expires_at) VALUES (?, ?, ?)").bind(await sessionId(token), user.id, expires),
  ]);
  setCookie(c, "session", token, {
    prefix: "host",
    path: "/",
    secure: true,
    httpOnly: true,
    sameSite: "Lax",
    maxAge: SESSION_JOURS * 24 * 3600,
  });
  return c.json({ user: { login: user.login, firstname: user.firstname } });
});

app.post("/logout", async (c) => {
  const token = getCookie(c, "session", "host");
  if (token) {
    await c.env.DB.prepare("DELETE FROM session WHERE id = ?").bind(await sessionId(token)).run();
  }
  deleteCookie(c, "session", { prefix: "host", path: "/", secure: true });
  return c.json({ ok: true });
});

// ── Recettes ────────────────────────────────────────────────────────────────

app.get("/recettes", async (c) => {
  const { results } = await c.env.DB.prepare(`SELECT ${COLONNES} FROM recette ORDER BY id`).all<RecetteRow>();
  return c.json({ recettes: results.map(toRecette) });
});

app.post("/recettes", requireAuth, async (c) => {
  const r = validerRecette(await c.req.json().catch(() => null));
  if (typeof r === "string") return c.json({ error: r }, 400);
  const row = await c.env.DB.prepare(
    `INSERT INTO recette (type, nom, ingredients, preparation, temps_prep, temps_cuisson, difficulte, nb_personne)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING ${COLONNES}`,
  )
    .bind(...valeurs(r))
    .first<RecetteRow>();
  return c.json({ recette: toRecette(row!) }, 201);
});

app.put("/recettes/:id", requireAuth, async (c) => {
  const id = parseId(c.req.param("id"));
  const r = validerRecette(await c.req.json().catch(() => null));
  if (typeof r === "string") return c.json({ error: r }, 400);
  const row = await c.env.DB.prepare(
    `UPDATE recette SET type = ?, nom = ?, ingredients = ?, preparation = ?, temps_prep = ?,
       temps_cuisson = ?, difficulte = ?, nb_personne = ?, updated_at = datetime('now')
     WHERE id = ? RETURNING ${COLONNES}`,
  )
    .bind(...valeurs(r), id)
    .first<RecetteRow>();
  if (!row) return c.json({ error: "Recette introuvable." }, 404);
  return c.json({ recette: toRecette(row) });
});

app.delete("/recettes/:id", requireAuth, async (c) => {
  const id = parseId(c.req.param("id"));
  const db = c.env.DB;
  await db.batch([
    db.prepare("DELETE FROM photo WHERE recette_id = ?").bind(id),
    db.prepare("DELETE FROM recette WHERE id = ?").bind(id),
  ]);
  return c.json({ ok: true });
});

// ── Photos ──────────────────────────────────────────────────────────────────

/** Reconnaît le format d'après les premiers octets du fichier. */
function detectMime(b: Uint8Array): string | null {
  if (b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff) return "image/jpeg";
  if (b[0] === 0x89 && b[1] === 0x50 && b[2] === 0x4e && b[3] === 0x47) return "image/png";
  const ascii = (from: number, to: number) => String.fromCharCode(...b.subarray(from, to));
  if (ascii(0, 4) === "RIFF" && ascii(8, 12) === "WEBP") return "image/webp";
  return null;
}

app.get("/photos/:id", async (c) => {
  const row = await c.env.DB.prepare("SELECT mime, data FROM photo WHERE recette_id = ?")
    .bind(parseId(c.req.param("id")))
    .first<{ mime: string; data: ArrayBuffer | number[] }>();
  if (!row) return c.json({ error: "Photo introuvable." }, 404);
  return new Response(new Uint8Array(row.data), {
    headers: {
      "Content-Type": row.mime,
      // L'URL contient ?v=<version> : une nouvelle photo change l'URL.
      "Cache-Control": "public, max-age=31536000, immutable",
      "X-Content-Type-Options": "nosniff",
    },
  });
});

app.put("/recettes/:id/photo", requireAuth, async (c) => {
  const id = parseId(c.req.param("id"));
  const db = c.env.DB;
  if (Number(c.req.header("Content-Length") ?? 0) > LIMITES.photoOctets) {
    return c.json({ error: "Photo trop lourde (1 Mo maximum)." }, 413);
  }
  const data = new Uint8Array(await c.req.arrayBuffer());
  if (data.length > LIMITES.photoOctets) return c.json({ error: "Photo trop lourde (1 Mo maximum)." }, 413);
  const mime = detectMime(data);
  if (!mime) return c.json({ error: "Format d'image non reconnu." }, 415);

  const existe = await db.prepare("SELECT 1 FROM recette WHERE id = ?").bind(id).first();
  if (!existe) return c.json({ error: "Recette introuvable." }, 404);

  const [, res] = await db.batch<RecetteRow>([
    db.prepare("INSERT OR REPLACE INTO photo (recette_id, mime, data) VALUES (?, ?, ?)").bind(id, mime, data),
    db.prepare(
      `UPDATE recette SET photo_version = COALESCE(photo_version, 0) + 1, updated_at = datetime('now')
       WHERE id = ? RETURNING ${COLONNES}`,
    ).bind(id),
  ]);
  return c.json({ recette: toRecette(res.results[0]) });
});

app.delete("/recettes/:id/photo", requireAuth, async (c) => {
  const id = parseId(c.req.param("id"));
  const db = c.env.DB;
  const [, res] = await db.batch<RecetteRow>([
    db.prepare("DELETE FROM photo WHERE recette_id = ?").bind(id),
    db.prepare(
      `UPDATE recette SET photo_version = NULL, updated_at = datetime('now') WHERE id = ? RETURNING ${COLONNES}`,
    ).bind(id),
  ]);
  if (!res.results[0]) return c.json({ error: "Recette introuvable." }, 404);
  return c.json({ recette: toRecette(res.results[0]) });
});

// ── Erreurs ─────────────────────────────────────────────────────────────────

app.notFound((c) => c.json({ error: "Introuvable." }, 404));
app.onError((err, c) => {
  console.error(err);
  return c.json({ error: "Erreur serveur." }, 500);
});

export default app;
