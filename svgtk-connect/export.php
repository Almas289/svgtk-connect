<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if ($type === 'resume') {
    $html = generateResumePDF($id);
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="resume_' . $id . '.html"');
    echo $html; exit;
}

if ($type === 'group') {
    $students = getStudentsByGroup($id);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="group_' . $id . '.csv"');
    $out = fopen('php://output','w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['ФИО','Email','Номер студента','Достижений','Сертификатов']);
    foreach ($students as $s) {
        $ac = getPDO()->prepare("SELECT COUNT(*) FROM achievements WHERE user_id=?"); $ac->execute([$s['id']]); $a = $ac->fetchColumn();
        $cc = getPDO()->prepare("SELECT COUNT(*) FROM certificates WHERE user_id=?"); $cc->execute([$s['id']]); $c = $cc->fetchColumn();
        fputcsv($out, [$s['full_name'],$s['email'],$s['student_num'],$a,$c]);
    }
    fclose($out); exit;
}

if ($type === 'rating') {
    $rating = getRating(200);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rating.csv"');
    $out = fopen('php://output','w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['#','ФИО','Достижения','Сертификаты','Мероприятия','Баллы']);
    foreach ($rating as $i => $r) fputcsv($out, [$i+1,$r['full_name'],$r['achievements_count'],$r['certificates_count'],$r['events_count'],$r['total_points']]);
    fclose($out); exit;
}

header('Location: ' . SITE_URL . '/dashboard.php');