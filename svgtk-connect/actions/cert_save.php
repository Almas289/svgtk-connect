<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole('admin','teacher');

$userId = (int)$_POST['user_id'];
getPDO()->prepare("INSERT INTO certificates (user_id,title,issuer,issue_date,expiry_date,added_by) VALUES(?,?,?,?,?,?)")
    ->execute([$userId, $_POST['title'], $_POST['issuer'] ?? '', $_POST['issue_date'] ?: null, $_POST['expiry_date'] ?: null, $user['id']]);

recalcRating($userId);
header('Location: ' . SITE_URL . '/profile.php?id=' . $userId);