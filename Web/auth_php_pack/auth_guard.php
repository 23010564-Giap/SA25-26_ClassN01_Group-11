<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['logged_in'])) {
  header('Location: /phenikaa_manager/auth_php_pack/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
  exit;
}