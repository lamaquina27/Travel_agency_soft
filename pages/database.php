<?php
// =====================================
// ARCHIVO: config/database.php - VERSIÓN CORREGIDA
// =====================================

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Cargar variables de entorno desde .env
        $this->loadEnv();
        
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'travelag_travel_agency';
        $username = $_ENV['DB_USER'] ?? 'travelag_admin';
        $password = $_ENV['DB_PASS'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    private function loadEnv() {
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                        putenv(sprintf('%s=%s', $name, $value));
                        $_ENV[$name] = $value;
                        $_SERVER[$name] = $value;
                    }
                }
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            error_log("Executing SQL: " . $sql);
            error_log("With params: " . print_r($params, true));
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            error_log("SQL was: " . $sql);
            error_log("Params were: " . print_r($params, true));
            throw new Exception("Error en la consulta de base de datos");
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert($table, $data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            error_log("Insert SQL: " . $sql);
            error_log("Insert data: " . print_r($data, true));
            
            $stmt = $this->query($sql, $data);
            return $this->connection->lastInsertId();
        } catch(Exception $e) {
            error_log("Insert error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update($table, $data, $where, $whereParams = []) {
    try {
        if (empty($data)) {
            error_log("Update: No data to update");
            return 0;
        }
        
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "`{$key}` = ?";
            $params[] = $value;
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
        
        // Agregar parámetros del WHERE al final
        $allParams = array_merge($params, $whereParams);
        
        error_log("Update SQL: " . $sql);
        error_log("Update params: " . print_r($allParams, true));
        
        $stmt = $this->query($sql, $allParams);
        $rowCount = $stmt->rowCount();
        
        error_log("Rows affected: " . $rowCount);
        
        return $rowCount;
    } catch(Exception $e) {
        error_log("Update error: " . $e->getMessage());
        error_log("Update SQL was: " . ($sql ?? 'N/A'));
        error_log("Update params were: " . print_r($allParams ?? [], true));
        throw $e;
    }
}
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }
    
    // ✅ MÉTODO ESPECÍFICO PARA VERIFICAR TABLAS
    public function tableExists($tableName) {
        try {
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
            $result = $this->fetch($sql, [$tableName]);
            return !empty($result);
        } catch(Exception $e) {
            error_log("Table exists check error: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ MÉTODO PARA OBTENER COLUMNAS DE TABLA
    public function getTableColumns($tableName) {
        try {
            $sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
                    FROM information_schema.columns 
                    WHERE table_schema = DATABASE() AND table_name = ?
                    ORDER BY ordinal_position";
            return $this->fetchAll($sql, [$tableName]);
        } catch(Exception $e) {
            error_log("Get table columns error: " . $e->getMessage());
            return [];
        }
    }
}