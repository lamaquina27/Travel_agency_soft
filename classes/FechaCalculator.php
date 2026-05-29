<?php
// ====================================================================
// ARCHIVO: classes/FechaCalculator.php
// Clase compartida para calcular fechas de los días del programa.
// Se usa desde dias_api.php y bonos_api.php (y cualquier otro que lo necesite).
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class FechaCalculator
{
    /**
     * Calcula la fecha real de cada día basándose en la fecha de llegada
     * y la duración de estancia acumulada.
     *
     * @param array  $dias          Array de días (cada uno debe tener 'duracion_estancia')
     * @param string $fecha_llegada Fecha de llegada del programa en formato 'Y-m-d'
     * @return array El mismo array de días pero con 'fecha_calculada' inyectada en cada uno
     */
    public static function calcularFechasDias(array $dias, string $fecha_llegada): array
    {
        // 1. Convertimos la fecha de llegada a un objeto DateTime de PHP para poder hacer cálculos matemáticos con ella
        $fecha_base = new DateTime($fecha_llegada);

        // 2. Este acumulador llevará la cuenta de cuántos días debemos sumar a la fecha base
        $dias_acumulados = 0;

        // 3. Recorremos cada día. Usamos "&" (referencia) para poder modificar el array original directamente
        foreach ($dias as &$dia) {
            // Clonamos la fecha base para que no se nos altere la original en cada vuelta del ciclo
            $fecha_dia = clone $fecha_base;

            // Si ya pasaron días (es decir, a partir del día 2), le sumamos el acumulador
            if ($dias_acumulados > 0) {
                $fecha_dia->modify("+{$dias_acumulados} days");
            }

            // 4. Inyectamos la nueva fecha ya formateada dentro del día actual
            $dia['fecha_calculada'] = $fecha_dia->format('Y-m-d');

            // 5. Preparamos el terreno para el SIGUIENTE día: sumamos la duración de la estancia actual al acumulador
            $dias_acumulados += (int) ($dia['duracion_estancia'] ?? 1);
        }
        // Por buenas prácticas en PHP, rompemos la referencia del último elemento
        unset($dia);

        return $dias;
    }

    /**
     * Recalcula y PERSISTE la fecha_dia de todos los días de una solicitud,
     * usando la fecha_llegada de la solicitud + la duración acumulada.
     * Es la fuente de verdad: se llama cada vez que cambia la fecha_llegada
     * o la estructura/duración de los días.
     *
     * @param Database $db          Instancia de base de datos
     * @param int      $solicitudId ID de la solicitud (programa)
     * @return void
     */
    public static function recalcularYGuardar(Database $db, int $solicitudId): void
    {
        // 1. Fecha base (día 1). Sin ella no hay nada que calcular.
        $solicitud = $db->fetch(
            "SELECT fecha_llegada FROM programa_solicitudes WHERE id = ?",
            [$solicitudId]
        );
        if (!$solicitud || empty($solicitud['fecha_llegada'])) {
            return;
        }

        // 2. Días ordenados, con su duración (default 1 si es null).
        $dias = $db->fetchAll(
            "SELECT id, COALESCE(duracion_estancia, 1) AS duracion_estancia
             FROM programa_dias
             WHERE solicitud_id = ?
             ORDER BY dia_numero ASC",
            [$solicitudId]
        );
        if (!$dias) {
            return;
        }

        // 3. Reusar el cálculo existente para obtener fecha_calculada por día.
        $dias = self::calcularFechasDias($dias, $solicitud['fecha_llegada']);

        // 4. Persistir la fecha de cada día en la base de datos.
        foreach ($dias as $dia) {
            $db->query(
                "UPDATE programa_dias SET fecha_dia = ? WHERE id = ?",
                [$dia['fecha_calculada'], $dia['id']]
            );
        }
    }
}
