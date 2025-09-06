<?php
require_once __DIR__ . '/php/db.php';
if (empty($_SESSION['docent_id'])) { header('Location: login.php'); exit; }
$docent_id = $_SESSION['docent_id'];
$docent_naam = $_SESSION['docent_naam'] ?? 'Docent';

/* voeg klas toe */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['klas_naam'])) {
    $naam = trim($_POST['klas_naam']);
    $vak = trim($_POST['vak'] ?? '');
    if($naam){
        // genereer klascode
        $code = strtoupper(substr(bin2hex(random_bytes(4)),0,6));
        $stmt = $pdo->prepare('INSERT INTO klassen (docent_id, naam, klascode, vak) VALUES (?, ?, ?, ?)');
        $stmt->execute([$docent_id, $naam, $code, $vak]);
    }
    header('Location: dashboard.php');
    exit;
}

/* haal klassen op */
$stmt = $pdo->prepare('SELECT * FROM klassen WHERE docent_id = ? ORDER BY id DESC');
$stmt->execute([$docent_id]);
$klassen = $stmt->fetchAll();
?>
<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Dashboard</title><link rel="stylesheet" href="assets/css/styles.css"><link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png"></head><body>
<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <div class="chip">Overhoorder • Docent</div>
    <div>
      <span class="helper">Ingelogd als <strong><?=htmlspecialchars($docent_naam)?></strong></span>
      <span class="logout"><a class="btn-text" href="logout.php"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M212-86q-53 0-89.5-36.5T86-212v-536q0-53 36.5-89.5T212-874h276v126H212v536h276v126H212Zm415-146-88-89 96-96H352v-126h283l-96-96 88-89 247 248-247 248Z"/></svg></a></span>
    </div>
  </div>

  <h1 class="app-title">Dashboard</h1>

  <div class="card">
    <h3>Nieuwe klas</h3>
    <form method="post">
      <label>Klasnaam</label><input name="klas_naam" required>
      <label>Vak (optioneel)</label><input name="vak">
      <div style="margin-top:12px"><button class="btn-primary" type="submit">Klas aanmaken</button></div>
    </form>
  </div>

  <div style="height:16px"></div>

  <div class="card">
    <h3>Jouw klassen</h3>
    <?php if(empty($klassen)) echo '<p class="helper">Nog geen klassen.</p>'; ?>
    <ul>
      <?php foreach($klassen as $k): ?>
        <li style="margin:10px 0">
          <strong><?=htmlspecialchars($k['naam'])?></strong>
          <div class="helper">Klascode: <?=htmlspecialchars($k['klascode'])?> - Vak: <?=htmlspecialchars($k['vak'])?></div>
          <div style="margin-top:8px">
            <a class="btn-secondary" href="manage_klas.php?klas=<?= $k['id'] ?>">Beheren</a>
            <a class="btn-primary" href="start_sessie.php?klas=<?= $k['id'] ?>">Start overhoring</a>
            <a class="btn-danger" href="delete_klas.php?klas=<?= $k['id'] ?>" onclick="return confirm('Weet je zeker dat je deze klas wilt verwijderen? Alle leerlingen, vragen en resultaten zullen ook worden verwijderd.');">Verwijderen</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

</div>
</body></html>
