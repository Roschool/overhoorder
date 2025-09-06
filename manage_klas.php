<?php
require_once __DIR__ . '/php/db.php';
if (empty($_SESSION['docent_id'])) { header('Location: login.php'); exit; }
$docent_id = $_SESSION['docent_id'];
$klas_id = $_GET['klas'] ?? null;
if (!$klas_id) die('Geen klas gekozen');

// check ownership
$stmt = $pdo->prepare('SELECT * FROM klassen WHERE id = ? AND docent_id = ?');
$stmt->execute([$klas_id,$docent_id]);
$klas = $stmt->fetch();
if(!$klas) die('Klas niet gevonden of geen rechten');

// verwijder alle leerlingen
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_students'])) {
    $stmt = $pdo->prepare('DELETE FROM leerlingen WHERE klas_id = ?');
    $stmt->execute([$klas_id]);
    header('Location: manage_klas.php?klas='.$klas_id);
    exit;
}

// voeg leerling toe
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_student'])) {
    $naam = trim($_POST['new_student']);
    if($naam){
        $stmt = $pdo->prepare('INSERT INTO leerlingen (klas_id, naam) VALUES (?, ?)');
        $stmt->execute([$klas_id, $naam]);
    }
    header('Location: manage_klas.php?klas='.$klas_id);
    exit;
}

// voeg vraag toe
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vraag_text'])) {
    $vraag = trim($_POST['vraag_text']);
    $antwoord = trim($_POST['vraag_answer'] ?? '');
    if($vraag && $antwoord){
        $stmt = $pdo->prepare('INSERT INTO vragen (klas_id, vraag, antwoord) VALUES (?, ?, ?)');
        $stmt->execute([$klas_id, $vraag, $antwoord]);
    }
    header('Location: manage_klas.php?klas='.$klas_id);
    exit;
}

// fetch lists
$stmt = $pdo->prepare('SELECT * FROM leerlingen WHERE klas_id=? ORDER BY id');
$stmt->execute([$klas_id]); $leerlingen = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT * FROM vragen WHERE klas_id=? ORDER BY id');
$stmt->execute([$klas_id]); $vragen = $stmt->fetchAll();
?>

<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<title>Beheer klas</title>
<link rel="stylesheet" href="assets/css/styles.css">
<link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png">
</head>
<body>
<div class="container">
  <a href="dashboard.php" class="btn-text"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2B225C"><path d="m368-417 202 202-90 89-354-354 354-354 90 89-202 202h466v126H368Z"/></svg></a>
  <h1 class="app-title">Beheer: <?=htmlspecialchars($klas['naam'])?></h1>

  <div class="card">
    <h3>Leerlingen</h3>
    <ul>
      <?php foreach($leerlingen as $l): ?>
        <li><?=htmlspecialchars($l['naam'])?></li>
      <?php endforeach; ?>
    </ul>

    <form method="post" onsubmit="return confirm('Weet je zeker dat je alle leerlingen wilt verwijderen?');">
      <button class="btn-danger" type="submit" name="delete_all_students">Verwijder alle leerlingen</button>
    </form>
  </div>

  <div style="height:16px"></div>

  <div class="card">
    <h3>Vraag toevoegen</h3>
    <form method="post">
      <label>Vraag</label><input name="vraag_text" required>
      <label>Antwoord</label><input name="vraag_answer" required>
      <div style="margin-top:12px"><button class="btn-secondary" type="submit">Opslaan</button></div>
    </form>

    <div style="margin-top:10px">
      <strong>Vragen</strong>
      <ul>
        <?php foreach($vragen as $q): ?>
          <li>
              <?=htmlspecialchars($q['vraag'])?> — <em><?=htmlspecialchars($q['antwoord'])?></em>
              <form method="post" action="delete_vraag.php" style="display:inline;">
                <input type="hidden" name="vraag_id" value="<?= $q['id'] ?>">
                <button type="submit" class="btn-text" onclick="return confirm('Weet je zeker dat je deze vraag wilt verwijderen?');">Verwijder</button>
              </form>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

</div>
</body>
</html>
