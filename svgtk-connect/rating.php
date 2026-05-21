<?php
require_once 'includes/header.php';
$allRating = getRating(100);
?>
<div class="page-header anim-fade">
  <div>
    <div class="page-header-title">Рейтинг студентов</div>
    <div class="page-header-sub">Топ по достижениям, сертификатам и участию</div>
  </div>
  <a href="<?= SITE_URL ?>/export.php?type=rating" class="btn btn-secondary">⬇ Экспорт CSV</a>
</div>

<div class="card anim-fade">
  <div class="table-wrap">
    <table class="data-table rating-table">
      <thead>
        <tr><th>#</th><th>Имя</th><th>Достижения</th><th>Сертификаты</th><th>Мероприятия</th><th>Баллы</th></tr>
      </thead>
      <tbody>
      <?php foreach ($allRating as $i => $r): ?>
        <tr class="rank-<?= $i+1 ?> anim-fade" style="animation-delay:<?= $i*0.04 ?>s">
          <td>
            <span class="rank-badge <?= $i===0?'r1':($i===1?'r2':($i===2?'r3':'')) ?>">
              <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : ($i+1) ?>
            </span>
          </td>
          <td><a href="<?= SITE_URL ?>/profile.php?id=<?= $r['user_id'] ?>"><?= h($r['full_name']) ?></a></td>
          <td><span class="badge badge-green"><?= $r['achievements_count'] ?></span></td>
          <td><span class="badge badge-amber"><?= $r['certificates_count'] ?></span></td>
          <td><span class="badge badge-blue"><?= $r['events_count'] ?></span></td>
          <td><strong style="font-family:var(--font-display);font-size:var(--text-lg)"><?= $r['total_points'] ?></strong></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card anim-fade" style="margin-top:var(--space-5);padding:var(--space-5)">
  <strong>Формула рейтинга:</strong>
  <span style="color:var(--color-text-muted);font-size:var(--text-sm)"> Достижение × 30 + Сертификат × 20 + Мероприятие × 10</span>
</div>

<?php require_once 'includes/footer.php'; ?>