<?php
require_once __DIR__ . '/php/db.php';

// accept POST: sessie_id, vraag_id, leerling_id OR if from teacher current student use sessie.current_student_id
$sessie_id = $_POST['sessie_id'] ?? null;
$vraag_id = $_POST['vraag_id'] ?? null;
$antwoord = trim($_POST['antwoord_leerling'] ?? $_POST['antwoord'] ?? '');
$leerling_id = $_POST['leerling_id'] ?? null;

if(!$sessie_id || !$vraag_id) die('Missing fields');

// if leerling_id not provided, try current_student from sessies
if(!$leerling_id){
    $stmt = $pdo->prepare('SELECT current_student_id FROM sessies WHERE id = ?');
    $stmt->execute([$sessie_id]);
    $leerling_id = $stmt->fetchColumn();
    if(!$leerling_id) die('Geen leerling bekend voor deze beurt.');
}

// fetch correct answer
$stmt = $pdo->prepare('SELECT antwoord FROM vragen WHERE id = ?');
$stmt->execute([$vraag_id]);
$correct_ans = $stmt->fetchColumn();
$correct = null;
if($correct_ans !== null){
    $correct = (mb_strtolower(trim($antwoord), 'UTF-8') === mb_strtolower(trim($correct_ans), 'UTF-8')) ? 1 : 0;
}

// store result
$stmt = $pdo->prepare('INSERT INTO resultaten (sessie_id, leerling_id, vraag_id, antwoord_given, correct) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$sessie_id, $leerling_id, $vraag_id, $antwoord, $correct]);

// clear current_student and current_question so next_turn picks again
$stmt = $pdo->prepare('UPDATE sessies SET current_student_id = NULL, current_question_id = NULL WHERE id = ?');
$stmt->execute([$sessie_id]);

header('Location: vraag_handler.php?sessie='.$sessie_id);
exit;
