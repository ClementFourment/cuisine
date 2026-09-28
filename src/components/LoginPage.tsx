import { useState, type FormEvent } from "react";
import type { User } from "../../shared/types";
import { api } from "../api";
import { Candle, Dust, Titre } from "./Decor";

interface Props {
  onLoggedIn: (user: User) => void;
  onBack: () => void;
}

export function LoginPage({ onLoggedIn, onBack }: Props) {
  const [login, setLogin] = useState("");
  const [password, setPassword] = useState("");
  const [showPwd, setShowPwd] = useState(false);
  const [error, setError] = useState("");
  const [errorKey, setErrorKey] = useState(0);
  const [busy, setBusy] = useState(false);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    try {
      onLoggedIn(await api.login(login, password));
    } catch (err) {
      setError((err as Error).message);
      setErrorKey((k) => k + 1); // rejoue l'animation de secousse
      setBusy(false);
    }
  }

  return (
    <div className="scene">
      <Dust />
      <Titre />

      <div className="login-card">
        <div className="card-header">
          <span className="card-header-icon">🔑</span>
          <div className="card-header-title">Accès au livre de recettes</div>
        </div>

        <div className="card-body">
          <div className="card-invite">
            <p className="card-invite-text">Entrez vos identifiants pour ouvrir les pages</p>
          </div>
          <div className="divider" />

          {error && (
            <div key={errorKey} className="error-msg" role="alert">
              🚫 {error}
            </div>
          )}

          <form onSubmit={submit} autoComplete="on" noValidate>
            <div className="field-group">
              <label className="field-label" htmlFor="login">Identifiant</label>
              <div className="field-wrap">
                <span className="field-icon">👤</span>
                <input
                  className="field-input"
                  id="login"
                  name="login"
                  type="text"
                  placeholder="Votre identifiant"
                  autoComplete="username"
                  autoCapitalize="none"
                  required
                  value={login}
                  onChange={(e) => setLogin(e.target.value)}
                />
              </div>
            </div>

            <div className="field-group">
              <label className="field-label" htmlFor="password">Mot de passe</label>
              <div className="field-wrap">
                <span className="field-icon">🔒</span>
                <input
                  className="field-input"
                  id="password"
                  name="password"
                  type={showPwd ? "text" : "password"}
                  placeholder="••••••••"
                  autoComplete="current-password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                />
                <button
                  type="button"
                  className="pwd-toggle"
                  title="Afficher / masquer"
                  aria-label={showPwd ? "Masquer le mot de passe" : "Afficher le mot de passe"}
                  onClick={() => setShowPwd((v) => !v)}
                >
                  {showPwd ? "🙈" : "👁"}
                </button>
              </div>
            </div>

            <button className="btn-login" type="submit" disabled={busy}>
              {busy ? "Connexion…" : "Se connecter"}
            </button>
          </form>
        </div>

        <div className="card-footer">
          <span className="footer-ornament">✦ &nbsp; Ma Cuisine &nbsp; ✦</span>
        </div>
      </div>

      <div className="candles">
        <Candle />
        <Candle />
      </div>

      <button type="button" className="login-back" onClick={onBack}>
        ← Retour aux recettes
      </button>
    </div>
  );
}
