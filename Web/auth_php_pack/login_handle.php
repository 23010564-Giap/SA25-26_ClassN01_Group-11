<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../backend/db.php';

function fail($msg) {
  $_SESSION['login_error'] = $msg;
  $next = $_POST['next'] ?? ($_GET['next'] ?? '');
  $qs = $next ? ('?next='.urlencode($next)) : '';
  header('Location: login.php'.$qs);
  exit;
}


if (!csrf_validate($_POST['_csrf'] ?? '')) {
  fail('Invalid session, please try again.');
}


$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$next     = $_POST['next'] ?? ($_GET['next'] ?? '');

if ($username === '' || $password === '') {
  fail('Please fill in all required fields.');
}


$hasMaSV = false;
$chk = $conn->prepare("
  SELECT COUNT(*) c FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='maSV'
");
$chk->execute();
$hasMaSV = (int)$chk->get_result()->fetch_assoc()['c'] > 0;
$chk->close();


$sql = $hasMaSV
  ? "SELECT id, username, password_hash, role, is_active, maSV
     FROM users WHERE username=? LIMIT 1"
  : "SELECT id, username, password_hash, role, is_active, NULL AS maSV
     FROM users WHERE username=? LIMIT 1";

$st = $conn->prepare($sql);
$st->bind_param('s', $username);
$st->execute();
$user = $st->get_result()->fetch_assoc();
$st->close();

if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
  fail('Thong tin ang nhap khong ung hoac tai khoan bi khoa.');
}


session_regenerate_id(true);
$_SESSION['uid']       = (int)$user['id'];
$_SESSION['uname']     = $user['username'];
$_SESSION['urole']     = $user['role'];
$_SESSION['logged_in'] = true;

if ($hasMaSV && !empty($user['maSV'])) {
  $_SESSION['maSV'] = $user['maSV'];
} else {
  $_SESSION['maSV'] = $user['username'];
}


if ($next && strpos($next, "\n")===false && strpos($next, "\r")===false) {
  header('Location: ' . $next);
} else {
  header('Location: /phenikaa_manager/frontend/index.php');
}
exit;