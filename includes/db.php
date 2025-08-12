<?php
$host = "localhost";
$db = "appsxtop_gestorx";
$user = "appsxtop_gestorx";
$pass = "gestorx12012510";

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Erro na conexão: " . $e->getMessage());
}
?>
