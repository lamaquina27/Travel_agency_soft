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
    private int $subUserId = 0; // >0 cuando el PDF se genera en contexto de una subagencia
    private string $lang = 'es'; // idioma de los rótulos fijos del PDF (idioma_predeterminado del programa)

    public function __construct(int $programaId)
    {
        $this->db = Database::getInstance();
        $this->programaId = $programaId;
        $this->rootPath = dirname(__DIR__, 2);
    }

    /**
     * Genera el PDF con la marca y los precios de una subagencia (link público B2B).
     * Espeja el override de pages/itinerary.php: sobrescribe marca (logo/colores/nombre)
     * y mergea los precios de subagencia_tour_precios + nombre_cliente.
     */
    public function setSubAgencia(int $userId): void
    {
        $this->subUserId = $userId;
    }

    public function renderHtml(): string
    {
        $data = $this->getData();
        $this->prewarmRemoteImages(); // descarga TODAS las imágenes remotas en paralelo (clave de la lentitud)
        $programa = $data['programa'];
        // Idioma de los rótulos fijos del PDF (el contenido del agente no se traduce).
        $lang = strtolower(trim((string)($programa['idioma_predeterminado'] ?? 'es')));
        $this->lang = in_array($lang, ['es','en','fr','pt','it','de'], true) ? $lang : 'es';
        $dias = $data['dias'];
        $precios = $data['precios'];
        $agencia = $data['agencia'];
        $hotelDelta = (float)($data['hotel_delta'] ?? 0); // variación del hotel elegido por el cliente
        // El precio total que ve el cliente incluye la diferencia del hotel alternativo elegido
        $precioTotalEfectivo = (float)($precios['precio_total'] ?? 0) + $hotelDelta;

        $primary = $this->normalizeColor($agencia['primary_color'] ?? '#0f766e');
        $secondary = $this->normalizeColor($agencia['secondary_color'] ?? '#0f172a');
        $dark = '#101828';
        $mostrarPrecio = !isset($precios['mostrar_precio']) || (int)($precios['mostrar_precio']) === 1;

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
        <html lang="<?= htmlspecialchars($this->lang) ?>">
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
                body { margin:0; font-family: TravelPdf, DejaVu Sans, Arial, sans-serif; color:#2f3747; font-size:12px; line-height:1.45; background:#fff; word-wrap:break-word; overflow-wrap:break-word; }
                /* Evita que tokens largos (URLs, emails, nombres sin espacios) desborden su caja en dompdf */
                td, div, span, p, h1, h2, h3 { word-wrap:break-word; overflow-wrap:break-word; }
                img { display:block; }

                /* Opciones de alojamiento (hotel principal + alternativas con su variación) */
                .hotel-alts { margin-top:6px; }
                .hotel-alts-title { font-size:9px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
                .hotel-alts-table { width:100%; border-collapse:collapse; }
                .hotel-alts-table td { font-size:10px; padding:3px 6px; border:1px solid #eef0f4; }
                .hotel-alts-table .alt-name { color:#2f3747; }
                .hotel-alts-table .alt-delta { text-align:right; white-space:nowrap; font-weight:700; color:#334155; }
                .hotel-alts-table tr.alt-elegida td { background:#f0f7f2; color:#15803d; border-color:#cfe9d8; }
                .hotel-alts-table .alt-thumb { width:52px; padding:2px 4px; vertical-align:middle; }
                .hotel-alts-table .alt-thumb img { width:46px; height:31px; border-radius:3px; display:block; }
                .hotel-alts-table .alt-loc { display:block; font-size:8.5px; color:#94a3b8; font-weight:400; margin-top:1px; }
                .hotel-alts-table .alt-badge { display:inline-block; font-size:8px; font-weight:700; color:#15803d; border:1px solid #cfe9d8; border-radius:6px; padding:0 4px; margin-left:5px; text-transform:uppercase; letter-spacing:.03em; }

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
                .stage-day { color:<?= $primary ?>; font-weight:700; display:inline-block; min-width:48px; padding-right:8px; white-space:nowrap; }

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
                            <div class="hero-kicker"><?= $this->t('itin_personalizado') ?></div>

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
                            <?php else: ?>
                                <div style="width:100%;height:145mm;background:<?= $primary ?>;color:#fff;text-align:center;">
                                    <div style="padding-top:62mm;font-size:16px;font-weight:700;"><?= htmlspecialchars($destino ?: ($agencia['nombre'] ?? '')) ?></div>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <table class="cover-stats">
                    <tr>
                        <td><div class="stat-label"><?= $this->t('duracion') ?></div><div class="stat-value"><?= $duracionDias ?> <?= $this->t('dias') ?></div></td>
                        <td><div class="stat-label"><?= $this->t('viajeros') ?></div><div class="stat-value"><?= $viajeros ?></div></td>
                        <td><div class="stat-label"><?= $this->t('fechas') ?></div><div class="stat-value"><?= htmlspecialchars($programa['fecha_llegada_formatted'] ?? '') ?> - <?= htmlspecialchars($programa['fecha_salida_formatted'] ?? '') ?></div></td>
                    </tr>
                </table>
            </div>

            <div class="summary-page">
                <div class="summary-title"><?= $this->t('resumen_viaje') ?></div>
                <table class="summary-grid"><tr>
                    <td><div class="summary-label"><?= $this->t('agencia') ?></div><div class="summary-value"><?= htmlspecialchars($agencia['nombre'] ?? '') ?></div></td>
                    <td><div class="summary-label"><?= $this->t('destino') ?></div><div class="summary-value"><?= htmlspecialchars($destino) ?></div></td>
                    <td><div class="summary-label"><?= $this->t('inicio') ?></div><div class="summary-value"><?= htmlspecialchars($programa['fecha_llegada_formatted'] ?? '') ?></div></td>
                    <td><div class="summary-label"><?= $this->t('duracion') ?></div><div class="summary-value"><?= $duracionDias ?> <?= $this->t('dias') ?></div></td>
                </tr></table>
                <div class="stage-title"><?= $this->t('etapas') ?></div>
                <?php foreach ($dias as $dia): ?>
                    <div class="stage-row"><span class="stage-day"><?= htmlspecialchars($this->diaLabel($dia)) ?></span><?= htmlspecialchars($this->shortText($dia['titulo'] ?? '', 85)) ?></div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($dias as $dia): ?>
                <?php $dayImgs = $this->prepareDayImages($dia['imagenes'] ?? []); ?>
                <div class="day">
                    <div class="day-head">
                        <span class="day-number"><?= htmlspecialchars($this->diaLabel($dia)) ?></span>
                        <span class="day-date"><?= $this->formatDateRange($dia) ?></span>
                        <?php if (!empty($dia['titulo'])): ?><div class="day-title"><?= htmlspecialchars($dia['titulo']) ?></div><?php endif; ?>
                        <?php if (!empty($dia['ubicacion'])): ?><div class="chips"><?php foreach ($this->splitLocations($dia['ubicacion']) as $loc): ?><span class="chip"><?= htmlspecialchars($loc) ?></span><?php endforeach; ?></div><?php endif; ?>
                    </div>

                    <?php if (!empty($dayImgs)): ?>
                        <table class="photo-grid"><tr>
                            <?php foreach ($dayImgs as $img): ?><td><img src="<?= $img ?>" alt=""></td><?php endforeach; ?>
                        </tr></table>
                    <?php endif; ?>

                    <?php if (!empty($dia['descripcion'])): ?><div class="day-desc"><?= nl2br(htmlspecialchars($this->shortText($dia['descripcion'], 12000))) ?></div><?php endif; ?>

                    <?php if (!empty($dia['comidas_incluidas'])): ?>
                        <div class="contains"><?= $this->t('comidas') ?></div>
                        <div class="meals"><?= htmlspecialchars($this->getMealsSummary($dia)) ?></div>
                    <?php endif; ?>

                    <?php foreach (($dia['vuelos'] ?? []) as $vuelo): ?>
                        <?php $ftTitle = trim(implode(' · ', array_filter([$vuelo['codigo_vuelo'] ?? '', $vuelo['aerolinea'] ?? '']))); ?>
                        <div class="flight">
                            <div class="flight-title"><?= htmlspecialchars($ftTitle !== '' ? $ftTitle : $this->t('vuelo')) ?></div>
                            <table class="flight-table"><tr>
                                <td style="width:35%;"><div class="airport-code"><?= htmlspecialchars($vuelo['codigo_aeropuerto_origen'] ?? '') ?></div><div class="airport-city"><?= htmlspecialchars($vuelo['ciudad_origen'] ?? '') ?></div><div class="flight-time"><?= htmlspecialchars(substr((string)($vuelo['hora_salida'] ?? ''), 0, 5)) ?></div></td>
                                <td style="width:30%;"><div class="flight-mid"><?= $this->t('vuelo') ?> <?= (int)($vuelo['orden'] ?? 0) ?><br>→</div></td>
                                <td style="width:35%; text-align:right;"><div class="airport-code"><?= htmlspecialchars($vuelo['codigo_aeropuerto_destino'] ?? '') ?></div><div class="airport-city"><?= htmlspecialchars($vuelo['ciudad_destino'] ?? '') ?></div><div class="flight-time"><?= htmlspecialchars(substr((string)($vuelo['hora_llegada'] ?? ''), 0, 5)) ?></div></td>
                            </tr></table>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($this->getVisibleServices($dia['servicios'] ?? []) as $servicio): ?>
                        <?php $tipo = $servicio['tipo_servicio'] ?? ''; ?>
                        <?php if ($tipo === 'alojamiento'): ?>
                            <?php $hotelImg = $this->thumbToDataUri($servicio['alojamiento_imagen_principal'] ?? '', 140, 95); ?>
                            <table class="hotel"><tr>
                                <td class="hotel-marker"><div class="dot"></div></td>
                                <td class="hotel-line"><div class="hotel-kind"><?= $this->t('alojamiento') ?></div>
                                    <table class="hotel-box"><tr>
                                        <?php if ($hotelImg): ?><td style="width:84px;"><img class="hotel-photo" src="<?= $hotelImg ?>" alt=""></td><?php endif; ?>
                                        <td><div class="hotel-name"><?= htmlspecialchars($servicio['nombre'] ?? $this->t('hotel')) ?></div>
                                            <?php if (!empty($servicio['ubicacion'])): ?><div class="hotel-info"><?= htmlspecialchars($this->shortText($servicio['ubicacion'], 120)) ?></div><?php endif; ?>
                                            <?php if (!empty($servicio['acomodacion_nombre']) || !empty($servicio['acomodacion_capacidad'])): ?><div class="room"><?= !empty($servicio['acomodacion_nombre']) ? $this->t('habitacion') . ': ' . htmlspecialchars($servicio['acomodacion_nombre']) : '' ?><?= !empty($servicio['acomodacion_capacidad']) ? ' · ' . $this->t('pax') . ': ' . htmlspecialchars($servicio['acomodacion_capacidad']) : '' ?></div><?php endif; ?>
                                        </td>
                                    </tr></table>
                                    <?php $hotelAlts = $servicio['_alternativas'] ?? []; if (!empty($hotelAlts)):
                                        $altSel = false; foreach ($hotelAlts as $a) { if ((int)($a['seleccionado'] ?? 0) === 1) { $altSel = true; break; } }
                                        $principalElegido = !$altSel;
                                        $mon = $precios['moneda'] ?? '';
                                        ?>
                                        <div class="hotel-alts">
                                            <div class="hotel-alts-title"><?= $this->t('opciones_aloj') ?></div>
                                            <table class="hotel-alts-table">
                                                <tr class="<?= $principalElegido ? 'alt-elegida' : '' ?>">
                                                    <td class="alt-thumb"><?php $thP = $this->thumbToDataUri($servicio['alojamiento_imagen_principal'] ?? '', 96, 64); if ($thP): ?><img src="<?= $thP ?>" alt=""><?php endif; ?></td>
                                                    <td class="alt-name"><?= htmlspecialchars($servicio['nombre'] ?? $this->t('hotel')) ?> (<?= $this->t('incluido') ?>)<?php if ($principalElegido): ?><span class="alt-badge"><?= $this->t('seleccionado') ?></span><?php endif; ?><?php $locP = $this->shortText($servicio['ubicacion'] ?? '', 90); if ($locP !== ''): ?><span class="alt-loc"><?= htmlspecialchars($locP) ?></span><?php endif; ?></td>
                                                    <td class="alt-delta"><?= $this->t('precio_base') ?></td>
                                                </tr>
                                                <?php foreach ($hotelAlts as $a):
                                                    $d = (float)($a['variacion_precio'] ?? 0);
                                                    $eleg = (int)($a['seleccionado'] ?? 0) === 1;
                                                    $dl = $d > 0 ? ('+ ' . number_format($d, 0, ',', '.') . ' ' . $mon) : ($d < 0 ? ('− ' . number_format(abs($d), 0, ',', '.') . ' ' . $mon) : $this->t('sin_coste'));
                                                    ?>
                                                    <tr class="<?= $eleg ? 'alt-elegida' : '' ?>">
                                                        <td class="alt-thumb"><?php $thA = $this->thumbToDataUri($a['alojamiento_imagen_principal'] ?? '', 96, 64); if ($thA): ?><img src="<?= $thA ?>" alt=""><?php endif; ?></td>
                                                        <td class="alt-name"><?= htmlspecialchars($a['nombre'] ?? $this->t('alternativa')) ?><?php if ($eleg): ?><span class="alt-badge"><?= $this->t('seleccionado') ?></span><?php endif; ?><?php $locA = $this->shortText($a['ubicacion'] ?? '', 90); if ($locA !== ''): ?><span class="alt-loc"><?= htmlspecialchars($locA) ?></span><?php endif; ?></td>
                                                        <td class="alt-delta"><?= htmlspecialchars($dl) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr></table>
                        <?php elseif ($tipo !== 'comida'): ?>
                            <div class="activity"><div class="service-kind"><?= htmlspecialchars($this->formatServiceType($tipo)) ?></div><div class="activity-title"><?= htmlspecialchars($servicio['nombre'] ?? $this->t('servicio')) ?></div><?php if (!empty($servicio['descripcion'])): ?><div class="activity-desc"><?= nl2br(htmlspecialchars($this->shortText($servicio['descripcion'], 5000))) ?></div><?php endif; ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php
            // Mostrar la sección de precios SOLO si hay algo imprimible (evita una página
            // "Precio y condiciones" con la tabla vacía cuando no hay datos).
            $priceTextKeys = ['precio_incluye', 'precio_no_incluye', 'condiciones_generales', 'info_pasaporte', 'info_seguros', 'visados_entrada', 'requisitos_sanitarios', 'llegada_punto_encuentro', 'asistencia_emergencia', 'info_hoteles_servicios', 'informacion_practica'];
            $hasPriceText = false;
            foreach ($priceTextKeys as $k) { if (!empty($precios[$k])) { $hasPriceText = true; break; } }
            $showPriceTotal = $mostrarPrecio && !empty($precios['precio_total']);
            ?>
            <?php if ($showPriceTotal || $hasPriceText): ?>
                <div class="price-section">
                    <h2 class="section-title"><?= $this->t('precio_condiciones') ?></h2>
                    <table class="pricing-table">
                        <?php if ($mostrarPrecio && !empty($precios['precio_total'])): $mon = htmlspecialchars($precios['moneda'] ?? ''); ?>
                            <?php if (abs($hotelDelta) > 0.001): // hubo cambio de hotel → mostrar el desglose ?>
                                <tr><td class="price-label"><?= $this->t('precio_base') ?></td><td><?= $mon ?> <?= number_format((float)$precios['precio_total'], 0, ',', '.') ?></td></tr>
                                <tr><td class="price-label"><?= $this->t('ajuste_hotel') ?></td><td><?= ($hotelDelta >= 0 ? '+ ' : '− ') . $mon ?> <?= number_format(abs($hotelDelta), 0, ',', '.') ?></td></tr>
                            <?php endif; ?>
                            <tr><td class="price-label"><?= $this->t('precio_total') ?></td><td><span class="price-main"><?= $mon ?> <?= number_format($precioTotalEfectivo, 0, ',', '.') ?></span></td></tr>
                        <?php endif; ?>
                        <?php foreach ([['precio_incluye','incluye'],['precio_no_incluye','no_incluye'],['condiciones_generales','condiciones_generales'],['info_pasaporte','pasaporte'],['info_seguros','seguros'],['visados_entrada','visados'],['requisitos_sanitarios','req_sanitarios'],['llegada_punto_encuentro','llegada'],['asistencia_emergencia','asistencia'],['info_hoteles_servicios','info_hoteles'],['informacion_practica','info_practica']] as $row): ?>
                            <?php if (!empty($precios[$row[0]])): ?><tr><td class="price-label"><?= $this->t($row[1]) ?></td><td><?= nl2br(htmlspecialchars($precios[$row[0]])) ?></td></tr><?php endif; ?>
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
        $hotelDelta = 0.0; // suma de las variaciones de los hoteles elegidos por el cliente

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
            // Numeración real del día teniendo en cuenta su duración (días que abarca).
            // Un día con duracion_estancia=3 que empieza en el día 1 cubre "Día 1 – 3";
            // el siguiente día empieza en el 4 (no en el 2).
            $dia['dia_inicio_real'] = $diasAcumulados + 1;
            $dia['dia_fin_real']    = $diasAcumulados + (int)($dia['duracion_estancia'] ?? 1);
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
                if ((int)($servicio['es_alternativa'] ?? 0) !== 0) {
                    // Alternativa: adjuntarla a su grupo principal (para mostrar ambos hoteles en el PDF)
                    $pid = (int)($servicio['servicio_principal_id'] ?? 0);
                    foreach ($serviciosOrganizados as &$g) {
                        if (!empty($g['principal']) && (int)($g['principal']['id'] ?? 0) === $pid) { $g['alternativas'][] = $servicio; break; }
                    }
                    unset($g);
                    continue;
                }
                $orden = $servicio['orden'] ?? count($serviciosOrganizados) + 1;
                if (!isset($serviciosOrganizados[$orden])) { $serviciosOrganizados[$orden] = ['principal' => null, 'alternativas' => []]; }
                $serviciosOrganizados[$orden]['principal'] = $servicio;
            }
            ksort($serviciosOrganizados);
            $dia['servicios'] = $serviciosOrganizados;

            // Variación de la alternativa de hotel elegida por el cliente (para el precio del PDF)
            foreach ($serviciosOrganizados as $g) {
                if (empty($g['principal']) || ($g['principal']['tipo_servicio'] ?? '') !== 'alojamiento') { continue; }
                foreach (($g['alternativas'] ?? []) as $a) {
                    if ((int)($a['seleccionado'] ?? 0) === 1) { $hotelDelta += (float)($a['variacion_precio'] ?? 0); break; }
                }
            }

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
            'hotel_delta' => $hotelDelta,
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

        // Override por subagencia (link público B2B): marca + precios propios.
        if ($this->subUserId > 0) {
            $this->applySubAgencia();
        }

        return $this->data;
    }

    /**
     * Sobrescribe marca y precios con los de la subagencia (espeja pages/itinerary.php).
     */
    private function applySubAgencia(): void
    {
        $subConfig = $this->db->fetch(
            "SELECT nombre, logo_url, primary_color, secondary_color, divisa
             FROM config_sub_agencias WHERE user_id = ?",
            [$this->subUserId]
        );
        if ($subConfig) {
            if (!empty($subConfig['nombre']))         { $this->data['agencia']['nombre'] = $subConfig['nombre']; }
            if (!empty($subConfig['logo_url']))       { $this->data['agencia']['logo_url'] = $subConfig['logo_url']; }
            if (!empty($subConfig['primary_color']))  { $this->data['agencia']['primary_color'] = $subConfig['primary_color']; }
            if (!empty($subConfig['secondary_color'])){ $this->data['agencia']['secondary_color'] = $subConfig['secondary_color']; }
        }

        $subPrecios = $this->db->fetch(
            "SELECT * FROM subagencia_tour_precios WHERE user_id = ? AND solicitud_id = ?",
            [$this->subUserId, $this->programaId]
        );
        if ($subPrecios) {
            $this->data['precios'] = array_merge($this->data['precios'] ?? [], [
                'precio_adulto'           => $subPrecios['precio_adulto'],
                'precio_nino'             => $subPrecios['precio_nino'],
                'cantidad_adultos'        => $subPrecios['cantidad_adultos'],
                'cantidad_ninos'          => $subPrecios['cantidad_ninos'],
                'precio_total'            => $subPrecios['precio_total'],
                'noches_incluidas'        => $subPrecios['noches_incluidas'],
                'moneda'                  => $subConfig['divisa'] ?? ($this->data['precios']['moneda'] ?? ''),
                'precio_incluye'          => $subPrecios['precio_incluye'],
                'precio_no_incluye'       => $subPrecios['precio_no_incluye'],
                'condiciones_generales'   => $subPrecios['condiciones_generales'],
                'movilidad_reducida'      => $subPrecios['movilidad_reducida'],
                'info_pasaporte'          => $subPrecios['info_pasaporte'],
                'info_seguros'            => $subPrecios['info_seguros'],
                'visados_entrada'         => $subPrecios['visados_entrada'],
                'requisitos_sanitarios'   => $subPrecios['requisitos_sanitarios'],
                'llegada_punto_encuentro' => $subPrecios['llegada_punto_encuentro'],
                'asistencia_emergencia'   => $subPrecios['asistencia_emergencia'],
                'info_hoteles_servicios'  => $subPrecios['info_hoteles_servicios'],
                'informacion_practica'    => $subPrecios['informacion_practica'],
                // La subagencia decide de forma independiente; si no eligió (NULL/columna ausente)
                // hereda el ajuste del tour principal.
                'mostrar_precio'          => (!array_key_exists('mostrar_precio', $subPrecios) || $subPrecios['mostrar_precio'] === null)
                    ? ($this->data['precios']['mostrar_precio'] ?? 1)
                    : (int) $subPrecios['mostrar_precio'],
            ]);
            if (!empty($subPrecios['nombre_cliente'])) {
                $this->data['programa']['nombre']   = $subPrecios['nombre_cliente'];
                $this->data['programa']['apellido'] = '';
            }
        }
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
        foreach ($servicios as $grupo) {
            if (empty($grupo['principal'])) { continue; }
            $s = $grupo['principal'];
            $tipo = $s['tipo_servicio'] ?? '';
            if ($tipo === 'alojamiento') {
                // Dedup por nombre (evita repetir el mismo hotel), pero permite varios
                // hoteles DISTINTOS el mismo día (p.ej. cambio de hotel).
                $key = mb_strtolower(trim((string)($s['nombre'] ?? '')), 'UTF-8');
                if ($key && isset($hotelsSeen[$key])) { continue; }
                $hotelsSeen[$key] = true;
            }
            $s['_alternativas'] = $grupo['alternativas'] ?? []; // para mostrar opciones en el PDF
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

        // GD/libwebp de este build de PHP revienta con un FATAL ("gd-webp cannot allocate
        // temporary buffer") al decodificar WebP reales, matando TODA la generación del PDF.
        // No hay decodificador alternativo (sin Imagick ni convertidor CLI), así que se omite
        // la imagen WebP en lugar de arriesgar el fatal. Ver isWebpBinary().
        if ($this->isWebpBinary($binary)) { return ''; }

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
        $path = trim((string)$path);
        if ($path === '') { return ''; }

        // Caché en disco (igual que thumbToDataUri): el logo/portada no se re-descargan
        // ni re-codifican en cada PDF. Clave = ruta original.
        $cacheDir = $this->rootPath . '/tmp/pdf-thumbs';
        if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
        $cacheFile = $cacheDir . '/full_' . md5($path) . '.bin';
        if (is_file($cacheFile)) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false && $cached !== '') {
                $info = @getimagesizefromstring($cached);
                if ($info && !empty($info['mime'])) {
                    return 'data:' . $info['mime'] . ';base64,' . base64_encode($cached);
                }
            }
        }

        $binary = $this->readImageBinary($path);
        if (!$binary) { return ''; }
        // No pasar WebP a dompdf: internamente también usa GD y se caería igual (ver isWebpBinary).
        if ($this->isWebpBinary($binary)) { return ''; }
        $info = @getimagesizefromstring($binary);
        if (!$info || empty($info['mime'])) { return ''; }
        @file_put_contents($cacheFile, $binary);
        return 'data:' . $info['mime'] . ';base64,' . base64_encode($binary);
    }

    /**
     * Detecta si el binario es un WebP (magic bytes "RIFF"...."WEBP"). Este build de PHP
     * no puede decodificar WebP reales sin lanzar un fatal de GD/libwebp, así que el PDF
     * los omite por completo.
     */
    private function isWebpBinary(string $binary): bool
    {
        return strlen($binary) >= 12
            && substr($binary, 0, 4) === 'RIFF'
            && substr($binary, 8, 4) === 'WEBP';
    }

    /**
     * Descarga EN PARALELO (curl_multi) todas las imágenes remotas que usará el PDF y
     * las guarda en tmp/pdf-thumbs/raw_<md5>.bin. Antes cada imagen se bajaba una por una
     * de forma síncrona, sumando los timeouts (con muchas imágenes lentas/caídas → minutos).
     * Si no hay curl, no hace nada y readImageBinary cae al fetch secuencial.
     */
    private function prewarmRemoteImages(): void
    {
        if (!function_exists('curl_multi_init')) { return; }

        // Recolectar todas las rutas de imagen del PDF (logo, portada, días, hoteles y alternativas).
        $paths = [];
        $paths[] = $this->data['agencia']['logo_url'] ?? '';
        $paths[] = $this->data['programa']['foto_portada'] ?? '';
        foreach (($this->data['dias'] ?? []) as $dia) {
            foreach (($dia['imagenes'] ?? []) as $img) { $paths[] = $img; }
            foreach (($dia['servicios'] ?? []) as $g) {
                if (!empty($g['principal']['alojamiento_imagen_principal'])) { $paths[] = $g['principal']['alojamiento_imagen_principal']; }
                foreach (($g['alternativas'] ?? []) as $a) {
                    if (!empty($a['alojamiento_imagen_principal'])) { $paths[] = $a['alojamiento_imagen_principal']; }
                }
            }
        }

        // Quedarnos solo con URLs remotas únicas que aún no estén cacheadas ni marcadas como caídas.
        $cacheDir = $this->rootPath . '/tmp/pdf-thumbs';
        if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
        $toFetch = [];
        foreach (array_unique(array_filter(array_map('trim', $paths))) as $url) {
            if (!preg_match('/^https?:\/\//i', $url)) { continue; }
            $hash = md5($url);
            if (is_file($cacheDir . '/raw_' . $hash . '.bin')) { continue; }
            $failFile = $cacheDir . '/' . $hash . '.fail';
            if (is_file($failFile) && (time() - (int)@filemtime($failFile)) < 900) { continue; }
            $toFetch[$url] = $hash;
        }
        if (!$toFetch) { return; }

        $mh = curl_multi_init();
        $handles = [];
        foreach ($toFetch as $url => $hash) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT      => 'TravelSoftPDF/1.0',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[] = ['ch' => $ch, 'hash' => $hash];
        }

        // Bucle de ejecución del multi-handle (todas las descargas a la vez).
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) { curl_multi_select($mh, 1.0); }
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $h) {
            $ch = $h['ch'];
            $body = curl_multi_getcontent($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $failFile = $cacheDir . '/' . $h['hash'] . '.fail';
            if (is_string($body) && $body !== '' && $code >= 200 && $code < 400) {
                @file_put_contents($cacheDir . '/raw_' . $h['hash'] . '.bin', $body);
                if (is_file($failFile)) { @unlink($failFile); }
            } else {
                @file_put_contents($failFile, '1'); // caída: la caché negativa evitará reintentos
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }

    private function readImageBinary(?string $path): ?string
    {
        $path = trim((string)$path);
        if ($path === '') { return null; }
        if (preg_match('/^https?:\/\//i', $path)) {
            $cacheDir = $this->rootPath . '/tmp/pdf-thumbs';
            if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
            $hash = md5($path);

            // 1) Caché de bytes crudos del prewarm en paralelo (prewarmRemoteImages):
            //    si ya se descargó esta URL, no tocar la red.
            $rawFile = $cacheDir . '/raw_' . $hash . '.bin';
            if (is_file($rawFile)) {
                $raw = @file_get_contents($rawFile);
                if ($raw !== false && $raw !== '') { return $raw; }
            }

            // 2) Caché NEGATIVA: si esta URL ya falló hace poco, no reintentar. Antes cada PDF
            //    esperaba el timeout completo por cada imagen caída (con 30-50 imágenes → minutos).
            $failFile = $cacheDir . '/' . $hash . '.fail';
            if (is_file($failFile) && (time() - (int)@filemtime($failFile)) < 900) {
                return null; // marcada como caída en los últimos 15 min
            }

            // 3) Fallback secuencial (cuando no hubo prewarm o no hay curl).
            //    timeout corto (antes 6s) para acotar el peor caso de un host que no responde.
            $context = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'header' => "User-Agent: TravelSoftPDF/1.0\r\n"], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $binary = @file_get_contents($path, false, $context);
            if ($binary === false || $binary === '') {
                @file_put_contents($failFile, '1'); // recordar el fallo: no reintentar en cada PDF
                return null;
            }
            @file_put_contents($rawFile, $binary);            // cachear para los próximos PDF
            if (is_file($failFile)) { @unlink($failFile); }   // funcionó: limpiar marcador previo
            return $binary;
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

    /**
     * Traduce un rótulo FIJO del PDF al idioma del programa ($this->lang).
     * Solo afecta a la "estructura" del documento (títulos, etiquetas); el contenido
     * escrito por el agente (descripciones, nombres de hotel, condiciones) NO se traduce,
     * porque dompdf no ejecuta JS y no hay API de traducción de texto libre.
     * Si falta la clave o el idioma, cae a español y, en último caso, devuelve la clave.
     */
    private function t(string $key): string
    {
        static $dict = null;
        if ($dict === null) {
            $dict = [
                'es' => [
                    'itin_personalizado'=>'Itinerario personalizado','duracion'=>'Duración','dias'=>'días','viajeros'=>'Viajeros','fechas'=>'Fechas',
                    'resumen_viaje'=>'Resumen del viaje','agencia'=>'Agencia','destino'=>'Destino','inicio'=>'Inicio','etapas'=>'Las principales etapas del viaje',
                    'comidas'=>'Comidas','vuelo'=>'Vuelo','alojamiento'=>'Alojamiento','hotel'=>'Hotel','habitacion'=>'Habitación','pax'=>'Pax',
                    'opciones_aloj'=>'Opciones de alojamiento','incluido'=>'incluido','precio_base'=>'Precio base','sin_coste'=>'Sin coste adicional','seleccionado'=>'Seleccionado',
                    'alternativa'=>'Alternativa','servicio'=>'Servicio','precio_condiciones'=>'Precio y condiciones','precio_total'=>'Precio total','ajuste_hotel'=>'Ajuste por hotel elegido',
                    'incluye'=>'Incluye','no_incluye'=>'No incluye','condiciones_generales'=>'Condiciones generales','pasaporte'=>'Pasaporte y visados',
                    'seguros'=>'Seguros','visados'=>'Visados y requisitos de entrada','req_sanitarios'=>'Requisitos sanitarios','llegada'=>'Llegada y punto de encuentro',
                    'asistencia'=>'Asistencia y emergencias','info_hoteles'=>'Información de hoteles y servicios','info_practica'=>'Información práctica',
                    'dia'=>'Día','desayuno'=>'Desayuno','almuerzo'=>'Almuerzo','cena'=>'Cena','comidas_incluidas'=>'Comidas incluidas',
                    't_actividad'=>'Actividad','t_transporte'=>'Transporte','t_alojamiento'=>'Alojamiento','t_comida'=>'Comida',
                ],
                'en' => [
                    'itin_personalizado'=>'Personalized itinerary','duracion'=>'Duration','dias'=>'days','viajeros'=>'Travelers','fechas'=>'Dates',
                    'resumen_viaje'=>'Trip summary','agencia'=>'Agency','destino'=>'Destination','inicio'=>'Start','etapas'=>'Main stages of the trip',
                    'comidas'=>'Meals','vuelo'=>'Flight','alojamiento'=>'Accommodation','hotel'=>'Hotel','habitacion'=>'Room','pax'=>'Pax',
                    'opciones_aloj'=>'Accommodation options','incluido'=>'included','precio_base'=>'Base price','sin_coste'=>'No additional cost','seleccionado'=>'Selected',
                    'alternativa'=>'Alternative','servicio'=>'Service','precio_condiciones'=>'Price and conditions','precio_total'=>'Total price','ajuste_hotel'=>'Selected hotel adjustment',
                    'incluye'=>'Included','no_incluye'=>'Not included','condiciones_generales'=>'General conditions','pasaporte'=>'Passport and visas',
                    'seguros'=>'Insurance','visados'=>'Visas and entry requirements','req_sanitarios'=>'Health requirements','llegada'=>'Arrival and meeting point',
                    'asistencia'=>'Assistance and emergencies','info_hoteles'=>'Hotel and service information','info_practica'=>'Practical information',
                    'dia'=>'Day','desayuno'=>'Breakfast','almuerzo'=>'Lunch','cena'=>'Dinner','comidas_incluidas'=>'Meals included',
                    't_actividad'=>'Activity','t_transporte'=>'Transport','t_alojamiento'=>'Accommodation','t_comida'=>'Meal',
                ],
                'fr' => [
                    'itin_personalizado'=>'Itinéraire personnalisé','duracion'=>'Durée','dias'=>'jours','viajeros'=>'Voyageurs','fechas'=>'Dates',
                    'resumen_viaje'=>'Résumé du voyage','agencia'=>'Agence','destino'=>'Destination','inicio'=>'Début','etapas'=>'Les principales étapes du voyage',
                    'comidas'=>'Repas','vuelo'=>'Vol','alojamiento'=>'Hébergement','hotel'=>'Hôtel','habitacion'=>'Chambre','pax'=>'Pax',
                    'opciones_aloj'=>"Options d'hébergement",'incluido'=>'inclus','precio_base'=>'Prix de base','sin_coste'=>'Sans coût supplémentaire','seleccionado'=>'Sélectionné',
                    'alternativa'=>'Alternative','servicio'=>'Service','precio_condiciones'=>'Prix et conditions','precio_total'=>'Prix total','ajuste_hotel'=>'Ajustement hôtel choisi',
                    'incluye'=>'Inclus','no_incluye'=>'Non inclus','condiciones_generales'=>'Conditions générales','pasaporte'=>'Passeport et visas',
                    'seguros'=>'Assurances','visados'=>"Visas et conditions d'entrée",'req_sanitarios'=>'Exigences sanitaires','llegada'=>'Arrivée et point de rencontre',
                    'asistencia'=>'Assistance et urgences','info_hoteles'=>'Informations sur les hôtels et services','info_practica'=>'Informations pratiques',
                    'dia'=>'Jour','desayuno'=>'Petit-déjeuner','almuerzo'=>'Déjeuner','cena'=>'Dîner','comidas_incluidas'=>'Repas inclus',
                    't_actividad'=>'Activité','t_transporte'=>'Transport','t_alojamiento'=>'Hébergement','t_comida'=>'Repas',
                ],
                'pt' => [
                    'itin_personalizado'=>'Itinerário personalizado','duracion'=>'Duração','dias'=>'dias','viajeros'=>'Viajantes','fechas'=>'Datas',
                    'resumen_viaje'=>'Resumo da viagem','agencia'=>'Agência','destino'=>'Destino','inicio'=>'Início','etapas'=>'As principais etapas da viagem',
                    'comidas'=>'Refeições','vuelo'=>'Voo','alojamiento'=>'Alojamento','hotel'=>'Hotel','habitacion'=>'Quarto','pax'=>'Pax',
                    'opciones_aloj'=>'Opções de alojamento','incluido'=>'incluído','precio_base'=>'Preço base','sin_coste'=>'Sem custo adicional','seleccionado'=>'Selecionado',
                    'alternativa'=>'Alternativa','servicio'=>'Serviço','precio_condiciones'=>'Preço e condições','precio_total'=>'Preço total','ajuste_hotel'=>'Ajuste por hotel escolhido',
                    'incluye'=>'Inclui','no_incluye'=>'Não inclui','condiciones_generales'=>'Condições gerais','pasaporte'=>'Passaporte e vistos',
                    'seguros'=>'Seguros','visados'=>'Vistos e requisitos de entrada','req_sanitarios'=>'Requisitos sanitários','llegada'=>'Chegada e ponto de encontro',
                    'asistencia'=>'Assistência e emergências','info_hoteles'=>'Informações de hotéis e serviços','info_practica'=>'Informações práticas',
                    'dia'=>'Dia','desayuno'=>'Pequeno-almoço','almuerzo'=>'Almoço','cena'=>'Jantar','comidas_incluidas'=>'Refeições incluídas',
                    't_actividad'=>'Atividade','t_transporte'=>'Transporte','t_alojamiento'=>'Alojamento','t_comida'=>'Refeição',
                ],
                'it' => [
                    'itin_personalizado'=>'Itinerario personalizzato','duracion'=>'Durata','dias'=>'giorni','viajeros'=>'Viaggiatori','fechas'=>'Date',
                    'resumen_viaje'=>'Riepilogo del viaggio','agencia'=>'Agenzia','destino'=>'Destinazione','inicio'=>'Inizio','etapas'=>'Le principali tappe del viaggio',
                    'comidas'=>'Pasti','vuelo'=>'Volo','alojamiento'=>'Alloggio','hotel'=>'Hotel','habitacion'=>'Camera','pax'=>'Pax',
                    'opciones_aloj'=>'Opzioni di alloggio','incluido'=>'incluso','precio_base'=>'Prezzo base','sin_coste'=>'Senza costi aggiuntivi','seleccionado'=>'Selezionato',
                    'alternativa'=>'Alternativa','servicio'=>'Servizio','precio_condiciones'=>'Prezzo e condizioni','precio_total'=>'Prezzo totale','ajuste_hotel'=>'Adeguamento hotel scelto',
                    'incluye'=>'Incluso','no_incluye'=>'Non incluso','condiciones_generales'=>'Condizioni generali','pasaporte'=>'Passaporto e visti',
                    'seguros'=>'Assicurazioni','visados'=>"Visti e requisiti d'ingresso",'req_sanitarios'=>'Requisiti sanitari','llegada'=>"Arrivo e punto d'incontro",
                    'asistencia'=>'Assistenza ed emergenze','info_hoteles'=>'Informazioni su hotel e servizi','info_practica'=>'Informazioni pratiche',
                    'dia'=>'Giorno','desayuno'=>'Colazione','almuerzo'=>'Pranzo','cena'=>'Cena','comidas_incluidas'=>'Pasti inclusi',
                    't_actividad'=>'Attività','t_transporte'=>'Trasporto','t_alojamiento'=>'Alloggio','t_comida'=>'Pasto',
                ],
                'de' => [
                    'itin_personalizado'=>'Individuelle Reiseroute','duracion'=>'Dauer','dias'=>'Tage','viajeros'=>'Reisende','fechas'=>'Reisedaten',
                    'resumen_viaje'=>'Reiseübersicht','agencia'=>'Agentur','destino'=>'Reiseziel','inicio'=>'Beginn','etapas'=>'Die wichtigsten Etappen der Reise',
                    'comidas'=>'Mahlzeiten','vuelo'=>'Flug','alojamiento'=>'Unterkunft','hotel'=>'Hotel','habitacion'=>'Zimmer','pax'=>'Pax',
                    'opciones_aloj'=>'Unterkunftsoptionen','incluido'=>'inbegriffen','precio_base'=>'Grundpreis','sin_coste'=>'Ohne Aufpreis','seleccionado'=>'Ausgewählt',
                    'alternativa'=>'Alternative','servicio'=>'Leistung','precio_condiciones'=>'Preis und Bedingungen','precio_total'=>'Gesamtpreis','ajuste_hotel'=>'Anpassung gewähltes Hotel',
                    'incluye'=>'Inbegriffen','no_incluye'=>'Nicht inbegriffen','condiciones_generales'=>'Allgemeine Bedingungen','pasaporte'=>'Reisepass und Visa',
                    'seguros'=>'Versicherungen','visados'=>'Visa und Einreisebestimmungen','req_sanitarios'=>'Gesundheitsvorschriften','llegada'=>'Ankunft und Treffpunkt',
                    'asistencia'=>'Hilfe und Notfälle','info_hoteles'=>'Informationen zu Hotels und Leistungen','info_practica'=>'Praktische Informationen',
                    'dia'=>'Tag','desayuno'=>'Frühstück','almuerzo'=>'Mittagessen','cena'=>'Abendessen','comidas_incluidas'=>'Mahlzeiten inbegriffen',
                    't_actividad'=>'Aktivität','t_transporte'=>'Transport','t_alojamiento'=>'Unterkunft','t_comida'=>'Mahlzeit',
                ],
            ];
        }
        $lang = isset($dict[$this->lang]) ? $this->lang : 'es';
        return $dict[$lang][$key] ?? $dict['es'][$key] ?? $key;
    }

    private function formatServiceType(string $tipo): string
    {
        $map = ['actividad'=>'t_actividad','transporte'=>'t_transporte','alojamiento'=>'t_alojamiento','comida'=>'t_comida'];
        return isset($map[$tipo]) ? $this->t($map[$tipo]) : ucfirst($tipo);
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

    /**
     * Etiqueta del día teniendo en cuenta su duración: "Día 4" o "Día 1 – 3".
     */
    private function diaLabel(array $dia): string
    {
        $ini = (int) ($dia['dia_inicio_real'] ?? $dia['dia_numero'] ?? 1);
        $fin = (int) ($dia['dia_fin_real'] ?? $ini);
        $dia_lbl = $this->t('dia');
        return $fin > $ini ? "{$dia_lbl} {$ini} – {$fin}" : "{$dia_lbl} {$ini}";
    }

    /**
     * Comidas del día a partir de los campos REALES de programa_dias
     * (comidas_incluidas/desayuno/almuerzo/cena), igual que la vista web.
     * Antes se "adivinaba" por palabras en la descripción → falsos positivos
     * ("dice que hay comida cuando no la incluye").
     */
    private function getMealsSummary(array $dia): string
    {
        $meals = [];
        if (!empty($dia['desayuno'])) { $meals[] = $this->t('desayuno'); }
        if (!empty($dia['almuerzo'])) { $meals[] = $this->t('almuerzo'); }
        if (!empty($dia['cena']))     { $meals[] = $this->t('cena'); }
        if (empty($meals)) { return $this->t('comidas_incluidas'); }
        return implode(' · ', $meals);
    }
}
