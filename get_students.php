<?php
// Voeg hier je databaseverbinding toe.
require_once __DIR__ . '/php/db.php';

// Start de sessie om toegang te krijgen tot de sessievariabelen
session_start();

// Controleer of de docent is ingelogd
if (empty($_SESSION['docent_id']) || empty($_SESSION['klas_id'])) {
    http_response_code(403);
    echo "Toegang geweigerd.";
    exit();
}

$klas_id = $_SESSION['klas_id'];

try {
    $stmt = $pdo->prepare("SELECT naam, status FROM leerlingen WHERE klas_id = ? ORDER BY naam ASC");
    $stmt->execute([$klas_id]);
    $leerlingen = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $actieve_leerlingen = array_filter($leerlingen, function($l) {
        return $l['status'] === 'actief';
    });
    $non_actieve_leerlingen = array_filter($leerlingen, function($l) {
        return $l['status'] === 'non-actief' || $l['status'] === 'tabblad_afgesloten';
    });

    echo '<h4>Actief</h4>';
    if (!empty($actieve_leerlingen)) {
        echo '<ul>';
        foreach($actieve_leerlingen as $l) {
            echo '<li>' . htmlspecialchars($l['naam']) . ' <span style="color:green; font-weight: bold;">(Actief)</span></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Geen actieve leerlingen.</p>';
    }

    echo '<h4>Non-actief</h4>';
    if (!empty($non_actieve_leerlingen)) {
        echo '<ul>';
        foreach($non_actieve_leerlingen as $l) {
            echo '<li>' . htmlspecialchars($l['naam']) . ' <span style="color:red; font-weight: bold;">(Heeft tabblad op non-actief gezet)</span></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Geen non-actieve leerlingen.</p>';
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "Er is een fout opgetreden bij het ophalen van de leerlingenlijst.";
}