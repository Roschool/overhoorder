<?php
require_once __DIR__ . '/php/db.php';
session_start();

if (empty($_SESSION['docent_id'])) {
    header('Location: login.php');
    exit;
}
$docent_id = $_SESSION['docent_id'];
$sessie_id = $_GET['sessie'] ?? null;
if (!$sessie_id) die('Geen sessie geselecteerd.');

// load session and klas
$stmt = $pdo->prepare('SELECT s.*, k.naam as klasnaam FROM sessies s JOIN klassen k ON k.id = s.klas_id WHERE s.id = ? AND s.docent_id = ?');
$stmt->execute([$sessie_id, $docent_id]);
$sess = $stmt->fetch();
if (!$sess) die('Sessie niet gevonden of geen rechten.');
$_SESSION['klas_id'] = $sess['klas_id'];

// add question (for class)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vraag'])) {
    $vraag = trim($_POST['vraag']); $antwoord = trim($_POST['antwoord']);
    if($vraag && $antwoord){
        $stmt = $pdo->prepare('INSERT INTO vragen (klas_id, vraag, antwoord) VALUES (?, ?, ?)');
        $stmt->execute([$sess['klas_id'], $vraag, $antwoord]);
    }
    header('Location: vraag_handler.php?sessie='.$sessie_id); exit;
}

// HANDMATIG NAKIJKEN: UPDATE NAAR STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_answer'])) {
    $resultaat_id = $_POST['resultaat_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare('UPDATE resultaten SET status = ? WHERE id = ?');
    $stmt->execute([$status, $resultaat_id]);
    header('Location: vraag_handler.php?sessie='.$sessie_id); exit;
}

// NEXT TURN action
if(isset($_GET['action']) && $_GET['action'] === 'next') {
    $stmt = $pdo->prepare('SELECT id, naam FROM leerlingen WHERE klas_id = ?');
    $stmt->execute([$sess['klas_id']]);
    $leerlingen = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, vraag FROM vragen WHERE klas_id = ?');
    $stmt->execute([$sess['klas_id']]);
    $vragen = $stmt->fetchAll();

    if(empty($leerlingen) || empty($vragen)) { $msg = 'Voeg leerlingen en vragen toe.'; }
    else {
        $roundSeen = json_decode($sess['round_seen'] ?? '[]', true);
        if(!is_array($roundSeen)) $roundSeen = [];
        $cands = array_filter($leerlingen, function($l) use ($roundSeen){ return !in_array($l['id'], $roundSeen); });
        if(empty($cands)){ $roundSeen = []; $cands = $leerlingen; }
        $prev = $sess['prev_student_id'];
        if(count($cands) > 1 && $prev){
            $cands = array_filter($cands, function($l) use ($prev){ return $l['id'] != $prev; });
            if(empty($cands)) $cands = $leerlingen;
        }
        $cands = array_values($cands);
        $picked = $cands[array_rand($cands)];
        $q = $vragen[array_rand($vragen)];
        
        $current_student_id = $sess['current_student_id'];
        if ($current_student_id === NULL) {
          $current_student_id = $picked['id'];
        }
        
        $roundSeen[] = $picked['id'];
        $stmt = $pdo->prepare('UPDATE sessies SET prev_student_id = ?, current_student_id = ?, current_question_id = ?, round_seen = ? WHERE id = ?');
        $stmt->execute([$current_student_id,$picked['id'],$q['id'], json_encode(array_values(array_unique($roundSeen))), $sessie_id]);
        header('Location: vraag_handler.php?sessie='.$sessie_id);
        exit;
    }
}

$stmt = $pdo->prepare('SELECT s.*, k.naam as klasnaam FROM sessies s JOIN klassen k ON k.id = s.klas_id WHERE s.id = ?');
$stmt->execute([$sessie_id]);
$sess = $stmt->fetch();

$stmt = $pdo->prepare('SELECT r.*, l.naam as leerlingNaam, v.vraag, v.antwoord as correct_antwoord, v.id as vraag_id FROM resultaten r JOIN leerlingen l ON l.id=r.leerling_id JOIN vragen v ON v.id=r.vraag_id WHERE r.sessie_id = ? ORDER BY r.created_at DESC LIMIT 50');
$stmt->execute([$sessie_id]);
$records = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM vragen WHERE klas_id = ?');
$stmt->execute([$sess['klas_id']]); $vragen = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT * FROM leerlingen WHERE klas_id = ?');
$stmt->execute([$sess['klas_id']]); $leerlingen = $stmt->fetchAll();
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Vragen</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png">
    <style>
      .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
      }
      .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 400px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        text-align: center;
      }
      .modal-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 20px;
      }
      .modal-buttons button {
        padding: 12px;
        font-size: 16px;
        border-radius: 20px;
        border: none;
        cursor: pointer;
      }
      .modal-buttons .btn-good {
        background-color: #4CAF50;
        color: white;
      }
      .modal-buttons .btn-typo {
        background-color: #2196F3;
        color: white;
      }
      .modal-buttons .btn-wrong {
        background-color: #F44336;
        color: white;
      }
    </style>
</head>
<body>
<div class="container">
    <a class="btn-text" href="dashboard.php"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2B225C"><path d="m368-417 202 202-90 89-354-354 354-354 90 89-202 202h466v126H368Z"/></svg></a>
    <h1 class="app-title">Overhoring - <?=htmlspecialchars($sess['klasnaam'])?></h1>

    <div class="card">
        <h3>Huidige beurt</h3>
        <?php
          $last_answer = false;
          if ($sess['current_student_id'] === null && !empty($records)) {
            $last_answer = $records[0];
          }
          if ($sess['current_student_id']) {
            $stmt = $pdo->prepare('SELECT naam FROM leerlingen WHERE id=?'); $stmt->execute([$sess['current_student_id']]); $ln = $stmt->fetchColumn();
            $stmt = $pdo->prepare('SELECT vraag FROM vragen WHERE id=?'); $stmt->execute([$sess['current_question_id']]); $qtxt = $stmt->fetchColumn();
            echo "<div class='badge'>Aan de beurt: <strong>".htmlspecialchars($ln)."</strong></div>";
            echo "<p style='margin-top:10px'><strong>Vraag:</strong> ".htmlspecialchars($qtxt)."</p>";
          } else if ($last_answer) {
            echo "<h3>Laatst ingevoerd antwoord</h3>";
            echo "<p><strong>Leerling:</strong> " . htmlspecialchars($last_answer['leerlingNaam']) . "</p>";
            echo "<p><strong>Vraag:</strong> " . htmlspecialchars($last_answer['vraag']) . "</p>";
            echo "<p><strong>Antwoord:</strong> " . htmlspecialchars($last_answer['antwoord_given']) . "</p>";
          } else {
            echo '<p class="helper">Geen actieve beurt. Klik "Volgende" om een leerling te kiezen.</p>';
          }
        ?>
        <div style="margin-top:12px">
          <a class="btn-primary" href="vraag_handler.php?sessie=<?= $sessie_id ?>&action=next">Volgende</a>
          <a class="btn-danger" href="stop_sessie.php?sessie=<?= $sessie_id ?>">Stop sessie</a>
        </div>
    </div>
    
    <div style="height:16px"></div>
    
    <div class="card">
        <h3>Vragen toevoegen</h3>
        <form method="post">
            <label>Vraag</label><input name="vraag" required>
            <label>Antwoord</label><input name="antwoord" required>
            <div style="margin-top:12px"><button class="btn-secondary" type="submit">Opslaan</button></div>
        </form>
    </div>
    
    <div style="height:16px"></div>
    
    <div class="card">
        <h3>Resultaten downloaden</h3>
        <p class="helper">Download de resultaten van deze sessie als CSV-bestand voor analyse.</p>
        <a class="btn-primary" href="export_results.php?sessie=<?= $sessie_id ?>">Download CSV</a>
    </div>
    
    <div style="height:16px"></div>

    <div class="card">
        <h3>Recente antwoorden</h3>
        <table class="table">
            <thead><tr><th>Leerling</th><th>Vraag</th><th>Antwoord</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach($records as $r): ?>
                    <tr>
                        <td><?=htmlspecialchars($r['leerlingNaam'])?></td>
                        <td><?=htmlspecialchars($r['vraag'])?></td>
                        <td><?=htmlspecialchars($r['antwoord_given'])?></td>
                        <td>
                            <?php
                            switch ($r['status']) {
                                case 'goed':
                                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#53b806"><path d="M382-208 122-468l90-90 170 170 366-366 90 90-456 456Z"/></svg>';
                                    break;
                                case 'typfout':
                                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#06a6b8"><path d="M564-48 379-233l83-84 102 102 214-214 83 84L564-48ZM100-320l199-520h127l199 520H499l-44-127H257l-44 127H100Zm187-214h140l-68-195h-4l-68 195Z"/></svg>';
                                    break;
                                case 'fout':
                                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#b82906"><path d="m256-168-88-88 224-224-224-224 88-88 224 224 224-224 88 88-224 224 224 224-88 88-224-224-224 224Z"/></svg>';
                                    break;
                                default:
                                    // Let op: htmlentities() of htmlspecialchars() is cruciaal om JavaScript-injectie te voorkomen
                                    $student_answer_js = htmlspecialchars($r['antwoord_given'], ENT_QUOTES, 'UTF-8');
                                    $correct_answer_js = htmlspecialchars($r['correct_antwoord'], ENT_QUOTES, 'UTF-8');
                                    echo '<p onclick="openModal(' . $r['id'] . ', \'' . $student_answer_js . '\', \'' . $correct_answer_js . '\')" style="border:none; background:none; font-size: 20px;"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fcba03"><path d="M417-385v-406h126v406H417Zm0 216v-126h126v126H417Z"/></svg></p>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div style="height:16px"></div>
    
    <div class="card">
        <h3>Leerlingen in de sessie</h3>
        <div id="students-list-container">
            </div>
    </div>

</div>

<div id="gradeModal" class="modal">
  <div class="modal-content">
    <span onclick="closeModal()" style="float:right; cursor:pointer; font-size: 24px;"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="m347-280 133-133 133 133 67-67-133-133 133-133-67-67-133 133-133-133-67 67 133 133-133 133 67 67ZM480-46q-91 0-169.99-34.08-78.98-34.09-137.41-92.52-58.43-58.43-92.52-137.41Q46-389 46-480q0-91 34.08-169.99 34.09-78.98 92.52-137.41 58.43-58.43 137.41-92.52Q389-914 480-914q91 0 169.99 34.08 78.98 34.09 137.41 92.52 58.43 58.43 92.52 137.41Q914-571 914-480q0 91-34.08 169.99-34.09 78.98-92.52 137.41-58.43 58.43-137.41 92.52Q571-46 480-46Zm0-126q130 0 219-89t89-219q0-130-89-219t-219-89q-130 0-219 89t-89 219q0 130 89 219t219 89Zm0-308Z"/></svg></span>
    <h4>Beoordeel antwoord</h4>
    <div style="text-align: left; margin-top: 20px;">
        <p><strong>Gegeven antwoord:</strong> <span id="modalStudentAnswer"></span></p>
        <p><strong>Correct antwoord:</strong> <span id="modalCorrectAnswer"></span></p>
    </div>
    <p style="margin-top: 20px;">Selecteer de status voor dit antwoord:</p>
    <div class="modal-buttons">
      <form method="post">
        <input type="hidden" name="resultaat_id" id="modalResultId">
        <input type="hidden" name="status" value="goed">
        <button class="btn-good" type="submit" name="grade_answer">Goed</button>
      </form>
      <form method="post">
        <input type="hidden" name="resultaat_id" id="modalResultId2">
        <input type="hidden" name="status" value="typfout">
        <button class="btn-typo" type="submit" name="grade_answer">Typfout</button>
      </form>
      <form method="post">
        <input type="hidden" name="resultaat_id" id="modalResultId3">
        <input type="hidden" name="status" value="fout">
        <button class="btn-wrong" type="submit" name="grade_answer">Fout</button>
      </form>
    </div>
  </div>
</div>

<script>
    function refreshStudentList() {
        const container = document.getElementById('students-list-container');
        fetch('get_students.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Netwerkrespons was niet ok.');
                }
                return response.text();
            })
            .then(html => {
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Er is een fout opgetreden bij het verversen:', error);
                container.innerHTML = '<p style="color:red;">Fout bij het laden van de lijst.</p>';
            });
    }

    document.addEventListener('DOMContentLoaded', refreshStudentList);
    setInterval(refreshStudentList, 1000);

    // Modal JavaScript
    function openModal(resultId, studentAnswer, correctAnswer) {
        document.getElementById('gradeModal').style.display = 'block';
        document.getElementById('modalResultId').value = resultId;
        document.getElementById('modalResultId2').value = resultId;
        document.getElementById('modalResultId3').value = resultId;
        document.getElementById('modalStudentAnswer').innerText = studentAnswer;
        document.getElementById('modalCorrectAnswer').innerText = correctAnswer;
    }

    function closeModal() {
        document.getElementById('gradeModal').style.display = 'none';
    }

    // Close the modal if the user clicks outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('gradeModal');
      if (event.target == modal) {
        closeModal();
      }
    }
</script>

</body>
</html>