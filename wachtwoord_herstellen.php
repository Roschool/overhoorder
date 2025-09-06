<?php
require_once __DIR__ . '/php/db.php';

$error = '';
$message = '';
$show_form = false;
$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $pdo->prepare('SELECT id, reset_token_expiry FROM docenten WHERE reset_token = ?');
    $stmt->execute([$token]);
    $docent = $stmt->fetch();

    if ($docent) {
        $expiry_datetime = new DateTime($docent['reset_token_expiry']);
        $current_datetime = new DateTime();

        if ($current_datetime <= $expiry_datetime) {
            $show_form = true;
        } else {
            $error = 'Deze herstellink is verlopen.';
        }
    } else {
        $error = 'Ongeldige herstellink.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token_post = $_POST['token'];

    if ($new_password !== $confirm_password) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else if (strlen($new_password) < 6) {
        $error = 'Het wachtwoord moet minstens 6 tekens lang zijn.';
    } else {
        $stmt = $pdo->prepare('SELECT id, reset_token_expiry FROM docenten WHERE reset_token = ?');
        $stmt->execute([$token_post]);
        $docent = $stmt->fetch();

        if ($docent) {
            $expiry_datetime = new DateTime($docent['reset_token_expiry']);
            $current_datetime = new DateTime();

            if ($current_datetime <= $expiry_datetime) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE docenten SET wachtwoord = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?');
                $stmt->execute([$hashed_password, $docent['id']]);
                $message = 'Je wachtwoord is succesvol gewijzigd. Je kunt nu inloggen.';
                $show_form = false;
            } else {
                $error = 'Deze herstellink is verlopen. Vraag een nieuwe aan.';
            }
        } else {
            $error = 'Ongeldige herstellink.';
        }
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Wachtwoord Herstellen</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png">
</head>
<body>
<div class="container" style="max-width:480px; margin: 40px auto;">
    <div class="card">
        <h2>Wachtwoord Herstellen</h2>
        <?php if (!empty($error)) echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>'; ?>
        <?php if (!empty($message)) echo '<p style="color:green;">' . htmlspecialchars($message) . '</p>'; ?>

        <?php if ($show_form): ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <label>Nieuw wachtwoord</label>
                <input type="password" name="new_password" required>
                <label>Bevestig nieuw wachtwoord</label>
                <input type="password" name="confirm_password" required>
                <div style="margin-top:12px">
                    <button class="btn-primary" type="submit">Wachtwoord wijzigen</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>