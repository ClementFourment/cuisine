import { useEffect, useRef, useState } from "react";
import { DIFFICULTES, type Difficulte, type Ingredient, type Recette, type TypeRecette } from "../../shared/types";
import { api } from "../api";
import { haptic } from "../effets";
import { compresserPhoto } from "../photo";
import { LIVRES } from "./Shelf";

type Photo =
  | { kind: "keep"; url: string | null }
  | { kind: "new"; blob: Blob; url: string }
  | { kind: "removed" };

interface Props {
  open: boolean;
  /** Recette à modifier, ou null pour en créer une. */
  recette: Recette | null;
  defaultType: TypeRecette;
  onClose: () => void;
  /** Appelé dès que la recette est enregistrée côté serveur (même si l'envoi de la photo échoue ensuite). */
  onSaved: (recette: Recette) => void;
}

export function RecipeForm({ open, recette, defaultType, onClose, onSaved }: Props) {
  const [type, setType] = useState<TypeRecette>(defaultType);
  const [nom, setNom] = useState("");
  const [prep, setPrep] = useState("");
  const [cuisson, setCuisson] = useState("");
  const [personnes, setPersonnes] = useState("");
  const [diff, setDiff] = useState<Difficulte>("Facile");
  const [ingredients, setIngredients] = useState<Ingredient[]>([{ ingredient: "", qty: "" }]);
  const [etapes, setEtapes] = useState<string[]>([""]);
  const [photo, setPhoto] = useState<Photo>({ kind: "keep", url: null });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const nomRef = useRef<HTMLInputElement>(null);
  const fileRef = useRef<HTMLInputElement>(null);
  const focusLast = useRef<"ing" | "step" | null>(null);
  // Id de la recette créée lors d'un essai précédent (évite un doublon si on réessaie).
  const createdId = useRef<number | null>(null);
  const ingListRef = useRef<HTMLDivElement>(null);
  const stepsListRef = useRef<HTMLDivElement>(null);

  // Remplit le formulaire à chaque ouverture.
  useEffect(() => {
    if (!open) return;
    const r = recette;
    setType(r?.type ?? defaultType);
    setNom(r?.nom ?? "");
    setPrep(r?.temps_prep ? String(r.temps_prep) : "");
    setCuisson(r?.temps_cuisson ? String(r.temps_cuisson) : "");
    setPersonnes(r?.nb_personne ? String(r.nb_personne) : "");
    setDiff(r?.difficulte ?? "Facile");
    setIngredients(r?.ingredients.length ? r.ingredients : [{ ingredient: "", qty: "" }]);
    setEtapes(r?.preparation.length ? r.preparation.map((e) => e.action) : [""]);
    setPhoto({ kind: "keep", url: r?.photo ?? null });
    setSaving(false);
    setError("");
    createdId.current = null;
  }, [open, recette, defaultType]);

  // Libère l'aperçu local de la photo quand il n'est plus utilisé.
  useEffect(() => {
    if (photo.kind !== "new") return;
    return () => URL.revokeObjectURL(photo.url);
  }, [photo]);

  // Place le curseur dans la ligne qu'on vient d'ajouter.
  useEffect(() => {
    const list = focusLast.current === "ing" ? ingListRef.current : focusLast.current === "step" ? stepsListRef.current : null;
    list?.querySelector<HTMLElement>(":scope > :last-child input, :scope > :last-child textarea")?.focus();
    focusLast.current = null;
  }, [ingredients.length, etapes.length]);

  async function choisirPhoto(file: File | undefined) {
    if (!file) return;
    setError("");
    try {
      const blob = await compresserPhoto(file);
      setPhoto({ kind: "new", blob, url: URL.createObjectURL(blob) });
    } catch (e) {
      setError((e as Error).message);
    }
  }

  async function enregistrer() {
    if (!nom.trim()) {
      nomRef.current?.focus();
      return;
    }
    const input = {
      type,
      nom: nom.trim(),
      temps_prep: parseInt(prep) || 0,
      temps_cuisson: parseInt(cuisson) || 0,
      nb_personne: parseInt(personnes) || 4,
      difficulte: diff,
      ingredients: ingredients
        .map((i) => ({ ingredient: i.ingredient.trim(), qty: i.qty.trim() }))
        .filter((i) => i.ingredient),
      preparation: etapes.map((a) => a.trim()).filter(Boolean).map((action) => ({ action })),
    };
    setSaving(true);
    setError("");
    try {
      const id = recette?.id ?? createdId.current;
      let saved = id ? await api.update(id, input) : await api.create(input);
      createdId.current = saved.id;
      onSaved(saved);
      if (photo.kind === "new") saved = await api.setPhoto(saved.id, photo.blob);
      else if (photo.kind === "removed" && saved.photo) saved = await api.removePhoto(saved.id);
      onSaved(saved);
      haptic([20, 10, 20]);
      onClose();
    } catch (e) {
      setError((e as Error).message);
      setSaving(false);
    }
  }

  const majIngredient = (idx: number, champ: keyof Ingredient, valeur: string) =>
    setIngredients((list) => list.map((i, n) => (n === idx ? { ...i, [champ]: valeur } : i)));

  const photoUrl = photo.kind === "removed" ? null : photo.url;

  return (
    <div className={`add-overlay${open ? " open" : ""}`} aria-hidden={!open}>
      <div className="add-backdrop" onClick={onClose} />
      <div className="add-modal" role="dialog" aria-labelledby="addModalTitle">
        <div className="add-modal-header">
          <div className="add-modal-title" id="addModalTitle">
            {recette ? "Modifier la recette" : "Nouvelle recette"}
          </div>
          <button type="button" className="close-btn" onClick={onClose} aria-label="Fermer">
            ✕
          </button>
        </div>

        <div className="add-modal-body">
          <div className="field-group">
            <label className="field-label" htmlFor="addType">Catégorie</label>
            <select
              className="field-select"
              id="addType"
              value={type}
              disabled={recette !== null}
              onChange={(e) => setType(e.target.value as TypeRecette)}
            >
              {LIVRES.map((l) => (
                <option key={l.type} value={l.type}>
                  {l.label}
                </option>
              ))}
            </select>
          </div>

          <div className="field-group">
            <label className="field-label" htmlFor="addNom">Nom de la recette</label>
            <input
              ref={nomRef}
              className="field-input"
              id="addNom"
              type="text"
              maxLength={100}
              placeholder="Ex : Tarte aux pommes de grand-mère"
              value={nom}
              onChange={(e) => setNom(e.target.value)}
            />
          </div>

          <div className="field-group">
            <span className="field-label">Photo</span>
            <div className="photo-field">
              {photoUrl && <img className="photo-preview" src={photoUrl} alt="Aperçu" />}
              <div className="photo-actions">
                <button type="button" className="add-ing-btn" onClick={() => fileRef.current?.click()}>
                  📷 {photoUrl ? "Changer la photo" : "Ajouter une photo"}
                </button>
                {photoUrl && (
                  <button type="button" className="add-ing-btn" onClick={() => setPhoto({ kind: "removed" })}>
                    Retirer
                  </button>
                )}
              </div>
              <input
                ref={fileRef}
                className="visually-hidden"
                type="file"
                accept="image/*"
                tabIndex={-1}
                onChange={(e) => {
                  choisirPhoto(e.target.files?.[0]);
                  e.target.value = "";
                }}
              />
            </div>
          </div>

          <div className="fields-row">
            <div className="field-group">
              <label className="field-label" htmlFor="addPrep">Préparation (min)</label>
              <input className="field-input" id="addPrep" type="number" min={0} placeholder="20" value={prep} onChange={(e) => setPrep(e.target.value)} />
            </div>
            <div className="field-group">
              <label className="field-label" htmlFor="addCuisson">Cuisson (min)</label>
              <input className="field-input" id="addCuisson" type="number" min={0} placeholder="35" value={cuisson} onChange={(e) => setCuisson(e.target.value)} />
            </div>
          </div>
          <div className="fields-row">
            <div className="field-group">
              <label className="field-label" htmlFor="addPersonnes">Personnes</label>
              <input className="field-input" id="addPersonnes" type="number" min={1} placeholder="4" value={personnes} onChange={(e) => setPersonnes(e.target.value)} />
            </div>
            <div className="field-group">
              <label className="field-label" htmlFor="addDiff">Difficulté</label>
              <select className="field-select" id="addDiff" value={diff} onChange={(e) => setDiff(e.target.value as Difficulte)}>
                {DIFFICULTES.map((d) => (
                  <option key={d}>{d}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="field-group">
            <span className="field-label">Ingrédients</span>
            <div className="ing-list" ref={ingListRef}>
              {ingredients.map((ing, n) => (
                <div key={n} className="ing-row">
                  <input className="field-input" placeholder="Ingrédient" aria-label={`Ingrédient ${n + 1}`} value={ing.ingredient} onChange={(e) => majIngredient(n, "ingredient", e.target.value)} />
                  <input className="field-input" placeholder="Qté" aria-label={`Quantité ${n + 1}`} value={ing.qty} onChange={(e) => majIngredient(n, "qty", e.target.value)} />
                  <button
                    type="button"
                    className="ing-del"
                    aria-label={`Retirer l'ingrédient ${n + 1}`}
                    onClick={() => ingredients.length > 1 && setIngredients((l) => l.filter((_, i) => i !== n))}
                  >
                    ×
                  </button>
                </div>
              ))}
            </div>
            <button
              type="button"
              className="add-ing-btn"
              onClick={() => {
                focusLast.current = "ing";
                setIngredients((l) => [...l, { ingredient: "", qty: "" }]);
              }}
            >
              + Ajouter un ingrédient
            </button>
          </div>

          <div className="field-group">
            <span className="field-label">Étapes de préparation</span>
            <div className="steps-add-list" ref={stepsListRef}>
              {etapes.map((texte, n) => (
                <div key={n} className="step-add-row">
                  <div className="step-add-num">{n + 1}</div>
                  <textarea
                    className="field-textarea"
                    placeholder="Décrivez cette étape…"
                    aria-label={`Étape ${n + 1}`}
                    value={texte}
                    onChange={(e) => setEtapes((l) => l.map((t, i) => (i === n ? e.target.value : t)))}
                  />
                  <button
                    type="button"
                    className="ing-del"
                    style={{ marginTop: 8 }}
                    aria-label={`Retirer l'étape ${n + 1}`}
                    onClick={() => etapes.length > 1 && setEtapes((l) => l.filter((_, i) => i !== n))}
                  >
                    ×
                  </button>
                </div>
              ))}
            </div>
            <button
              type="button"
              className="add-ing-btn"
              onClick={() => {
                focusLast.current = "step";
                setEtapes((l) => [...l, ""]);
              }}
            >
              + Ajouter une étape
            </button>
          </div>
        </div>

        <div className="add-modal-footer">
          {error && <div className="form-error" role="alert">{error}</div>}
          <button type="button" className="btn-cancel" onClick={onClose}>
            Annuler
          </button>
          <button type="button" className="btn-save" onClick={enregistrer} disabled={saving}>
            {saving ? "Enregistrement…" : "Enregistrer la recette"}
          </button>
        </div>
      </div>
    </div>
  );
}
