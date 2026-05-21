<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
requireLogin();
$user = currentUser();
$role = $user['role'];

function navItem(string $href, string $icon, string $label): string {
    $cur = basename($_SERVER['PHP_SELF'], '.php');
    $page = basename(parse_url($href, PHP_URL_PATH), '.php');
    $cls = ($cur === $page) ? ' active' : '';
    return "<a href=\"$href\" class=\"nav-item$cls\">$icon <span>$label</span></a>";
}

$roleLabel = ['admin'=>'Администратор','teacher'=>'Преподаватель','student'=>'Студент'][$role] ?? $role;
$initials  = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w,0,1)), array_slice(explode(' ', $user['full_name']), 0, 2)));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
        <rect width="36" height="36" rx="8" fill="#0284c7"/>
        <path d="M8 26 C8 20 14 12 18 12 C22 12 28 18 28 24" stroke="#fff" stroke-width="2.5" stroke-linecap="round" fill="none"/>
        <circle cx="18" cy="12" r="3" fill="#fff"/>
        <path d="M12 26h12" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <div>
        <div class="sidebar-logo-text"><?= SITE_NAME ?></div>
        <div class="sidebar-logo-sub">Портал достижений</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Навигация</div>
      <?= navItem(SITE_URL.'/dashboard.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>', 'Главная') ?>
      <?= navItem(SITE_URL.'/groups.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>', 'Группы') ?>
      <?= navItem(SITE_URL.'/rating.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>', 'Рейтинг') ?>

      <?php if ($role !== 'student'): ?>
      <div class="nav-section-label">Управление</div>
      <?= navItem(SITE_URL.'/achievements.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>', 'Достижения') ?>
      <?= navItem(SITE_URL.'/certificates.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', 'Сертификаты') ?>
      <?= navItem(SITE_URL.'/events.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>', 'Мероприятия') ?>
      <?= navItem(SITE_URL.'/grades.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>', 'Оценки') ?>
      <?= navItem(SITE_URL.'/absences.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>', 'Отсутствия') ?>
      <?php endif; ?>

      <?php if ($role === 'admin'): ?>
      <div class="nav-section-label">Администратор</div>
      <?= navItem(SITE_URL.'/users.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>', 'Пользователи') ?>
      <?php endif; ?>

      <div class="nav-section-label">Профиль</div>
      <?= navItem(SITE_URL.'/profile.php', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>', 'Мой профиль') ?>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= $initials ?></div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?= h($user['full_name']) ?></div>
          <div class="sidebar-user-role"><?= $roleLabel ?></div>
        </div>
      </div>
      <a href="<?= SITE_URL ?>/logout.php" class="btn-logout">Выйти из системы</a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:var(--space-3)">
        <button class="hamburger" id="hamburger" aria-label="Меню">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="topbar-breadcrumb"><?= SITE_NAME ?></div>
      </div>
      <div class="topbar-actions">
        <span class="badge badge-<?= $role === 'admin' ? 'purple' : ($role === 'teacher' ? 'blue' : 'green') ?>"><?= $roleLabel ?></span>
      </div>
    </header>
    <main class="page-content">