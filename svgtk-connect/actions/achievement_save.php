<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole('admin','teacher');
$user = currentUser();

$userId      = (int)($_POST['user_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? 'other');
$level       = trim($_POST['level'] ?? 'college');
$place       = (isset($_POST['place']) && $_POST['place'] !== '') ? (int)$_POST['place'] : null;
$dateEvent   = (!empty($_POST['date_event'])) ? $_POST['date_event'] : null;
$tab         = in_array($_POST['tab'] ?? '', ['students','teachers']) ? $_POST['tab'] : 'students';

if (!$userId || !$title) {
    $_SESSION['flash_error'] = "Ошибка: не выбран пользователь или не указано название.";
    header('Location: ' . SITE_URL . '/achievements.php?tab=' . $tab);
    exit;
}

$pdo = getPDO();

$check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$check->execute([$userId]);
if (!$check->fetch()) {
    $_SESSION['flash_error'] = "Ошибка: пользователь не найден (ID=$userId). Обновите страницу и попробуйте снова.";
    header('Location: ' . SITE_URL . '/achievements.php?tab=' . $tab);
    exit;
}

$pdo->prepare(
    "INSERT INTO achievements (user_id, title, description, category, level, place, date_event, added_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
)->execute([$userId, $title, $description, $category, $level, $place, $dateEvent, $user['id']]);

recalcRating($userId);

header('Location: ' . SITE_URL . '/achievements.php?tab=' . $tab);