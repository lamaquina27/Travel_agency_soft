<?php
// ====================================================================
// ARCHIVO: classes/SubAgenciaManager.php
// Mantiene sincronizada la tabla `config_sub_agencias` (marca propia de
// cada subagencia B2B) con el rol del usuario. Un usuario con
// role='subagencia' SIEMPRE tiene su fila en `config_sub_agencias`.
// Espeja el patrón de OperadorManager::sync().
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class SubAgenciaManager
{
    /**
     * Garantiza la fila de configuración/marca de una subagencia.
     *
     * @param Database    $db
     * @param int         $userId
     * @param string      $role    Rol efectivo del usuario tras crear/editar
     * @param string|null $nombre  Nombre comercial (fallback: se deja como viene en BD)
     * @return void
     */
    public static function sync(Database $db, int $userId, string $role, ?string $nombre = null): void
    {
        if (!$userId) {
            return;
        }

        if ($role !== 'subagencia') {
            // No es subagencia: no tocamos nada. Si el usuario se elimina,
            // el ON DELETE CASCADE de config_sub_agencias limpia su fila.
            // No borramos al cambiar de rol para no perder su marca.
            return;
        }

        $nombre = $nombre !== null ? trim($nombre) : '';

        // ¿Ya existe la fila? (UNIQUE user_id)
        $existe = $db->fetch(
            "SELECT id FROM config_sub_agencias WHERE user_id = ?",
            [$userId]
        );

        if ($existe) {
            // Sólo actualizamos el nombre si se envió uno no vacío.
            if ($nombre !== '') {
                $db->update('config_sub_agencias', ['nombre' => $nombre], 'user_id = ?', [$userId]);
            }
        } else {
            $db->insert('config_sub_agencias', [
                'user_id' => $userId,
                'nombre'  => $nombre !== '' ? $nombre : null,
            ]);
        }
    }
}
