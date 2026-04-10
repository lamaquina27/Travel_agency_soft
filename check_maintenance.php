<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $config = $db->fetch("SELECT maintenance_mode FROM company_settings LIMIT 1");
    
    echo json_encode([
        'maintenance_mode' => $config ? (bool)$config['maintenance_mode'] : false
    ]);
} catch(Exception $e) {
    echo json_encode(['maintenance_mode' => false]);
}
?>