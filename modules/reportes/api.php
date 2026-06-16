<?php
// =====================================================================
// modules/reportes/api.php — Reportes del admin (SOLO LECTURA)
// Devuelve agregados (COUNT/SUM/GROUP BY) de la agencia en sesión.
// No escribe nada ni toca ningún flujo. Acceso: admin.
// =====================================================================

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

App::init();
App::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $agenciaId = (int) ($_SESSION['agencia_id'] ?? 0);
    if (!$agenciaId) {
        throw new Exception('Usuario sin agencia asignada');
    }

    // ── KPIs ──
    $totalLeads = (int) ($db->fetch("SELECT COUNT(*) c FROM pipeline WHERE agencia_id = ?", [$agenciaId])['c'] ?? 0);
    $vendidos   = (int) ($db->fetch("SELECT COUNT(*) c FROM programa_solicitudes WHERE agencia_id = ? AND comprado = 1", [$agenciaId])['c'] ?? 0);
    $ganados    = (int) ($db->fetch(
        "SELECT COUNT(*) c FROM pipeline p
         JOIN pipeline_estados e ON e.id = p.estado_id
         WHERE p.agencia_id = ? AND e.tipo_final = 'ganado'",
        [$agenciaId]
    )['c'] ?? 0);
    $conversion = $totalLeads > 0 ? round($ganados * 100 / $totalLeads, 1) : 0;
    $ingresos   = (float) ($db->fetch(
        "SELECT COALESCE(SUM(pp.precio_total), 0) s
         FROM programa_solicitudes ps
         JOIN programa_precios pp ON pp.solicitud_id = ps.id
         WHERE ps.agencia_id = ? AND ps.comprado = 1",
        [$agenciaId]
    )['s'] ?? 0);

    // ── Leads por estado (orden del pipeline) ──
    $porEstado = $db->fetchAll(
        "SELECT e.nombre, e.color, COUNT(p.id) total
         FROM pipeline_estados e
         LEFT JOIN pipeline p ON p.estado_id = e.id AND p.agencia_id = e.agencia_id
         WHERE e.agencia_id = ?
         GROUP BY e.id, e.nombre, e.color, e.posicion
         ORDER BY e.posicion ASC",
        [$agenciaId]
    );

    // ── Leads por origen ──
    $porOrigen = $db->fetchAll(
        "SELECT COALESCE(s.nombre, 'Sin origen') nombre, COUNT(p.id) total
         FROM pipeline p
         LEFT JOIN pipeline_sources s ON s.id = p.source
         WHERE p.agencia_id = ?
         GROUP BY COALESCE(s.nombre, 'Sin origen')
         ORDER BY total DESC",
        [$agenciaId]
    );

    // ── Top destinos (demanda de leads) ──
    $topDestinos = $db->fetchAll(
        "SELECT TRIM(destino) destino, COUNT(*) total
         FROM pipeline
         WHERE agencia_id = ? AND TRIM(COALESCE(destino, '')) <> ''
         GROUP BY TRIM(destino)
         ORDER BY total DESC
         LIMIT 8",
        [$agenciaId]
    );

    // ── Ventas por mes (programas vendidos, últimos 12 meses) ──
    $ventasRaw = $db->fetchAll(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(*) total
         FROM programa_solicitudes
         WHERE agencia_id = ? AND comprado = 1
           AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
         GROUP BY ym
         ORDER BY ym ASC",
        [$agenciaId]
    );
    $mapVentas = [];
    foreach ($ventasRaw as $r) { $mapVentas[$r['ym']] = (int) $r['total']; }
    // Serie continua de 12 meses (rellena los meses sin ventas con 0)
    $ventas = [];
    $cursor = new DateTime('first day of this month');
    $cursor->modify('-11 months');
    for ($i = 0; $i < 12; $i++) {
        $ym = $cursor->format('Y-m');
        $ventas[] = ['label' => $cursor->format('m/Y'), 'total' => $mapVentas[$ym] ?? 0];
        $cursor->modify('+1 month');
    }

    echo json_encode([
        'success'      => true,
        'kpis'         => [
            'leads'      => $totalLeads,
            'vendidos'   => $vendidos,
            'conversion' => $conversion,
            'ingresos'   => $ingresos,
        ],
        'por_estado'   => $porEstado,
        'por_origen'   => $porOrigen,
        'top_destinos' => $topDestinos,
        'ventas'       => $ventas,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
