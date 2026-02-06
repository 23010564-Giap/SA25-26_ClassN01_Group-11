<?php
function checkAuth() {
    if (empty($_SESSION['logged_in'])) {
        http_response_code(401);
        exit('Unauthorized');
    }
}

function requireRole(array $roles) {
    $role = $_SESSION['urole'] ?? 'viewer';
    if (!in_array($role, $roles)) {
        http_response_code(403);
        exit('Forbidden');
    }
}