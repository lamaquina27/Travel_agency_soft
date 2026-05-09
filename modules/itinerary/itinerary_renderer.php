<?php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/config_functions.php';

class ItineraryRenderer
{
    private Database $db;
    private int $programaId;
    private array $data = [];

    public function __construct(int $programaId)
    {
        $this->db = Database::getInstance();
        $this->programaId = $programaId;
    }

    public function getData(): array
    {
        if (!empty($this->data)) {
            return $this->data;
        }

        ConfigManager::init();

        $programa = $this->db->fetch(
            "SELECT ps.*, pp.titulo_programa, pp.foto_portada, pp.idioma_predeterminado,
                    DATE_FORMAT(ps.fecha_llegada, '%d/%m/%Y') as fecha_llegada_formatted,
                    DATE_FORMAT(
                        DATE_ADD(ps.fecha_llegada, INTERVAL COALESCE(
                            (SELECT SUM(COALESCE(pd2.duracion_estancia, 1))
                            FROM programa_dias pd2
                            WHERE pd2.solicitud_id = ps.id),
                            0
                        ) DAY),
                        '%d/%m/%Y'
                    ) as fecha_salida_formatted,
                    DATEDIFF(ps.fecha_salida, ps.fecha_llegada) as duracion_dias,
                    (SELECT COUNT(*) FROM viajeros_solicitud vs WHERE vs.solicitud_id = ps.id) as viajeros_count
             FROM programa_solicitudes ps
             LEFT JOIN programa_personalizacion pp ON ps.id = pp.solicitud_id
             WHERE ps.id = ?
             LIMIT 1",
            [$this->programaId]
        );

        if (!$programa) {
            throw new Exception('Programa no encontrado.');
        }

        $dias = $this->db->fetchAll(
            "SELECT *, COALESCE(duracion_estancia, 1) as duracion_estancia
             FROM programa_dias
             WHERE solicitud_id = ?
             ORDER BY dia_numero ASC",
            [$this->programaId]
        );

        $fechaBase = !empty($programa['fecha_llegada']) ? new DateTime($programa['fecha_llegada']) : null;
        $diasAcumulados = 0;

        foreach ($dias as &$dia) {
            if ($fechaBase) {
                $fechaDia = clone $fechaBase;

                if ($diasAcumulados > 0) {
                    $fechaDia->modify("+{$diasAcumulados} days");
                }

                $dia['fecha_calculada'] = $fechaDia->format('Y-m-d');

                $duracion = (int)($dia['duracion_estancia'] ?? 1);
                $fechaFinDia = clone $fechaDia;

                if ($duracion > 1) {
                    $fechaFinDia->modify('+' . ($duracion - 1) . ' days');
                }

                $dia['fecha_fin_calculada'] = $fechaFinDia->format('Y-m-d');
            }

            $diasAcumulados += (int)($dia['duracion_estancia'] ?? 1);

            $serviciosRaw = $this->db->fetchAll(
                "SELECT 
                    pds.*,
                    pds.nombre_servicio as nombre,
                    pds.descripcion_servicio as descripcion,
                    pds.ubicacion_servicio as ubicacion,
                    pds.latitud,
                    pds.longitud,
                    pds.actividad_imagen1 as imagen,
                    pds.actividad_imagen2 as imagen2,
                    pds.actividad_imagen3 as imagen3,
                    pds.transporte_medio as medio_transporte,
                    pds.transporte_titulo,
                    pds.transporte_lugar_salida,
                    pds.transporte_lugar_llegada,
                    pds.transporte_duracion as duracion,
                    pds.transporte_distancia_km,
                    pds.alojamiento_tipo as tipo_alojamiento,
                    pds.alojamiento_categoria as categoria_alojamiento,
                    pds.alojamiento_imagen as alojamiento_imagen_principal,
                    pds.alojamiento_sitio_web,
                    pds.acomodacion_id,
                    a.tipo_acomodacion AS acomodacion_nombre,
                    a.descripcion AS acomodacion_descripcion,
                    a.acomodacion AS acomodacion_capacidad
                 FROM programa_dias_servicios pds
                 LEFT JOIN acomodaciones a ON pds.acomodacion_id = a.id
                 WHERE pds.programa_dia_id = ?
                 ORDER BY pds.orden ASC, pds.es_alternativa ASC, pds.orden_alternativa ASC",
                [$dia['id']]
            );

            $serviciosOrganizados = [];
            foreach ($serviciosRaw as $servicio) {
                $orden = $servicio['orden'];

                if (!isset($serviciosOrganizados[$orden])) {
                    $serviciosOrganizados[$orden] = [
                        'principal' => null,
                        'alternativas' => []
                    ];
                }

                if ((int)$servicio['es_alternativa'] === 0) {
                    $serviciosOrganizados[$orden]['principal'] = $servicio;
                } else {
                    $serviciosOrganizados[$orden]['alternativas'][] = $servicio;
                }
            }

            ksort($serviciosOrganizados);
                $dia['servicios'] = $serviciosOrganizados;

                $imagenesDia = [];

                foreach ($serviciosRaw as $servicioImagen) {
                    foreach (['imagen', 'imagen2', 'imagen3', 'alojamiento_imagen_principal'] as $campoImagen) {
                        if (!empty($servicioImagen[$campoImagen])) {
                            $imagenesDia[] = $servicioImagen[$campoImagen];
                        }
                    }
                }

                $imagenesDia = array_values(array_unique(array_filter($imagenesDia)));
                $dia['imagenes'] = array_slice($imagenesDia, 0, 3);
            $dia['vuelos'] = $this->db->fetchAll(
                "SELECT 
                    vd.orden,
                    cv.codigo_vuelo,
                    cv.aerolinea,
                    cv.ciudad_origen,
                    cv.codigo_aeropuerto_origen,
                    cv.aeropuerto_origen,
                    cv.ciudad_destino,
                    cv.codigo_aeropuerto_destino,
                    cv.aeropuerto_destino,
                    cv.terminal,
                    cv.hora_salida,
                    cv.hora_llegada
                 FROM vuelos_dias vd
                 INNER JOIN codigos_vuelos cv ON cv.id = vd.codigo_vuelo_id
                 WHERE vd.programa_dias_id = ?
                 ORDER BY vd.orden ASC",
                [$dia['id']]
            );
        }
        unset($dia);

        $precios = $this->db->fetch(
            "SELECT * FROM programa_precios WHERE solicitud_id = ?",
            [$this->programaId]
        );

        $duracionDias = 0;
        foreach ($dias as $dia) {
            $duracionDias += (int)($dia['duracion_estancia'] ?? 1);
        }

        $agencia = $this->db->fetch(
            "SELECT 
                nombre,
                logo_url,
                email_contacto,
                telefono,
                agent_primary_color,
                agent_secondary_color,
                admin_primary_color,
                admin_secondary_color
             FROM agencias
             WHERE id = ?
             LIMIT 1",
            [(int)$programa['agencia_id']]
        );

        $this->data = [
            'programa' => $programa,
            'dias' => $dias,
            'precios' => $precios ?: [],
            'duracion_dias' => $duracionDias ?: count($dias),
            'agencia' => [
                'nombre' => $agencia['nombre'] ?? ConfigManager::getCompanyName(),
                'logo_url' => $agencia['logo_url'] ?? '',
                'telefono' => $agencia['telefono'] ?? '',
                'email_contacto' => $agencia['email_contacto'] ?? '',
                'primary_color' => $agencia['agent_primary_color'] ?: ($agencia['admin_primary_color'] ?? '#667eea'),
                'secondary_color' => $agencia['agent_secondary_color'] ?: ($agencia['admin_secondary_color'] ?? '#764ba2')
            ]
        ];

        return $this->data;
    }

    public function renderHtml(): string
    {
        $data = $this->getData();

        $programa = $data['programa'];
        $dias = $data['dias'];
        $precios = $data['precios'];
        $agencia = $data['agencia'];
        $duracionDias = $data['duracion_dias'];

        $primary = $this->normalizeColor($agencia['primary_color']);
        $secondary = $this->normalizeColor($agencia['secondary_color']);

        $titulo = $programa['titulo_programa'] ?: ('Viaje a ' . ($programa['destino'] ?? ''));
        $viajero = trim(($programa['nombre_viajero'] ?? '') . ' ' . ($programa['apellido_viajero'] ?? ''));
        $logo = $this->resolveAsset($agencia['logo_url']);
        $cover = $this->resolveAsset($programa['foto_portada'] ?? '');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    margin: 12mm 12mm 14mm 12mm;
                }

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    padding: 0;
                    font-family: DejaVu Sans, Arial, sans-serif;
                    color: #1f2937;
                    font-size: 10.5px;
                    line-height: 1.42;
                    background: #ffffff;
                }

                .cover {
                    position: relative;
                    width: 100%;
                    height: 247mm;
                    overflow: hidden;
                    page-break-after: always;
                    background-image: <?= $cover ? "url('" . htmlspecialchars($cover) . "')" : 'none' ?>;
                    background-position: center center;
                    background-size: cover;
                    background-repeat: no-repeat;
                    background-color: <?= $primary ?>;
                    color: white;
                }

                .cover-overlay {
                    position: absolute;
                    inset: 0;
                    padding: 26mm 18mm 18mm;
                    background: linear-gradient(135deg, rgba(0,0,0,.68), rgba(0,0,0,.35));
                }

                .brand-row {
                    width: 100%;
                    margin-bottom: 60mm;
                }

                .logo {
                    max-width: 145px;
                    max-height: 62px;
                    background: rgba(255,255,255,.92);
                    padding: 7px 10px;
                    border-radius: 10px;
                }

                .agency-name {
                    margin-top: 8px;
                    font-size: 11px;
                    letter-spacing: .8px;
                    text-transform: uppercase;
                    font-weight: 700;
                }

                .cover-kicker {
                    font-size: 12px;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                    opacity: .88;
                    margin-bottom: 10px;
                }

                .cover-title {
                    font-size: 42px;
                    line-height: 1.05;
                    font-weight: 800;
                    margin-bottom: 14px;
                }

                .cover-subtitle {
                    font-size: 14px;
                    opacity: .92;
                    max-width: 620px;
                }

                .cover-stats {
                    position: absolute;
                    left: 18mm;
                    right: 18mm;
                    bottom: 16mm;
                }

                .stats-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .stats-table td {
                    width: 33.33%;
                    padding: 14px 16px;
                    background: rgba(255,255,255,.16);
                    border: 1px solid rgba(255,255,255,.28);
                    color: white;
                }

                .stat-label {
                    font-size: 9px;
                    text-transform: uppercase;
                    letter-spacing: .8px;
                    opacity: .85;
                    margin-bottom: 5px;
                }

                .stat-value {
                    font-size: 16px;
                    font-weight: 800;
                }

                .doc-header {
                    border-top: 5px solid <?= $primary ?>;
                    border-bottom: 1px solid #e5e7eb;
                    padding: 10px 0 12px;
                    margin-bottom: 14px;
                }

                .doc-header-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .doc-header-table td {
                    vertical-align: middle;
                }

                .small-logo {
                    max-width: 105px;
                    max-height: 45px;
                }

                .doc-title {
                    text-align: right;
                    font-size: 18px;
                    font-weight: 800;
                    color: #111827;
                }

                .section {
                    margin-bottom: 16px;
                }

                .section-title {
                    font-size: 20px;
                    font-weight: 800;
                    color: #111827;
                    margin: 0 0 18px;
                    padding-left: 10px;
                    border-left: 5px solid <?= $primary ?>;
                }

                .summary-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 14px;
                }

                .summary-table td {
                    width: 25%;
                    padding: 9px 10px;
                    border: 1px solid #e5e7eb;
                    background: #f9fafb;
                    vertical-align: top;
                }

                .label {
                    font-size: 8px;
                    color: #6b7280;
                    text-transform: uppercase;
                    letter-spacing: .5px;
                    font-weight: 800;
                    margin-bottom: 3px;
                }

                .value {
                    font-size: 10.5px;
                    font-weight: 700;
                    color: #111827;
                }

                .itinerary-timeline-pdf {
                    width: 100%;
                }

                .day-card {
                    position: relative;
                    margin-bottom: 14px;
                    padding-left: 54px;
                    page-break-inside: auto;
                }

                .day-number-box {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 38px;
                    height: 38px;
                    border: 2px solid <?= $primary ?>;
                    border-radius: 19px;
                    text-align: center;
                    background: #ffffff;
                }

                .day-number-main {
                    font-size: 14px;
                    font-weight: 900;
                    line-height: 18px;
                    color: <?= $primary ?>;
                    margin-top: 5px;
                }

                .day-number-label {
                    font-size: 5px;
                    color: #6b7280;
                    text-transform: uppercase;
                    letter-spacing: .3px;
                }

                .day-content-box {
                    border: 1px solid #e5e7eb;
                    border-radius: 16px;
                    background: #ffffff;
                    overflow: hidden;
                }

                .day-head {
                    width: 100%;
                    border-collapse: collapse;
                    border-bottom: 1px solid #e5e7eb;
                }

                .day-head td {
                    padding: 12px 14px;
                    vertical-align: top;
                }

                .day-title {
                    font-size: 16px;
                    font-weight: 900;
                    color: #111827;
                    margin-bottom: 3px;
                }

                .day-date {
                    color: <?= $primary ?>;
                    font-weight: 900;
                    font-size: 9px;
                    text-transform: uppercase;
                    text-align: right;
                }

                .day-location {
                    color: #6b7280;
                    font-size: 9px;
                }

                .day-gallery {
                    border-bottom: 1px solid #e5e7eb;
                }

                .day-gallery-table {
                    width: 100%;
                    border-collapse: collapse;
                    table-layout: fixed;
                }

                .day-gallery-table td {
                    padding: 0;
                    border-right: 2px solid #ffffff;
                    height: 36mm;
                    overflow: hidden;
                    vertical-align: middle;
                }

                .day-gallery-table td:last-child {
                    border-right: 0;
                }

                .day-gallery img {
                    width: 100%;
                    height: 36mm;
                    object-fit: cover;
                    display: block;
                }

                .day-body {
                    padding: 12px 14px 14px;
                }

                .day-description {
                    color: #374151;
                    margin: 0 0 10px;
                    font-size: 9.4px;
                    line-height: 1.42;
                }

                .flight-card {
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    padding: 9px 11px;
                    margin: 9px 0;
                    background: #f9fafb;
                    page-break-inside: avoid;
                }

                .flight-route-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .flight-route-table td {
                    vertical-align: middle;
                }

                .airport-code {
                    font-size: 20px;
                    font-weight: 900;
                    color: #111827;
                    letter-spacing: .5px;
                }

                .airport-city {
                    color: #6b7280;
                    font-size: 8px;
                }

                .flight-time {
                    color: <?= $primary ?>;
                    font-size: 11px;
                    font-weight: 900;
                    margin-top: 3px;
                }

                .flight-line {
                    text-align: center;
                    color: <?= $primary ?>;
                    font-weight: 900;
                    font-size: 8px;
                    text-transform: uppercase;
                }

                .flight-arrow {
                    font-size: 12px;
                    margin-top: 2px;
                }

                .service-card {
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    padding: 10px 12px;
                    margin-bottom: 9px;
                    background: #ffffff;
                    page-break-inside: auto;
                }

                .service-title {
                    font-weight: 900;
                    font-size: 11.2px;
                    color: #111827;
                    margin-bottom: 4px;
                }

                .service-meta {
                    color: #6b7280;
                    font-size: 8.4px;
                    margin-bottom: 5px;
                }

                .service-type-pill {
                    display: inline-block;
                    background: #fff1f2;
                    color: <?= $primary ?>;
                    border-radius: 999px;
                    padding: 2px 7px;
                    font-size: 7px;
                    font-weight: 900;
                    text-transform: uppercase;
                    letter-spacing: .3px;
                    margin-right: 5px;
                }

                .service-description {
                    color: #374151;
                    font-size: 9px;
                    line-height: 1.38;
                    margin-top: 5px;
                }

                .info-box {
                    border-radius: 12px;
                    border: 1px dashed <?= $primary ?>;
                    padding: 8px 10px;
                    background: #f9fafb;
                    margin: 6px 0 9px;
                    color: #374151;
                    font-size: 8.6px;
                }

                .pricing-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .pricing-table td {
                    border: 1px solid #e5e7eb;
                    padding: 8px 10px;
                }

                .price-main {
                    font-size: 18px;
                    font-weight: 800;
                    color: <?= $primary ?>;
                }

                .footer {
                    border-top: 1px solid #e5e7eb;
                    padding-top: 10px;
                    margin-top: 18px;
                    color: #6b7280;
                    font-size: 9px;
                    text-align: center;
                }
            </style>
        </head>

        <body>
            <div class="cover">
                <div class="cover-overlay">
                    <div class="brand-row">
                        <?php if ($logo): ?>
                            <img class="logo" src="<?= htmlspecialchars($logo) ?>" alt="Logo">
                        <?php endif; ?>
                        <div class="agency-name"><?= htmlspecialchars($agencia['nombre']) ?></div>
                    </div>

                    <div class="cover-kicker">Travel itinerary</div>
                    <div class="cover-title"><?= htmlspecialchars($titulo) ?></div>
                    <div class="cover-subtitle">
                        <?= htmlspecialchars($programa['destino'] ?? '') ?>
                        <?= $viajero ? ' - Prepared for ' . htmlspecialchars($viajero) : '' ?>
                    </div>

                    <div class="cover-stats">
                        <table class="stats-table">
                            <tr>
                                <td>
                                    <div class="stat-label">Duration</div>
                                    <div class="stat-value"><?= (int)$duracionDias ?> days</div>
                                </td>
                                <td>
                                    <div class="stat-label">Travelers</div>
                                    <div class="stat-value"><?= (int)($programa['viajeros_count'] ?? $programa['numero_pasajeros'] ?? 0) ?></div>
                                </td>
                                <td>
                                    <div class="stat-label">Dates</div>
                                    <div class="stat-value">
                                        <?= htmlspecialchars($programa['fecha_llegada_formatted'] ?? '') ?>
                                        <?= !empty($programa['fecha_salida_formatted']) ? ' - ' . htmlspecialchars($programa['fecha_salida_formatted']) : '' ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            

            <div class="section">
                <h2 class="section-title">Day by day itinerary</h2>

                <div class="itinerary-timeline-pdf">
                    <?php foreach ($dias as $dia): ?>
                        <div class="day-card">
                            <div class="day-number-box">
                                <div class="day-number-main"><?= htmlspecialchars($dia['dia_numero']) ?></div>
                                <div class="day-number-label">Day</div>
                            </div>

                            <div class="day-content-box">
                                <table class="day-head">
                                    <tr>
                                        <td style="width: 70%;">
                                            <div class="day-title">
                                                <?= htmlspecialchars($dia['titulo'] ?? '') ?>
                                            </div>

                                            <?php if (!empty($dia['ubicacion'])): ?>
                                                <div class="day-location">
                                                    <?= htmlspecialchars($dia['ubicacion']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td style="width: 30%;">
                                            <div class="day-date">
                                                <?= $this->formatDateRange($dia) ?>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <?php if (!empty($dia['imagenes'])): ?>
                                    <div class="day-gallery">
                                        <table class="day-gallery-table">
                                            <tr>
                                                <?php foreach ($dia['imagenes'] as $img): ?>
                                                    <?php $imgUrl = $this->resolveAsset($img); ?>
                                                    <?php if ($imgUrl): ?>
                                                        <td style="width: <?= 100 / max(1, count($dia['imagenes'])) ?>%;">
                                                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="">
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tr>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <div class="day-body">
                                    <?php if (!empty($dia['descripcion'])): ?>
                                        <div class="day-description">
                                            <?= nl2br(htmlspecialchars($dia['descripcion'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($dia['vuelos'])): ?>
                                        <?php foreach ($dia['vuelos'] as $vuelo): ?>
                                            <div class="flight-card">
                                                <div class="service-title">
                                                    <?= htmlspecialchars($vuelo['codigo_vuelo']) ?> - <?= htmlspecialchars($vuelo['aerolinea']) ?>
                                                </div>

                                                <table class="flight-route-table">
                                                    <tr>
                                                        <td style="width: 35%;">
                                                            <div class="airport-code"><?= htmlspecialchars($vuelo['codigo_aeropuerto_origen']) ?></div>
                                                            <div class="airport-city"><?= htmlspecialchars($vuelo['ciudad_origen']) ?></div>
                                                            <div class="flight-time"><?= htmlspecialchars(substr($vuelo['hora_salida'], 0, 5)) ?></div>
                                                        </td>

                                                        <td style="width: 30%;">
                                                            <div class="flight-line">
                                                                Flight <?= (int)$vuelo['orden'] ?>
                                                                <div class="flight-arrow">→</div>
                                                            </div>
                                                        </td>

                                                        <td style="width: 35%; text-align: right;">
                                                            <div class="airport-code"><?= htmlspecialchars($vuelo['codigo_aeropuerto_destino']) ?></div>
                                                            <div class="airport-city"><?= htmlspecialchars($vuelo['ciudad_destino']) ?></div>
                                                            <div class="flight-time"><?= htmlspecialchars(substr($vuelo['hora_llegada'], 0, 5)) ?></div>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <div class="service-meta">
                                                    <?= htmlspecialchars($vuelo['aeropuerto_origen']) ?>
                                                    -
                                                    <?= htmlspecialchars($vuelo['aeropuerto_destino']) ?>
                                                    <?= !empty($vuelo['terminal']) ? ' | Terminal ' . htmlspecialchars($vuelo['terminal']) : '' ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php foreach (($dia['servicios'] ?? []) as $grupo): ?>
                                        <?php if (!empty($grupo['principal'])): ?>
                                            <?php $servicio = $grupo['principal']; ?>

                                            <div class="service-card">
                                                <div class="service-title">
                                                    <?= htmlspecialchars($servicio['nombre'] ?? 'Service') ?>
                                                </div>

                                                <div class="service-meta">
                                                    <span class="service-type-pill">
                                                        <?= htmlspecialchars($this->formatServiceType($servicio['tipo_servicio'] ?? '')) ?>
                                                    </span>

                                                    <?= !empty($servicio['ubicacion']) ? htmlspecialchars($servicio['ubicacion']) : '' ?>
                                                </div>

                                                <?php if (!empty($servicio['descripcion'])): ?>
                                                    <div class="service-description">
                                                        <?= nl2br(htmlspecialchars($servicio['descripcion'])) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (($servicio['tipo_servicio'] ?? '') === 'alojamiento'): ?>
                                                    <div class="service-meta">
                                                        <?= !empty($servicio['acomodacion_nombre']) ? 'Room: ' . htmlspecialchars($servicio['acomodacion_nombre']) : '' ?>
                                                        <?= !empty($servicio['acomodacion_capacidad']) ? ' | Pax: ' . htmlspecialchars($servicio['acomodacion_capacidad']) : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($grupo['alternativas'])): ?>
                                                <div class="info-box">
                                                    <strong>Alternatives:</strong>
                                                    <?php foreach ($grupo['alternativas'] as $alt): ?>
                                                        <br><?= htmlspecialchars($alt['nombre'] ?? 'Alternative') ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($precios)): ?>
                <div class="section">
                    <h2 class="section-title">Investment and conditions</h2>

                    <table class="pricing-table">
                        <?php if (!empty($precios['precio_total'])): ?>
                            <tr>
                                <td style="width: 35%;"><strong>Total price</strong></td>
                                <td><span class="price-main"><?= htmlspecialchars($precios['moneda'] ?? '') ?> <?= number_format((float)$precios['precio_total'], 0, ',', '.') ?></span></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($precios['incluye'])): ?>
                            <tr>
                                <td><strong>Includes</strong></td>
                                <td><?= nl2br(htmlspecialchars($precios['incluye'])) ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($precios['no_incluye'])): ?>
                            <tr>
                                <td><strong>Does not include</strong></td>
                                <td><?= nl2br(htmlspecialchars($precios['no_incluye'])) ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($precios['condiciones_generales'])): ?>
                            <tr>
                                <td><strong>General conditions</strong></td>
                                <td><?= nl2br(htmlspecialchars($precios['condiciones_generales'])) ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($precios['info_pasaporte'])): ?>
                            <tr>
                                <td><strong>Passport information</strong></td>
                                <td><?= nl2br(htmlspecialchars($precios['info_pasaporte'])) ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($precios['info_seguros'])): ?>
                            <tr>
                                <td><strong>Insurance information</strong></td>
                                <td><?= nl2br(htmlspecialchars($precios['info_seguros'])) ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>

            <div class="footer">
                <?= htmlspecialchars($agencia['nombre']) ?>
                <?php if (!empty($agencia['telefono'])): ?>
                    | <?= htmlspecialchars($agencia['telefono']) ?>
                <?php endif; ?>
                <?php if (!empty($agencia['email_contacto'])): ?>
                    | <?= htmlspecialchars($agencia['email_contacto']) ?>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function resolveAsset(?string $path): string
    {
        if (!$path) {
            return '';
        }

        if (preg_match('/^https?:\/\//', $path)) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    }

    private function normalizeColor(?string $color): string
    {
        $color = trim((string)$color);

        if ($color === '') {
            return '#667eea';
        }

        if ($color[0] !== '#') {
            $color = '#' . $color;
        }

        return $color;
    }

    private function formatDateRange(array $dia): string
    {
        if (empty($dia['fecha_calculada'])) {
            return '';
        }

        $inicio = date('d/m/Y', strtotime($dia['fecha_calculada']));

        if (!empty($dia['fecha_fin_calculada']) && $dia['fecha_fin_calculada'] !== $dia['fecha_calculada']) {
            $fin = date('d/m/Y', strtotime($dia['fecha_fin_calculada']));
            return $inicio . ' - ' . $fin;
        }

        return $inicio;
    }

    private function formatServiceType(string $tipo): string
    {
        $tipos = [
            'actividad' => 'Activity',
            'transporte' => 'Transport',
            'alojamiento' => 'Accommodation',
            'comida' => 'Meal'
        ];

        return $tipos[$tipo] ?? ucfirst($tipo);
    }

    private function hexToRgba(string $hex, float $alpha = 0.1): string
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return "rgba($r, $g, $b, $alpha)";
    }
}