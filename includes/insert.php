<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    echo json_encode([
        "success" => false,
    ]);
    exit;
}

require "db.php"; 

$data = json_decode(file_get_contents("php://input"), true);
$recette = $data["recette"];

$sql = "INSERT INTO recette 
( `type`, `nom`, `photo`, `ingredients`, `temps_prep`, `temps_cuisson`, `difficulte`, `nb_personne`, `preparation`) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $recette['type'],
    $recette['nom'],
    $recette['photo'] ? $recette['photo'] : '',
    $recette['ingredients'],
    $recette['temps_prep'],
    $recette['temps_cuisson'],
    $recette['difficulte'],
    $recette['nb_personne'],
    $recette['preparation']
]);

$id = $pdo->lastInsertId();

echo json_encode([
    "success" => true,
    "id" => $id
]);