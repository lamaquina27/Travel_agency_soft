<?php
require_once dirname(__DIR__) . '/config/app.php';
App::init();
header('Content-Type: application/json');
if (App::isLoggedIn()) {
    $_SESSION['last_activity'] = time();
    echo json_encode(['alive' => true]);
} else {
    http_response_code(401);
    echo json_encode(['alive' => false]);
}