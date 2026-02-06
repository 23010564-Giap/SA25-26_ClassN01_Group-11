<?php

function dispatch($route) {

    // ✅ DEFAULT ROUTE
    if ($route === '' || $route === 'home') {
        header('Location: index.php?r=courses');
        exit;
    }

    switch ($route) {

        case 'courses':
            requireRole(['admin','viewer']);
            require __DIR__ . '/../frontend/monhoc.php';
            break;

        case 'courses.edit':
            requireRole(['admin','viewer']);
            require __DIR__ . '/../frontend/monhoc.php';
            break;

        default:
            http_response_code(404);
            echo 'Route not found';
    }
}