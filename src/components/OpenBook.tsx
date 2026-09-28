import type { Recette } from "../../shared/types";

const ETOILES: Record<string, string> = { Facile: "★☆☆", Moyen: "★★☆", Difficile: "★★★" };

interface Props {
  open: boolean;
  label: string;
  recettes: Recette[] | null; // null = en cours de chargement
  erreur: string;
  page: number;
  /** Sens de la dernière page tournée (1 = vers la droite), et compteur pour rejouer l'animation. */
  flip: { dir: number; n: number };
  loggedIn: boolean;
  onClose: () => void;
  onPage: (delta: -1 | 1) => void;
  onEdit: () => void;
  onDelete: () => void;
}

export function OpenBook({ open, label, recettes, erreur, page, flip, loggedIn, onClose, onPage, onEdit, onDelete }: Props) {
  const recette = recettes?.[page];
  const total = recettes?.length ?? 0;

  let contenu;
  if (!recettes) {
    contenu = (
      <div className="empty-state">
        <p>📖</p>
        <p className="loading">{erreur || "Chargement…"}</p>
      </div>
    );
  } else if (!recette) {
    contenu = (
      <div className="empty-state">
        <p>📖</p>
        <p>
          Aucune recette dans « {label} ».
          {loggedIn && (
            <>
              <br />
              Appuyez sur + pour en ajouter une.
            </>
          )}
        </p>
      </div>
    );
  } else {
    contenu = (
      <RecettePage recette={recette} dir={flip.dir} loggedIn={loggedIn} onEdit={onEdit} onDelete={onDelete} />
    );
  }

  return (
    <div className={`parchment-overlay${open ? " open" : ""}`} aria-hidden={!open}>
      <div className="parchment-backdrop" onClick={onClose} />
      <div className="open-book" role="dialog" aria-label={label}>
        <div className="book-header">
          <button type="button" className="close-btn" onClick={onClose} aria-label="Fermer le livre">
            ✕
          </button>
          <div className="book-header-title">{label || "—"}</div>
          <div style={{ width: 30 }} />
        </div>
        {/* Changer de clé recrée la page : l'animation se rejoue et le défilement revient en haut. */}
        <div className="book-pages" key={flip.n}>
          {contenu}
        </div>
        <div className="book-footer">
          <button
            type="button"
            className={`page-btn${page > 0 ? " active" : ""}`}
            onClick={() => onPage(-1)}
            aria-label="Recette précédente"
          >
            ‹
          </button>
          <div className="page-indicator">{total ? `${page + 1} / ${total}` : ""}</div>
          <button
            type="button"
            className={`page-btn${page < total - 1 ? " active" : ""}`}
            onClick={() => onPage(1)}
            aria-label="Recette suivante"
          >
            ›
          </button>
        </div>
      </div>
    </div>
  );
}

interface PageProps {
  recette: Recette;
  dir: number;
  loggedIn: boolean;
  onEdit: () => void;
  onDelete: () => void;
}

function RecettePage({ recette: r, dir, loggedIn, onEdit, onDelete }: PageProps) {
  return (
    <div className={dir > 0 ? "flip-right" : "flip-left"}>
      <h2 className="recipe-title">{r.nom}</h2>
      <p className="recipe-subtitle">pour {r.nb_personne || 4} personnes</p>
      {r.photo ? (
        <img className="recipe-photo" src={r.photo} alt={r.nom} />
      ) : (
        <div className="recipe-photo-placeholder">📷 Pas encore de photo</div>
      )}
      <div className="recipe-meta">
        <div className="meta-pill">⏱ Prépa {r.temps_prep} min</div>
        {r.temps_cuisson > 0 && <div className="meta-pill">🔥 Cuisson {r.temps_cuisson} min</div>}
        <div className="meta-pill">
          {ETOILES[r.difficulte] ?? "★☆☆"} {r.difficulte}
        </div>
      </div>
      <div className="recipe-divider" />
      <div className="section-title">Ingrédients</div>
      <div className="ingredients-grid">
        {r.ingredients.map((i, n) => (
          <div key={n} className="ingredient-row">
            <span className="ingredient-name">{i.ingredient}</span>
            <span className="ingredient-qty">{i.qty}</span>
          </div>
        ))}
      </div>
      <div className="recipe-divider" />
      <div className="section-title">Préparation</div>
      <div className="steps-list">
        {r.preparation.map((s, n) => (
          <div key={n} className="step-item">
            <div className="step-number">{n + 1}</div>
            <div className="step-text">{s.action}</div>
          </div>
        ))}
      </div>
      {loggedIn && (
        <div className="recipe-actions">
          <button type="button" className="btn-action btn-edit" onClick={onEdit}>
            ✏️ Modifier
          </button>
          <button type="button" className="btn-action btn-delete" onClick={onDelete}>
            🗑 Supprimer
          </button>
        </div>
      )}
    </div>
  );
}
