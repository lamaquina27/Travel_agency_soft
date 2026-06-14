<?php
// ====================================================================
// ARCHIVO: classes/OperadorManager.php
// Mantiene sincronizada la tabla `operadores` (pool de asignables a
// roomings) con el rol del usuario. Un usuario con role='operador'
// SIEMPRE tiene su fila en `operadores`; si deja de serlo, se elimina
// (y por FK ON DELETE CASCADE se limpian sus asignaciones a roomings).
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class OperadorManager
{
    /**
     * Sincroniza la pertenencia de un usuario al pool de operadores
     * de su agencia, según su rol.
     *
     * @param Database $db
     * @param int      $userId
     * @param int      $agenciaId
     * @param string   $role  Rol efectivo del usuario tras crear/editar
     * @return void
     */
    public static function sync(Database $db, int $userId, int $agenciaId, string $role): void
    {
        if (!$userId || !$agenciaId) {
            return;
        }

        if ($role === 'operador') {
            // Garantizar su fila (UNIQUE agencia_id+usuario_id evita duplicados)
            $db->query(
                "INSERT IGNORE INTO operadores (agencia_id, usuario_id) VALUES (?, ?)",
                [$agenciaId, $userId]
            );
        } else {
            // Ya no es operador: quitarlo del pool (cascade limpia asignacion_operadores)
            $db->query(
                "DELETE FROM operadores WHERE usuario_id = ? AND agencia_id = ?",
                [$userId, $agenciaId]
            );
        }
    }
}
