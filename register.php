<?php
require_once __DIR__ . '/php/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pwd = $_POST['wachtwoord'] ?? '';
    if(!$naam || !$email || !$pwd){
        $error = 'Vul alle velden in.';
    } else {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO docenten (naam,email,wachtwoord) VALUES (?, ?, ?)');
        try {
            $stmt->execute([$naam,$email,$hash]);
            echo "<p>Account aangemaakt. <a href='login.php'>Inloggen</a></p>";
            exit;
        } catch (Exception $e) {
            $error = 'Fout: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Registreer</title><link rel="stylesheet" href="assets/css/styles.css"><link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png"></head><body>
<div class="container card" style="max-width:480px;margin:40px auto">
  <h2>Docent registreren</h2>
  <?php if(!empty($error)) echo "<p style='color:red;'>".htmlspecialchars($error)."</p>"; ?>
  <form method="post">
    <label>Naam</label><input name="naam" required>
    <label>Email</label><input name="email" type="email" required>
    <label>Wachtwoord</label><input name="wachtwoord" type="password" required>
    <div style="margin-top:12px"><button class="btn-primary" type="submit">Registreer</button></div>
  </form>
  <p class="helper">Al account? <a href="login.php">Log in</a></p>
</div>
</body></html>
