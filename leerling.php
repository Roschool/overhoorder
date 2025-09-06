<?php
require_once __DIR__ . '/php/db.php';

session_start();

// join flow
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_klascode'])) {
    $code = strtoupper(trim($_POST['join_klascode']));
    $naam = trim($_POST['naam']);
    if(!$code || !$naam) { $error='Vul klascode en naam in'; }
    else {
        // find klas
        $stmt = $pdo->prepare('SELECT * FROM klassen WHERE klascode = ?');
        $stmt->execute([$code]);
        $k = $stmt->fetch();
        if(!$k) { $error='Klascode onbekend'; }
        else {
            $stmt = $pdo->prepare('INSERT INTO leerlingen (klas_id, naam) VALUES (?, ?)');
            $stmt->execute([$k['id'], $naam]);
            $leerling_id = $pdo->lastInsertId();
            $_SESSION['leerling_id'] = $leerling_id;
            $_SESSION['klas_id'] = $k['id'];
            $_SESSION['klas_code'] = $code;
            header('Location: leerling.php');
            exit;
        }
    }
}

// submit answer from student
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $sessie_id = $_POST['sessie_id'];
    $vraag_id = $_POST['vraag_id'];
    $antwoord = trim($_POST['antwoord']);
    $leerling_id = $_SESSION['leerling_id'] ?? null;
    if($leerling_id && $sessie_id && $vraag_id){
        // ensure this student is current_student
        $stmt = $pdo->prepare('SELECT current_student_id FROM sessies WHERE id = ?');
        $stmt->execute([$sessie_id]);
        $curr = $stmt->fetchColumn();
        if($curr != $leerling_id) { $msg='Niet jouw beurt.'; }
        else {
            // insert resultaat with 'onbekend' status for manual grading
            $stmt = $pdo->prepare('INSERT INTO resultaten (sessie_id, leerling_id, vraag_id, antwoord_given, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$sessie_id, $leerling_id, $vraag_id, $antwoord, 'onbekend']);
            
            // clear current student/question
            $stmt = $pdo->prepare('UPDATE sessies SET current_student_id = NULL, current_question_id = NULL WHERE id = ?');
            $stmt->execute([$sessie_id]);
            $msg = 'Antwoord verstuurd. Wacht op beoordeling van de docent.';
        }
    }
}

// simple page showing status and polling via AJAX
$leerling_id = $_SESSION['leerling_id'] ?? null;
$klas_id = $_SESSION['klas_id'] ?? null;

?>
<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Leerling</title><link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png"><link rel="stylesheet" href="assets/css/styles.css"><script>
document.addEventListener('DOMContentLoaded', function() {
    const element = document.documentElement; // or document.body
    if (element.requestFullscreen) {
        element.requestFullscreen();
    } else if (element.webkitRequestFullscreen) { // Safari
        element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) { // IE11
        element.msRequestFullscreen();
    }
});
</script><script>
window.addEventListener('pagehide', function() {
    // Check if the student is logged in and in a session
    const leerlingId = '<?= htmlspecialchars($leerling_id) ?>';
    const sessieId = '<?= htmlspecialchars($s['id'] ?? '') ?>';

    if (leerlingId && sessieId) {
        // Use sendBeacon to send a POST request to notify the server
        const data = new FormData();
        data.append('leerling_id', leerlingId);
        data.append('sessie_id', sessieId);
        navigator.sendBeacon('leerling_afgesloten.php', data);
    }
});
</script>
<script>
    // Functie om de status bij te werken via een AJAX-verzoek
    function updateStatus(status) {
        const leerlingId = '<?= htmlspecialchars($_SESSION['leerling_id']) ?>';
        if (leerlingId) {
            const data = new FormData();
            data.append('id', leerlingId);
            data.append('status', status);

            // Gebruik fetch om de status bij te werken
            fetch('status_update.php', {
                method: 'POST',
                body: data
            }).catch(error => console.error('Fout bij het bijwerken van de status:', error));
        }
    }

    // Zet de status op 'actief' zodra de pagina volledig is geladen
    document.addEventListener('DOMContentLoaded', () => {
        updateStatus('actief');
    });

    // Luister naar het 'blur' evenement (wanneer het tabblad inactief wordt)
    window.addEventListener('blur', () => {
        updateStatus('non-actief');
    });

    // Luister naar het 'focus' evenement (wanneer het tabblad weer actief wordt)
    window.addEventListener('focus', () => {
        updateStatus('actief');
    });

    // Optioneel: Stuur een 'non-actief' status bij het sluiten van de pagina
    window.addEventListener('beforeunload', () => {
        navigator.sendBeacon('status_update.php', 'status=non-actief&id=<?= htmlspecialchars($_SESSION['leerling_id']) ?>');
    });
</script>
</head><body>
<div class="container">
    <?php if(!$leerling_id): ?>
        <div class="card" style="max-width:480px;margin:40px auto">
            <h2>Leerling: doe mee</h2>
            <?php if(!empty($error)) echo "<p style='color:red;'>".htmlspecialchars($error)."</p>"; ?>
            <form method="post">
                <label>Klascode</label><input name="join_klascode" required>
                <label>Naam</label><input name="naam" required>
                <div style="margin-top:12px"><button class="btn-primary" type="submit">Deelnemen</button></div>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Wachten op je beurt</h2>
            <?php
                // find active session for klas
                $stmt = $pdo->prepare('SELECT * FROM sessies WHERE klas_id = ? AND actief = 1 LIMIT 1');
                $stmt->execute([$klas_id]);
                $s = $stmt->fetch();
                if(!$s){ echo '<p class="helper">Geen actieve sessie.</p>'; session_destroy();}
                else {
                    echo '<p class="helper">Sessie actief sinds: '.htmlspecialchars($s['started_at']).'</p>';
                    if($s['current_student_id'] == $leerling_id && $s['current_question_id']){
                        $stmt = $pdo->prepare('SELECT vraag FROM vragen WHERE id = ?'); $stmt->execute([$s['current_question_id']]); $q = $stmt->fetchColumn();
                        echo '<h3>Jij bent aan de beurt!</h3>';
                        echo '<p style="font-weight:700">'.htmlspecialchars($q).'</p>';
                        ?>
                        <form method="post">
                            <input type="hidden" name="sessie_id" value="<?=htmlspecialchars($s['id'])?>">
                            <input type="hidden" name="vraag_id" value="<?=htmlspecialchars($s['current_question_id'])?>">
                            <label>Jouw antwoord</label><input name="antwoord" required>
                            <div style="margin-top:12px"><button class="btn-primary" type="submit" name="submit_answer">Verstuur</button></div>
                        </form>
                        <?php
                    } else {
                        // show who is current
                        if($s['current_student_id']){
                            $stmt = $pdo->prepare('SELECT naam FROM leerlingen WHERE id = ?'); $stmt->execute([$s['current_student_id']]); $ln = $stmt->fetchColumn();
                            echo '<p class="helper">Aan de beurt: <strong>'.htmlspecialchars($ln).'</strong></p>';
                        } else {
                            echo '<p class="helper">Even wachten — docent kiest de volgende leerling.</p>';
                        }
                    }
                }
                if(!empty($msg)) echo '<p class="badge">'.htmlspecialchars($msg).'</p>';
            ?>
            <p class="helper" style="margin-top:12px">Pagina <a href="">vernieuwen</a></p>
        </div>
    <?php endif; ?>
</div>
</body></html>