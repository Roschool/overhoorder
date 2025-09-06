<?php
require_once __DIR__ . '/php/db.php';
$leerling_id = $_POST['leerling_id'] ?? null;
$sessie_id = $_POST['sessie_id'] ?? null;

if($leerling_id && $sessie_id){
    // Update the student's status (e.g., 'active' or 'closed_tab')
    $stmt = $pdo->prepare('UPDATE leerlingen SET status = "tabblad_afgesloten" WHERE id = ?');
    $stmt->execute([$leerling_id]);
}
?>