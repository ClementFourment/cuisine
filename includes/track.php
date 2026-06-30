<?php
require "db.php";

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
if (strpos($ip, ',') !== false) {
    $ip = explode(',', $ip)[0];
}

$stmt = $pdo->prepare("INSERT INTO track_ip (ip, date_visite) VALUES (?, NOW())");
$stmt->execute([$ip]);
?>