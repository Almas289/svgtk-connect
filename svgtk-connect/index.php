<?php
session_start();

// --- Настройки БД ---
$host    = 'localhost';
$dbname  = 'SVGTK_Connect';
$dbuser  = 'root';
$dbpass  = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("<div style='font-family:Arial;padding:40px;color:red'>
        <h2>❌ Ошибка подключения к БД</h2>
        <p>" . $e->getMessage() . "</p>
        <p>Проверь: OSPanel запущен, база <b>SVGTK_Connect</b> существует, логин/пароль в index.php верные.</p>
    </div>");
}

// Если уже залогинен — редирект
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Введите email и пароль.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Неверный email или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Вход — СВГТК Портал</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #0f2240 0%, #1e3a5f 50%, #0284c7 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 48px 40px;
    width: 100%;
    max-width: 420px;
  }
  .logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
  }
  .logo-icon {
    width: 48px; height: 48px;
    background: #0284c7;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .logo-text { font-size: 1.1rem; font-weight: 800; color: #0f2240; line-height: 1.2; }
  .logo-sub  { font-size: 0.75rem; color: #64748b; }
  h1 { font-size: 1.75rem; font-weight: 800; color: #0f2240; margin-bottom: 6px; }
  .subtitle { font-size: 0.9rem; color: #64748b; margin-bottom: 28px; }
  .error {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 0.88rem;
    margin-bottom: 20px;
  }
  label {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 6px;
    margin-top: 16px;
  }
  input[type=email], input[type=password] {
    width: 100%;
    padding: 13px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.95rem;
    color: #1a202c;
    background: #f8fafc;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  input:focus {
    outline: none;
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2,132,199,0.15);
    background: #fff;
  }
  button[type=submit] {
    width: 100%;
    margin-top: 24px;
    padding: 14px;
    background: #0284c7;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    letter-spacing: 0.02em;
  }
  button[type=submit]:hover  { background: #0369a1; }
  button[type=submit]:active { transform: scale(0.98); }
  .hints {
    margin-top: 24px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 10px;
    padding: 14px 16px;
    font-size: 0.8rem;
    color: #475569;
    line-height: 1.8;
  }
  .hints strong { display: block; color: #0f2240; margin-bottom: 4px; font-size: 0.82rem; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">
      <svg width="28" height="28" viewBox="0 0 36 36" fill="none">
        <path d="M8 26 C8 20 14 12 18 12 C22 12 28 18 28 24" stroke="#fff" stroke-width="2.5" stroke-linecap="round" fill="none"/>
        <circle cx="18" cy="12" r="3" fill="#fff"/>
        <path d="M12 26h12" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    <div>
      <div class="logo-text">СВГТК Портал</div>
      <div class="logo-sub">Информационная система</div>
    </div>
  </div>

  <h1>Добро пожаловать</h1>
  <p class="subtitle">Введите ваши данные для входа</p>

  <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <label for="email">Email</label>
    <input
      type="email"
      id="email"
      name="email"
      placeholder="almas@svgtk.kz"
      value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
      required
      autofocus
    >

    <label for="password">Пароль</label>
    <input
      type="password"
      id="password"
      name="password"
      placeholder="Введите пароль"
      required
    >

    <button type="submit">Войти в систему →</button>
  </form>

  <div class="hints">
    <strong>🔑 Тестовые аккаунты:</strong>
    👑 almas@svgtk.kz / 12345678<br>
    📚 teacher@svgtk.kz / Teacher123!<br>
    🎓 daniyar.aliev@svgtk.kz / Student123!
  </div>
</div>
</body>
</html>
