<?php
require_once 'includes/header.php';

$viewId = isset($_GET['id']) ? (int)$_GET['id'] : $user['id'];
$pdo    = getPDO();
$stmt   = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$viewId]);
$viewUser = $stmt->fetch();
if (!$viewUser) { echo '<p>Пользователь не найден.</p>'; require_once 'includes/footer.php'; exit; }

$studentInfo = getStudentByUserId($viewId);
$achievements = getAchievements($viewId);
$certs        = getCertificates($viewId);
$events       = getEvents($viewId);
$grades       = $studentInfo ? getGrades($studentInfo['id']) : [];
$absences     = $studentInfo ? getAbsences($studentInfo['id']) : [];

$initials = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w,0,1)), array_slice(explode(' ',$viewUser['full_name']),0,2)));
$roleMap  = ['admin'=>'Администратор','teacher'=>'Преподаватель','student'=>'Студент'];
?>

<div class="page-header anim-fade">
  <a href="javascript:history.back()" class="btn btn-secondary btn-sm">← Назад</a>
  <a href="<?= SITE_URL ?>/export.php?type=resume&id=<?= $viewId ?>" class="btn btn-success">📄 Экспорт резюме</a>
</div>

<div class="profile-hero anim-fade">
  <div class="profile-avatar"><?= $initials ?></div>
  <div>
    <div class="profile-name"><?= h($viewUser['full_name']) ?></div>
    <div class="profile-meta">
      <?= $roleMap[$viewUser['role']] ?> · <?= h($viewUser['email']) ?>
      <?php if ($studentInfo): ?> · Группа: <strong><?= h($studentInfo['group_name']) ?></strong><?php endif; ?>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid anim-fade">
  <div class="stat-card">
    <div class="stat-icon green"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg></div>
    <div><div class="stat-number"><?= count($achievements) ?></div><div class="stat-label">Достижений</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
    <div><div class="stat-number"><?= count($certs) ?></div><div class="stat-label">Сертификатов</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
    <div><div class="stat-number"><?= count($events) ?></div><div class="stat-label">Мероприятий</div></div>
  </div>
  <?php if (!empty($grades)):
    $avg = round(array_sum(array_column($grades,'grade')) / count($grades), 1);
  ?>
  <div class="stat-card">
    <div class="stat-icon purple"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg></div>
    <div><div class="stat-number"><?= $avg ?></div><div class="stat-label">Средний балл</div></div>
  </div>
  <?php endif; ?>
</div>

<!-- Tabs -->
<div data-tabs>
  <div class="tabs">
    <button class="tab-btn active" data-tab="tab-ach">🏆 Достижения</button>
    <button class="tab-btn" data-tab="tab-cert">📜 Сертификаты</button>
    <button class="tab-btn" data-tab="tab-events">📅 Мероприятия</button>
    <?php if ($studentInfo): ?>
    <button class="tab-btn" data-tab="tab-grades">📊 Оценки</button>
    <button class="tab-btn" data-tab="tab-absences">⚠️ Отсутствия</button>
    <?php endif; ?>
  </div>

  <div class="tab-content active" id="tab-ach">
    <?php if (in_array($role, ['admin','teacher'])): ?>
    <div style="margin-bottom:var(--space-4)">
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-ach')">+ Добавить достижение</button>
    </div>
    <?php endif; ?>
    <?php if (empty($achievements)): ?>
      <div style="text-align:center;padding:var(--space-10);color:var(--color-text-muted)">Достижений пока нет.</div>
    <?php else: ?>
    <div class="achievement-grid">
    <?php foreach ($achievements as $a): ?>
      <div class="achievement-item">
        <div class="achievement-item-header">
          <div class="achievement-title"><?= h($a['title']) ?></div>
          <?php if (in_array($role, ['admin','teacher'])): ?>
          <a href="<?= SITE_URL ?>/actions/achievement_delete.php?id=<?= $a['id'] ?>&user_id=<?= $viewId ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить?')">🗑</a>
          <?php endif; ?>
        </div>
        <p style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= h($a['description'] ?? '') ?></p>
        <div class="achievement-meta">
          <span class="badge badge-blue"><?= categoryLabel($a['category']) ?></span>
          <span class="badge badge-purple"><?= levelLabel($a['level']) ?></span>
          <?php if ($a['place']): ?><span class="badge badge-amber">🥇 <?= h($a['place']) ?> место</span><?php endif; ?>
          <?php if ($a['date_event']): ?><span class="badge badge-gray"><?= h($a['date_event']) ?></span><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-content" id="tab-cert">
    <?php if (in_array($role, ['admin','teacher'])): ?>
    <div style="margin-bottom:var(--space-4)">
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-cert')">+ Добавить сертификат</button>
    </div>
    <?php endif; ?>
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Название</th><th>Организация</th><th>Дата</th><?php if (in_array($role,['admin','teacher'])): ?><th>Действия</th><?php endif; ?></tr></thead>
          <tbody>
          <?php foreach ($certs as $c): ?>
            <tr>
              <td><?= h($c['title']) ?></td>
              <td><?= h($c['issuer'] ?? '—') ?></td>
              <td><?= h($c['issue_date'] ?? '—') ?></td>
              <?php if (in_array($role,['admin','teacher'])): ?>
              <td><a href="<?= SITE_URL ?>/actions/cert_delete.php?id=<?= $c['id'] ?>&user_id=<?= $viewId ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить?')">🗑</a></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($certs)): ?><tr><td colspan="4" style="text-align:center;color:var(--color-text-muted)">Нет сертификатов</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-content" id="tab-events">
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Мероприятие</th><th>Дата</th><th>Роль</th><th>Результат</th></tr></thead>
          <tbody>
          <?php foreach ($events as $e): ?>
            <tr><td><?= h($e['title']) ?></td><td><?= h($e['event_date'] ?? '—') ?></td><td><?= h($e['role_event']) ?></td><td><?= h($e['result'] ?? '—') ?></td></tr>
          <?php endforeach; ?>
          <?php if (empty($events)): ?><tr><td colspan="4" style="text-align:center;color:var(--color-text-muted)">Участий нет</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($studentInfo): ?>
  <div class="tab-content" id="tab-grades">
    <?php if (in_array($role, ['admin','teacher'])): ?>
    <div style="margin-bottom:var(--space-4)">
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-grade')">+ Добавить оценку</button>
    </div>
    <?php endif; ?>
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Предмет</th><th>Оценка</th><th>Период</th><th>Преподаватель</th><?php if (in_array($role,['admin','teacher'])): ?><th>Действия</th><?php endif; ?></tr></thead>
          <tbody>
          <?php foreach ($grades as $g):
            $cls = $g['grade']>=5?'green':($g['grade']>=4?'blue':($g['grade']>=3?'amber':'red'));
          ?>
            <tr>
              <td><?= h($g['subject']) ?></td>
              <td><span class="badge badge-<?= $cls ?>"><?= $g['grade'] ?></span></td>
              <td><?= h($g['period'] ?? '—') ?></td>
              <td><?= h($g['teacher_name'] ?? '—') ?></td>
              <?php if (in_array($role,['admin','teacher'])): ?>
              <td><a href="<?= SITE_URL ?>/actions/grade_delete.php?id=<?= $g['id'] ?>&user_id=<?= $viewId ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить?')">🗑</a></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($grades)): ?><tr><td colspan="5" style="text-align:center;color:var(--color-text-muted)">Нет оценок</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-content" id="tab-absences">
    <?php if (in_array($role, ['admin','teacher'])): ?>
    <div style="margin-bottom:var(--space-4)">
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-absence')">+ Отметить отсутствие</button>
    </div>
    <?php endif; ?>
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Дата</th><th>Предмет</th><th>Причина</th><th>Часов</th><?php if (in_array($role,['admin','teacher'])): ?><th>Действия</th><?php endif; ?></tr></thead>
          <tbody>
          <?php foreach ($absences as $ab):
            $cls = $ab['reason']==='sick'?'blue':($ab['reason']==='excused'?'amber':'red');
          ?>
            <tr>
              <td><?= h($ab['absent_date']) ?></td>
              <td><?= h($ab['subject'] ?? '—') ?></td>
              <td><span class="badge badge-<?= $cls ?>"><?= absenceReasonLabel($ab['reason']) ?></span></td>
              <td><?= $ab['hours'] ?></td>
              <?php if (in_array($role,['admin','teacher'])): ?>
              <td><a href="<?= SITE_URL ?>/actions/absence_delete.php?id=<?= $ab['id'] ?>&user_id=<?= $viewId ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить?')">🗑</a></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($absences)): ?><tr><td colspan="5" style="text-align:center;color:var(--color-text-muted)">Пропусков нет</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modals for adding data -->
<?php if (in_array($role, ['admin','teacher'])): ?>
<!-- Achievement -->
<div class="modal-overlay" id="modal-ach">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Добавить достижение</div><button class="modal-close" onclick="closeModal('modal-ach')">✕</button></div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/achievement_save.php">
        <input type="hidden" name="user_id" value="<?= $viewId ?>">
        <div class="form-group"><label class="form-label">Название</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Описание</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Категория</label>
            <select name="category" class="form-control">
              <option value="olympiad">Олимпиада</option><option value="conference">Конференция</option>
              <option value="sport">Спорт</option><option value="art">Творчество</option>
              <option value="science">Наука</option><option value="other">Другое</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Уровень</label>
            <select name="level" class="form-control">
              <option value="college">Колледж</option><option value="city">Город</option>
              <option value="regional">Регион</option><option value="national">Республика</option>
              <option value="international">Международный</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Дата</label><input type="date" name="date_event" class="form-control"></div>
          <div class="form-group"><label class="form-label">Место/Результат</label><input type="text" name="place" class="form-control" placeholder="1, 2, 3 или участие"></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-ach')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Certificate -->
<div class="modal-overlay" id="modal-cert">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Добавить сертификат</div><button class="modal-close" onclick="closeModal('modal-cert')">✕</button></div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/cert_save.php">
        <input type="hidden" name="user_id" value="<?= $viewId ?>">
        <div class="form-group"><label class="form-label">Название</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Организация</label><input type="text" name="issuer" class="form-control"></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Дата выдачи</label><input type="date" name="issue_date" class="form-control"></div>
          <div class="form-group"><label class="form-label">Действует до</label><input type="date" name="expiry_date" class="form-control"></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-cert')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Grade -->
<?php if ($studentInfo): ?>
<div class="modal-overlay" id="modal-grade">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Добавить оценку</div><button class="modal-close" onclick="closeModal('modal-grade')">✕</button></div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/grade_save.php">
        <input type="hidden" name="student_id" value="<?= $studentInfo['id'] ?>">
        <input type="hidden" name="user_id" value="<?= $viewId ?>">
        <div class="form-group"><label class="form-label">Предмет</label><input type="text" name="subject" class="form-control" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Оценка</label>
            <select name="grade" class="form-control">
              <option value="5">5 — Отлично</option><option value="4">4 — Хорошо</option>
              <option value="3">3 — Удовлетворительно</option><option value="2">2 — Неудовлетворительно</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Период</label><input type="text" name="period" class="form-control" placeholder="2024 осень"></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-grade')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Absence -->
<div class="modal-overlay" id="modal-absence">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Отметить отсутствие</div><button class="modal-close" onclick="closeModal('modal-absence')">✕</button></div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/absence_save.php">
        <input type="hidden" name="student_id" value="<?= $studentInfo['id'] ?>">
        <input type="hidden" name="user_id" value="<?= $viewId ?>">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Дата</label><input type="date" name="absent_date" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label class="form-label">Предмет</label><input type="text" name="subject" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Причина</label>
            <select name="reason" class="form-control">
              <option value="no_reason">Без причины</option><option value="sick">По болезни</option><option value="excused">Уважительная</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Часов</label><input type="number" name="hours" class="form-control" value="2" min="1" max="8"></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-absence')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>