<?php
// =====================================
// ARCHIVO: classes/AuditLogger.php - Sistema de Auditoría
// =====================================

require_once __DIR__ . '/../config/database.php';

class AuditLogger {
    
    /**
     * Registrar una acción en el log de auditoría
     * 
     * @param int $agencyId ID de la agencia
     * @param string $actionType Tipo de acción (created, subscription_renewed, etc.)
     * @param string $description Descripción de la acción
     * @param int|null $userId ID del usuario que realizó la acción
     * @param string|null $oldValue Valor anterior (opcional)
     * @param string|null $newValue Valor nuevo (opcional)
     * @return bool
     */
    public static function log($agencyId, $actionType, $description, $userId = null, $oldValue = null, $newValue = null) {
        try {
            $db = Database::getInstance();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            $db->query(
                "INSERT INTO agency_audit_log 
                 (agency_id, action_type, description, old_value, new_value, user_id, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $agencyId,
                    $actionType,
                    $description,
                    $oldValue,
                    $newValue,
                    $userId,
                    $ipAddress
                ]
            );
            
            return true;
            
        } catch(Exception $e) {
            error_log("Error en AuditLogger: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar creación de agencia
     */
    public static function logAgencyCreated($agencyId, $agencyName, $userId) {
        return self::log(
            $agencyId,
            'created',
            "Agencia '{$agencyName}' creada",
            $userId
        );
    }
    
    /**
     * Registrar renovación de suscripción
     */
    public static function logSubscriptionRenewed($agencyId, $oldDate, $newDate, $userId) {
        return self::log(
            $agencyId,
            'subscription_renewed',
            "Suscripción renovada",
            $userId,
            $oldDate,
            $newDate
        );
    }
    
    /**
     * Registrar cambio en máximo de usuarios
     */
    public static function logMaxUsersChanged($agencyId, $oldMax, $newMax, $userId) {
        return self::log(
            $agencyId,
            'max_users_changed',
            "Límite de usuarios cambiado de {$oldMax} a {$newMax}",
            $userId,
            (string)$oldMax,
            (string)$newMax
        );
    }
    
    /**
     * Registrar login de usuario
     */
    public static function logUserLogin($agencyId, $userName, $userId) {
        $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        return self::log(
            $agencyId,
            'user_login',
            "Usuario '{$userName}' inició sesión - Dispositivo: {$deviceInfo}",
            $userId
        );
    }
    
    /**
     * Registrar logout de usuario
     */
    public static function logUserLogout($agencyId, $userName, $userId) {
        return self::log(
            $agencyId,
            'user_logout',
            "Usuario '{$userName}' cerró sesión",
            $userId
        );
    }
    
    /**
     * Registrar modificación de datos
     */
    public static function logDataModified($agencyId, $entityType, $entityId, $description, $userId) {
        return self::log(
            $agencyId,
            'data_modified',
            "{$entityType} #{$entityId}: {$description}",
            $userId
        );
    }
    
    /**
     * Registrar uso de almacenamiento
     */
    public static function logStorageUsed($agencyId, $fileSize, $fileName, $userId) {
        $fileSizeMB = round($fileSize / (1024 * 1024), 2);
        return self::log(
            $agencyId,
            'storage_used',
            "Archivo subido: {$fileName} ({$fileSizeMB} MB)",
            $userId,
            null,
            (string)$fileSize
        );
    }
    
    /**
     * Obtener historial de auditoría de una agencia
     * 
     * @param int $agencyId
     * @param int $limit
     * @param int $offset
     * @param string|null $actionType Filtrar por tipo de acción
     * @return array
     */
    public static function getAgencyAuditLog($agencyId, $limit = 50, $offset = 0, $actionType = null) {
        try {
            $db = Database::getInstance();
            
            $sql = "SELECT aal.*, u.full_name as user_name
                    FROM agency_audit_log aal
                    LEFT JOIN users u ON aal.user_id = u.id
                    WHERE aal.agency_id = ?";
            
            $params = [$agencyId];
            
            if ($actionType) {
                $sql .= " AND aal.action_type = ?";
                $params[] = $actionType;
            }
            
            $sql .= " ORDER BY aal.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            return $db->fetchAll($sql, $params);
            
        } catch(Exception $e) {
            error_log("Error obteniendo audit log: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de auditoría
     * 
     * @param int $agencyId
     * @param string $startDate Fecha inicio (Y-m-d)
     * @param string $endDate Fecha fin (Y-m-d)
     * @return array
     */
    public static function getAuditStats($agencyId, $startDate, $endDate) {
        try {
            $db = Database::getInstance();
            
            // Contar acciones por tipo
            $stats = $db->fetchAll(
                "SELECT action_type, COUNT(*) as count
                 FROM agency_audit_log
                 WHERE agency_id = ?
                 AND DATE(created_at) BETWEEN ? AND ?
                 GROUP BY action_type
                 ORDER BY count DESC",
                [$agencyId, $startDate, $endDate]
            );
            
            // Total de logins
            $totalLogins = $db->fetch(
                "SELECT COUNT(*) as total
                 FROM agency_audit_log
                 WHERE agency_id = ?
                 AND action_type = 'user_login'
                 AND DATE(created_at) BETWEEN ? AND ?",
                [$agencyId, $startDate, $endDate]
            );
            
            // Total de almacenamiento usado
            $totalStorage = $db->fetch(
                "SELECT SUM(CAST(new_value AS UNSIGNED)) as total_bytes
                 FROM agency_audit_log
                 WHERE agency_id = ?
                 AND action_type = 'storage_used'
                 AND DATE(created_at) BETWEEN ? AND ?",
                [$agencyId, $startDate, $endDate]
            );
            
            return [
                'actions_by_type' => $stats,
                'total_logins' => $totalLogins['total'] ?? 0,
                'total_storage_bytes' => $totalStorage['total_bytes'] ?? 0,
                'total_storage_mb' => round(($totalStorage['total_bytes'] ?? 0) / (1024 * 1024), 2)
            ];
            
        } catch(Exception $e) {
            error_log("Error obteniendo estadísticas de auditoría: " . $e->getMessage());
            return [
                'actions_by_type' => [],
                'total_logins' => 0,
                'total_storage_bytes' => 0,
                'total_storage_mb' => 0
            ];
        }
    }
    
    /**
     * Limpiar logs antiguos (opcional, para mantenimiento)
     * 
     * @param int $daysToKeep Días a mantener
     * @return int Número de registros eliminados
     */
    public static function cleanOldLogs($daysToKeep = 90) {
        try {
            $db = Database::getInstance();
            $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
            
            $result = $db->query(
                "DELETE FROM agency_audit_log WHERE created_at < ?",
                [$cutoffDate]
            );
            
            return $result->rowCount();
            
        } catch(Exception $e) {
            error_log("Error limpiando logs antiguos: " . $e->getMessage());
            return 0;
        }
    }
}