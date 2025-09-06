<?php
require_once __DIR__ . '/php/db.php';
if (empty($_SESSION['docent_id'])) { header('Location: login.php'); exit; }
$sessie_id = $_GET['sessie'] ?? null;
if (!$sessie_id) die('Geen sessie');

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT klas_id FROM sessies WHERE id = ?');
    $stmt->execute([$sessie_id]);
    $klas_id = $stmt->fetchColumn();
    if (!$klas_id) die('Sessie niet gevonden.');

    $stmt = $pdo->prepare('UPDATE sessies SET actief = 0 WHERE id = ?');
    $stmt->execute([$sessie_id]);

    $stmt = $pdo->prepare('DELETE FROM leerlingen WHERE klas_id = ?');
    $stmt->execute([$klas_id]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die('Fout bij het beëindigen van de sessie en het verwijderen van leerlingen: ' . $e->getMessage());
}
header('Location: dashboard.php');
exit;