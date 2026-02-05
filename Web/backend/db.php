<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'phenikaa_manager';
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) { http_response_code(500); die('Connection failed: ' . $mysqli->connect_error); }
$mysqli->set_charset('utf8mb4');
$conn = $mysqli;
?>