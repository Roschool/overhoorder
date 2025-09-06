<?php
require_once __DIR__ . '/php/db.php';
if (empty($_SESSION['docent_id'])) { header('Location: login.php'); exit; }
$docent_id = $_SESSION['docent_id'];

$klas_id = $_GET['klas'] ?? null;
if (!$klas_id) die('Geen klas gekozen');

// ownership check
$stmt = $pdo->prepare('SELECT * FROM klassen WHERE id = ? AND docent_id = ?');
$stmt->execute([$klas_id, $docent_id]);
if (!$stmt->fetch()) die('Klas hoort niet bij jouw account.');

// stop andere sessies van deze klas
$stmt = $pdo->prepare('UPDATE sessies SET actief = 0 WHERE klas_id = ?');
$stmt->execute([$klas_id]);

// start nieuwe sessie met lege round_seen en null current
$stmt = $pdo->prepare('INSERT INTO sessies (klas_id, docent_id, actief, started_at, round_seen, prev_student_id, current_student_id, current_question_id) VALUES (?, ?, 1, NOW(), JSON_ARRAY(), NULL, NULL, NULL)');
$stmt->execute([$klas_id, $docent_id]);
$sessie_id = $pdo->lastInsertId();

header('Location: vraag_handler.php?sessie=' . $sessie_id);
exit;
