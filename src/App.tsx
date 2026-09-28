import { useCallback, useEffect, useRef, useState } from "react";
import type { Recette, TypeRecette, User } from "../shared/types";
import { api } from "./api";
import { ConfirmDelete } from "./components/ConfirmDelete";
import { Dust, Titre } from "./components/Decor";
import { LoginPage } from "./components/LoginPage";
import { OpenBook } from "./components/OpenBook";
import { RecipeForm } from "./components/RecipeForm";
import { LIVRES, Shelf } from "./components/Shelf";
import { animateBookOpen, haptic, playPageSound } from "./effets";

const LOGIN_PATH = "/connexion";

export function App() {
  const [path, setPath] = useState(location.pathname);
  const [user, setUser] = useState<User | null>(null);
  const [recettes, setRecettesState] = useState<Recette[] | null>(null);
  const [loadError, setLoadError] = useState("");

  const [bookType, setBookType] = useState<TypeRecette>("apero");
  const [bookOpen, setBookOpen] = useState(false);
  const [page, setPage] = useState(0);
  const [flip, setFlip] = useState({ dir: 1, n: 0 });

  const [form, setForm] = useState<{ open: boolean; recette: Recette | null }>({ open: false, recette: null });
  const [confirm, setConfirm] = useState({ open: false, busy: false, error: "" });

  // Copie synchrone de la liste, pour enchaîner plusieurs mises à jour dans le même événement.
  const recettesRef = useRef<Recette[] | null>(null);
  const setRecettes = useCallback((next: Recette[]) => {
    recettesRef.current = next;
    setRecettesState(next);
  }, []);

  useEffect(() => {
    api.recettes().then(setRecettes, () => setLoadError("Impossible de charger les recettes."));
    api.me().then(setUser, () => {});
    const onPop = () => setPath(location.pathname);
    addEventListener("popstate", onPop);
    return () => removeEventListener("popstate", onPop);
  }, [setRecettes]);

  function navigate(to: string) {
    history.pushState(null, "", to);
    setPath(to);
  }

  const liste = recettes?.filter((r) => r.type === bookType) ?? null;
  const recette = liste?.[page] ?? null;
  const label = LIVRES.find((l) => l.type === bookType)?.label ?? "";

  function tourner(dir: number) {
    setFlip((f) => ({ dir, n: f.n + 1 }));
    playPageSound();
    haptic(8);
  }

  function ouvrirLivre(type: TypeRecette, el: HTMLElement) {
    setBookType(type);
    setPage(0);
    haptic(15);
    animateBookOpen(el, () => {
      setBookOpen(true);
      tourner(1);
    });
  }

  function fermerLivre() {
    setBookOpen(false);
    haptic(10);
  }

  function changerPage(delta: -1 | 1) {
    const next = page + delta;
    if (!liste || next < 0 || next >= liste.length) return;
    setPage(next);
    tourner(delta);
  }

  function ouvrirFormulaire(r: Recette | null) {
    setForm({ open: true, recette: r });
    haptic(12);
  }

  function recetteEnregistree(r: Recette) {
    const avant = recettesRef.current ?? [];
    const nouvelle = !avant.some((x) => x.id === r.id);
    const next = nouvelle ? [...avant, r] : avant.map((x) => (x.id === r.id ? r : x));
    setRecettes(next);
    // Une nouvelle recette du livre ouvert : on tourne jusqu'à elle.
    if (nouvelle && r.type === bookType) {
      setPage(next.filter((x) => x.type === bookType).length - 1);
      tourner(1);
    }
  }

  async function supprimer() {
    if (!recette) return;
    setConfirm((c) => ({ ...c, busy: true, error: "" }));
    try {
      await api.remove(recette.id);
    } catch (e) {
      setConfirm((c) => ({ ...c, busy: false, error: (e as Error).message }));
      return;
    }
    const next = (recettesRef.current ?? []).filter((x) => x.id !== recette.id);
    setRecettes(next);
    const restantes = next.filter((x) => x.type === bookType).length;
    if (page >= restantes && page > 0) setPage(page - 1);
    setConfirm({ open: false, busy: false, error: "" });
    haptic([20, 10, 20]);
    tourner(0);
  }

  async function deconnexion() {
    await api.logout().catch(() => {});
    setUser(null);
    setForm((f) => ({ ...f, open: false }));
    setConfirm((c) => ({ ...c, open: false }));
  }

  // Clavier : Échap ferme la fenêtre du dessus, les flèches tournent les pages.
  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") {
        if (confirm.open) setConfirm((c) => ({ ...c, open: false }));
        else if (form.open) setForm((f) => ({ ...f, open: false }));
        else if (bookOpen) fermerLivre();
      } else if (bookOpen && !form.open && !confirm.open) {
        if (e.key === "ArrowLeft") changerPage(-1);
        if (e.key === "ArrowRight") changerPage(1);
      }
    }
    addEventListener("keydown", onKey);
    return () => removeEventListener("keydown", onKey);
  });

  if (path === LOGIN_PATH) {
    return (
      <LoginPage
        onLoggedIn={(u) => {
          setUser(u);
          navigate("/");
        }}
        onBack={() => navigate("/")}
      />
    );
  }

  return (
    <>
      <div className="scene">
        <div className="auth-corner">
          {user ? (
            <button type="button" className="login-btn logout" onClick={deconnexion}>
              ✕ Déconnexion
            </button>
          ) : (
            <button type="button" className="login-btn" onClick={() => navigate(LOGIN_PATH)}>
              🔐 Connexion
            </button>
          )}
        </div>
        <Dust />
        <Titre />
        <Shelf onOpen={ouvrirLivre} />
      </div>

      <OpenBook
        open={bookOpen}
        label={label}
        recettes={liste}
        erreur={loadError}
        page={page}
        flip={flip}
        loggedIn={user !== null}
        onClose={fermerLivre}
        onPage={changerPage}
        onEdit={() => ouvrirFormulaire(recette)}
        onDelete={() => {
          setConfirm({ open: true, busy: false, error: "" });
          haptic(12);
        }}
      />

      <ConfirmDelete
        open={confirm.open}
        nom={recette?.nom ?? "cette recette"}
        busy={confirm.busy}
        error={confirm.error}
        onCancel={() => setConfirm((c) => ({ ...c, open: false }))}
        onConfirm={supprimer}
      />

      {user && (
        <>
          <RecipeForm
            open={form.open}
            recette={form.recette}
            defaultType={bookType}
            onClose={() => setForm((f) => ({ ...f, open: false }))}
            onSaved={recetteEnregistree}
          />
          <button
            type="button"
            className={`fab${bookOpen ? "" : " hidden"}`}
            aria-label="Ajouter une recette"
            onClick={() => ouvrirFormulaire(null)}
          >
            +
          </button>
        </>
      )}
    </>
  );
}
