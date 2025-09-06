<?php
require_once __DIR__ . '/php/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Haal de gegevens op die via de POST-methode zijn verzonden
    $leerlingId = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($leerlingId && $status) {
        try {
            // Stap 2: Bereid de SQL-query voor om de status bij te werken
            $stmt = $pdo->prepare("UPDATE leerlingen SET status = ? WHERE id = ?");

            // Stap 3: Voer de query uit met de ontvangen waarden
            $stmt->execute([$status, $leerlingId]);

            // Optioneel: Stuur een succesreactie terug
            http_response_code(200);
            echo "Status succesvol bijgewerkt.";

        } catch (PDOException $e) {
            // Optioneel: Stuur een foutreactie terug in geval van een databasefout
            http_response_code(500);
            echo "Fout bij het bijwerken van de status: " . $e->getMessage();
        }
    } else {
        // Optioneel: Stuur een foutreactie als de vereiste gegevens ontbreken
        http_response_code(400);
        echo "Ontbrekende gegevens voor de statusupdate.";
    }
} else {
    // Optioneel: Geef een melding als de HTTP-methode niet POST is
    http_response_code(405);
    echo "Methode niet toegestaan.";
}
?>