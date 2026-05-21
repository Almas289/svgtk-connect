<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$host   = 'localhost';
$dbname = 'SVGTK_Connect';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Ошибка БД: " . $e->getMessage());
}

$userId   = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];
$email    = $_SESSION['email'];

$roleLabel = ['admin' => 'Администратор', 'teacher' => 'Преподаватель', 'student' => 'Студент'][$role] ?? $role;
$initials  = '';
foreach (array_slice(explode(' ', $fullName), 0, 2) as $w) {
    $initials .= mb_strtoupper(mb_substr($w, 0, 1));
}

// Статистика
$totalStudents  = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalGroups    = $pdo->query("SELECT COUNT(*) FROM groups_list")->fetchColumn();
$totalAch       = $pdo->query("SELECT COUNT(*) FROM achievements")->fetchColumn();
$totalCerts     = $pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();

// Топ рейтинг
$topRating = $pdo->query("SELECT r.*, u.full_name FROM ratings r JOIN users u ON r.user_id=u.id ORDER BY r.total_points DESC LIMIT 5")->fetchAll();

// Последние достижения
$recentAch = $pdo->query("SELECT a.*, u.full_name FROM achievements a JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 6")->fetchAll();

// Группы
$groups = $pdo->query("SELECT g.*, (SELECT COUNT(*) FROM students s WHERE s.group_id=g.id) as cnt FROM groups_list g ORDER BY g.name")->fetchAll();

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Главная — СВГТК Портал</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; color: #1a202c; min-height: 100vh; }
  a { color: #0284c7; text-decoration: none; }
  a:hover { text-decoration: underline; }

  /* LAYOUT */
  .layout { display: flex; min-height: 100vh; }

  /* SIDEBAR */
  .sidebar {
    width: 260px; flex-shrink: 0;
    background: linear-gradient(180deg, #0f2240 0%, #1a3560 100%);
    color: #fff;
    position: fixed; top: 0; left: 0; height: 100vh;
    display: flex; flex-direction: column;
    overflow-y: auto; z-index: 100;
  }
  .sb-logo {
    display: flex; align-items: center; gap: 12px;
    padding: 24px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }
  .sb-logo-icon {
    width: 40px; height: 40px; background: #0284c7; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .sb-logo-name { font-size: 0.95rem; font-weight: 800; line-height: 1.2; }
  .sb-logo-sub  { font-size: 0.7rem; opacity: 0.6; }

  .sb-nav { flex: 1; padding: 16px 0; }
  .sb-section {
    font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: rgba(255,255,255,0.4);
    padding: 16px 20px 6px;
  }
  .sb-link {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 20px;
    color: rgba(255,255,255,0.75);
    font-size: 0.9rem; font-weight: 500;
    transition: all 0.18s; cursor: pointer;
    text-decoration: none;
  }
  .sb-link:hover { background: rgba(255,255,255,0.08); color: #fff; text-decoration: none; }
  .sb-link.active { background: rgba(2,132,199,0.3); color: #fff; border-left: 3px solid #38bdf8; }

  .sb-footer {
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
  }
  .sb-user { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
  .sb-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: #0284c7; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.85rem; flex-shrink: 0;
  }
  .sb-uname { font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .sb-urole { font-size: 0.72rem; opacity: 0.6; }
  .btn-logout {
    display: block; width: 100%; text-align: center;
    padding: 8px; border-radius: 8px;
    background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8);
    font-size: 0.85rem; transition: background 0.18s; text-decoration: none;
  }
  .btn-logout:hover { background: rgba(220,38,38,0.4); color: #fff; text-decoration: none; }

  /* MAIN */
  .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }

  .topbar {
    background: #fff; border-bottom: 1px solid rgba(0,0,0,0.08);
    padding: 16px 32px; display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  }
  .topbar-title { font-size: 1.1rem; font-weight: 800; color: #0f2240; }
  .badge {
    display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 999px;
    font-size: 0.75rem; font-weight: 700;
  }
  .badge-admin   { background: #ede9fe; color: #6d28d9; }
  .badge-teacher { background: #dbeafe; color: #1d4ed8; }
  .badge-student { background: #dcfce7; color: #15803d; }

  .content { padding: 32px; }

  /* PAGE HEADER */
  .page-hdr { margin-bottom: 28px; }
  .page-hdr h2 { font-size: 1.5rem; font-weight: 800; color: #0f2240; }
  .page-hdr p  { font-size: 0.9rem; color: #64748b; margin-top: 4px; }

  /* STATS */
  .stats { display: grid; grid-template-columns: repeat(auto-fill,minmax(200px,1fr)); gap: 20px; margin-bottom: 28px; }
  .stat-card {
    background: #fff; border-radius: 14px;
    padding: 24px; display: flex; align-items: center; gap: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
    transition: transform 0.18s, box-shadow 0.18s;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
  .stat-icon {
    width: 50px; height: 50px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.5rem;
  }
  .ic-blue   { background: #dbeafe; }
  .ic-purple { background: #ede9fe; }
  .ic-green  { background: #dcfce7; }
  .ic-amber  { background: #fef3c7; }
  .stat-num  { font-size: 2rem; font-weight: 800; line-height: 1; color: #0f2240; }
  .stat-lbl  { font-size: 0.8rem; color: #64748b; margin-top: 3px; }

  /* GRID 2 COL */
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }

  /* CARDS */
  .card { background: #fff; border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.07); overflow: hidden; }
  .card-hdr {
    padding: 18px 24px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
  }
  .card-title { font-size: 1rem; font-weight: 700; color: #0f2240; }
  .card-body  { padding: 0; }

  /* TABLE */
  table { width: 100%; border-collapse: collapse; }
  th {
    background: #f8fafc; padding: 10px 16px;
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: #64748b;
    border-bottom: 1px solid #e2e8f0; text-align: left;
  }
  td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.88rem; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #f8fafc; }

  /* GROUPS GRID */
  .groups-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap: 16px; }
  .group-card {
    background: #fff; border-radius: 14px;
    padding: 20px; text-align: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
    transition: transform 0.18s, box-shadow 0.18s;
    cursor: pointer; text-decoration: none; color: inherit;
    display: block;
  }
  .group-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); text-decoration: none; }
  .group-icon { font-size: 2rem; margin-bottom: 8px; }
  .group-name { font-weight: 800; font-size: 1rem; color: #0f2240; margin-bottom: 4px; }
  .group-count { font-size: 0.8rem; color: #64748b; }

  /* RANK */
  .rank { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; font-weight: 800; font-size: 0.8rem; }
  .r1 { background: #fef3c7; color: #b45309; }
  .r2 { background: #f1f5f9; color: #475569; }
  .r3 { background: #fef9c3; color: #92400e; }

  /* ACHIEVEMENT BADGE */
  .badge-cat { padding: 2px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 600; background: #dbeafe; color: #1d4ed8; }

  /* BUTTONS */
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.18s; text-decoration: none; }
  .btn-primary { background: #0284c7; color: #fff; }
  .btn-primary:hover { background: #0369a1; color: #fff; text-decoration: none; }
  .btn-sm { padding: 5px 10px; font-size: 0.78rem; }

  /* ANIMATIONS */
  @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
  .fade { animation: fadeUp 0.4s ease both; }
  .d1  { animation-delay: 0.05s; }
  .d2  { animation-delay: 0.1s; }
  .d3  { animation-delay: 0.15s; }
  .d4  { animation-delay: 0.2s; }

  @media (max-width: 900px) {
    .sidebar { display: none; }
    .main { margin-left: 0; }
    .grid2 { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon">
      <svg width="22" height="22" viewBox="0 0 36 36" fill="none">
        <path d="M8 26 C8 20 14 12 18 12 C22 12 28 18 28 24" stroke="#fff" stroke-width="2.5" stroke-linecap="round" fill="none"/>
        <circle cx="18" cy="12" r="3" fill="#fff"/>
        <path d="M12 26h12" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    <div>
      <div class="sb-logo-name">СВГТК Портал</div>
      <div class="sb-logo-sub">Достижения студентов</div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-section">Навигация</div>
    <a class="sb-link active" href="dashboard.php"> Главная</a>
    <a class="sb-link" href="groups.php"> Группы</a>
    <a class="sb-link" href="rating.php"> Рейтинг</a>

    <?php if ($role !== 'student'): ?>
    <div class="sb-section">Управление</div>
    <a class="sb-link" href="achievements.php"> Достижения</a>
    <a class="sb-link" href="certificates.php"> Сертификаты</a>
    <a class="sb-link" href="events.php"> Мероприятия</a>
    <a class="sb-link" href="grades.php"> Оценки</a>
    <a class="sb-link" href="absences.php"> Отсутствия</a>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <div class="sb-section">Администратор</div>
    <a class="sb-link" href="users.php"> Пользователи</a>
    <?php endif; ?>

    <div class="sb-section">Профиль</div>
    <a class="sb-link" href="profile.php"> Мой профиль</a>
  </nav>

  <div class="sb-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= h($initials) ?></div>
      <div>
        <div class="sb-uname"><?= h($fullName) ?></div>
        <div class="sb-urole"><?= h($roleLabel) ?></div>
      </div>
    </div>
    <a href="logout.php" class="btn-logout">Выйти из системы</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="topbar-title">📊 Главная панель</div>
    <span class="badge badge-<?= h($role) ?>"><?= h($roleLabel) ?></span>
  </header>

  <div class="content">

    <div class="page-hdr fade">
      <h2>Добро пожаловать, <?= h($fullName) ?>! 👋</h2>
      <p>Обзор системы СВГТК Портал — достижения, сертификаты, рейтинг</p>
    </div>

    <!-- STATS -->
    <div class="stats">
      <div class="stat-card fade d1">
        <div class="stat-icon ic-blue">👥</div>
        <div><div class="stat-num"><?= $totalStudents ?></div><div class="stat-lbl">Студентов</div></div>
      </div>
      <div class="stat-card fade d2">
        <div class="stat-icon ic-purple">🗂</div>
        <div><div class="stat-num"><?= $totalGroups ?></div><div class="stat-lbl">Групп</div></div>
      </div>
      <div class="stat-card fade d3">
        <div class="stat-icon ic-green">🏆</div>
        <div><div class="stat-num"><?= $totalAch ?></div><div class="stat-lbl">Достижений</div></div>
      </div>
      <div class="stat-card fade d4">
        <div class="stat-icon ic-amber">📜</div>
        <div><div class="stat-num"><?= $totalCerts ?></div><div class="stat-lbl">Сертификатов</div></div>
      </div>
    </div>

    <!-- GROUPS -->
    <div class="card fade" style="margin-bottom:24px">
      <div class="card-hdr">
        <div class="card-title">🗂 Учебные группы</div>
        <a href="groups.php" class="btn btn-primary btn-sm">Все группы</a>
      </div>
      <div class="card-body" style="padding:20px">
        <div class="groups-grid">
          <?php foreach ($groups as $g): ?>
          <a href="group_detail.php?id=<?= $g['id'] ?>" class="group-card">
            <div class="group-icon">🎓</div>
            <div class="group-name"><?= h($g['name']) ?></div>
            <div class="group-count"><?= $g['cnt'] ?> студентов</div>
          </a>
          <?php endforeach; ?>
          <?php if (empty($groups)): ?>
          <p style="color:#94a3b8;padding:20px">Группы не найдены</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RATING + ACHIEVEMENTS -->
    <div class="grid2">
      <div class="card fade">
        <div class="card-hdr">
          <div class="card-title">🏆 Топ рейтинг</div>
          <a href="rating.php" class="btn btn-primary btn-sm">Все</a>
        </div>
        <div class="card-body">
          <table>
            <thead><tr><th>#</th><th>Студент</th><th>Баллы</th></tr></thead>
            <tbody>
            <?php if (empty($topRating)): ?>
              <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:30px">Рейтинг пуст</td></tr>
            <?php else: ?>
            <?php foreach ($topRating as $i => $r): ?>
              <tr>
                <td><span class="rank <?= $i===0?'r1':($i===1?'r2':($i===2?'r3':'')) ?>"><?= $i+1 ?></span></td>
                <td><a href="profile.php?id=<?= $r['user_id'] ?>"><?= h($r['full_name']) ?></a></td>
                <td><strong><?= $r['total_points'] ?></strong></td>
              </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card fade">
        <div class="card-hdr">
          <div class="card-title">⭐ Последние достижения</div>
        </div>
        <div class="card-body">
          <table>
            <thead><tr><th>Студент</th><th>Достижение</th></tr></thead>
            <tbody>
            <?php if (empty($recentAch)): ?>
              <tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:30px">Нет данных</td></tr>
            <?php else: ?>
            <?php foreach ($recentAch as $a): ?>
              <tr>
                <td><?= h($a['full_name']) ?></td>
                <td><span class="badge-cat"><?= h($a['title']) ?></span></td>
              </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

</div><!-- /layout -->
</body>
</html>
