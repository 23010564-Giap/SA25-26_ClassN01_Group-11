<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function csrf_token() {
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['_csrf'];
}

function csrf_validate($token) {
  return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$token);
}