<?php
require_once 'includes/header.php';

$pdo = getPDO();

// Active tab
$activeTab = $_GET['tab'] ?? 'students';

// ── Filters ───────────────────────────────────────────────────────────────
$filterGroup    = (int)($_GET['group_id'] ?? 0);
$filterCategory = trim($_GET['category'] ?? '');
$filterLevel    = trim($_GET['level'] ?? '');
$filterSearch   = trim($_GET['q'] ?? '');

// ── Students achievements ─────────────────────────────────────────────────
$swhere  = ['u.role = "student"'];
$sparams = [];
if ($filterGroup)    { $swhere[] = 's.group_id = ?';                         $sparams[] = $filterGroup; }
if ($filterCategory) { $swhere[] = 'a.category = ?';                         $sparams[] = $filterCategory; }
if ($filterLevel)    { $swhere[] = 'a.level = ?';                            $sparams[] = $filterLevel; }
if ($filterSearch)   { $swhere[] = '(a.title LIKE ? OR u.full_name LIKE ?)'; $sparams[] = "%$filterSearch%"; $sparams[] = "%$filterSearch%"; }

$sstmt = $pdo->prepare("SELECT a.*, u.full_name, u.id AS user_id, gl.name AS group_name,
    added.full_name AS added_by_name
    FROM achievements a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN students s ON s.user_id = a.user_id
    LEFT JOIN groups_list gl ON s.group_id = gl.id
    LEFT JOIN users added ON a.added_by = added.id
    WHERE " . implode(' AND ', $swhere) . "
    ORDER BY a.date_event DESC, a.created_at DESC");
$sstmt->execute($sparams);
$studentAchs = $sstmt->fetchAll();

// ── Teachers achievements ─────────────────────────────────────────────────
$twhere  = ['u.role = "teacher"'];
$tparams = [];
if ($filterCategory) { $twhere[] = 'a.category = ?'; $tparams[] = $filterCategory; }
if ($filterLevel)    { $twhere[] = 'a.level = ?';    $tparams[] = $filterLevel; }
if ($filterSearch)   { $twhere[] = '(a.title LIKE ? OR u.full_name LIKE ?)'; $tparams[] = "%$filterSearch%"; $tparams[] = "%$filterSearch%"; }

$tstmt = $pdo->prepare("SELECT a.*, u.full_name, u.id AS user_id,
    added.full_name AS added_by_name
    FROM achievements a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users added ON a.added_by = added.id
    WHERE " . implode(' AND ', $twhere) . "
    ORDER BY a.date_event DESC, a.created_at DESC");
$tstmt->execute($tparams);
$teacherAchs = $tstmt->fetchAll();

// ── Stats ─────────────────────────────────────────────────────────────────
$allAchs = array_merge($studentAchs, $teacherAchs);
$levels  = ['international'=>0,'national'=>0,'regional'=>0,'city'=>0,'college'=>0];
foreach ($allAchs as $a) { $l = $a['level'] ?? ''; if (isset($levels[$l])) $levels[$l]++; }

// ── Dropdowns ─────────────────────────────────────────────────────────────
$allGroups   = getAllGroups();
$allStudents = $pdo->query("SELECT u.id, u.full_name, gl.name AS group_name
    FROM students s JOIN users u ON s.user_id=u.id
    LEFT JOIN groups_list gl ON s.group_id=gl.id
    ORDER BY u.full_name")->fetchAll();
$allTeachers = $pdo->query("SELECT id, full_name FROM users WHERE role='teacher' ORDER BY full_name")->fetchAll();

$lvlColors = ['international'=>'purple','national'=>'blue','regional'=>'green','city'=>'amber','college'=>'gray'];
$levelMeta = [
    'international' => ['🌍','Международный','purple'],
    'national'      => ['🇰🇿','Республика',   'blue'],
    'regional'      => ['🏙', 'Регион',        'green'],
    'city'          => ['🏢', 'Город',         'amber'],
    'college'       => ['🎓', 'Колледж',       'gray'],
];
?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-error anim-fade">⚠️ <?= h($_SESSION['flash_error']) ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="page-header anim-fade">
  <div>
    <div class="page-header-title">🏆 Достижения</div>
    <div class="page-header-sub">Достижения студентов и преподавателей · всего: <?= count($allAchs) ?></div>
  </div>
  <?php if (in_array($role, ['admin','teacher'])): ?>
  <div style="display:flex;gap:var(--space-3)">
    <button class="btn btn-primary" onclick="openModal('modal-ach-student')">+ Студент</button>
    <?php if ($role === 'admin'): ?>
    <button class="btn btn-secondary" onclick="openModal('modal-ach-teacher')">+ Преподаватель</button>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:var(--space-4);margin-bottom:var(--space-5)">
  <div class="card anim-fade" style="padding:var(--space-4);text-align:center">
    <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:4px">🎓 Студенты</div>
    <div style="font-size:1.8rem;font-weight:800;color:var(--color-heading)"><?= count($studentAchs) ?></div>
  </div>
  <div class="card anim-fade" style="padding:var(--space-4);text-align:center">
    <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:4px">📚 Преподаватели</div>
    <div style="font-size:1.8rem;font-weight:800;color:var(--color-heading)"><?= count($teacherAchs) ?></div>
  </div>
  <?php foreach ($levelMeta as $key => [$icon, $label, $color]): ?>
  <div class="card anim-fade" style="padding:var(--space-4);text-align:center">
    <div style="font-size:1.2rem"><?= $icon ?></div>
    <div style="margin:4px 0"><span class="badge badge-<?= $color ?>"><?= $levels[$key] ?></span></div>
    <div style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card anim-fade" style="padding:var(--space-4);margin-bottom:var(--space-5)">
  <form method="GET" style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="tab" value="<?= h($activeTab) ?>">
    <div class="form-group" style="margin:0;flex:2;min-width:180px">
      <label class="form-label">Поиск</label>
      <input type="text" name="q" class="form-control" placeholder="Название или ФИО…" value="<?= h($filterSearch) ?>">
    </div>
    <?php if ($activeTab === 'students'): ?>
    <div class="form-group" style="margin:0;flex:1;min-width:130px">
      <label class="form-label">Группа</label>
      <select name="group_id" class="form-control">
        <option value="">Все группы</option>
        <?php foreach ($allGroups as $g): ?>
          <option value="<?= $g['id'] ?>" <?= $filterGroup===$g['id']?'selected':'' ?>><?= h($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group" style="margin:0;flex:1;min-width:130px">
      <label class="form-label">Категория</label>
      <select name="category" class="form-control">
        <option value="">Все</option>
        <option value="olympiad"   <?= $filterCategory==='olympiad'?'selected':'' ?>>Олимпиада</option>
        <option value="conference" <?= $filterCategory==='conference'?'selected':'' ?>>Конференция</option>
        <option value="sport"      <?= $filterCategory==='sport'?'selected':'' ?>>Спорт</option>
        <option value="art"        <?= $filterCategory==='art'?'selected':'' ?>>Творчество</option>
        <option value="science"    <?= $filterCategory==='science'?'selected':'' ?>>Наука</option>
        <option value="other"      <?= $filterCategory==='other'?'selected':'' ?>>Другое</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;flex:1;min-width:130px">
      <label class="form-label">Уровень</label>
      <select name="level" class="form-control">
        <option value="">Все</option>
        <option value="college"       <?= $filterLevel==='college'?'selected':'' ?>>Колледж</option>
        <option value="city"          <?= $filterLevel==='city'?'selected':'' ?>>Город</option>
        <option value="regional"      <?= $filterLevel==='regional'?'selected':'' ?>>Регион</option>
        <option value="national"      <?= $filterLevel==='national'?'selected':'' ?>>Республика</option>
        <option value="international" <?= $filterLevel==='international'?'selected':'' ?>>Международный</option>
      </select>
    </div>
    <div style="display:flex;gap:var(--space-2)">
      <button type="submit" class="btn btn-primary">Найти</button>
      <a href="achievements.php?tab=<?= h($activeTab) ?>" class="btn btn-secondary">Сбросить</a>
    </div>
  </form>
</div>

<!-- Tabs + Table -->
<div class="card anim-fade">
  <div style="display:flex;border-bottom:1px solid var(--color-border)">
    <a href="achievements.php?tab=students&q=<?= urlencode($filterSearch) ?>&category=<?= urlencode($filterCategory) ?>&level=<?= urlencode($filterLevel) ?>&group_id=<?= $filterGroup ?>"
       style="padding:var(--space-3) var(--space-6);font-size:var(--text-sm);font-weight:600;border-bottom:2px solid <?= $activeTab==='students'?'var(--color-primary)':'transparent' ?>;color:<?= $activeTab==='students'?'var(--color-primary)':'var(--color-text-muted)' ?>;margin-bottom:-1px;text-decoration:none">
      🎓 Студенты <span class="badge badge-<?= $activeTab==='students'?'blue':'gray' ?>" style="margin-left:4px"><?= count($studentAchs) ?></span>
    </a>
    <a href="achievements.php?tab=teachers&q=<?= urlencode($filterSearch) ?>&category=<?= urlencode($filterCategory) ?>&level=<?= urlencode($filterLevel) ?>"
       style="padding:var(--space-3) var(--space-6);font-size:var(--text-sm);font-weight:600;border-bottom:2px solid <?= $activeTab==='teachers'?'var(--color-primary)':'transparent' ?>;color:<?= $activeTab==='teachers'?'var(--color-primary)':'var(--color-text-muted)' ?>;margin-bottom:-1px;text-decoration:none">
      📚 Преподаватели <span class="badge badge-<?= $activeTab==='teachers'?'blue':'gray' ?>" style="margin-left:4px"><?= count($teacherAchs) ?></span>
    </a>
  </div>

  <div class="table-wrap">
    <?php
    $rows         = $activeTab === 'teachers' ? $teacherAchs : $studentAchs;
    $isTeacherTab = $activeTab === 'teachers';
    ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th><?= $isTeacherTab ? 'Преподаватель' : 'Студент' ?></th>
          <?php if (!$isTeacherTab): ?><th>Группа</th><?php endif; ?>
          <th>Достижение</th>
          <th>Категория</th>
          <th>Уровень</th>
          <th>Место</th>
          <th>Дата</th>
          <th>Добавил</th>
          <?php if (in_array($role,['admin','teacher'])): ?><th>Действия</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="10" style="text-align:center;padding:var(--space-10);color:var(--color-text-muted)">
            Достижений не найдено
          </td>
        </tr>
      <?php else: ?>
      <?php foreach ($rows as $i => $a):
        $lc = $lvlColors[$a['level'] ?? ''] ?? 'gray';
      ?>
        <tr class="anim-fade" style="animation-delay:<?= $i*0.025 ?>s">
          <td><span class="badge badge-gray"><?= $i+1 ?></span></td>
          <td>
            <a href="<?= SITE_URL ?>/profile.php?id=<?= $a['user_id'] ?>" style="font-weight:600">
              <?= h($a['full_name']) ?>
            </a>
          </td>
          <?php if (!$isTeacherTab): ?>
          <td><?= h($a['group_name'] ?? '—') ?></td>
          <?php endif; ?>
          <td>
            <div style="font-weight:600"><?= h($a['title']) ?></div>
            <?php if (!empty($a['description'])): ?>
              <div style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= h(mb_strimwidth($a['description'],0,55,'…')) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-blue"><?= categoryLabel($a['category'] ?? '') ?></span></td>
          <td><span class="badge badge-<?= $lc ?>"><?= levelLabel($a['level'] ?? '') ?></span></td>
          <td><?= $a['place'] ? '<span class="badge badge-amber">🥇 '.(int)$a['place'].' место</span>' : '—' ?></td>
          <td><?= h($a['date_event'] ?? '—') ?></td>
          <td style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= h($a['added_by_name'] ?? '—') ?></td>
          <?php if (in_array($role,['admin','teacher'])): ?>
          <td>
            <a href="<?= SITE_URL ?>/actions/achievement_delete.php?id=<?= $a['id'] ?>&user_id=<?= $a['user_id'] ?>&tab=<?= h($activeTab) ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Удалить достижение?')">🗑</a>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (in_array($role, ['admin','teacher'])): ?>

<!-- Modal: Add student achievement -->
<div class="modal-overlay" id="modal-ach-student">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🎓 Достижение студента</div>
      <button class="modal-close" onclick="closeModal('modal-ach-student')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/achievement_save.php">
        <input type="hidden" name="tab" value="students">
        <div class="form-group">
          <label class="form-label">Студент</label>
          <select name="user_id" class="form-control" required>
            <option value="">Выберите студента</option>
            <?php foreach ($allStudents as $st): ?>
              <option value="<?= $st['id'] ?>"><?= h($st['full_name']) ?> — <?= h($st['group_name'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?= achievementFields() ?>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-ach-student')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($role === 'admin'): ?>

<!-- Modal: Add teacher achievement -->
<div class="modal-overlay" id="modal-ach-teacher">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">📚 Достижение преподавателя</div>
      <button class="modal-close" onclick="closeModal('modal-ach-teacher')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/achievement_save.php">
        <input type="hidden" name="tab" value="teachers">
        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
            Преподаватель
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="closeModal('modal-ach-teacher');resetNewTeacherModal();openModal('modal-new-teacher')">
              + Добавить нового
            </button>
          </label>
          <select name="user_id" id="teacher-select" class="form-control" required>
            <option value="">Выберите преподавателя</option>
            <?php foreach ($allTeachers as $t): ?>
              <option value="<?= $t['id'] ?>"><?= h($t['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:6px">
            Нет нужного? Нажмите «+ Добавить нового», создайте преподавателя и вернитесь сюда.
          </div>
        </div>
        <?= achievementFields() ?>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-ach-teacher')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Create new teacher (только ФИО и пароль) -->
<div class="modal-overlay" id="modal-new-teacher">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">👤 Создать преподавателя</div>
      <button class="modal-close" onclick="closeModal('modal-new-teacher');openModal('modal-ach-teacher')">✕</button>
    </div>
    <div class="modal-body">
      <div id="nt-success" style="display:none;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.875rem;color:#16a34a"></div>
      <div id="nt-error"   style="display:none;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.875rem;color:#dc2626"></div>

      <div id="nt-form">
        <div class="form-group">
          <label class="form-label">ФИО</label>
          <input type="text" id="nt-fullname" class="form-control" placeholder="Иванова Марина Сергеевна">
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" id="nt-password" class="form-control" placeholder="Минимум 8 символов">
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-primary" id="nt-save-btn" onclick="saveNewTeacher()">
            ✅ Создать преподавателя
          </button>
          <button type="button" class="btn btn-secondary"
                  onclick="closeModal('modal-new-teacher');openModal('modal-ach-teacher')">
            ← Назад
          </button>
        </div>
      </div>

      <div id="nt-done" style="display:none;text-align:center;padding:16px 0">
        <button type="button" class="btn btn-primary" style="width:100%"
                onclick="closeModal('modal-new-teacher');openModal('modal-ach-teacher')">
          ← Вернуться и выбрать преподавателя
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function resetNewTeacherModal() {
  document.getElementById('nt-fullname').value        = '';
  document.getElementById('nt-password').value        = '';
  document.getElementById('nt-error').style.display   = 'none';
  document.getElementById('nt-success').style.display = 'none';
  document.getElementById('nt-form').style.display    = '';
  document.getElementById('nt-done').style.display    = 'none';
  const btn = document.getElementById('nt-save-btn');
  btn.disabled    = false;
  btn.textContent = '✅ Создать преподавателя';
}

function saveNewTeacher() {
  const fullname = document.getElementById('nt-fullname').value.trim();
  const password = document.getElementById('nt-password').value;
  const errBox   = document.getElementById('nt-error');
  const sucBox   = document.getElementById('nt-success');
  const saveBtn  = document.getElementById('nt-save-btn');

  errBox.style.display = 'none';
  sucBox.style.display = 'none';

  if (!fullname || password.length < 8) {
    errBox.textContent   = '⚠️ Укажите ФИО и пароль (минимум 8 символов).';
    errBox.style.display = '';
    return;
  }

  saveBtn.disabled    = true;
  saveBtn.textContent = 'Сохранение…';

  // Генерируем уникальный email из ФИО + timestamp
  const slug  = fullname.toLowerCase().replace(/\s+/g, '.').replace(/[^a-zа-яё.]/gi, '') + '.' + Date.now();
  const email = slug + '@svgtk.local';

  const fd = new FormData();
  fd.append('full_name', fullname);
  fd.append('email',     email);
  fd.append('password',  password);
  fd.append('role',      'teacher');

  fetch('<?= SITE_URL ?>/actions/user_save.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const sel    = document.getElementById('teacher-select');
        const newOpt = document.createElement('option');
        newOpt.value = data.id;
        newOpt.text  = data.full_name;
        sel.add(newOpt);
        sel.selectedIndex = sel.options.length - 1;

        sucBox.textContent   = '✅ Преподаватель «' + data.full_name + '» создан и выбран! Нажмите «Вернуться».';
        sucBox.style.display = '';
        document.getElementById('nt-form').style.display = 'none';
        document.getElementById('nt-done').style.display = '';
      } else {
        errBox.textContent   = '⚠️ ' + (data.error ?? 'Ошибка сохранения');
        errBox.style.display = '';
        saveBtn.disabled    = false;
        saveBtn.textContent = '✅ Создать преподавателя';
      }
    })
    .catch(() => {
      errBox.textContent   = '⚠️ Ошибка соединения с сервером';
      errBox.style.display = '';
      saveBtn.disabled    = false;
      saveBtn.textContent = '✅ Создать преподавателя';
    });
}

document.getElementById('modal-new-teacher').addEventListener('click', function(e) {
  if (e.target === this) {
    closeModal('modal-new-teacher');
    openModal('modal-ach-teacher');
  }
});
</script>

<?php endif; ?>
<?php endif; ?>

<?php
function achievementFields(): string {
    return '
    <div class="form-group">
      <label class="form-label">Название достижения</label>
      <input type="text" name="title" class="form-control" placeholder="Олимпиада по программированию" required>
    </div>
    <div class="form-group">
      <label class="form-label">Описание</label>
      <textarea name="description" class="form-control" rows="2" placeholder="Краткое описание…"></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Категория</label>
        <select name="category" class="form-control" required>
          <option value="olympiad">Олимпиада</option>
          <option value="conference">Конференция</option>
          <option value="sport">Спорт</option>
          <option value="art">Творчество</option>
          <option value="science">Наука</option>
          <option value="other">Другое</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Уровень</label>
        <select name="level" class="form-control" required>
          <option value="college">Колледж</option>
          <option value="city">Город</option>
          <option value="regional">Регион</option>
          <option value="national">Республика</option>
          <option value="international">Международный</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Место (1, 2, 3…)</label>
        <input type="number" name="place" class="form-control" min="1" max="99" placeholder="Необязательно">
      </div>
      <div class="form-group">
        <label class="form-label">Дата</label>
        <input type="date" name="date_event" class="form-control">
      </div>
    </div>';
}
?>

<?php require_once 'includes/footer.php'; ?>