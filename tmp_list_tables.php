<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance();
    $tables = $db->fetchAll("SHOW TABLES");
    echo json_encode($tables);
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
