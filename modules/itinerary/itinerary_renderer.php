<?php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/config_functions.php';

class ItineraryRenderer
{
    private Database $db;
    private int $programaId;
    private array $data = [];
    private string $rootPath;

    public function __construct(int $programaId)
    {
        $this->db = Database::getInstance();
        $this->programaId = $programaId;
        $this->rootPath = dirname(__DIR__, 2);
    }

    public function renderHtml(): string
    {
        $data = $this->getData();
        $programa = $data['programa'];
        $dias = $data['dias'];
        $precios = $data['precios'];
        $agencia = $data['agencia'];

        $primary = $this->normalizeColor($agencia['primary_color'] ?? '#0f766e');
        $secondary = $this->normalizeColor($agencia['secondary_color'] ?? '#0f172a');
        $dark = '#101828';

        $titulo = $programa['titulo_programa'] ?: 'Travel itinerary';
        $destino = $programa['destino'] ?? '';
        $duracionDias = (int)($data['duracion_dias'] ?? count($dias));
        $viajeros = (int)($programa['viajeros_count'] ?: ($programa['numero_pasajeros'] ?? 0));
        $logo = $this->imageToDataUri($agencia['logo_url'] ?? '');
        // Portada: thumb con la proporción de la caja vertical (≈108x145mm) para que NO se deforme
        $cover = $this->thumbToDataUri($programa['foto_portada'] ?? '', 600, 800);
        $fontRegular = $this->fontFileUri('NotoSansThai-Regular.ttf');
        $fontBold = $this->fontFileUri('NotoSansThai-Bold.ttf');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <style>
                <?php if ($fontRegular): ?>
                @font-face { font-family: TravelPdf; src: url('<?= $fontRegular ?>') format('truetype'); font-weight: 400; }
                <?php endif; ?>
                <?php if ($fontBold): ?>
                @font-face { font-family: TravelPdf; src: url('<?= $fontBold ?>') format('truetype'); font-weight: 700; }
                <?php endif; ?>

                @page { margin: 12mm 12mm 14mm 12mm; }
                * { box-sizing: border-box; }
                body { margin:0; font-family: TravelPdf, DejaVu Sans, Arial, sans-serif; color:#2f3747; font-size:12px; line-height:1.45; background:#fff; }
                img { display:block; }

                .cover {
                    page-break-after: always;
                    padding-bottom: 8mm;
                }

                .brand-row {
                    width:100%;
                    border-collapse:collapse;
                    margin-bottom:12mm;
                }

                .brand-row td {
                    vertical-align:middle;
                }

                .logo {
                    max-width:95px;
                    max-height:50px;
                }

                .agency {
                    text-align:right;
                    color:<?= $primary ?>;
                    font-size:12px;
                    font-weight:700;
                    letter-spacing:2px;
                    text-transform:uppercase;
                }

                .hero-card {
                    width:100%;
                    border-collapse:collapse;
                    background:#111827;
                    overflow:hidden;
                }

                .hero-card td {
                    vertical-align:stretch;
                }

                .hero-left {
                    width:42%;
                    background:#0f172a;
                    color:#fff;
                    padding:18mm 10mm;
                    border-top:6px solid <?= $primary ?>;
                }

                .hero-kicker {
                    color:#cbd5e1;
                    font-size:10px;
                    text-transform:uppercase;
                    letter-spacing:3px;
                    margin-bottom:10mm;
                }

                .hero-title {
                    font-size:30px;
                    line-height:1.18;
                    font-weight:700;
                    color:#fff;
                    margin-bottom:8mm;
                }

                .hero-subtitle {
                    color:#d1d5db;
                    font-size:12px;
                    line-height:1.5;
                }

                .hero-right {
                    width:58%;
                    background:#e5e7eb;
                }

                .hero-image {
                    width:100%;
                    height:145mm;
                    object-fit:cover;
                }

                .cover-stats {
                    width:100%;
                    border-collapse:collapse;
                    margin-top:8mm;
                }

                .cover-stats td {
                    width:33.33%;
                    padding:7mm 6mm;
                    border:1px solid #e5e7eb;
                    background:#f8fafc;
                }

                .stat-label {
                    font-size:9px;
                    letter-spacing:1.5px;
                    text-transform:uppercase;
                    color:#94a3b8;
                    font-weight:700;
                    margin-bottom:4px;
                }

                .stat-value {
                    font-size:13px;
                    color:#111827;
                    font-weight:700;
                }

                .summary-page { page-break-after: always; }
                .summary-title { font-size:27px; color:<?= $primary ?>; font-weight:700; margin-bottom:18px; }
                .summary-grid { width:100%; border-collapse:collapse; margin-bottom:18px; }
                .summary-grid td { width:25%; padding:11px 12px; border:1px solid #e5e7eb; vertical-align:top; }
                .summary-label { color:#7b8494; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; margin-bottom:4px; }
                .summary-value { font-size:13px; color:#111827; font-weight:700; }
                .stage-title { color:#111827; font-size:17px; font-weight:700; margin:14px 0 9px; }
                .stage-row { font-size:12px; padding:5px 0; border-bottom:1px solid #eef0f4; }
                .stage-day { color:<?= $primary ?>; font-weight:700; display:inline-block; width:48px; }

                .day { margin-bottom:20px; page-break-inside:auto; }
                .day-head { margin-bottom:8px; }
                .day-number { color:<?= $primary ?>; font-size:22px; font-weight:700; display:inline; }
                .day-date { color:#9aa3b2; font-size:11px; margin-left:6px; }
                .day-title { color:#293241; font-size:23px; line-height:1.22; font-weight:700; margin:3px 0 6px; }
                .chips { margin:4px 0 10px; }
                .chip { display:inline-block; border:1px solid #ccd3dd; border-radius:4px; color:#8b95a5; padding:3px 7px; font-size:10px; margin-right:4px; margin-bottom:4px; }
                .photo-grid { width:100%; border-collapse:collapse; margin:8px 0 9px; }
                .photo-grid td { width:33.33%; padding-right:7px; vertical-align:top; }
                .photo-grid td:last-child { padding-right:0; }
                .photo-grid img { width:100%; height:35mm; }
                .day-desc { font-size:12px; line-height:1.45; color:#3f4755; margin:8px 0 12px; }
                .contains { font-size:13px; color:#293241; font-weight:700; margin:11px 0 7px; }
                .meals { font-size:11px; color:<?= $primary ?>; font-weight:700; margin-bottom:9px; }
                .empty-meals { color:#9aa3b2; }

                .hotel { width:100%; border-collapse:collapse; margin:10px 0 12px; page-break-inside:avoid; }
                .hotel-marker { width:28px; vertical-align:top; }
                .dot { width:8px; height:8px; border:1px solid #9ca3af; border-radius:50%; margin-top:4px; }
                .hotel-line { border-left:1px dotted #aeb6c2; padding-left:13px; }
                .hotel-kind { color:<?= $primary ?>; font-size:12px; font-weight:700; margin-bottom:4px; }
                .hotel-box { width:100%; border-collapse:collapse; }
                .hotel-box td { vertical-align:top; }
                .hotel-photo { width:74px; height:54px; }
                .hotel-name { font-size:14px; color:#293241; font-weight:700; margin-bottom:4px; }
                .hotel-info { color:#6b7280; font-size:10.5px; line-height:1.38; }
                .room { color:#6b7280; font-size:10.5px; margin-top:4px; }

                .activity { margin:8px 0 10px; page-break-inside:auto; }
                .service-kind { color:<?= $primary ?>; font-size:11px; font-weight:700; margin-bottom:3px; }
                .activity-title { font-size:14px; font-weight:700; color:#293241; margin-bottom:4px; }
                .activity-desc { font-size:11px; line-height:1.42; color:#3f4755; }

                .flight {
                    border-left:3px solid <?= $primary ?>;
                    background:#f8fafc;
                    padding:7px 9px;
                    margin:6px 0 8px;
                    page-break-inside:avoid;
                }
                .flight-title {
                    font-size:10.5px;
                    font-weight:700;
                    color:#293241;
                    margin-bottom:4px;
                }
                .flight-table { width:100%; border-collapse:collapse; }
                .flight-table td { vertical-align:middle; }
                .airport-code {
                    font-size:15px;
                    font-weight:700;
                    color:#111827;
                }
                .airport-city { font-size:8px; color:#7b8494; }
                .flight-time { color:<?= $primary ?>; font-weight:700; font-size:9px; }
                .flight-mid { text-align:center; font-size:8px; color:<?= $primary ?>; font-weight:700; }

                .price-section { page-break-before:always; }
                .section-title { font-size:23px; color:<?= $primary ?>; font-weight:700; margin:0 0 14px; }
                .pricing-table { width:100%; border-collapse:collapse; }
                .pricing-table td { border:1px solid #e5e7eb; padding:9px 10px; vertical-align:top; font-size:11px; line-height:1.45; }
                .price-label { width:30%; background:#f8fafc; color:#111827; font-weight:700; }
                .price-main { color:<?= $primary ?>; font-size:19px; font-weight:700; }
                .footer { color:#7b8494; font-size:10px; text-align:right; margin-top:16px; border-top:1px solid #e5e7eb; padding-top:8px; }
            </style>
        </head>
        <body>
            <div class="cover">
                <table class="brand-row">
                    <tr>
                        <td style="width:35%;"><?php if ($logo): ?><img class="logo" src="<?= $logo ?>" alt=""><?php endif; ?></td>
                        <td style="width:65%;"><div class="agency"><?= htmlspecialchars($agencia['nombre'] ?? '') ?></div></td>
                    </tr>
                </table>

                <table class="hero-card">
                    <tr>
                        <td class="hero-left">
                            <div class="hero-kicker">Itinerario personalizado</div>

                            <div class="hero-title">
                                <?= htmlspecialchars($titulo) ?>
                            </div>

                            <div class="hero-subtitle">
                                <?= htmlspecialchars($destino) ?>
                            </div>
                        </td>

                        <td class="hero-right">
                            <?php if ($cover): ?>
                                <img class="hero-image" src="<?= $cover ?>" alt="">
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <table class="cover-stats">
                    <tr>
                        <td><div class="stat-label">Duración</div><div class="stat-value"><?= $duracionDias ?> días</div></td>
                        <td><div class="stat-label">Viajeros</div><div class="stat-value"><?= $viajeros ?></div></td>
                        <td><div class="stat-label">Fechas</div><div class="stat-value"><?= htmlspecialchars($programa['fecha_llegada_formatted'] ?? '') ?> - <?= htmlspecialchars($programa['fecha_salida_formatted'] ?? '') ?></div></td>
                    </tr>
                </table>
            </div>

            <div class="summary-page">
                <div class="summary-title">Resumen del viaje</div>
                <table class="summary-grid"><tr>
                    <td><div class="summary-label">Agencia</div><div class="summary-value"><?= htmlspecialchars($agencia['nombre'] ?? '') ?></div></td>
                    <td><div class="summary-label">Destino</div><div class="summary-value"><?= htmlspecialchars($destino) ?></div></td>
                    <td><div class="summary-label">Inicio</div><div class="summary-value"><?= htmlspecialchars($programa['fecha_llegada_formatted'] ?? '') ?></div></td>
                    <td><div class="summary-label">Duración</div><div class="summary-value"><?= $duracionDias ?> días</div></td>
                </tr></table>
                <div class="stage-title">Las principales etapas del viaje</div>
                <?php foreach ($dias as $dia): ?>
                    <div class="stage-row"><span class="stage-day">Día <?= htmlspecialchars($dia['dia_numero']) ?></span><?= htmlspecialchars($this->shortText($dia['titulo'] ?? '', 85)) ?></div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($dias as $dia): ?>
                <?php $dayImgs = $this->prepareDayImages($dia['imagenes'] ?? []); ?>
                <div class="day">
                    <div class="day-head">
                        <span class="day-number">Día <?= htmlspecialchars($dia['dia_numero']) ?></span>
                        <span class="day-date"><?= $this->formatDateRange($dia) ?></span>
                        <div class="day-title"><?= htmlspecialchars($dia['titulo'] ?? '') ?></div>
                        <?php if (!empty($dia['ubicacion'])): ?><div class="chips"><?php foreach ($this->splitLocations($dia['ubicacion']) as $loc): ?><span class="chip"><?= htmlspecialchars($loc) ?></span><?php endforeach; ?></div><?php endif; ?>
                    </div>

                    <?php if (!empty($dayImgs)): ?>
                        <table class="photo-grid"><tr>
                            <?php foreach ($dayImgs as $img): ?><td><img src="<?= $img ?>" alt=""></td><?php endforeach; ?>
                        </tr></table>
                    <?php endif; ?>

                    <?php if (!empty($dia['descripcion'])): ?><div class="day-desc"><?= nl2br(htmlspecialchars($this->shortText($dia['descripcion'], 12000))) ?></div><?php endif; ?>

                    <?php $meals = $this->getMealsSummary($dia['servicios'] ?? [], $dia['descripcion'] ?? ''); ?>
                    <div class="contains">Comidas</div>
                    <div class="meals <?= str_contains($meals, 'no están') ? 'empty-meals' : '' ?>"><?= htmlspecialchars($meals) ?></div>

                    <?php foreach (($dia['vuelos'] ?? []) as $vuelo): ?>
                        <div class="flight">
                            <div class="flight-title"><?= htmlspecialchars($vuelo['codigo_vuelo']) ?> - <?= htmlspecialchars($vuelo['aerolinea']) ?></div>
                            <table class="flight-table"><tr>
                                <td style="width:35%;"><div class="airport-code"><?= htmlspecialchars($vuelo['codigo_aeropuerto_origen']) ?></div><div class="airport-city"><?= htmlspecialchars($vuelo['ciudad_origen']) ?></div><div class="flight-time"><?= htmlspecialchars(substr($vuelo['hora_salida'], 0, 5)) ?></div></td>
                                <td style="width:30%;"><div class="flight-mid">Vuelo <?= (int)$vuelo['orden'] ?><br>→</div></td>
                                <td style="width:35%; text-align:right;"><div class="airport-code"><?= htmlspecialchars($vuelo['codigo_aeropuerto_destino']) ?></div><div class="airport-city"><?= htmlspecialchars($vuelo['ciudad_destino']) ?></div><div class="flight-time"><?= htmlspecialchars(substr($vuelo['hora_llegada'], 0, 5)) ?></div></td>
                            </tr></table>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($this->getVisibleServices($dia['servicios'] ?? []) as $servicio): ?>
                        <?php $tipo = $servicio['tipo_servicio'] ?? ''; ?>
                        <?php if ($tipo === 'alojamiento'): ?>
                            <?php $hotelImg = $this->thumbToDataUri($servicio['alojamiento_imagen_principal'] ?? '', 140, 95); ?>
                            <table class="hotel"><tr>
                                <td class="hotel-marker"><div class="dot"></div></td>
                                <td class="hotel-line"><div class="hotel-kind">Alojamiento</div>
                                    <table class="hotel-box"><tr>
                                        <?php if ($hotelImg): ?><td style="width:84px;"><img class="hotel-photo" src="<?= $hotelImg ?>" alt=""></td><?php endif; ?>
                                        <td><div class="hotel-name"><?= htmlspecialchars($servicio['nombre'] ?? 'Hotel') ?></div>
                                            <?php if (!empty($servicio['ubicacion'])): ?><div class="hotel-info"><?= htmlspecialchars($this->shortText($servicio['ubicacion'], 120)) ?></div><?php endif; ?>
                                            <?php if (!empty($servicio['acomodacion_nombre']) || !empty($servicio['acomodacion_capacidad'])): ?><div class="room"><?= !empty($servicio['acomodacion_nombre']) ? 'Room: ' . htmlspecialchars($servicio['acomodacion_nombre']) : '' ?><?= !empty($servicio['acomodacion_capacidad']) ? ' · Pax: ' . htmlspecialchars($servicio['acomodacion_capacidad']) : '' ?></div><?php endif; ?>
                                        </td>
                                    </tr></table>
                                </td>
                            </tr></table>
                        <?php elseif ($tipo !== 'comida'): ?>
                            <div class="activity"><div class="service-kind"><?= htmlspecialchars($this->formatServiceType($tipo)) ?></div><div class="activity-title"><?= htmlspecialchars($servicio['nombre'] ?? 'Service') ?></div><?php if (!empty($servicio['descripcion'])): ?><div class="activity-desc"><?= nl2br(htmlspecialchars($this->shortText($servicio['descripcion'], 5000))) ?></div><?php endif; ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($precios)): ?>
                <div class="price-section">
                    <h2 class="section-title">Precio y condiciones</h2>
                    <table class="pricing-table">
                        <?php if (!empty($precios['precio_total'])): ?><tr><td class="price-label">Precio total</td><td><span class="price-main"><?= htmlspecialchars($precios['moneda'] ?? '') ?> <?= number_format((float)$precios['precio_total'], 0, ',', '.') ?></span></td></tr><?php endif; ?>
                        <?php foreach ([['precio_incluye','Incluye'],['precio_no_incluye','No incluye'],['condiciones_generales','Condiciones generales'],['info_pasaporte','Pasaporte y visados'],['info_seguros','Seguros'],['visados_entrada','Visados y requisitos de entrada'],['requisitos_sanitarios','Requisitos sanitarios'],['llegada_punto_encuentro','Llegada y punto de encuentro'],['asistencia_emergencia','Asistencia y emergencias'],['info_hoteles_servicios','Información de hoteles y servicios'],['informacion_practica','Información práctica']] as $row): ?>
                            <?php if (!empty($precios[$row[0]])): ?><tr><td class="price-label"><?= $row[1] ?></td><td><?= nl2br(htmlspecialchars($precios[$row[0]])) ?></td></tr><?php endif; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
            <div class="footer"><?= htmlspecialchars($agencia['nombre'] ?? '') ?><?php if (!empty($agencia['telefono'])): ?> | <?= htmlspecialchars($agencia['telefono']) ?><?php endif; ?><?php if (!empty($agencia['email_contacto'])): ?> | <?= htmlspecialchars($agencia['email_contacto']) ?><?php endif; ?></div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function getData(): array
    {
        if (!empty($this->data)) { return $this->data; }
        ConfigManager::init();

        $programa = $this->db->fetch(
            "SELECT ps.*, pp.titulo_programa, pp.foto_portada, pp.idioma_predeterminado,
                DATE_FORMAT(ps.fecha_llegada, '%d/%m/%Y') AS fecha_llegada_formatted,
                DATE_FORMAT(DATE_ADD(ps.fecha_llegada, INTERVAL COALESCE((SELECT SUM(COALESCE(pd2.duracion_estancia, 1)) FROM programa_dias pd2 WHERE pd2.solicitud_id = ps.id),0) DAY),'%d/%m/%Y') AS fecha_salida_formatted,
                (SELECT COUNT(*) FROM viajeros_solicitud vs WHERE vs.solicitud_id = ps.id) AS viajeros_count
             FROM programa_solicitudes ps
             LEFT JOIN programa_personalizacion pp ON ps.id = pp.solicitud_id
             WHERE ps.id = ? LIMIT 1",
            [$this->programaId]
        );
        if (!$programa) { throw new Exception('Programa no encontrado.'); }

        $dias = $this->db->fetchAll("SELECT *, COALESCE(duracion_estancia, 1) AS duracion_estancia FROM programa_dias WHERE solicitud_id = ? ORDER BY dia_numero ASC", [$this->programaId]);
        $fechaBase = !empty($programa['fecha_llegada']) ? new DateTime($programa['fecha_llegada']) : null;
        $diasAcumulados = 0;

        foreach ($dias as &$dia) {
            if ($fechaBase) {
                $fechaDia = clone $fechaBase;
                if ($diasAcumulados > 0) { $fechaDia->modify("+{$diasAcumulados} days"); }
                $dia['fecha_calculada'] = $fechaDia->format('Y-m-d');
                $duracion = (int)($dia['duracion_estancia'] ?? 1);
                $fechaFinDia = clone $fechaDia;
                if ($duracion > 1) { $fechaFinDia->modify('+' . ($duracion - 1) . ' days'); }
                $dia['fecha_fin_calculada'] = $fechaFinDia->format('Y-m-d');
            }
            $diasAcumulados += (int)($dia['duracion_estancia'] ?? 1);

            $serviciosRaw = $this->db->fetchAll(
                "SELECT pds.*, pds.nombre_servicio AS nombre, pds.descripcion_servicio AS descripcion, pds.ubicacion_servicio AS ubicacion,
                    pds.actividad_imagen1 AS imagen, pds.actividad_imagen2 AS imagen2, pds.actividad_imagen3 AS imagen3,
                    pds.alojamiento_imagen AS alojamiento_imagen_principal, pds.acomodacion_id,
                    a.tipo_acomodacion AS acomodacion_nombre, a.descripcion AS acomodacion_descripcion, a.acomodacion AS acomodacion_capacidad
                 FROM programa_dias_servicios pds
                 LEFT JOIN acomodaciones a ON pds.acomodacion_id = a.id
                 WHERE pds.programa_dia_id = ?
                 ORDER BY pds.orden ASC, pds.es_alternativa ASC, pds.orden_alternativa ASC",
                [$dia['id']]
            );

            $serviciosOrganizados = [];
            foreach ($serviciosRaw as $servicio) {
                if ((int)($servicio['es_alternativa'] ?? 0) !== 0) { continue; }
                $orden = $servicio['orden'] ?? count($serviciosOrganizados) + 1;
                if (!isset($serviciosOrganizados[$orden])) { $serviciosOrganizados[$orden] = ['principal' => null, 'alternativas' => []]; }
                $serviciosOrganizados[$orden]['principal'] = $servicio;
            }
            ksort($serviciosOrganizados);
            $dia['servicios'] = $serviciosOrganizados;

            $imagenesDia = [];
            foreach (['imagen','imagen1','imagen2','imagen3','foto','foto_dia','imagen_principal','foto_portada','portada'] as $campoDia) {
                if (!empty($dia[$campoDia])) { $imagenesDia[] = $dia[$campoDia]; }
            }
            foreach ($serviciosRaw as $servicioImagen) {
                if (($servicioImagen['tipo_servicio'] ?? '') === 'alojamiento') { continue; }
                foreach (['imagen', 'imagen2', 'imagen3'] as $campoImagen) {
                    if (!empty($servicioImagen[$campoImagen])) { $imagenesDia[] = $servicioImagen[$campoImagen]; }
                }
            }
            $dia['imagenes'] = array_slice(array_values(array_unique(array_filter($imagenesDia))), 0, 3);

            $dia['vuelos'] = $this->db->fetchAll(
                "SELECT vd.orden, cv.codigo_vuelo, cv.aerolinea, cv.ciudad_origen, cv.codigo_aeropuerto_origen, cv.aeropuerto_origen, cv.ciudad_destino, cv.codigo_aeropuerto_destino, cv.aeropuerto_destino, cv.terminal, cv.hora_salida, cv.hora_llegada
                 FROM vuelos_dias vd INNER JOIN codigos_vuelos cv ON cv.id = vd.codigo_vuelo_id
                 WHERE vd.programa_dias_id = ? ORDER BY vd.orden ASC",
                [$dia['id']]
            );
        }
        unset($dia);

        $precios = $this->db->fetch("SELECT * FROM programa_precios WHERE solicitud_id = ?", [$this->programaId]);
        $agencia = $this->db->fetch("SELECT nombre, logo_url, email_contacto, telefono, agent_primary_color, agent_secondary_color, admin_primary_color, admin_secondary_color FROM agencias WHERE id = ? LIMIT 1", [(int)$programa['agencia_id']]);

        $this->data = [
            'programa' => $programa,
            'dias' => $dias,
            'precios' => $precios ?: [],
            'duracion_dias' => $diasAcumulados ?: count($dias),
            'agencia' => [
                'nombre' => $agencia['nombre'] ?? ConfigManager::getCompanyName(),
                'logo_url' => $agencia['logo_url'] ?? '',
                'telefono' => $agencia['telefono'] ?? '',
                'email_contacto' => $agencia['email_contacto'] ?? '',
                'primary_color' => $agencia['agent_primary_color'] ?: ($agencia['admin_primary_color'] ?? '#0f766e'),
                'secondary_color' => $agencia['agent_secondary_color'] ?: ($agencia['admin_secondary_color'] ?? '#0f172a')
            ]
        ];
        return $this->data;
    }

    private function prepareDayImages(array $paths): array
    {
        $out = [];
        foreach ($paths as $path) {
            $src = $this->thumbToDataUri($path, 300, 180);
            if ($src) { $out[] = $src; }
            if (count($out) >= 3) { break; }
        }
        return $out;
    }

    private function getVisibleServices(array $servicios): array
    {
        $visible = [];
        $hotelsSeen = [];
        $hotelCount = 0;
        foreach ($servicios as $grupo) {
            if (empty($grupo['principal'])) { continue; }
            $s = $grupo['principal'];
            $tipo = $s['tipo_servicio'] ?? '';
            if ($tipo === 'alojamiento') {
                $key = mb_strtolower(trim((string)($s['nombre'] ?? '')), 'UTF-8');
                if ($key && isset($hotelsSeen[$key])) { continue; }
                $hotelsSeen[$key] = true;
                $hotelCount++;
                if ($hotelCount > 1) { continue; } // solo el alojamiento principal del día
            }
            $visible[] = $s;
        }
        return $visible;
    }

    private function thumbToDataUri(?string $path, int $targetW, int $targetH): string
    {
        $path = trim((string)$path);
        if ($path === '') { return ''; }

        // Caché en disco: evita re-descargar/re-procesar la misma imagen en cada PDF (clave: ruta+tamaño)
        $cacheDir = $this->rootPath . '/tmp/pdf-thumbs';
        if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
        $cacheFile = $cacheDir . '/' . md5($path . '|' . $targetW . 'x' . $targetH) . '.jpg';
        if (is_file($cacheFile)) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false && $cached !== '') {
                return 'data:image/jpeg;base64,' . base64_encode($cached);
            }
        }

        $binary = $this->readImageBinary($path);
        if (!$binary) { return ''; }

        // Si GD no está disponible, devolver la imagen original sin redimensionar
        if (!function_exists('imagecreatefromstring')) {
            $info = @getimagesizefromstring($binary);
            if (!$info || empty($info['mime'])) { return ''; }
            return 'data:' . $info['mime'] . ';base64,' . base64_encode($binary);
        }

        $src = @imagecreatefromstring($binary);
        if (!$src) { return ''; }
        $w = imagesx($src); $h = imagesy($src);
        if ($w <= 0 || $h <= 0) { imagedestroy($src); return ''; }
        $srcRatio = $w / $h; $dstRatio = $targetW / $targetH;
        if ($srcRatio > $dstRatio) { $newH = $h; $newW = (int)round($h * $dstRatio); $srcX = (int)(($w - $newW) / 2); $srcY = 0; }
        else { $newW = $w; $newH = (int)round($w / $dstRatio); $srcX = 0; $srcY = (int)(($h - $newH) / 2); }
        $dst = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $newW, $newH);
        ob_start(); imagejpeg($dst, null, 78); $jpg = ob_get_clean();
        imagedestroy($src); imagedestroy($dst);
        @file_put_contents($cacheFile, $jpg);
        return 'data:image/jpeg;base64,' . base64_encode($jpg);
    }

    private function imageToDataUri(?string $path): string
    {
        $binary = $this->readImageBinary($path);
        if (!$binary) { return ''; }
        $info = @getimagesizefromstring($binary);
        if (!$info || empty($info['mime'])) { return ''; }
        return 'data:' . $info['mime'] . ';base64,' . base64_encode($binary);
    }

    private function readImageBinary(?string $path): ?string
    {
        $path = trim((string)$path);
        if ($path === '') { return null; }
        if (preg_match('/^https?:\/\//i', $path)) {
            $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true, 'header' => "User-Agent: TravelSoftPDF/1.0\r\n"], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $binary = @file_get_contents($path, false, $context);
            return $binary ?: null;
        }
        $cleanPath = str_replace('\\', '/', $path);
        $cleanPath = preg_replace('/^\.\//', '', $cleanPath);
        foreach ([$this->rootPath . '/' . ltrim($cleanPath, '/'), ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/' . ltrim($cleanPath, '/')] as $localPath) {
            if ($localPath && is_file($localPath)) { return @file_get_contents($localPath) ?: null; }
        }
        return null;
    }

    private function fontFileUri(string $filename): string
    {
        $path = $this->rootPath . '/assets/fonts/' . $filename;
        return is_file($path) ? 'file://' . $path : '';
    }

    private function normalizeColor(?string $color): string
    {
        $color = trim((string)$color);
        if ($color === '') { return '#0f766e'; }
        if ($color[0] !== '#') { $color = '#' . $color; }
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#0f766e';
    }

    private function hexToRgba(string $hex, float $alpha = 0.1): string
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) !== 6) { return "rgba(15,118,110,{$alpha})"; }
        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
        return "rgba($r,$g,$b,$alpha)";
    }

    private function formatDateRange(array $dia): string
    {
        if (empty($dia['fecha_calculada'])) { return ''; }
        $inicio = date('d/m/Y', strtotime($dia['fecha_calculada']));
        if (!empty($dia['fecha_fin_calculada']) && $dia['fecha_fin_calculada'] !== $dia['fecha_calculada']) { return $inicio . ' - ' . date('d/m/Y', strtotime($dia['fecha_fin_calculada'])); }
        return $inicio;
    }

    private function formatServiceType(string $tipo): string
    {
        return ['actividad'=>'Actividad','transporte'=>'Transporte','alojamiento'=>'Alojamiento','comida'=>'Comida'][$tipo] ?? ucfirst($tipo);
    }

    private function shortText(?string $text, int $limit = 300): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$text)));
        if ($text === '' || mb_strlen($text, 'UTF-8') <= $limit) { return $text; }
        return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '...';
    }

    private function splitLocations(?string $location): array
    {
        $location = trim((string)$location);
        if ($location === '') { return []; }
        $parts = preg_split('/[,|→-]+/u', $location);
        $parts = array_values(array_filter(array_map('trim', $parts)));
        return array_slice($parts, 0, 3);
    }

    private function getMealsSummary(array $servicios, string $diaDescripcion = ''): string
    {
        $source = mb_strtolower($diaDescripcion, 'UTF-8');
        foreach ($servicios as $grupo) {
            if (!empty($grupo['principal'])) { $source .= ' ' . mb_strtolower((string)($grupo['principal']['nombre'] ?? ''), 'UTF-8'); }
        }
        $meals = [];
        if (str_contains($source, 'desayuno')) { $meals[] = 'Desayuno'; }
        if (str_contains($source, 'almuerzo') || str_contains($source, 'comida')) { $meals[] = 'Almuerzo'; }
        if (str_contains($source, 'cena')) { $meals[] = 'Cena'; }
        $meals = array_values(array_unique($meals));
        return empty($meals) ? 'Las comidas no están incluidas para este día' : '✓ ' . implode('   ✓ ', $meals);
    }
}
