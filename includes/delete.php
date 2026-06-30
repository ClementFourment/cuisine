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
$id = $data["id"];

$sql = "DELETE FROM `recette` WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $id,
]);

echo json_encode([
    "success" => true,
    "id" => $id
]);