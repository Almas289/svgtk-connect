<?php
require_once 'includes/header.php';

$pdo = getPDO();

// All students for the dropdown
$allStudents = $pdo->query("SELECT u.id, u.full_name, g.name AS group_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN groups_list g ON s.group_id = g.id
    ORDER BY u.full_name")->fetchAll();

// Filters
$filterStudent = (int)($_GET['student_id'] ?? 0);
$filterSearch  = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterStudent) { $where[] = 'c.user_id = ?'; $params[] = $filterStudent; }
if ($filterSearch)  { $where[] = '(c.title LIKE ? OR c.issuer LIKE ?)'; $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; }

$sql = "SELECT c.*, u.full_name, g.name AS group_name
    FROM certificates c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN students s ON s.user_id = c.user_id
    LEFT JOIN groups_list g ON s.group_id = g.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.issue_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$certs = $stmt->fetchAll();

$totalCount = count($certs);
?>

<div class="page-header anim-fade">
  <div>
    <div class="page-header-title">📜 Сертификаты</div>
    <div class="page-header-sub">Все сертификаты студентов · найдено: <?= $totalCount ?></div>
  </div>
  <?php if (in_array($role, ['admin','teacher'])): ?>
  <button class="btn btn-primary" onclick="openModal('modal-cert-add')">+ Добавить сертификат</button>
  <?php endif; ?>
</div>

<!-- Filters -->
<div class="card anim-fade" style="padding:var(--space-4);margin-bottom:var(--space-5)">
  <form method="GET" style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="margin:0;flex:1;min-width:180px">
      <label class="form-label">Поиск</label>
      <input type="text" name="q" class="form-control" placeholder="Название или организация…" value="<?= h($filterSearch) ?>">
    </div>
    <div class="form-group" style="margin:0;flex:1;min-width:180px">
      <label class="form-label">Студент</label>
      <select name="student_id" class="form-control">
        <option value="">Все студенты</option>
        <?php foreach ($allStudents as $st): ?>
          <option value="<?= $st['id'] ?>" <?= $filterStudent===$st['id']?'selected':'' ?>><?= h($st['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:var(--space-2)">
      <button type="submit" class="btn btn-primary">Найти</button>
      <a href="certificates.php" class="btn btn-secondary">Сбросить</a>
    </div>
  </form>
</div>

<!-- Table -->
<div class="card anim-fade">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Студент</th>
          <th>Группа</th>
          <th>Название</th>
          <th>Организация</th>
          <th>Дата выдачи</th>
          <th>Действует до</th>
          <?php if (in_array($role,['admin','teacher'])): ?><th>Действия</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($certs)): ?>
        <tr><td colspan="8" style="text-align:center;padding:var(--space-10);color:var(--color-text-muted)">Сертификатов не найдено</td></tr>
      <?php else: ?>
      <?php foreach ($certs as $i => $c): ?>
        <tr class="anim-fade" style="animation-delay:<?= $i*0.03 ?>s">
          <td><span class="badge badge-gray"><?= $i+1 ?></span></td>
          <td><a href="<?= SITE_URL ?>/profile.php?id=<?= $c['user_id'] ?>"><?= h($c['full_name']) ?></a></td>
          <td><?= h($c['group_name'] ?? '—') ?></td>
          <td><strong><?= h($c['title']) ?></strong></td>
          <td><?= h($c['issuer'] ?? '—') ?></td>
          <td><?= h($c['issue_date'] ?? '—') ?></td>
          <td>
            <?php if ($c['expiry_date']): ?>
              <?php $expired = strtotime($c['expiry_date']) < time(); ?>
              <span class="badge badge-<?= $expired?'red':'green' ?>"><?= h($c['expiry_date']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <?php if (in_array($role,['admin','teacher'])): ?>
          <td>
            <a href="<?= SITE_URL ?>/actions/cert_delete.php?id=<?= $c['id'] ?>&user_id=<?= $c['user_id'] ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Удалить сертификат?')">🗑</a>
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
<!-- Modal: Add certificate -->
<div class="modal-overlay" id="modal-cert-add">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Добавить сертификат</div>
      <button class="modal-close" onclick="closeModal('modal-cert-add')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= SITE_URL ?>/actions/cert_save.php">
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
          <label class="form-label">Название сертификата</label>
          <input type="text" name="title" class="form-control" placeholder="Например: Веб-разработка на Python" required>
        </div>
        <div class="form-group">
          <label class="form-label">Организация / выдал</label>
          <input type="text" name="issuer" class="form-control" placeholder="Coursera, Stepik…">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Дата выдачи</label>
            <input type="date" name="issue_date" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Действует до</label>
            <input type="date" name="expiry_date" class="form-control">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Сохранить</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-cert-add')">Отмена</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
