<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole('admin','teacher');

$id     = (int)($_GET['id'] ?? 0);
$userId = (int)($_GET['user_id'] ?? 0);
$tab    = in_array($_GET['tab'] ?? '', ['students','teachers']) ? $_GET['tab'] : 'students';

if ($id) {
    getPDO()->prepare("DELETE FROM achievements WHERE id=?")->execute([$id]);
    if ($userId) recalcRating($userId);
}

header('Location: ' . SITE_URL . '/achievements.php?tab=' . $tab);