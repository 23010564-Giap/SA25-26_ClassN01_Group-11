<?php
$password = $_GET['p'] ?? '';
if ($password === '') {
  echo "Usage: generate_password_hash.php?p=YourPassword";
  exit;
}
$hash = password_hash($password, PASSWORD_DEFAULT);
header('Content-Type: text/plain; charset=utf-8');
echo $hash;