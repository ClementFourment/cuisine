interface Props {
  open: boolean;
  nom: string;
  busy: boolean;
  error: string;
  onCancel: () => void;
  onConfirm: () => void;
}

export function ConfirmDelete({ open, nom, busy, error, onCancel, onConfirm }: Props) {
  return (
    <div className={`confirm-overlay${open ? " open" : ""}`} aria-hidden={!open}>
      <div className="confirm-backdrop" onClick={onCancel} />
      <div className="confirm-box" role="alertdialog" aria-labelledby="confirmTitle">
        <div className="confirm-title" id="confirmTitle">
          Supprimer cette recette ?
        </div>
        <div className="confirm-text">
          {error || `« ${nom} » sera définitivement supprimée.`}
        </div>
        <div className="confirm-btns">
          <button type="button" className="btn-confirm-cancel" onClick={onCancel}>
            Annuler
          </button>
          <button type="button" className="btn-confirm-delete" onClick={onConfirm} disabled={busy}>
            {busy ? "Suppression…" : "Supprimer"}
          </button>
        </div>
      </div>
    </div>
  );
}
