<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['logout'])) { 
  unset($_SESSION['user']);
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"],
      $params["domain"],
      $params["secure"],
      $params["httponly"]
    );
  }
  session_destroy();
  header("Location: /");
  exit;
}
if (isset($_POST['login'])) {
    header('Location: includes/login.php');
    exit;
}
include "includes/track.php";
require "includes/db.php"; 

$sql = "SELECT * FROM recette";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$recettes = $stmt->fetchAll(PDO::FETCH_ASSOC);


echo "<script>let RECETTES_DATA = {};";


$cpt = [
    "apero" => 0,
    "entree" => 0,
    "plat" => 0,
    "dessert" => 0
];

foreach ($recettes as $recette) {

    $id                 = $recette['id'];
    $type               = json_encode($recette['type']);
    $nom                = json_encode($recette['nom']);
    $photo              = json_encode($recette['photo']);
    $ingredients        = $recette['ingredients'];
    $temps_prep         = json_encode($recette['temps_prep']);
    $temps_cuisson      = json_encode($recette['temps_cuisson']);
    $difficulte         = json_encode($recette['difficulte']);
    $nb_personne        = json_encode($recette['nb_personne']);
    $preparation        = $recette['preparation'];
    
    

    $cpt[$recette['type']]++;
    $num = $cpt[$recette['type']];

    echo "if (!RECETTES_DATA[$type]) RECETTES_DATA[$type] = [];\n";
    echo "RECETTES_DATA[$type].push({
        id: $id,
        type: $type,
        nom: $nom,
        photo: $photo,
        ingredients: $ingredients,
        temps_prep: $temps_prep,
        temps_cuisson: $temps_cuisson,
        difficulte: $difficulte,
        nb_personne: $nb_personne,
        preparation: $preparation,
        
    });\n";
}
echo "console.log(RECETTES_DATA)";
echo "</script>";

echo "<script>";
if (!isset($_SESSION['user'])) {
  echo <<<'JS'
  // ── Rendu recette ───────────────────────────────────────────────────────────
function renderRecipe(recipe, dir){
  const stars = {Facile:'★☆☆', Moyen:'★★☆', Difficile:'★★★'};
  const cls = dir > 0 ? 'flip-right' : 'flip-left';
  let ingH = '<div class="ingredients-grid">';
  (recipe.ingredients||[]).forEach(i => {
    ingH += `<div class="ingredient-row"><span class="ingredient-name">${i.ingredient}</span><span class="ingredient-qty">${i.qty}</span></div>`;
  });
  ingH += '</div>';
  let stH = '<div class="steps-list">';
  (recipe.preparation||[]).forEach((s,n) => {
    stH += `<div class="step-item"><div class="step-number">${n+1}</div><div class="step-text">${s.action}</div></div>`;
  });
  stH += '</div>';
  const photo = recipe.photo
    ? `<img class="recipe-photo" src="${recipe.photo}" alt="">`
    : `<div class="recipe-photo-placeholder">📷 Pas encore de photo</div>`;
  return `<div class="${cls}">
    <h2 class="recipe-title">${recipe.nom}</h2>
    <p class="recipe-subtitle">pour ${recipe.nb_personne||4} personnes</p>
    ${photo}
    <div class="recipe-meta">
      <div class="meta-pill">⏱ Prépa ${recipe.temps_prep} min</div>
      ${recipe.temps_cuisson > 0 ? `<div class="meta-pill">🔥 Cuisson ${recipe.temps_cuisson} min</div>` : ''}
      <div class="meta-pill">${stars[recipe.difficulte]||'★☆☆'} ${recipe.difficulte}</div>
    </div>
    <div class="recipe-divider"></div>
    <div class="section-title">Ingrédients</div>${ingH}
    <div class="recipe-divider"></div>
    <div class="section-title">Préparation</div>${stH}
  </div>`;
}
JS;
}
else {
  echo <<<'JS'
  // ── Rendu recette ───────────────────────────────────────────────────────────
function renderRecipe(recipe, dir){
  const stars = {Facile:'★☆☆', Moyen:'★★☆', Difficile:'★★★'};
  const cls = dir > 0 ? 'flip-right' : 'flip-left';
  let ingH = '<div class="ingredients-grid">';
  (recipe.ingredients||[]).forEach(i => {
    ingH += `<div class="ingredient-row"><span class="ingredient-name">${i.ingredient}</span><span class="ingredient-qty">${i.qty}</span></div>`;
  });
  ingH += '</div>';
  let stH = '<div class="steps-list">';
  (recipe.preparation||[]).forEach((s,n) => {
    stH += `<div class="step-item"><div class="step-number">${n+1}</div><div class="step-text">${s.action}</div></div>`;
  });
  stH += '</div>';
  const photo = recipe.photo
    ? `<img class="recipe-photo" src="${recipe.photo}" alt="">`
    : `<div class="recipe-photo-placeholder">📷 Pas encore de photo</div>`;
  return `<div class="${cls}">
    <h2 class="recipe-title">${recipe.nom}</h2>
    <p class="recipe-subtitle">pour ${recipe.nb_personne||4} personnes</p>
    ${photo}
    <div class="recipe-meta">
      <div class="meta-pill">⏱ Prépa ${recipe.temps_prep} min</div>
      ${recipe.temps_cuisson > 0 ? `<div class="meta-pill">🔥 Cuisson ${recipe.temps_cuisson} min</div>` : ''}
      <div class="meta-pill">${stars[recipe.difficulte]||'★☆☆'} ${recipe.difficulte}</div>
    </div>
    <div class="recipe-divider"></div>
    <div class="section-title">Ingrédients</div>${ingH}
    <div class="recipe-divider"></div>
    <div class="section-title">Préparation</div>${stH}
    <div class="recipe-actions">
      <button class="btn-action btn-edit" onclick="openEdit()">✏️ Modifier</button>
      <button class="btn-action btn-delete" onclick="openDeleteConfirm()">🗑 Supprimer</button>
    </div>
  </div>`;
}
JS;

}
echo "</script>";

?>


<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Ma Cuisine</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bois-fonce:#2C1A0E; --bois-moyen:#5C3317; --bois-clair:#8B5A2B;
  --cuivre:#C4773B; --parchemin:#F2E8C8; --parchemin-ombre:#D4C090;
  --ivoire:#FAF5E4; --encre:#1A0F05;
}
html,body{ width:100%;height:100%;overflow:hidden;background:var(--bois-fonce);font-family:'Lora',serif; }

.scene{
  position:fixed;inset:0;display:flex;flex-direction:column;
  align-items:center;justify-content:center;overflow:hidden;
}
.scene::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    repeating-linear-gradient(93deg,transparent 0,transparent 60px,rgba(0,0,0,.07) 61px,transparent 62px),
    repeating-linear-gradient(180deg,transparent 0,transparent 90px,rgba(0,0,0,.04) 91px,transparent 92px),
    radial-gradient(ellipse 130% 80% at 50% 10%,#4A2810 0%,#2C1A0E 55%,#160A03 100%);
}
.dust{position:absolute;inset:0;pointer-events:none;overflow:hidden;}
.dust-p{
  position:absolute;border-radius:50%;
  background:rgba(196,119,59,.15);
  animation:floatUp linear infinite;
}
@keyframes floatUp{
  0%{transform:translateY(0) translateX(0);opacity:0;}
  10%{opacity:1;}90%{opacity:.5;}
  100%{transform:translateY(-110vh) translateX(20px);opacity:0;}
}

.app-title{
  position:relative;z-index:5;
  font-family:'Playfair Display',serif;font-style:italic;
  font-size:clamp(18px,5vw,26px);color:var(--cuivre);
  letter-spacing:.15em;text-shadow:0 2px 12px rgba(0,0,0,.6);
  margin-bottom:4px;
}
.app-subtitle{
  position:relative;z-index:5;font-size:10px;
  color:rgba(196,119,59,.45);letter-spacing:.3em;
  text-transform:uppercase;margin-bottom:clamp(16px,4vw,36px);
}

/* Étagère */
.shelf-container{position:relative;z-index:5;width:min(96vw,500px);}
.books-row{
  display:flex;align-items:flex-end;justify-content:center;
  gap:clamp(3px,1.5vw,10px);padding:0 12px;
}
.shelf-board{
  width:100%;height:20px;position:relative;
  background:linear-gradient(180deg,#D4884A 0%,#A05828 35%,#7A3F18 70%,#5C2E10 100%);
  border-radius:2px 2px 0 0;
  box-shadow:0 8px 28px rgba(0,0,0,.7),0 2px 0 rgba(255,255,255,.08) inset,0 -3px 0 rgba(0,0,0,.4) inset;
}
.shelf-board::before{
  content:'';position:absolute;inset:0;border-radius:inherit;
  background:repeating-linear-gradient(90deg,transparent 0,transparent 28px,rgba(0,0,0,.05) 29px,transparent 30px);
}
.shelf-shadow{
  position:absolute;top:0;left:0;right:0;height:100%;pointer-events:none;
  background:linear-gradient(90deg,
    rgba(0,0,0,.35) 0%,rgba(0,0,0,.1) 7%,transparent 14%,
    rgba(0,0,0,.2) 22%,rgba(0,0,0,.05) 27%,transparent 34%,
    rgba(0,0,0,.28) 42%,rgba(0,0,0,.07) 49%,transparent 56%,
    rgba(0,0,0,.18) 66%,rgba(0,0,0,.04) 72%,transparent 79%
  );
}
.shelf-support{
  position:absolute;bottom:-44px;width:100%;height:44px;
  display:flex;justify-content:space-between;padding:0 44px;pointer-events:none;
}
.shelf-support::before,.shelf-support::after{
  content:'';width:16px;height:46px;
  background:linear-gradient(180deg,#7A3F18 0%,#4A2510 100%);
  clip-path:polygon(8% 0,92% 0,100% 28%,65% 100%,35% 100%,0 28%);
  box-shadow:2px 2px 6px rgba(0,0,0,.5);
}

/* Déco */
.shelf-deco{position:absolute;z-index:6;bottom:20px;display:flex;align-items:flex-end;gap:6px;pointer-events:none;}
.shelf-deco.left{left:clamp(4px,2vw,14px);}
.shelf-deco.right{right:clamp(4px,2vw,14px);}
.candle{display:flex;flex-direction:column;align-items:center;position:relative;}
.candle-glow{
  position:absolute;top:-10px;left:50%;transform:translateX(-50%);
  width:44px;height:44px;border-radius:50%;
  background:radial-gradient(ellipse,rgba(255,180,0,.22) 0%,transparent 70%);
  animation:flicker 1.9s ease-in-out infinite alternate;
}
.candle-flame{
  width:8px;height:14px;
  background:radial-gradient(ellipse at 50% 80%,#fff 0%,#FFD700 35%,#FF6600 70%,transparent 100%);
  border-radius:50% 50% 20% 20%;filter:blur(1px);
  animation:flicker 1.9s ease-in-out infinite alternate;
}
@keyframes flicker{
  0%{transform:scaleX(1) scaleY(1) rotate(-2deg);}
  33%{transform:scaleX(.82) scaleY(1.12) rotate(3deg);}
  66%{transform:scaleX(1.08) scaleY(.93) rotate(-1deg);}
  100%{transform:scaleX(.88) scaleY(1.06) rotate(2deg);}
}
.candle-body{
  width:10px;height:30px;
  background:linear-gradient(90deg,#f0e8d0 0%,#fff8e8 45%,#e8d8b8 100%);
  border-radius:1px;box-shadow:1px 1px 4px rgba(0,0,0,.4);
}
.candle-base{width:14px;height:4px;background:linear-gradient(180deg,#C4773B 0%,#8B5A2B 100%);border-radius:2px;}
.herb-pot{display:flex;flex-direction:column;align-items:center;}
.herb-leaves{display:flex;gap:2px;align-items:flex-end;margin-bottom:1px;}
.herb-leaf{background:radial-gradient(ellipse at 50% 80%,#3A7D44 0%,#2D5A30 100%);border-radius:50% 50% 50% 50% / 60% 60% 40% 40%;transform-origin:bottom center;}
.herb-leaf:nth-child(1){width:8px;height:12px;transform:rotate(-25deg);}
.herb-leaf:nth-child(2){width:10px;height:15px;transform:rotate(-5deg);}
.herb-leaf:nth-child(3){width:9px;height:13px;transform:rotate(20deg);}
.herb-leaf:nth-child(4){width:7px;height:10px;transform:rotate(35deg);}
.herb-pot-body{width:20px;height:16px;background:linear-gradient(180deg,#C4773B 0%,#8B4513 100%);border-radius:1px 1px 3px 3px;clip-path:polygon(5% 0,95% 0,88% 100%,12% 100%);}
.herb-pot-rim{width:24px;height:5px;background:linear-gradient(180deg,#D4884A 0%,#A05828 100%);border-radius:2px;margin-bottom:1px;}

/* Livres */
.book{
  position:relative;cursor:pointer;flex-shrink:0;
  transform-origin:bottom center;
  transition:transform .25s cubic-bezier(.34,1.56,.64,1),filter .25s ease;
  -webkit-tap-highlight-color:transparent;user-select:none;
  filter:drop-shadow(2px 0 5px rgba(0,0,0,.5));
}
.book:hover{transform:translateY(-10px) scale(1.03);filter:drop-shadow(3px 0 10px rgba(0,0,0,.65));}
.book:active{transform:translateY(-4px) scale(0.96);}
.book.opening{pointer-events:none;opacity:.4;transition:opacity .15s;}
.book-spine{
  border-radius:8px;/*2px 8px 8px 2px;*/
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  position:relative;overflow:hidden;
  box-shadow:-3px 0 0 rgba(0,0,0,.5),4px 0 8px rgba(0,0,0,.35),0 2px 4px rgba(0,0,0,.4),inset 2px 0 0 rgba(255,255,255,.07);
}
.book-spine::after{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    repeating-linear-gradient(180deg,transparent 0,transparent 9px,rgba(0,0,0,.04) 10px,transparent 11px),
    linear-gradient(90deg,rgba(0,0,0,.18) 0%,transparent 22%,transparent 78%,rgba(0,0,0,.12) 100%);
}
.book-label{
  font-family:'Playfair Display',serif;writing-mode:vertical-rl;text-orientation:mixed;transform:rotate(180deg);
  font-size:clamp(10px,2.8vw,14px);font-weight:700;letter-spacing:.08em;
  text-align:center;position:relative;z-index:2;
  text-shadow:0 1px 3px rgba(0,0,0,.5);padding:8px 0;
}
.book-ornament{position:absolute;left:50%;transform:translateX(-50%);font-size:9px;opacity:.55;z-index:2;}
.book-ornament.top{top:8px;}.book-ornament.bot{bottom:8px;}
.book-lines{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:space-between;padding:28px 0;pointer-events:none;z-index:1;}
.book-line{width:100%;height:1px;}

.book-aperos .book-spine{width:clamp(40px,10.5vw,58px);height:clamp(155px,37vw,205px);background:linear-gradient(160deg,#8B2020 0%,#6B1515 45%,#4A0E0E 100%);}
.book-aperos .book-label{color:#F5D5A0;}.book-aperos .book-line{background:rgba(245,213,160,.22);}.book-aperos .book-ornament{color:#F5D5A0;}
.book-entrees .book-spine{width:clamp(48px,12.5vw,70px);height:clamp(125px,29vw,165px);background:linear-gradient(155deg,#2D5A3D 0%,#1E3D2A 45%,#122618 100%);}
.book-entrees .book-label{color:#A8D5B5;}.book-entrees .book-line{background:rgba(168,213,181,.18);}.book-entrees .book-ornament{color:#A8D5B5;}
.book-plats .book-spine{width:clamp(42px,11.5vw,63px);height:clamp(165px,39vw,225px);background:linear-gradient(160deg,#1A3A5C 0%,#0F2540 45%,#081527 100%);}
.book-plats .book-label{color:#A8C5E0;}.book-plats .book-line{background:rgba(168,197,224,.18);}.book-plats .book-ornament{color:#A8C5E0;}
.book-plats:not(:hover):not(:active):not(.opening){transform:rotate(-4deg) translateY(-4px);}
.book-plats:hover{transform:rotate(-4deg) translateY(-14px) scale(1.03);}
.book-desserts .book-spine{width:clamp(36px,9.5vw,52px);height:clamp(115px,27vw,150px);background:linear-gradient(150deg,#6B4226 0%,#4A2D1A 45%,#2E1B10 100%);}
.book-desserts .book-label{color:#E8C9A0;}.book-desserts .book-line{background:rgba(232,201,160,.22);}.book-desserts .book-ornament{color:#E8C9A0;}
.book-desserts {margin-left: 0px;}
/* Overlay parchemin */
.parchment-overlay{
  position:fixed;inset:0;z-index:1000;
  display:flex;align-items:center;justify-content:center;
  pointer-events:none;
}
.parchment-overlay.open{pointer-events:all;}
.parchment-backdrop{
  position:absolute;inset:0;background:rgba(8,4,1,.88);
  opacity:0;transition:opacity .45s ease;
}
.parchment-overlay.open .parchment-backdrop{opacity:1;}
.open-book{
  height: 100%;
  position:relative;z-index:2;
  width:min(96vw,500px);max-height:90vh;
  transform:perspective(900px) rotateX(14deg) scale(.86);
  opacity:0;
  transition:transform .55s cubic-bezier(.22,1,.36,1),opacity .4s ease;
  display:flex;flex-direction:column;
  box-shadow:0 30px 80px rgba(0,0,0,.7),0 0 0 1px rgba(196,119,59,.18);
  border-radius:10px;
}
.parchment-overlay.open .open-book{transform:perspective(900px) rotateX(0) scale(1);opacity:1;}

.book-header{
  background:linear-gradient(135deg,#4A2810 0%,#2C1A0E 100%);
  border-radius:10px 10px 0 0;padding:12px 18px 10px;
  display:flex;align-items:center;justify-content:space-between;
  border-bottom:2px solid var(--cuivre);flex-shrink:0;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.05);
}
.book-header-title{font-family:'Playfair Display',serif;font-style:italic;font-size:17px;color:var(--cuivre);letter-spacing:.06em;}
.close-btn{
  cursor:pointer;width:30px;height:30px;border-radius:50%;
  background:rgba(196,119,59,.12);border:1px solid rgba(196,119,59,.3);
  display:flex;align-items:center;justify-content:center;
  color:var(--cuivre);font-size:15px;transition:background .2s;
  -webkit-tap-highlight-color:transparent;flex-shrink:0;
}
.close-btn:hover{background:rgba(196,119,59,.28);}

/* Pages — texture parchemin riche */
.book-pages{
  flex:1;overflow-y:auto;overflow-x:hidden;
  padding:22px 20px 16px 26px;
  max-height:calc(90vh - 108px);position:relative;
  background:
    repeating-linear-gradient(90deg,transparent 0,transparent 2px,rgba(180,140,80,.04) 3px,transparent 4px),
    radial-gradient(ellipse 75% 55% at 28% 18%,rgba(210,175,110,.2) 0%,transparent 60%),
    radial-gradient(ellipse 55% 45% at 82% 82%,rgba(165,120,55,.14) 0%,transparent 55%),
    radial-gradient(ellipse 38% 28% at 8% 92%,rgba(140,95,45,.12) 0%,transparent 50%),
    radial-gradient(ellipse 30% 22% at 94% 12%,rgba(175,135,70,.1) 0%,transparent 50%),
    linear-gradient(172deg,#f8efd8 0%,#f2e5c2 22%,#eedcb8 50%,#e6d0a8 75%,#dcc898 100%);
}
.book-pages::before{
  content:'';position:absolute;top:0;left:24px;bottom:0;width:1px;
  background:rgba(180,50,50,.16);pointer-events:none;
}
.book-pages::after{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    linear-gradient(90deg,rgba(100,60,20,.14) 0%,transparent 9%,transparent 91%,rgba(80,40,10,.1) 100%),
    linear-gradient(180deg,rgba(120,70,20,.1) 0%,transparent 14%,transparent 86%,rgba(100,55,15,.16) 100%);
}
.book-pages::-webkit-scrollbar{width:3px;}
.book-pages::-webkit-scrollbar-thumb{background:rgba(139,90,43,.25);border-radius:2px;}

.book-footer{
  background:linear-gradient(135deg,#2C1A0E 0%,#4A2810 100%);
  border-radius:0 0 10px 10px;padding:10px 20px;
  display:flex;align-items:center;justify-content:center;
  gap:18px;border-top:2px solid var(--cuivre);flex-shrink:0;
}
.page-btn{
  cursor:pointer;width:34px;height:34px;border-radius:50%;
  background:rgba(196,119,59,.1);border:1px solid rgba(196,119,59,.38);
  color:var(--cuivre);font-size:20px;
  display:flex;align-items:center;justify-content:center;
  transition:all .2s;-webkit-tap-highlight-color:transparent;
  opacity:0;pointer-events:none;
}
.page-btn.active{opacity:1;pointer-events:all;}
.page-btn:active{transform:scale(.88);}
.page-indicator{font-family:'Playfair Display',serif;font-style:italic;color:rgba(196,119,59,.6);font-size:12px;min-width:70px;text-align:center;}

/* Feuilletage */
@keyframes flipRight{0%{opacity:0;transform:perspective(700px) rotateY(-22deg) translateX(-12px);}100%{opacity:1;transform:none;}}
@keyframes flipLeft{0%{opacity:0;transform:perspective(700px) rotateY(22deg) translateX(12px);}100%{opacity:1;transform:none;}}
.flip-right{animation:flipRight .35s cubic-bezier(.22,1,.36,1);}
.flip-left{animation:flipLeft .35s cubic-bezier(.22,1,.36,1);}

/* Contenu recette */
.recipe-title{font-family:'Playfair Display',serif;font-size:clamp(16px,4.5vw,22px);font-weight:700;color:var(--encre);text-align:center;margin-bottom:3px;line-height:1.3;position:relative;z-index:1;}
.recipe-subtitle{text-align:center;font-style:italic;color:var(--bois-clair);font-size:12px;margin-bottom:14px;position:relative;z-index:1;}
.recipe-photo{width:100%;max-height:185px;object-fit:cover;border-radius:4px;border:3px solid var(--parchemin-ombre);box-shadow:0 4px 18px rgba(0,0,0,.22);margin-bottom:16px;display:block;position:relative;z-index:1;filter:sepia(.18) saturate(.88);}
.recipe-photo-placeholder{width:100%;height:130px;background:linear-gradient(135deg,rgba(196,150,80,.14) 0%,rgba(160,110,50,.1) 100%);border-radius:4px;border:2px dashed rgba(139,90,43,.26);display:flex;align-items:center;justify-content:center;color:rgba(139,90,43,.42);font-style:italic;font-size:12px;margin-bottom:16px;position:relative;z-index:1;}
.recipe-meta{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;justify-content:center;position:relative;z-index:1;}
.meta-pill{background:rgba(139,90,43,.1);border:1px solid rgba(139,90,43,.2);border-radius:20px;padding:3px 10px;font-size:11px;color:var(--bois-moyen);display:flex;align-items:center;gap:4px;}
.recipe-divider{width:100%;height:1px;position:relative;margin:14px 0;z-index:1;background:linear-gradient(90deg,transparent,rgba(139,90,43,.26) 20%,rgba(139,90,43,.26) 80%,transparent);}
.recipe-divider::before{content:'✦';position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);padding:0 7px;background:linear-gradient(180deg,#f0e0b8,#e8d4a8);color:rgba(139,90,43,.36);font-size:9px;}
.section-title{font-family:'Playfair Display',serif;font-size:14px;font-weight:700;color:var(--encre);margin-bottom:10px;display:flex;align-items:center;gap:7px;position:relative;z-index:1;}
.section-title::after{content:'';flex:1;height:1px;background:rgba(139,90,43,.18);}
.ingredients-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px 14px;margin-bottom:4px;position:relative;z-index:1;}
.ingredient-row{display:flex;justify-content:space-between;align-items:baseline;padding:4px 0;border-bottom:1px dotted rgba(139,90,43,.18);font-size:12px;}
.ingredient-name{color:var(--encre);}
.ingredient-qty{color:var(--bois-moyen);font-style:italic;font-size:11px;white-space:nowrap;margin-left:6px;flex-shrink:0;}
.steps-list{position:relative;z-index:1;}
.step-item{display:flex;gap:10px;margin-bottom:12px;align-items:flex-start;}
.step-number{flex-shrink:0;width:22px;height:22px;border-radius:50%;background:var(--bois-fonce);color:var(--cuivre);font-family:'Playfair Display',serif;font-size:11px;display:flex;align-items:center;justify-content:center;margin-top:1px;}
.step-text{font-size:12px;line-height:1.65;color:var(--encre);flex:1;}
.empty-state{text-align:center;padding:36px 20px;color:rgba(139,90,43,.44);}
.empty-state p:first-child{font-size:30px;margin-bottom:8px;}
.empty-state p:last-child{font-style:italic;font-size:13px;}

/* Boutons actions recette */
.recipe-actions{
  display:flex;gap:8px;justify-content:center;margin-top:18px;
  position:relative;z-index:1;
}
.btn-action{
  padding:7px 16px;border-radius:20px;font-family:'Lora',serif;font-size:12px;
  cursor:pointer;transition:all .2s;-webkit-tap-highlight-color:transparent;
  display:flex;align-items:center;gap:5px;
}
.btn-edit{
  background:rgba(139,90,43,.12);border:1px solid rgba(139,90,43,.3);
  color:var(--bois-moyen);
}
.btn-edit:hover{background:rgba(139,90,43,.22);}
.btn-delete{
  background:rgba(140,30,30,.09);border:1px solid rgba(140,30,30,.22);
  color:#8B2020;
}
.btn-delete:hover{background:rgba(140,30,30,.18);}

/* Confirm delete */
.confirm-overlay{
  position:fixed;inset:0;z-index:3000;
  display:flex;align-items:center;justify-content:center;
  pointer-events:none;opacity:0;transition:opacity .25s;
}
.confirm-overlay.open{pointer-events:all;opacity:1;}
.confirm-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.6);}
.confirm-box{
  position:relative;z-index:2;
  background:linear-gradient(160deg,#3D2410 0%,#2C1A0E 100%);
  border:2px solid rgba(196,119,59,.35);border-radius:10px;
  padding:24px 22px;width:min(90vw,340px);
  box-shadow:0 20px 60px rgba(0,0,0,.7);
  text-align:center;
  transform:scale(.93);transition:transform .25s cubic-bezier(.22,1,.36,1);
}
.confirm-overlay.open .confirm-box{transform:scale(1);}
.confirm-title{font-family:'Playfair Display',serif;font-style:italic;color:var(--cuivre);font-size:16px;margin-bottom:8px;}
.confirm-text{color:rgba(242,232,200,.6);font-size:13px;margin-bottom:20px;line-height:1.5;}
.confirm-btns{display:flex;gap:10px;}
.btn-confirm-cancel{flex:1;padding:10px;border-radius:6px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(242,232,200,.55);font-family:'Lora',serif;font-size:13px;cursor:pointer;}
.btn-confirm-delete{flex:1;padding:10px;border-radius:6px;background:linear-gradient(135deg,#8B2020 0%,#5C1515 100%);border:none;color:#FAF5E4;font-family:'Playfair Display',serif;font-style:italic;font-size:14px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.3);}
.btn-confirm-delete:hover{filter:brightness(1.15);}

/* Formulaire ajout/edit */
.add-overlay{
  position:fixed;inset:0;z-index:2000;
  display:flex;align-items:center;justify-content:center;
  pointer-events:none;opacity:0;transition:opacity .3s ease;
}
.add-overlay.open{pointer-events:all;opacity:1;}
.add-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.72);}
.add-modal{
  position:relative;z-index:2;
  width:min(96vw,480px);max-height:88vh;
  background:linear-gradient(160deg,#3D2410 0%,#2C1A0E 100%);
  border-radius:10px;border:2px solid rgba(196,119,59,.38);
  box-shadow:0 20px 60px rgba(0,0,0,.7);
  display:flex;flex-direction:column;
  transform:translateY(28px) scale(.95);
  transition:transform .35s cubic-bezier(.22,1,.36,1);overflow:hidden;
}
.add-overlay.open .add-modal{transform:translateY(0) scale(1);}
.add-modal-header{padding:13px 18px 11px;border-bottom:1px solid rgba(196,119,59,.22);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.add-modal-title{font-family:'Playfair Display',serif;font-style:italic;font-size:17px;color:var(--cuivre);}
.add-modal-body{overflow-y:auto;padding:16px 18px 10px;flex:1;}
.add-modal-body::-webkit-scrollbar{width:3px;}
.add-modal-body::-webkit-scrollbar-thumb{background:rgba(196,119,59,.22);}
.field-group{margin-bottom:13px;}
.field-label{display:block;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:rgba(196,119,59,.65);margin-bottom:5px;font-family:'Playfair Display',serif;}
.field-input,.field-select,.field-textarea{
  width:100%;background:rgba(255,255,255,.06);
  border:1px solid rgba(196,119,59,.22);border-radius:5px;
  color:#F2E8C8;font-family:'Lora',serif;font-size:13px;
  padding:8px 12px;outline:none;
  transition:border-color .2s,background .2s;-webkit-appearance:none;
}
.field-input::placeholder,.field-textarea::placeholder{color:rgba(242,232,200,.25);}
.field-input:focus,.field-select:focus,.field-textarea:focus{border-color:rgba(196,119,59,.55);background:rgba(255,255,255,.09);}
.field-select option{background:#2C1A0E;color:#F2E8C8;}
.field-textarea{resize:none;height:70px;line-height:1.5;}
.fields-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.ing-list{display:flex;flex-direction:column;gap:6px;margin-top:6px;}
.ing-row{display:grid;grid-template-columns:1fr 80px auto;gap:6px;align-items:center;}
.ing-row .field-input{font-size:12px;padding:7px 10px;}
.ing-del{width:28px;height:28px;border-radius:50%;flex-shrink:0;background:rgba(180,40,40,.16);border:1px solid rgba(180,40,40,.28);color:rgba(255,130,130,.75);font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;-webkit-tap-highlight-color:transparent;}
.ing-del:hover{background:rgba(180,40,40,.3);}
.add-ing-btn{margin-top:6px;padding:7px 14px;background:rgba(196,119,59,.09);border:1px solid rgba(196,119,59,.25);border-radius:5px;color:rgba(196,119,59,.75);font-family:'Lora',serif;font-size:12px;cursor:pointer;transition:background .2s;-webkit-tap-highlight-color:transparent;width:100%;}
.add-ing-btn:hover{background:rgba(196,119,59,.18);}
.steps-add-list{display:flex;flex-direction:column;gap:8px;margin-top:6px;}
.step-add-row{display:grid;grid-template-columns:auto 1fr auto;gap:7px;align-items:flex-start;}
.step-add-num{width:24px;height:24px;border-radius:50%;flex-shrink:0;background:rgba(196,119,59,.18);color:var(--cuivre);font-family:'Playfair Display',serif;font-size:12px;display:flex;align-items:center;justify-content:center;margin-top:8px;}
.add-modal-footer{padding:11px 18px;border-top:1px solid rgba(196,119,59,.18);display:flex;gap:10px;flex-shrink:0;}
.btn-cancel{flex:1;padding:10px;border-radius:6px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(242,232,200,.55);font-family:'Lora',serif;font-size:13px;cursor:pointer;transition:background .2s;-webkit-tap-highlight-color:transparent;}
.btn-cancel:hover{background:rgba(255,255,255,.1);}
.btn-save{flex:2;padding:10px;border-radius:6px;background:linear-gradient(135deg,#C4773B 0%,#A05828 100%);border:none;color:#FAF5E4;font-family:'Playfair Display',serif;font-style:italic;font-size:14px;cursor:pointer;transition:filter .2s,transform .1s;box-shadow:0 2px 8px rgba(0,0,0,.3);-webkit-tap-highlight-color:transparent;}
.btn-save:hover{filter:brightness(1.1);}
.btn-save:active{transform:scale(.97);}
.fab{
  position:fixed;bottom:28px;right:20px;z-index:1999;
  width:48px;height:48px;border-radius:50%;
  background:linear-gradient(135deg,#C4773B 0%,#8B4513 100%);
  border:none;color:#FAF5E4;font-size:22px;
  box-shadow:0 4px 16px rgba(0,0,0,.5),0 0 0 2px rgba(196,119,59,.28);
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:transform .22s cubic-bezier(.34,1.56,.64,1),box-shadow .2s,opacity .3s;
  -webkit-tap-highlight-color:transparent;
}
.fab:hover{transform:scale(1.1);}
.fab:active{transform:scale(.91);}
.fab.hidden{opacity:0;pointer-events:none;transform:scale(0);}

.login{position:absolute;top:12px;right:12px;z-index:9999;}
.login-btn{
  -webkit-tap-highlight-color: transparent;
  display:inline-flex;align-items:center;gap:6px;padding:8px 14px;
  border-radius:20px;font-family:'Playfair Display',serif;
  font-size:12px;letter-spacing:.06em;cursor:pointer;text-decoration:none;
  background:rgba(196,119,59,.12);border:1px solid rgba(196,119,59,.35);
  color:var(--cuivre);box-shadow:0 2px 10px rgba(0,0,0,.25);
  transition:all .25s ease;backdrop-filter:blur(3px);
}
.login-btn:hover{background:rgba(196,119,59,.22);transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,.35);}

.login-btn.logout{background:rgba(140,30,30,.12);border:1px solid rgba(140,30,30,.35);color:#ffb3b3;}

.login-btn.logout:hover{background:rgba(140,30,30,.22);}


/* Animation ouverture livre */
@keyframes bookFlip{
  0%{transform:perspective(700px) rotateY(0) scaleX(1);opacity:1;}
  55%{transform:perspective(700px) rotateY(-90deg) scaleX(.6);opacity:.5;}
  100%{transform:perspective(700px) rotateY(-120deg) scaleX(.3);opacity:0;}
}
.book-clone-wrap{
  position:fixed;z-index:950;pointer-events:none;transform-style:preserve-3d;
}
.book-clone-inner{
  width:100%;height:100%;
  animation:bookFlip .5s cubic-bezier(.4,0,.2,1) forwards;
}

@media(prefers-reduced-motion:reduce){
  .book,.open-book,.add-modal,.parchment-backdrop{transition:none!important;}
  .flip-right,.flip-left,.candle-flame,.candle-glow,.dust-p{animation:none!important;}
}
</style>
</head>
<body>
<div class="scene">
  <div class="login">
    <form method="post">
      <?php 
      if (isset($_SESSION['user'])) {
        ?><button type="submit" name="logout" class="login-btn logout">✕ Déconnexion</button><?
      }
      else {
      ?><button type="submit" name="login" class="login-btn login">🔐 Connexion</button><?php 
      }
      ?>
    </form>
  </div>
  <div class="dust" id="dust"></div>
  <p class="app-title">Ma Cuisine</p>
  <p class="app-subtitle">— Recettes de famille —</p>

  <div class="shelf-container">
    <div class="shelf-deco left">
      <div class="candle">
        <div class="candle-glow"></div>
        <div class="candle-flame"></div>
        <div class="candle-body"></div>
        <div class="candle-base"></div>
      </div>
    </div>
    <div class="books-row">
      <div class="book book-aperos" data-type="apero" data-label="Apéros">
        <div class="book-spine">
          <div class="book-ornament top">✦</div>
          <div class="book-lines"><div class="book-line"></div><div class="book-line"></div></div>
          <div class="book-label">Apéros</div>
          <div class="book-lines"><div class="book-line"></div><div class="book-line"></div></div>
          <div class="book-ornament bot">✦</div>
        </div>
      </div>
      <div class="book book-entrees" data-type="entree" data-label="Entrées">
        <div class="book-spine">
          <div class="book-ornament top">❧</div>
          <div class="book-lines"><div class="book-line"></div></div>
          <div class="book-label">Entrées</div>
          <div class="book-lines"><div class="book-line"></div></div>
          <div class="book-ornament bot">❧</div>
        </div>
      </div>
      <div class="book book-plats" data-type="plat" data-label="Plats">
        <div class="book-spine">
          <div class="book-ornament top">◆</div>
          <div class="book-lines"><div class="book-line"></div></div>
          <div class="book-label">Plats</div>
          <div class="book-lines"><div class="book-line"></div></div>
          <div class="book-ornament bot">◆</div>
        </div>
      </div>
      <div class="book book-desserts" data-type="dessert" data-label="Desserts">
        <div class="book-spine">
          <div class="book-ornament top">✿</div>
          <div class="book-label">Desserts</div>
          <div class="book-ornament bot">✿</div>
        </div>
      </div>
    </div>
    <div class="shelf-deco right">
      <div class="herb-pot">
        <div class="herb-leaves">
          <div class="herb-leaf"></div><div class="herb-leaf"></div>
          <div class="herb-leaf"></div><div class="herb-leaf"></div>
        </div>
        <div class="herb-pot-rim"></div>
        <div class="herb-pot-body"></div>
      </div>
    </div>
    <div class="shelf-board">
      <div class="shelf-shadow"></div>
      <div class="shelf-support"></div>
    </div>
  </div>
</div>

<!-- Parchemin -->
<div class="parchment-overlay" id="parchment">
  <div class="parchment-backdrop" id="backdrop"></div>
  <div class="open-book">
    <div class="book-header">
      <div class="close-btn" id="closeBtn">✕</div>
      <div class="book-header-title" id="bookHeaderTitle">—</div>
      <div style="width:30px"></div>
    </div>
    <div class="book-pages" id="bookPages"></div>
    <div class="book-footer">
      <div class="page-btn" id="prevBtn">‹</div>
      <div class="page-indicator" id="pageIndicator"></div>
      <div class="page-btn" id="nextBtn">›</div>
    </div>
  </div>
</div>

<!-- Confirmation suppression -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-backdrop" id="confirmBackdrop"></div>
  <div class="confirm-box">
    <div class="confirm-title">Supprimer cette recette ?</div>
    <div class="confirm-text" id="confirmText">Cette action est irréversible.</div>
    <div class="confirm-btns">
      <button class="btn-confirm-cancel" id="confirmCancel">Annuler</button>
      <button class="btn-confirm-delete" id="confirmDelete">Supprimer</button>
    </div>
  </div>
</div>

<!-- Ajout / Modification recette -->
<div class="add-overlay" id="addOverlay">
  <div class="add-backdrop" id="addBackdrop"></div>
  <div class="add-modal">
    <div class="add-modal-header">
      <div class="add-modal-title" id="addModalTitle">Nouvelle recette</div>
      <div class="close-btn" id="closeAddBtn">✕</div>
    </div>
    <div class="add-modal-body">
      <div class="field-group">
        <label class="field-label">Catégorie</label>
        <select class="field-select" id="addType">
          <option value="apero">Apéros</option>
          <option value="entree">Entrées</option>
          <option value="plat">Plats</option>
          <option value="dessert">Desserts</option>
        </select>
      </div>
      <div class="field-group">
        <label class="field-label">Nom de la recette</label>
        <input class="field-input" id="addNom" type="text" placeholder="Ex : Tarte aux pommes de grand-mère">
      </div>
      <div class="fields-row">
        <div class="field-group">
          <label class="field-label">Préparation (min)</label>
          <input class="field-input" id="addPrep" type="number" placeholder="20">
        </div>
        <div class="field-group">
          <label class="field-label">Cuisson (min)</label>
          <input class="field-input" id="addCuisson" type="number" placeholder="35">
        </div>
      </div>
      <div class="fields-row">
        <div class="field-group">
          <label class="field-label">Personnes</label>
          <input class="field-input" id="addPersonnes" type="number" placeholder="4">
        </div>
        <div class="field-group">
          <label class="field-label">Difficulté</label>
          <select class="field-select" id="addDiff">
            <option>Facile</option><option>Moyen</option><option>Difficile</option>
          </select>
        </div>
      </div>
      <div class="field-group">
        <label class="field-label">Ingrédients</label>
        <div class="ing-list" id="ingList">
          <div class="ing-row">
            <input class="field-input" placeholder="Ingrédient" data-ing-name>
            <input class="field-input" placeholder="Qté" data-ing-qty>
            <div class="ing-del" onclick="delIng(this)">×</div>
          </div>
        </div>
        <button class="add-ing-btn" onclick="addIng()">+ Ajouter un ingrédient</button>
      </div>
      <div class="field-group">
        <label class="field-label">Étapes de préparation</label>
        <div class="steps-add-list" id="stepsList">
          <div class="step-add-row">
            <div class="step-add-num">1</div>
            <textarea class="field-textarea" placeholder="Décrivez cette étape…" data-step></textarea>
            <div class="ing-del" onclick="delStep(this)" style="margin-top:8px">×</div>
          </div>
        </div>
        <button class="add-ing-btn" onclick="addStep()">+ Ajouter une étape</button>
      </div>
    </div>
    <div class="add-modal-footer">
      <button class="btn-cancel" id="cancelAddBtn">Annuler</button>
      <button class="btn-save" onclick="saveRecette()">Enregistrer la recette</button>
    </div>
  </div>
</div>

<?php 
if (isset($_SESSION['user'])) {
  ?>
  <button class="fab hidden" id="fab">+</button>
  <?php
}
?>


<script>

let RECETTES = JSON.parse(JSON.stringify(RECETTES_DATA));//loadRecettes();

// ── État global ─────────────────────────────────────────────────────────────
let state = {type:null, page:0, label:''};
let editIndex = null; // null = ajout, number = modification
let id = null;

// ── Particules de poussière ─────────────────────────────────────────────────
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

// ── Son de page ─────────────────────────────────────────────────────────────
function playPageSound(){
  try {
    const ctx = new(window.AudioContext||window.webkitAudioContext)();
    const len = ctx.sampleRate * 0.07;
    const buf = ctx.createBuffer(1, len, ctx.sampleRate);
    const d = buf.getChannelData(0);
    for(let i = 0; i < len; i++) d[i] = (Math.random()*2-1) * Math.pow(1-i/len, 2.5) * 0.15;
    const src = ctx.createBufferSource();
    src.buffer = buf;
    const f = ctx.createBiquadFilter();
    f.type = 'bandpass'; f.frequency.value = 3200; f.Q.value = 0.4;
    src.connect(f); f.connect(ctx.destination);
    src.start();
  } catch(e){}
}

function haptic(p){try{navigator.vibrate&&navigator.vibrate(p);}catch(e){}}

// ── Animation ouverture livre 3D ────────────────────────────────────────────
function animateBookOpen(bookEl, cb){
  const spine = bookEl.querySelector('.book-spine');
  const rect = spine.getBoundingClientRect();
  const clone = spine.cloneNode(true);
  const wrap = document.createElement('div');
  wrap.className = 'book-clone-wrap';
  wrap.style.cssText = `left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;`;
  const inner = document.createElement('div');
  inner.className = 'book-clone-inner';
  inner.appendChild(clone);
  wrap.appendChild(inner);
  document.body.appendChild(wrap);
  bookEl.classList.add('opening');
  setTimeout(()=>{ wrap.remove(); bookEl.classList.remove('opening'); cb(); }, 460);
}



function renderEmpty(label){
  return `<div class="empty-state"><p>📖</p><p>Aucune recette dans « ${label} ».<br>Appuyez sur + pour en ajouter une.</p></div>`;
}

function updateNav(){
  const list = RECETTES[state.type]||[];
  document.getElementById('prevBtn').classList.toggle('active', state.page > 0);
  document.getElementById('nextBtn').classList.toggle('active', state.page < list.length-1);
  document.getElementById('pageIndicator').textContent = list.length ? `${state.page+1} / ${list.length}` : '';
}

function loadPage(dir){
  const list = RECETTES[state.type]||[];
  const pages = document.getElementById('bookPages');
  pages.innerHTML = list.length ? renderRecipe(list[state.page], dir) : renderEmpty(state.label);
  pages.scrollTop = 0;
  updateNav();
  playPageSound();
  haptic(8);
}

function openBook(type, label, bookEl){
  state.type = type; state.page = 0; state.label = label;
  document.getElementById('bookHeaderTitle').textContent = label;
  document.getElementById('addType').value = type;
  haptic(15);
  animateBookOpen(bookEl, ()=>{
    document.getElementById('parchment').classList.add('open');
    loadPage(1);
    document.getElementById('fab').classList.remove('hidden');
  });
}

function closeParchment(){
  document.getElementById('parchment').classList.remove('open');
  document.getElementById('fab').classList.add('hidden');
  haptic(10);
}

document.querySelectorAll('.book').forEach(b => {
  b.addEventListener('click', ()=> openBook(b.dataset.type, b.dataset.label, b));
});
document.getElementById('closeBtn').addEventListener('click', closeParchment);
document.getElementById('backdrop').addEventListener('click', closeParchment);
document.getElementById('prevBtn').addEventListener('click', ()=>{
  if(state.page > 0){ state.page--; loadPage(-1); }
});
document.getElementById('nextBtn').addEventListener('click', ()=>{
  const list = RECETTES[state.type]||[];
  if(state.page < list.length-1){ state.page++; loadPage(1); }
});

// ── Suppression ─────────────────────────────────────────────────────────────
function openDeleteConfirm(){
  const list = RECETTES[state.type]||[];
  const nom = list[state.page]?.nom || 'cette recette';
  document.getElementById('confirmText').textContent = `« ${nom} » sera définitivement supprimée.`;
  document.getElementById('confirmOverlay').classList.add('open');
  haptic(12);
}

function closeConfirm(){
  document.getElementById('confirmOverlay').classList.remove('open');
}

async function deleteRecette(){
  const list = RECETTES[state.type]||[];
  const id = RECETTES[state.type][state.page].id;
  
  list.splice(state.page, 1);
  
  closeConfirm();
  haptic([20,10,20]);
  // Ajuste la page si on était à la fin
  if(state.page >= list.length && state.page > 0) state.page--;
  loadPage(0);

  await deleteBdd(id);
}

document.getElementById('confirmCancel').addEventListener('click', closeConfirm);
document.getElementById('confirmBackdrop').addEventListener('click', closeConfirm);
document.getElementById('confirmDelete').addEventListener('click', deleteRecette);

// ── Formulaire ajout / modification ─────────────────────────────────────────
function resetForm(){
  document.getElementById('addNom').value='';
  document.getElementById('addPrep').value='';
  document.getElementById('addCuisson').value='';
  document.getElementById('addPersonnes').value='';
  document.getElementById('addDiff').value='Facile';
  document.getElementById('ingList').innerHTML=`<div class="ing-row"><input class="field-input" placeholder="Ingrédient" data-ing-name><input class="field-input" placeholder="Qté" data-ing-qty><div class="ing-del" onclick="delIng(this)">×</div></div>`;
  document.getElementById('stepsList').innerHTML=`<div class="step-add-row"><div class="step-add-num">1</div><textarea class="field-textarea" placeholder="Décrivez cette étape…" data-step></textarea><div class="ing-del" onclick="delStep(this)" style="margin-top:8px">×</div></div>`;
}

function fillForm(recipe){
  document.getElementById('addType').value = state.type;
  document.getElementById('addNom').value = recipe.nom||'';
  document.getElementById('addPrep').value = recipe.temps_prep||'';
  document.getElementById('addCuisson').value = recipe.temps_cuisson||'';
  document.getElementById('addPersonnes').value = recipe.nb_personne||'';
  document.getElementById('addDiff').value = recipe.difficulte||'Facile';
  // Ingrédients
  const ingList = document.getElementById('ingList');
  ingList.innerHTML = '';
  (recipe.ingredients||[]).forEach(ing => {
    const row = document.createElement('div'); row.className = 'ing-row';
    row.innerHTML = `<input class="field-input" placeholder="Ingrédient" data-ing-name value="${ing.ingredient||''}"><input class="field-input" placeholder="Qté" data-ing-qty value="${ing.qty||''}"><div class="ing-del" onclick="delIng(this)">×</div>`;
    ingList.appendChild(row);
  });
  if(!recipe.ingredients||!recipe.ingredients.length){
    ingList.innerHTML=`<div class="ing-row"><input class="field-input" placeholder="Ingrédient" data-ing-name><input class="field-input" placeholder="Qté" data-ing-qty><div class="ing-del" onclick="delIng(this)">×</div></div>`;
  }
  // Étapes
  const stepsList = document.getElementById('stepsList');
  stepsList.innerHTML = '';
  (recipe.preparation||[]).forEach((s, i) => {
    const row = document.createElement('div'); row.className = 'step-add-row';
    const escaped = (s.action||'').replace(/"/g,'&quot;');
    row.innerHTML = `<div class="step-add-num">${i+1}</div><textarea class="field-textarea" placeholder="Décrivez cette étape…" data-step>${s.action||''}</textarea><div class="ing-del" onclick="delStep(this)" style="margin-top:8px">×</div>`;
    stepsList.appendChild(row);
  });
  if(!recipe.preparation||!recipe.preparation.length){
    stepsList.innerHTML=`<div class="step-add-row"><div class="step-add-num">1</div><textarea class="field-textarea" placeholder="Décrivez cette étape…" data-step></textarea><div class="ing-del" onclick="delStep(this)" style="margin-top:8px">×</div></div>`;
  }
}

function openEdit(){
  const list = RECETTES[state.type]||[];
  const recipe = list[state.page];
  console.log(RECETTES)
  if(!recipe) return;
  editIndex = state.page;
  id = recipe.id;
  
  document.getElementById('addModalTitle').textContent = 'Modifier la recette';
  document.getElementById('addType').disabled = true; // Pas de changement de catégorie en modif
  fillForm(recipe);
  document.getElementById('addOverlay').classList.add('open');
  haptic(12);
}

function openAdd(){
  editIndex = null;
  id = null;
  document.getElementById('addModalTitle').textContent = 'Nouvelle recette';
  document.getElementById('addType').disabled = false;
  document.getElementById('addType').value = state.type||'apero';
  resetForm();
  document.getElementById('addOverlay').classList.add('open');
  haptic(12);
}

function closeAdd(){
  document.getElementById('addOverlay').classList.remove('open');
  document.getElementById('addType').disabled = false;
}

document.getElementById('fab').addEventListener('click', openAdd);
document.getElementById('closeAddBtn').addEventListener('click', closeAdd);
document.getElementById('cancelAddBtn').addEventListener('click', closeAdd);
document.getElementById('addBackdrop').addEventListener('click', closeAdd);

function addIng(){
  const list = document.getElementById('ingList');
  const row = document.createElement('div'); row.className = 'ing-row';
  row.innerHTML = `<input class="field-input" placeholder="Ingrédient" data-ing-name><input class="field-input" placeholder="Qté" data-ing-qty><div class="ing-del" onclick="delIng(this)">×</div>`;
  list.appendChild(row); row.querySelector('input').focus();
}
function delIng(btn){
  const rows = document.getElementById('ingList').querySelectorAll('.ing-row');
  if(rows.length > 1) btn.closest('.ing-row').remove();
}
function addStep(){
  const list = document.getElementById('stepsList');
  const n = list.querySelectorAll('.step-add-row').length + 1;
  const row = document.createElement('div'); row.className = 'step-add-row';
  row.innerHTML = `<div class="step-add-num">${n}</div><textarea class="field-textarea" placeholder="Décrivez cette étape…" data-step></textarea><div class="ing-del" onclick="delStep(this)" style="margin-top:8px">×</div>`;
  list.appendChild(row); row.querySelector('textarea').focus();
}
function delStep(btn){
  const rows = document.getElementById('stepsList').querySelectorAll('.step-add-row');
  if(rows.length > 1){
    btn.closest('.step-add-row').remove();
    document.getElementById('stepsList').querySelectorAll('.step-add-num').forEach((n,i)=>n.textContent=i+1);
  }
}

async function saveRecette(){
  const type = document.getElementById('addType').value;
  const nom = document.getElementById('addNom').value.trim();
  if(!nom){ document.getElementById('addNom').focus(); return; }
  const prep = parseInt(document.getElementById('addPrep').value)||0;
  const cuisson = parseInt(document.getElementById('addCuisson').value)||0;
  const pers = parseInt(document.getElementById('addPersonnes').value)||4;
  const diff = document.getElementById('addDiff').value;
  const ingredients = [];
  document.getElementById('ingList').querySelectorAll('.ing-row').forEach(r=>{
    const n = r.querySelector('[data-ing-name]').value.trim();
    const q = r.querySelector('[data-ing-qty]').value.trim();
    if(n) ingredients.push({ingredient:n, qty:q||''});
  });
  const preparation = [];
  document.getElementById('stepsList').querySelectorAll('[data-step]').forEach(t=>{
    const a = t.value.trim(); if(a) preparation.push({action:a});
  });

  const recette = {type ,id, nom, photo:null, temps_prep:prep, temps_cuisson:cuisson, difficulte:diff, nb_personne:pers, ingredients, preparation};
  if(!RECETTES[type]) RECETTES[type] = [];

  if(editIndex !== null){
    // Modification
    RECETTES[state.type][editIndex] = recette;
    
    // state.page reste le même
    await updateBdd(recette);
  } else {
    // Ajout
    RECETTES[type].push(recette);
    if(state.type === type) state.page = RECETTES[type].length - 1;
    await addBdd(recette);
  }


  closeAdd();

  if(state.type === type || editIndex !== null){
    loadPage(editIndex !== null ? 0 : 1);
  }
  haptic([20,10,20]);
}

async function addBdd(recette) {
    let r = {
        ...recette,
        ingredients: JSON.stringify(recette.ingredients),
        preparation: JSON.stringify(recette.preparation)
    };

    const response = await fetch("includes/insert.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            recette: r
        })
    });

    const data = await response.json();
    recette.id = data.id;
}
async function updateBdd(recette) {
    let r = {
        ...recette,
        ingredients: JSON.stringify(recette.ingredients),
        preparation: JSON.stringify(recette.preparation)
    };

    const response = await fetch("includes/update.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            recette: r
        })
    });

    const data = await response.json();
}
async function deleteBdd(id) {

    const response = await fetch("includes/delete.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            id: id
        })
    });

    const data = await response.json();
}
</script>
</body>
</html>