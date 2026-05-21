<?php
require_once 'includes/header.php';

// Students have no access here at all
if ($role === 'student') {
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit;
}

$pdo   = getPDO();
$isAdmin   = ($role === 'admin');
$isTeacher = ($role === 'teacher');

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);

// ── Filters ──────────────────────────────────────────────────────────────────
$filterRole   = trim($_GET['role'] ?? '');
$filterGroup  = (int)($_GET['group_id'] ?? 0);
$filterSearch = trim($_GET['q'] ?? '');

// Teachers only see students
if ($isTeacher) { $filterRole = 'student'; }

$where  = ['1=1'];
$params = [];
if ($filterRole)   { $where[] = 'u.role = ?';                              $params[] = $filterRole; }
if ($filterGroup)  { $where[] = 's.group_id = ?';                          $params[] = $filterGroup; }
if ($filterSearch) { $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';  $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; }

$sql = "SELECT u.*,
    s.id AS student_id, s.student_num, s.phone,
    gl.id AS group_id, gl.name AS group_name,
    (SELECT COUNT(*) FROM achievements a WHERE a.user_id=u.id) AS ach_count,
    (SELECT COUNT(*) FROM certificates c WHERE c.user_id=u.id) AS cert_count,
    (SELECT COUNT(*) FROM grades g WHERE g.student_id=u.id) AS grade_count,
    (SELECT IFNULL(ROUND(AVG(g2.grade),1),0) FROM grades g2 WHERE g2.student_id=u.id) AS avg_grade,
    (SELECT COUNT(*) FROM absences ab WHERE ab.student_id=u.id) AS absence_count
    FROM users u
    LEFT JOIN students s ON s.user_id = u.id
    LEFT JOIN groups_list gl ON s.group_id = gl.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY u.role='admin' DESC, u.role='teacher' DESC, u.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Stats (always full counts regardless of filter)
$counts = $pdo->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll();
$statMap = ['admin'=>0,'teacher'=>0,'student'=>0];
foreach ($counts as $c) $statMap[$c['role']] = (int)$c['cnt'];
$totalUsers = array_sum($statMap);

$allGroups = getAllGroups();
$roleMap   = ['admin'=>'Администратор','teacher'=>'Преподаватель','student'=>'Студент'];
$roleColors = ['admin'=>'purple','teacher'=>'blue','student'=>'green'];
?>

<div class="page-header anim-fade">
  <div>
    <div class="page-header-title">
      <?= $isAdmin ? '👤 Пользователи' : '🎓 Студенты' ?>
    </div>
    <div class="page-header-sub">
      <?= $isAdmin
        ? 'Управление аккаунтами системы · всего: '.$totalUsers
        : 'Список студентов · найдено: '.count($users) ?>
    </div>
  </div>
  <?php if ($isAdmin): ?>
  <button class="btn btn-primary" onclick="openModal('modal-user-add')">+ Добавить пользователя</button>
  <?php endif; ?>
</div>

<?php if ($flash): ?>
<div class="alert alert-success anim-fade" style="
  background:#dcfce7;border:1px solid #86efac;color:#166534;
  padding:var(--space-3) var(--space-5);border-radius:10px;margin-bottom:var(--space-4)">
  ✅ <?= h($flash) ?>
</div>
<?php endif; ?>

<!-- ── Stats row ──────────────────────────────────────────────────────────── -->
<?php if ($isAdmin): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:var(--space-4);margin-bottom:var(--space-5)">
  <div class="card anim-fade" style="padding:var(--space-4);text-align:center">
    <div style="font-size:2rem;font-weight:800;color:var(--color-heading)"><?= $totalUsers ?></div>
    <div style="font-size:var(--text-xs);color:var(--color-text-muted)">Всего</div>
  </div>
  <div class="card anim-fade" style="padding:var(--space-4);text-align:center">
    <div style="font-size:1.4rem;margin-bottom:4px">👑</div>
    <div style="font-size:1.8rem;font-weight:800"><span class="badge badge-purple" style="font-size:1.1rem;padding:3px 10px"><?= $statMap['admin'] ?></span></div>
    <div style="font-size:var(--text-xs);color:var(--color-text-muted)">Администраторов</div>
  </div>
  <div class="card anim-fade" style="padding:var(--space-4);text-align:center">
    <div style="font-size:1.4rem;margin-bottom:4px">📚</div>
    <div style="font-size:1.8rem;font-weight:800"><span class="badge badge-blue" style="font-size:1.1rem;padding:3px 10px"><?= $statMap['teacher'] ?></span></div>
    <div style="font-size:var(--text-xs);color:var(--color-text-muted)">Преподавателей</div>
  </div>
  <div class="card anim-fade" style="padding:var(--space-4);text-align:center">
    <div style="font-size:1.4rem;margin-bottom:4px">🎓</div>
    <div style="font-size:1.8rem;font-weight:800"><span class="badge badge-green" style="font-size:1.1rem;padding:3px 10px"><?= $statMap['student'] ?></span></div>
    <div style="font-size:var(--text-xs);color:var(--color-text-muted)">Студентов</div>
  </div>
</div>
<?php endif; ?>

<!-- ── Role legend for teachers ──────────────────────────────────────────── -->
<?php if ($isTeacher): ?>
<div class="card anim-fade" style="padding:var(--space-4);margin-bottom:var(--space-5);background:#eff6ff;border:1px solid #bfdbfe">
  <div style="font-size:var(--text-sm);color:#1e40af">
    📌 Вы видите только студентов. Для просмотра профиля нажмите на имя. Редактирование доступно только администратору.
  </div>
</div>
<?php endif; ?>

<!-- ── Filters ────────────────────────────────────────────────────────────── -->
<div class="card anim-fade" style="padding:var(--space-4);margin-bottom:var(--space-5)">
  <form method="GET" style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="margin:0;flex:2;min-width:200px">
      <label class="form-label">Поиск по имени / email</label>
      <input type="text" name="q" class="form-control" placeholder="Имя или email…" value="<?= h($filterSearch) ?>">
    </div>
    <?php if ($isAdmin): ?>
    <div class="form-group" style="margin:0;flex:1;min-width:150px">
      <label class="form-label">Роль</label>
      <select name="role" class="form-control">
        <option value="">Все роли</option>
        <option value="admin"   <?= $filterRole==='admin'?'selected':'' ?>>👑 Администратор</option>
        <option value="teacher" <?= $filterRole==='teacher'?'selected':'' ?>>📚 Преподаватель</option>
        <option value="student" <?= $filterRole==='student'?'selected':'' ?>>🎓 Студент</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group" style="margin:0;flex:1;min-width:150px">
      <label class="form-label">Группа</label>
      <select name="group_id" class="form-control">
        <option value="">Все группы</option>
        <?php foreach ($allGroups as $g): ?>
          <option value="<?= $g['id'] ?>" <?= $filterGroup===$g['id']?'selected':'' ?>><?= h($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:var(--space-2)">
      <button type="submit" class="btn btn-primary">Найти</button>
      <a href="users.php" class="btn btn-secondary">Сбросить</a>
    </div>
  </form>
</div>

<!-- ── Table ──────────────────────────────────────────────────────────────── -->
<div class="card anim-fade">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>ФИО</th>
          <th>Email</th>
          <?php if ($isAdmin): ?><th>Роль</th><?php endif; ?>
          <th>Группа</th>
          <th>🏆 Дост.</th>
          <th>📜 Серт.</th>
          <?php if (!$isTeacher || true): /* teachers also see grades */ ?>
          <th>📊 Ср. балл</th>
          <th>⚠️ Пропуски</th>
          <?php endif; ?>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($users)): ?>
        <tr>
          <td colspan="10" style="text-align:center;padding:var(--space-10);color:var(--color-text-muted)">
            <?= $isTeacher ? 'Студентов не найдено' : 'Пользователей не найдено' ?>
          </td>
        </tr>
      <?php else: ?>
      <?php foreach ($users as $i => $u):
        $rc = $roleColors[$u['role']] ?? 'gray';
        $roleIcon = ['admin'=>'👑','teacher'=>'📚','student'=>'🎓'][$u['role']] ?? '';
      ?>
        <tr class="anim-fade" style="animation-delay:<?= $i*0.02 ?>s">
          <td><span class="badge badge-gray"><?= $i+1 ?></span></td>
          <td>
            <a href="<?= SITE_URL ?>/profile.php?id=<?= $u['id'] ?>" style="font-weight:600">
              <?= h($u['full_name']) ?>
            </a>
            <?php if ($u['student_num']): ?>
              <div style="font-size:var(--text-xs);color:var(--color-text-muted)">№ <?= h($u['student_num']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:var(--text-sm)"><?= h($u['email']) ?></td>
          <?php if ($isAdmin): ?>
          <td>
            <span class="badge badge-<?= $rc ?>">
              <?= $roleIcon ?> <?= $roleMap[$u['role']] ?? $u['role'] ?>
            </span>
          </td>
          <?php endif; ?>
          <td><?= $u['group_name'] ? '<a href="'.SITE_URL.'/group_detail.php?id='.$u['group_id'].'">'.h($u['group_name']).'</a>' : '<span style="color:var(--color-text-muted)">—</span>' ?></td>
          <td><span class="badge badge-<?= $u['ach_count']>0?'green':'gray' ?>"><?= $u['ach_count'] ?></span></td>
          <td><span class="badge badge-<?= $u['cert_count']>0?'amber':'gray' ?>"><?= $u['cert_count'] ?></span></td>
          <td>
            <?php if ($u['avg_grade'] > 0):
              $gc = $u['avg_grade']>=4.5?'green':($u['avg_grade']>=3.5?'blue':($u['avg_grade']>=2.5?'amber':'red'));
            ?>
              <span class="badge badge-<?= $gc ?>"><?= $u['avg_grade'] ?></span>
            <?php else: ?>
              <span style="color:var(--color-text-muted)">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u['absence_count'] > 0): ?>
              <span class="badge badge-<?= $u['absence_count']>10?'red':($u['absence_count']>5?'amber':'gray') ?>">
                <?= $u['absence_count'] ?>
              </span>
            <?php else: ?>
              <span style="color:var(--color-text-muted)">0</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
              <!-- Everyone can view profile -->
              <a href="<?= SITE_URL ?>/profile.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">👁 Профиль</a>

              <?php if ($isAdmin && $u['id'] !== $user['id']): ?>
              <!-- Admin: edit -->
              <button class="btn btn-secondary btn-sm"
                      onclick="openEditModal(
                        <?= $u['id'] ?>,
                        '<?= h(addslashes($u['full_name'])) ?>',
                        '<?= h(addslashes($u['email'])) ?>',
                        '<?= $u['role'] ?>'
                      )" title="Редактировать">✏️</button>
              <!-- Admin: delete -->
              <a href="<?= SITE_URL ?>/actions/user_delete.php?id=<?= $u['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Удалить «<?= h(addslashes($u['full_name'])) ?>»?\nЭто действие нельзя отменить.')"
                 title="Удалить">🗑</a>
              <?php endif; ?>

              <?php if ($isTeacher && $u['role']==='student'): ?>
              <!-- Teacher: quick links to student data -->
              <a href="<?= SITE_URL ?>/profile.php?id=<?= $u['id'] ?>#tab-grades"   class="btn btn-secondary btn-sm" title="Оценки">📊</a>
              <a href="<?= SITE_URL ?>/profile.php?id=<?= $u['id'] ?>#tab-absences" class="btn btn-secondary btn-sm" title="Отсутствия">⚠️</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- ADMIN-ONLY MODALS                                                        -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php if ($isAdmin): ?>

<!-- Modal: Add user -->
<div class="modal-overlay" id="modal-user-add">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Добавить пользователя</div>
      <button class="modal-close" onclick="closeModal('modal-user-add')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/user_save.php">

        <!-- Role selector with visual cards -->
        <div class="form-group">
          <label class="form-label">Роль</label>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-3);margin-bottom:var(--space-2)">
            <?php foreach ([
              ['student','🎓','Студент',   'green'],
              ['teacher','📚','Преподаватель','blue'],
              ['admin',  '👑','Администратор','purple'],
            ] as [$val,$icon,$label,$col]): ?>
            <label style="cursor:pointer">
              <input type="radio" name="role" value="<?= $val ?>"
                     <?= $val==='student'?'checked':'' ?>
                     onchange="toggleGroupField(this.value)"
                     style="display:none" class="role-radio">
              <div class="role-card" data-role="<?= $val ?>" style="
                border:2px solid <?= $val==='student'?'#22c55e':($val==='teacher'?'#3b82f6':'#a855f7') ?>;
                border-radius:10px;padding:12px 8px;text-align:center;
                background:<?= $val==='student'?'#f0fdf4':($val==='teacher'?'#eff6ff':'#faf5ff') ?>;
                transition:all 0.15s;opacity:<?= $val==='student'?'1':'0.45' ?>">
                <div style="font-size:1.4rem"><?= $icon ?></div>
                <div style="font-size:var(--text-xs);font-weight:700;margin-top:4px"><?= $label ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">ФИО</label>
          <input type="text" name="full_name" class="form-control" placeholder="Иванов Иван Иванович" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="user@svgtk.kz" required>
          </div>
          <div class="form-group">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-control" minlength="8" required placeholder="Минимум 8 символов">
          </div>
        </div>

        <!-- Student-only fields -->
        <div id="student-fields">
          <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:var(--space-3) var(--space-4);margin-bottom:var(--space-3)">
            <div style="font-size:var(--text-xs);font-weight:700;color:#15803d;margin-bottom:var(--space-3)">🎓 Данные студента</div>
            <div class="form-row">
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Группа</label>
                <select name="group_id" class="form-control">
                  <option value="">Без группы</option>
                  <?php foreach ($allGroups as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= h($g['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Номер студента</label>
                <input type="text" name="student_num" class="form-control" placeholder="2024-001">
              </div>
            </div>
            <div class="form-group" style="margin-top:var(--space-3);margin-bottom:0">
              <label class="form-label">Телефон</label>
              <input type="text" name="phone" class="form-control" placeholder="+7 700 000 0000">
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">✅ Создать</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-user-add')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit user -->
<div class="modal-overlay" id="modal-user-edit">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Редактировать пользователя</div>
      <button class="modal-close" onclick="closeModal('modal-user-edit')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/user_update.php">
        <input type="hidden" name="id" id="edit-user-id">
        <div class="form-group">
          <label class="form-label">ФИО</label>
          <input type="text" name="full_name" id="edit-user-name" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" id="edit-user-email" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Роль</label>
          <select name="role" id="edit-user-role" class="form-control">
            <option value="student">🎓 Студент</option>
            <option value="teacher">📚 Преподаватель</option>
            <option value="admin">👑 Администратор</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">
            Новый пароль
            <span style="color:var(--color-text-muted);font-weight:400">(оставьте пустым, чтобы не менять)</span>
          </label>
          <input type="password" name="password" class="form-control" minlength="8" placeholder="Минимум 8 символов">
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">💾 Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-user-edit')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── Role card visual selector ───────────────────────────────────────────────
document.querySelectorAll('.role-radio').forEach(radio => {
  radio.addEventListener('change', function () {
    document.querySelectorAll('.role-card').forEach(c => c.style.opacity = '0.45');
    this.closest('label').querySelector('.role-card').style.opacity = '1';
  });
});

// ── Toggle student-only fields ──────────────────────────────────────────────
function toggleGroupField(role) {
  document.getElementById('student-fields').style.display = role === 'student' ? '' : 'none';
}

// ── Open edit modal ─────────────────────────────────────────────────────────
function openEditModal(id, name, email, role) {
  document.getElementById('edit-user-id').value    = id;
  document.getElementById('edit-user-name').value  = name;
  document.getElementById('edit-user-email').value = email;
  document.getElementById('edit-user-role').value  = role;
  openModal('modal-user-edit');
}
</script>

<?php endif; // isAdmin ?>

<?php require_once 'includes/footer.php'; ?>