<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/csrf.php';

$next = $_GET['next'] ?? '';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{background:#f8fafc;font-family:system-ui, -apple-system, Segoe UI, Roboto}
    .card{max-width:420px;margin:7rem auto;background:#fff;padding:24px;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,.08)}
    h2{margin:0 0 16px}
    .danger{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;margin-bottom:12px}
    input[type="text"],input[type="password"]{width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;margin-bottom:10px}
    button{width:100%;padding:12px;border:none;border-radius:10px;background:#2563eb;color:#fff;font-weight:600;cursor:pointer}
    button:hover{background:#1d4ed8}
    label{display:flex;gap:8px;align-items:center;margin:6px 0 12px}
    .muted{font-size:12px;color:#6b7280;margin-top:10px}
  </style>
</head>
<body>
  <div class="card">
    <h2>Login</h2>

    <?php if (!empty($_SESSION['login_error'])): ?>
      <div class="danger"><?=$_SESSION['login_error']; unset($_SESSION['login_error']);?></div>
    <?php endif; ?>

    <form method="post" action="login_handle.php<?= $next ? ('?next='.urlencode($next)) : '' ?>">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">
      <input type="hidden" name="next" value="<?= htmlspecialchars($next,ENT_QUOTES,'UTF-8') ?>">
      <input type="text"     name="username" placeholder="Username" autocomplete="username" required>
      <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
      <label><input type="checkbox" name="remember" value="1"> Remember me</label>
      <button type="submit">Login</button>
    </form>

    <div class="muted">N/A<b>admin / Admin@1234!</b>N/A<b>sv001 / Student@2025</b>
    </div>
  </div>
</body>
</html>