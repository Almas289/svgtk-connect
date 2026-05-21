<?php
require_once 'includes/header.php';

$pdo = getPDO();

// Filters
$filterSearch = trim($_GET['q'] ?? '');
$filterYear   = (int)($_GET['year'] ?? 0);

$where  = ['1=1'];
$params = [];
if ($filterSearch) { $where[] = 'e.title LIKE ?'; $params[] = "%$filterSearch%"; }
if ($filterYear)   { $where[] = 'YEAR(e.event_date) = ?'; $params[] = $filterYear; }

$sql = "SELECT e.*,
    (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
    FROM events e
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.event_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Years for filter
$years = $pdo->query("SELECT DISTINCT YEAR(event_date) AS yr FROM events WHERE event_date IS NOT NULL ORDER BY yr DESC")->fetchAll(PDO::FETCH_COLUMN);

// All students for participant modal
$allStudents = $pdo->query("SELECT u.id, u.full_name, g.name AS group_name
    FROM students s JOIN users u ON s.user_id=u.id
    LEFT JOIN groups_list g ON s.group_id=g.id
    ORDER BY u.full_name")->fetchAll();
?>

<div class="page-header anim-fade">
  <div>
    <div class="page-header-title">📅 Мероприятия</div>
    <div class="page-header-sub">Все мероприятия колледжа · найдено: <?= count($events) ?></div>
  </div>
  <?php if (in_array($role, ['admin','teacher'])): ?>
  <button class="btn btn-primary" onclick="openModal('modal-event-add')">+ Добавить мероприятие</button>
  <?php endif; ?>
</div>

<!-- Filters -->
<div class="card anim-fade" style="padding:var(--space-4);margin-bottom:var(--space-5)">
  <form method="GET" style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="margin:0;flex:2;min-width:180px">
      <label class="form-label">Поиск по названию</label>
      <input type="text" name="q" class="form-control" placeholder="Название мероприятия…" value="<?= h($filterSearch) ?>">
    </div>
    <div class="form-group" style="margin:0;flex:1;min-width:120px">
      <label class="form-label">Год</label>
      <select name="year" class="form-control">
        <option value="">Все годы</option>
        <?php foreach ($years as $yr): ?>
          <option value="<?= $yr ?>" <?= $filterYear==$yr?'selected':'' ?>><?= $yr ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:var(--space-2)">
      <button type="submit" class="btn btn-primary">Найти</button>
      <a href="events.php" class="btn btn-secondary">Сбросить</a>
    </div>
  </form>
</div>

<!-- Events grid -->
<?php if (empty($events)): ?>
  <div class="card anim-fade" style="text-align:center;padding:var(--space-12);color:var(--color-text-muted)">
    Мероприятий не найдено
  </div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:var(--space-5)">
<?php foreach ($events as $i => $e): ?>
  <div class="card anim-fade" style="animation-delay:<?= $i*0.05 ?>s;overflow:visible">
    <div style="padding:var(--space-5)">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:var(--space-3)">
        <div>
          <div style="font-size:var(--text-base);font-weight:700;color:var(--color-heading);margin-bottom:var(--space-1)"><?= h($e['title']) ?></div>
          <?php if ($e['event_date']): ?>
            <div style="font-size:var(--text-sm);color:var(--color-text-muted)">📅 <?= h($e['event_date']) ?></div>
          <?php endif; ?>
        </div>
        <span class="badge badge-blue" style="flex-shrink:0"><?= $e['participant_count'] ?> уч.</span>
      </div>

      <?php if (!empty($e['description'])): ?>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-top:var(--space-3)"><?= h($e['description']) ?></p>
      <?php endif; ?>

      <div style="display:flex;gap:var(--space-2);margin-top:var(--space-4);flex-wrap:wrap">
        <button class="btn btn-secondary btn-sm" onclick="loadParticipants(<?= $e['id'] ?>, '<?= h(addslashes($e['title'])) ?>')">
          👥 Участники
        </button>
        <?php if (in_array($role,['admin','teacher'])): ?>
        <button class="btn btn-primary btn-sm" onclick="openAddParticipant(<?= $e['id'] ?>)">+ Участник</button>
        <a href="<?= SITE_URL ?>/actions/event_delete.php?id=<?= $e['id'] ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Удалить мероприятие и всех участников?')">🗑</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal: Participants list -->
<div class="modal-overlay" id="modal-participants">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <div class="modal-title" id="participants-title">Участники</div>
      <button class="modal-close" onclick="closeModal('modal-participants')">✕</button>
    </div>
    <div class="modal-body" id="participants-body">
      <p style="color:var(--color-text-muted)">Загрузка…</p>
    </div>
  </div>
</div>

<?php if (in_array($role, ['admin','teacher'])): ?>
<!-- Modal: Add event -->
<div class="modal-overlay" id="modal-event-add">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Добавить мероприятие</div>
      <button class="modal-close" onclick="closeModal('modal-event-add')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/event_save.php">
        <div class="form-group">
          <label class="form-label">Название мероприятия</label>
          <input type="text" name="title" class="form-control" placeholder="Олимпиада по программированию" required>
        </div>
        <div class="form-group">
          <label class="form-label">Дата проведения</label>
          <input type="date" name="event_date" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Описание</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Краткое описание…"></textarea>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-event-add')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Add participant -->
<div class="modal-overlay" id="modal-participant-add">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Добавить участника</div>
      <button class="modal-close" onclick="closeModal('modal-participant-add')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/participant_save.php">
        <input type="hidden" name="event_id" id="part-event-id" value="">
        <div class="form-group">
          <label class="form-label">Студент</label>
          <select name="user_id" class="form-control" required>
            <option value="">Выберите студента</option>
            <?php foreach ($allStudents as $st): ?>
              <option value="<?= $st['id'] ?>"><?= h($st['full_name']) ?> — <?= h($st['group_name'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Роль на мероприятии</label>
          <input type="text" name="role_event" class="form-control" placeholder="Участник, Победитель, Организатор…">
        </div>
        <div class="form-group">
          <label class="form-label">Результат</label>
          <input type="text" name="result" class="form-control" placeholder="1 место, Диплом…">
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Добавить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-participant-add')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function loadParticipants(eventId, title) {
  document.getElementById('participants-title').textContent = '👥 ' + title;
  document.getElementById('participants-body').innerHTML = '<p style="color:var(--color-text-muted)">Загрузка…</p>';
  openModal('modal-participants');
  fetch('<?= SITE_URL ?>/actions/event_participants_list.php?event_id=' + eventId)
    .then(r => r.text())
    .then(html => { document.getElementById('participants-body').innerHTML = html; });
}
function openAddParticipant(eventId) {
  document.getElementById('part-event-id').value = eventId;
  openModal('modal-participant-add');
}
</script>

<?php require_once 'includes/footer.php'; ?>
