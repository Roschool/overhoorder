<?php
require_once __DIR__ . '/php/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pwd = $_POST['wachtwoord'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM docenten WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $doc = $stmt->fetch();
    if ($doc && password_verify($pwd, $doc['wachtwoord'])) {
        $_SESSION['docent_id'] = $doc['id'];
        $_SESSION['docent_naam'] = $doc['naam'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Onjuiste gegevens';
    }
}
?>
<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Login</title><link rel="stylesheet" href="assets/css/styles.css"><link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png"></head><body>
<div class="container card" style="max-width:420px;margin:40px auto">
  <h2>Docent inloggen</h2>
  <?php if(!empty($error)) echo "<p style='color:red;'>".htmlspecialchars($error)."</p>"; ?>
  <form method="post">
    <label>Email</label><input name="email" type="email" required>
    <label>Wachtwoord</label><input name="wachtwoord" type="password" required>
    <div style="margin-top:12px"><button class="btn-primary" type="submit">Inloggen</button></div>
  </form>
  <p class="helper">Wachtwoord vergeten? <a href="wachtwoord_vergeten.php">Herstel wachtwoord</a></p>
  <p class="helper">Nog geen account? <a href="register.php">Registreer</a></p>
</div>
</body></html>
