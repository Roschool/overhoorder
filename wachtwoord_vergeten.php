<?php
require_once __DIR__ . '/php/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig e-mailadres.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM docenten WHERE email = ?');
        $stmt->execute([$email]);
        $docent = $stmt->fetch();

        if ($docent) {
            // Genereer een unieke token en stel de vervaldatum in (bijv. 1 uur)
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Sla de token op in de database
            $stmt = $pdo->prepare('UPDATE docenten SET reset_token = ?, reset_token_expiry = ? WHERE id = ?');
            $stmt->execute([$token, $expiry, $docent['id']]);

            // Creëer de herstellink
            $reset_link = "https://overhoren.ivenboxem.nl/wachtwoord_herstellen.php?token=$token";

            // Verstuur de e-mail met HTML-opmaak
            $subject = "Wachtwoord herstellen voor Overhoorder";
            $headers = "From: Overhoorder <no-reply@ivenboxem.nl>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $body = '
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; }
    .email-container { max-width: 600px; margin: 20px auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-align: center;}
    .button { display: inline-block; padding: 10px 20px; font-size: 16px; background-color: #007BFF; text-decoration: none; border-radius: 5px; }
  </style>
</head>
<body>
  <div class="email-container">
    <img src="https://overhoren.ivenboxem.nl/assets/img/logo.png" alt="Overhoorder Logo" style="max-width: 150px; height: auto; display: block; margin: 0 auto 20px;">
    <h2>Wachtwoord Herstellen</h2>
    <p>Hallo,</p>
    <p>Je hebt een verzoek ingediend om je wachtwoord te herstellen.</p>
    <p>Klik op de onderstaande knop om een nieuw wachtwoord in te stellen:</p>
    <p style="text-align: center;"><a href="' . htmlspecialchars($reset_link) . '" class="button" style="color: #fff !important;">Wachtwoord Herstellen</a></p>
    <p>Of kopieer en plak de volgende link in je browser:</p>
    <p><a href="' . htmlspecialchars($reset_link) . '">' . htmlspecialchars($reset_link) . '</a></p>
    <p>Deze link vervalt over 1 uur.</p>
    <p>Met vriendelijke groet,<br>Iven Boxem</p>
  </div>
</body>
</html>';

            if (mail($email, $subject, $body, $headers)) {
                $message = 'Een herstellink is naar je e-mailadres verstuurd.';
            } else {
                $error = 'Fout bij het versturen van de e-mail. Neem contact op met de beheerder.';
            }
        } else {
            $error = 'Geen gebruiker gevonden met dit e-mailadres.';
        }
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Wachtwoord Vergeten</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png">
</head>
<body>
<div class="container" style="max-width:480px; margin: 40px auto;">
    <a class="btn-text" href="login.php"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2B225C"><path d="m368-417 202 202-90 89-354-354 354-354 90 89-202 202h466v126H368Z"/></svg></a>
    <div class="card">
        <h2>Wachtwoord Vergeten</h2>
        <?php if (!empty($error)) echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>'; ?>
        <?php if (!empty($message)) echo '<p style="color:green;">' . htmlspecialchars($message) . '</p>'; ?>
        <form method="post">
            <label>E-mailadres</label>
            <input type="email" name="email" required>
            <div style="margin-top:12px">
                <button class="btn-primary" type="submit">Verstuur herstellink</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>