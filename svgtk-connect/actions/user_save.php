<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'teacher';

    if (!$full_name || !$email || strlen($password) < 8) {
        echo json_encode([
            'success' => false,
            'error'   => 'Заполните все поля'
        ]);
        exit;
    }

    // Проверка email
    $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);

    if ($check->fetch()) {
        echo json_encode([
            'success' => false,
            'error'   => 'Пользователь уже существует'
        ]);
        exit;
    }

    // Сохранение
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password, role)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $full_name,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $role
    ]);

    // ID нового пользователя
    $newId = $pdo->lastInsertId();

    echo json_encode([
        'success'   => true,
        'id'        => $newId,
        'full_name' => $full_name
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}