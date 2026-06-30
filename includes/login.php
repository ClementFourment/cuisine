<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require "db.php"; 


// Traitement du formulaire
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = 'SELECT * FROM user WHERE login = :login';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':login', $login, PDO::PARAM_STR);
            $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($login === $users[0]['login'] && $password === $users[0]['password']) {
        $success = true;
        $_SESSION['user'] = $users[0];
        header('Location: /'); 
        exit;
    }
    else {
        $error = 'Identifiants incorrects. Veuillez réessayer.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Ma Cuisine — Connexion</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bois-fonce:#2C1A0E; --bois-moyen:#5C3317; --bois-clair:#8B5A2B;
  --cuivre:#C4773B; --parchemin:#F2E8C8; --parchemin-ombre:#D4C090;
  --ivoire:#FAF5E4; --encre:#1A0F05;
}
html, body {
  width:100%; height:100%; overflow:hidden;
  background:var(--bois-fonce); font-family:'Lora',serif;
}

/* ── Scène ── */
.scene {
  position:fixed; inset:0; display:flex; flex-direction:column;
  align-items:center; justify-content:center; overflow:hidden;
}
.scene::before {
  content:''; position:absolute; inset:0; pointer-events:none;
  background:
    repeating-linear-gradient(93deg,transparent 0,transparent 60px,rgba(0,0,0,.07) 61px,transparent 62px),
    repeating-linear-gradient(180deg,transparent 0,transparent 90px,rgba(0,0,0,.04) 91px,transparent 92px),
    radial-gradient(ellipse 130% 80% at 50% 10%,#4A2810 0%,#2C1A0E 55%,#160A03 100%);
}

/* ── Poussière ── */
.dust { position:absolute; inset:0; pointer-events:none; overflow:hidden; }
.dust-p {
  position:absolute; border-radius:50%;
  background:rgba(196,119,59,.15);
  animation:floatUp linear infinite;
}
@keyframes floatUp {
  0%   { transform:translateY(0) translateX(0); opacity:0; }
  10%  { opacity:1; }
  90%  { opacity:.5; }
  100% { transform:translateY(-110vh) translateX(20px); opacity:0; }
}

/* ── En-tête ── */
.app-title {
  position:relative; z-index:5;
  font-family:'Playfair Display',serif; font-style:italic;
  font-size:clamp(18px,5vw,26px); color:var(--cuivre);
  letter-spacing:.15em; text-shadow:0 2px 12px rgba(0,0,0,.6);
  margin-bottom:4px;
}
.app-subtitle {
  position:relative; z-index:5; font-size:10px;
  color:rgba(196,119,59,.45); letter-spacing:.3em;
  text-transform:uppercase; margin-bottom:clamp(20px,5vw,40px);
}

/* ── Carte parchemin ── */
.card {
  position:relative; z-index:5;
  width:min(92vw, 400px);
  border-radius:10px;
  box-shadow:0 30px 80px rgba(0,0,0,.7), 0 0 0 1px rgba(196,119,59,.18);
  overflow:hidden;
  animation:riseUp .6s cubic-bezier(.22,1,.36,1) both;
}
@keyframes riseUp {
  from { opacity:0; transform:perspective(700px) rotateX(10deg) translateY(24px) scale(.96); }
  to   { opacity:1; transform:none; }
}

.card-header {
  background:linear-gradient(135deg,#4A2810 0%,#2C1A0E 100%);
  padding:14px 20px 12px;
  display:flex; align-items:center; justify-content:center;
  border-bottom:2px solid var(--cuivre);
  gap:10px;
}
.card-header-title {
  font-family:'Playfair Display',serif; font-style:italic;
  font-size:17px; color:var(--cuivre); letter-spacing:.06em;
}
.card-header-icon { font-size:18px; }

/* Pages texture parchemin */
.card-body {
  padding:28px 28px 24px;
  background:
    repeating-linear-gradient(90deg,transparent 0,transparent 2px,rgba(180,140,80,.04) 3px,transparent 4px),
    radial-gradient(ellipse 75% 55% at 28% 18%,rgba(210,175,110,.2) 0%,transparent 60%),
    radial-gradient(ellipse 55% 45% at 82% 82%,rgba(165,120,55,.14) 0%,transparent 55%),
    linear-gradient(172deg,#f8efd8 0%,#f2e5c2 22%,#eedcb8 50%,#e6d0a8 75%,#dcc898 100%);
  position:relative;
}
.card-body::after {
  content:''; position:absolute; inset:0; pointer-events:none;
  background:
    linear-gradient(90deg,rgba(100,60,20,.1) 0%,transparent 10%,transparent 90%,rgba(80,40,10,.08) 100%),
    linear-gradient(180deg,rgba(120,70,20,.08) 0%,transparent 15%,transparent 85%,rgba(100,55,15,.12) 100%);
}

/* Invite calligraphique */
.card-invite {
  text-align:center; margin-bottom:22px; position:relative; z-index:1;
}
.card-invite-text {
  font-family:'Playfair Display',serif; font-style:italic;
  font-size:13px; color:var(--bois-moyen); letter-spacing:.04em;
}
.divider {
  width:100%; height:1px; margin:10px 0; position:relative; z-index:1;
  background:linear-gradient(90deg,transparent,rgba(139,90,43,.26) 20%,rgba(139,90,43,.26) 80%,transparent);
}
.divider::before {
  content:'✦'; position:absolute; left:50%; top:50%;
  transform:translate(-50%,-50%); padding:0 7px;
  background:linear-gradient(180deg,#f0e0b8,#e8d4a8);
  color:rgba(139,90,43,.36); font-size:9px;
}

/* Champs */
.field-group { margin-bottom:16px; position:relative; z-index:1; }
.field-label {
  display:block; font-size:11px; letter-spacing:.08em;
  text-transform:uppercase; color:rgba(92,51,23,.7);
  margin-bottom:6px; font-family:'Playfair Display',serif;
}
.field-wrap { position:relative; }
.field-icon {
  position:absolute; left:12px; top:50%; transform:translateY(-50%);
  font-size:14px; pointer-events:none; opacity:.45;
}
.field-input {
  width:100%; padding:10px 12px 10px 36px;
  background:rgba(255,255,255,.45);
  border:1px solid rgba(139,90,43,.28); border-radius:5px;
  color:var(--encre); font-family:'Lora',serif; font-size:13px;
  outline:none; transition:border-color .2s, background .2s, box-shadow .2s;
  -webkit-appearance:none;
}
.field-input::placeholder { color:rgba(44,26,14,.28); }
.field-input:focus {
  border-color:rgba(196,119,59,.6);
  background:rgba(255,255,255,.62);
  box-shadow:0 0 0 3px rgba(196,119,59,.1);
}

/* Toggle mot de passe */
.pwd-toggle {
  position:absolute; right:12px; top:50%; transform:translateY(-50%);
  cursor:pointer; font-size:15px; opacity:.38; user-select:none;
  transition:opacity .2s; -webkit-tap-highlight-color:transparent;
}
.pwd-toggle:hover { opacity:.65; }

/* Option "Se souvenir" */
.remember-row {
  display:flex; align-items:center; gap:8px;
  margin-bottom:20px; position:relative; z-index:1;
}
.remember-checkbox {
  width:16px; height:16px; cursor:pointer;
  accent-color:var(--cuivre); flex-shrink:0;
}
.remember-label {
  font-size:12px; color:var(--bois-moyen); cursor:pointer;
  font-style:italic;
}

/* Message erreur */
.error-msg {
  display:flex; align-items:center; gap:8px;
  background:rgba(140,30,30,.1); border:1px solid rgba(140,30,30,.22);
  border-radius:5px; padding:9px 12px; margin-bottom:16px;
  font-size:12px; color:#6B1515; position:relative; z-index:1;
  animation:shake .35s cubic-bezier(.36,.07,.19,.97);
}
@keyframes shake {
  0%,100%{transform:translateX(0);}
  20%{transform:translateX(-5px);}
  40%{transform:translateX(5px);}
  60%{transform:translateX(-3px);}
  80%{transform:translateX(3px);}
}

/* Message succès */
.success-msg {
  display:flex; align-items:center; gap:8px;
  background:rgba(45,90,61,.12); border:1px solid rgba(45,90,61,.28);
  border-radius:5px; padding:9px 12px; margin-bottom:16px;
  font-size:12px; color:#1E3D2A; position:relative; z-index:1;
}

/* Bouton connexion */
.btn-login {
  width:100%; padding:12px;
  background:linear-gradient(135deg,#C4773B 0%,#A05828 100%);
  border:none; border-radius:6px;
  color:var(--ivoire); font-family:'Playfair Display',serif;
  font-style:italic; font-size:15px; letter-spacing:.04em;
  cursor:pointer; position:relative; z-index:1;
  box-shadow:0 4px 14px rgba(0,0,0,.28), 0 1px 0 rgba(255,255,255,.08) inset;
  transition:filter .2s, transform .12s;
  -webkit-tap-highlight-color:transparent;
}
.btn-login:hover  { filter:brightness(1.1); }
.btn-login:active { transform:scale(.97); }

.card-footer {
  background:linear-gradient(135deg,#2C1A0E 0%,#4A2810 100%);
  padding:10px 20px;
  display:flex; align-items:center; justify-content:center;
  border-top:2px solid var(--cuivre);
}
.footer-ornament {
  font-family:'Playfair Display',serif; font-style:italic;
  font-size:11px; color:rgba(196,119,59,.42); letter-spacing:.12em;
}

/* Bougies latérales */
.candles {
  position:relative; z-index:5;
  display:flex; gap:clamp(180px,42vw,260px);
  margin-top:clamp(14px,3vw,22px);
  pointer-events:none;
}
.candle { display:flex; flex-direction:column; align-items:center; position:relative; }
.candle-glow {
  position:absolute; top:-10px; left:50%; transform:translateX(-50%);
  width:44px; height:44px; border-radius:50%;
  background:radial-gradient(ellipse,rgba(255,180,0,.22) 0%,transparent 70%);
  animation:flicker 1.9s ease-in-out infinite alternate;
}
.candle-flame {
  width:8px; height:14px;
  background:radial-gradient(ellipse at 50% 80%,#fff 0%,#FFD700 35%,#FF6600 70%,transparent 100%);
  border-radius:50% 50% 20% 20%; filter:blur(1px);
  animation:flicker 1.9s ease-in-out infinite alternate;
}
@keyframes flicker {
  0%   { transform:scaleX(1)    scaleY(1)    rotate(-2deg); }
  33%  { transform:scaleX(.82)  scaleY(1.12) rotate(3deg); }
  66%  { transform:scaleX(1.08) scaleY(.93)  rotate(-1deg); }
  100% { transform:scaleX(.88)  scaleY(1.06) rotate(2deg); }
}
.candle-body {
  width:10px; height:30px;
  background:linear-gradient(90deg,#f0e8d0 0%,#fff8e8 45%,#e8d8b8 100%);
  border-radius:1px; box-shadow:1px 1px 4px rgba(0,0,0,.4);
}
.candle-base { width:14px; height:4px; background:linear-gradient(180deg,#C4773B 0%,#8B5A2B 100%); border-radius:2px; }

@media(prefers-reduced-motion:reduce){
  .card { animation:none; }
  .candle-flame,.candle-glow,.dust-p { animation:none; }
}
</style>
</head>
<body>
<div class="scene">
  <div class="dust" id="dust"></div>

  <p class="app-title">Ma Cuisine</p>
  <p class="app-subtitle">— Recettes de famille —</p>

  <div class="card">
    <div class="card-header">
      <span class="card-header-icon">🔑</span>
      <div class="card-header-title">Accès au livre de recettes</div>
    </div>

    <div class="card-body">
      <div class="card-invite">
        <p class="card-invite-text">Entrez vos identifiants pour ouvrir les pages</p>
      </div>
      <div class="divider"></div>

      <?php if ($success): ?>
      <div class="success-msg">✅ Connexion réussie — Bonne cuisine !</div>
      <?php elseif ($error): ?>
      <div class="error-msg">🚫 <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="" autocomplete="on" novalidate>
        <div class="field-group">
          <label class="field-label" for="login">Identifiant</label>
          <div class="field-wrap">
            <span class="field-icon">👤</span>
            <input
              class="field-input"
              id="login" name="login" type="text"
              placeholder="Votre identifiant"
              value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
              autocomplete="username"
              required
            >
          </div>
        </div>

        <div class="field-group">
          <label class="field-label" for="password">Mot de passe</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input
              class="field-input"
              id="password" name="password" type="password"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            >
            <span class="pwd-toggle" id="pwdToggle" title="Afficher / masquer">👁</span>
          </div>
        </div>

        <button class="btn-login" type="submit">Se connecter</button>
      </form>
    </div>

    <div class="card-footer">
      <span class="footer-ornament">✦ &nbsp; Ma Cuisine &nbsp; ✦</span>
    </div>
  </div>

  <div class="candles">
    <div class="candle">
      <div class="candle-glow"></div>
      <div class="candle-flame"></div>
      <div class="candle-body"></div>
      <div class="candle-base"></div>
    </div>
    <div class="candle">
      <div class="candle-glow"></div>
      <div class="candle-flame"></div>
      <div class="candle-body"></div>
      <div class="candle-base"></div>
    </div>
  </div>
</div>

<script>
/* Poussière */
(function(){
  const dust = document.getElementById('dust');
  for(let i = 0; i < 64; i++){
    const p = document.createElement('div');
    p.className = 'dust-p';
    const s = Math.random() * 2.5 + 1;
    p.style.cssText = `width:${s}px;height:${s}px;left:${Math.random()*100}%;bottom:${Math.random()*25}%;animation-duration:${9+Math.random()*16}s;animation-delay:${Math.random()*14}s;`;
    dust.appendChild(p);
  }
})();

/* Toggle mot de passe */
document.getElementById('pwdToggle').addEventListener('click', function(){
  const inp = document.getElementById('password');
  const isHidden = inp.type === 'password';
  inp.type = isHidden ? 'text' : 'password';
  this.textContent = isHidden ? '🙈' : '👁';
});
</script>
</body>
</html>