<?php
require_once __DIR__ . '/config.php';

function getAllGroups(): array {
    return getPDO()->query("SELECT g.*, u.full_name AS teacher_name FROM groups_list g LEFT JOIN users u ON g.teacher_id=u.id ORDER BY g.name")->fetchAll();
}

function getGroupById(int $id): ?array {
    $s = getPDO()->prepare("SELECT g.*, u.full_name AS teacher_name FROM groups_list g LEFT JOIN users u ON g.teacher_id=u.id WHERE g.id=?");
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function getStudentsByGroup(int $groupId): array {
    $s = getPDO()->prepare("SELECT u.*, s.id AS student_id, s.student_num, s.phone, s.birth_date FROM students s JOIN users u ON s.user_id=u.id WHERE s.group_id=? ORDER BY u.full_name");
    $s->execute([$groupId]);
    return $s->fetchAll();
}

function getStudentByUserId(int $userId): ?array {
    $s = getPDO()->prepare("SELECT s.*, u.full_name, u.email, u.role, g.name AS group_name FROM students s JOIN users u ON s.user_id=u.id JOIN groups_list g ON s.group_id=g.id WHERE s.user_id=?");
    $s->execute([$userId]);
    return $s->fetch() ?: null;
}

function getAchievements(int $userId): array {
    $s = getPDO()->prepare("SELECT a.*, u.full_name AS added_by_name FROM achievements a LEFT JOIN users u ON a.added_by=u.id WHERE a.user_id=? ORDER BY a.date_event DESC");
    $s->execute([$userId]);
    return $s->fetchAll();
}

function getCertificates(int $userId): array {
    $s = getPDO()->prepare("SELECT * FROM certificates WHERE user_id=? ORDER BY issue_date DESC");
    $s->execute([$userId]);
    return $s->fetchAll();
}

function getEvents(int $userId): array {
    $s = getPDO()->prepare("SELECT e.*, ep.role_event, ep.result FROM events e JOIN event_participants ep ON e.id=ep.event_id WHERE ep.user_id=? ORDER BY e.event_date DESC");
    $s->execute([$userId]);
    return $s->fetchAll();
}

function getGrades(int $studentId): array {
    $s = getPDO()->prepare("SELECT g.*, u.full_name AS teacher_name FROM grades g LEFT JOIN users u ON g.teacher_id=u.id WHERE g.student_id=? ORDER BY g.created_at DESC");
    $s->execute([$studentId]);
    return $s->fetchAll();
}

function getAbsences(int $studentId): array {
    $s = getPDO()->prepare("SELECT a.*, u.full_name AS teacher_name FROM absences a LEFT JOIN users u ON a.teacher_id=u.id WHERE a.student_id=? ORDER BY a.absent_date DESC");
    $s->execute([$studentId]);
    return $s->fetchAll();
}

function getRating(int $limit = 10): array {
    return getPDO()->query("SELECT r.*, u.full_name, u.role FROM ratings r JOIN users u ON r.user_id=u.id ORDER BY r.total_points DESC LIMIT $limit")->fetchAll();
}

function recalcRating(int $userId): void {
    $pdo = getPDO();
    $a = $pdo->prepare("SELECT COUNT(*) FROM achievements WHERE user_id=?"); $a->execute([$userId]); $ac = (int)$a->fetchColumn();
    $c = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE user_id=?"); $c->execute([$userId]); $cc = (int)$c->fetchColumn();
    $e = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE user_id=?"); $e->execute([$userId]); $ec = (int)$e->fetchColumn();
    $pts = $ac * 30 + $cc * 20 + $ec * 10;
    $pdo->prepare("INSERT INTO ratings (user_id,total_points,achievements_count,certificates_count,events_count) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE total_points=VALUES(total_points),achievements_count=VALUES(achievements_count),certificates_count=VALUES(certificates_count),events_count=VALUES(events_count)")->execute([$userId,$pts,$ac,$cc,$ec]);
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES);
}

function categoryLabel(string $c): string {
    $map = ['olympiad'=>'Олимпиада','conference'=>'Конференция','sport'=>'Спорт','art'=>'Творчество','science'=>'Наука','other'=>'Другое'];
    return $map[$c] ?? $c;
}

function levelLabel(string $l): string {
    $map = ['college'=>'Колледж','city'=>'Город','regional'=>'Регион','national'=>'Республика','international'=>'Международный'];
    return $map[$l] ?? $l;
}

function absenceReasonLabel(string $r): string {
    $map = ['sick'=>'По болезни','no_reason'=>'Без причины','excused'=>'Уважительная'];
    return $map[$r] ?? $r;
}

function generateResumePDF(int $userId): string {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$userId]); $user = $stmt->fetch();
    $ach  = getAchievements($userId);
    $cert = getCertificates($userId);
    $evts = getEvents($userId);
    $html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Резюме — ' . h($user['full_name']) . '</title>';
    $html .= '<style>body{font-family:Arial,sans-serif;margin:40px;color:#222;max-width:900px}h1{color:#0284c7;border-bottom:3px solid #0284c7;padding-bottom:8px}h2{color:#0284c7;margin-top:30px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:10px;text-align:left}th{background:#f0f8ff;font-weight:bold}.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:12px;background:#e0f2fe;color:#0284c7}</style>';
    $html .= '</head><body>';
    $html .= '<h1>' . h($user['full_name']) . '</h1>';
    $html .= '<p><strong>Email:</strong> ' . h($user['email']) . ' &nbsp;|&nbsp; <strong>Дата выгрузки:</strong> ' . date('d.m.Y') . '</p>';
    $html .= '<h2>🏆 Достижения (' . count($ach) . ')</h2>';
    if ($ach) {
        $html .= '<table><tr><th>Название</th><th>Категория</th><th>Уровень</th><th>Дата</th><th>Место</th></tr>';
        foreach ($ach as $row) $html .= '<tr><td>' . h($row['title']) . '</td><td>' . categoryLabel($row['category']) . '</td><td>' . levelLabel($row['level']) . '</td><td>' . h($row['date_event'] ?? '—') . '</td><td>' . h($row['place'] ?? '—') . '</td></tr>';
        $html .= '</table>';
    } else { $html .= '<p style="color:#999">Нет данных</p>'; }
    $html .= '<h2>📜 Сертификаты (' . count($cert) . ')</h2>';
    if ($cert) {
        $html .= '<table><tr><th>Название</th><th>Организация</th><th>Дата выдачи</th><th>Действует до</th></tr>';
        foreach ($cert as $row) $html .= '<tr><td>' . h($row['title']) . '</td><td>' . h($row['issuer'] ?? '—') . '</td><td>' . h($row['issue_date'] ?? '—') . '</td><td>' . h($row['expiry_date'] ?? '—') . '</td></tr>';
        $html .= '</table>';
    } else { $html .= '<p style="color:#999">Нет данных</p>'; }
    $html .= '<h2>📅 Мероприятия (' . count($evts) . ')</h2>';
    if ($evts) {
        $html .= '<table><tr><th>Мероприятие</th><th>Дата</th><th>Роль</th><th>Результат</th></tr>';
        foreach ($evts as $row) $html .= '<tr><td>' . h($row['title']) . '</td><td>' . h($row['event_date'] ?? '—') . '</td><td>' . h($row['role_event']) . '</td><td>' . h($row['result'] ?? '—') . '</td></tr>';
        $html .= '</table>';
    } else { $html .= '<p style="color:#999">Нет данных</p>'; }
    $html .= '</body></html>';
    return $html;
}