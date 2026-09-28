export const TYPES = ["apero", "entree", "plat", "dessert"] as const;
export type TypeRecette = (typeof TYPES)[number];

export const DIFFICULTES = ["Facile", "Moyen", "Difficile"] as const;
export type Difficulte = (typeof DIFFICULTES)[number];

export interface Ingredient {
  ingredient: string;
  qty: string;
}

export interface Etape {
  action: string;
}

/** Ce que le formulaire envoie pour créer ou modifier une recette. */
export interface RecetteInput {
  type: TypeRecette;
  nom: string;
  ingredients: Ingredient[];
  preparation: Etape[];
  temps_prep: number;
  temps_cuisson: number;
  difficulte: Difficulte;
  nb_personne: number;
}

export interface Recette extends RecetteInput {
  id: number;
  /** URL de la photo, ou null s'il n'y en a pas. */
  photo: string | null;
}

export interface User {
  login: string;
  firstname: string;
}

export const LIMITES = {
  nom: 100,
  texte: 2000,
  lignes: 100,
  minutes: 24 * 60,
  personnes: 100,
  photoOctets: 1024 * 1024,
} as const;

/** Vérifie et nettoie une recette reçue. Renvoie un message d'erreur ou la recette propre. */
export function validerRecette(body: unknown): RecetteInput | string {
  if (typeof body !== "object" || body === null) return "Requête invalide.";
  const b = body as Record<string, unknown>;

  if (!TYPES.includes(b.type as TypeRecette)) return "Catégorie inconnue.";
  if (!DIFFICULTES.includes(b.difficulte as Difficulte)) return "Difficulté inconnue.";

  const nom = typeof b.nom === "string" ? b.nom.trim() : "";
  if (!nom) return "Le nom est obligatoire.";
  if (nom.length > LIMITES.nom) return `Le nom dépasse ${LIMITES.nom} caractères.`;

  const entier = (v: unknown, max: number) =>
    Number.isInteger(v) && (v as number) >= 0 && (v as number) <= max ? (v as number) : null;
  const temps_prep = entier(b.temps_prep, LIMITES.minutes);
  const temps_cuisson = entier(b.temps_cuisson, LIMITES.minutes);
  const nb_personne = entier(b.nb_personne, LIMITES.personnes);
  if (temps_prep === null || temps_cuisson === null) return "Temps invalide.";
  if (nb_personne === null || nb_personne < 1) return "Nombre de personnes invalide.";

  const texte = (v: unknown) =>
    typeof v === "string" && v.length <= LIMITES.texte ? v.trim() : null;

  if (!Array.isArray(b.ingredients) || b.ingredients.length > LIMITES.lignes)
    return "Liste d'ingrédients invalide.";
  const ingredients: Ingredient[] = [];
  for (const i of b.ingredients) {
    const ingredient = texte(i?.ingredient);
    const qty = texte(i?.qty ?? "");
    if (ingredient === null || qty === null) return "Ingrédient invalide.";
    if (ingredient) ingredients.push({ ingredient, qty });
  }

  if (!Array.isArray(b.preparation) || b.preparation.length > LIMITES.lignes)
    return "Liste d'étapes invalide.";
  const preparation: Etape[] = [];
  for (const e of b.preparation) {
    const action = texte(e?.action);
    if (action === null) return "Étape invalide.";
    if (action) preparation.push({ action });
  }

  return {
    type: b.type as TypeRecette,
    nom,
    ingredients,
    preparation,
    temps_prep,
    temps_cuisson,
    difficulte: b.difficulte as Difficulte,
    nb_personne,
  };
}
