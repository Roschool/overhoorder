<?php
session_start();
?>

<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<title>Overhoorder</title>
<link rel="stylesheet" href="assets/css/styles.css">
<link rel="icon" type="image/x-icon" href="http://overhoren.ivenboxem.nl/assets/img/logo2.png">
<style>
body { font-family:'Roboto',sans-serif; background:#E3F2FD; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.container { text-align:center; }
button { padding:1rem 2rem; font-size:1.2rem; margin:12px; border:none; border-radius:12px; cursor:pointer; font-weight:500; }
.btn-docent { background:#6200EE; color:white; }
.btn-docent:hover { background:#3700B3; }
.btn-leerling { background:#03DAC6; color:black; }
.btn-leerling:hover { background:#018786; }
</style>
</head>
<body>

<div class="container">
    <h1>Welkom bij Overhoorder</h1>
    <p>Kies je rol:</p>
    <div>
        <button class="btn-docent" onclick="window.location.href='login.php'">Ik ben docent</button>
        <button class="btn-leerling" onclick="window.location.href='leerling.php'">Ik ben leerling</button>
    </div>
</div>

</body>
</html>
