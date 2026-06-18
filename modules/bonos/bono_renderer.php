<?php

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/config_functions.php';
require_once dirname(__DIR__, 2) . '/classes/FechaCalculator.php';

class BonoRenderer
{
    private Database $db;
    private int $programaId;
    private int $agenciaId;
    private int $userId;
    private int $hotelsPerPage;

    public function __construct(int $programaId, int $hotelsPerPage = 1)
    {
        $this->db = Database::getInstance();
        $this->programaId = $programaId;
        $this->agenciaId = (int) ($_SESSION['agencia_id'] ?? 0);
        $this->userId = (int) ($_SESSION['user_id'] ?? 0);
        $this->hotelsPerPage = max(1, min(4, $hotelsPerPage));

        if (!$this->agenciaId || !$this->userId) {
            throw new Exception('Sesión inválida o agencia no asignada.');
        }
    }

    public function getData(): array
    {
        $programa = $this->db->fetch(
            "SELECT 
                ps.*,
                pp.titulo_programa
            FROM programa_solicitudes ps
            LEFT JOIN programa_personalizacion pp ON pp.solicitud_id = ps.id
            WHERE ps.id = ?
            AND ps.user_id = ?
            AND ps.agencia_id = ?
            LIMIT 1",
            [$this->programaId, $this->userId, $this->agenciaId]
        );

        if (!$programa) {
            throw new Exception('Programa no encontrado o sin permisos.');
        }

        $viajeros = $this->db->fetchAll(
            "SELECT 
                v.nombre,
                v.apellido,
                v.tipo_documento,
                v.numero_documento
            FROM viajeros_solicitud vs
            INNER JOIN viajeros v ON v.id = vs.viajero_id
            WHERE vs.solicitud_id = ?
            AND v.agencia_id = ?
            ORDER BY v.nombre ASC, v.apellido ASC",
            [$this->programaId, $this->agenciaId]
        );

        $dias = $this->db->fetchAll(
            "SELECT 
                *,
                COALESCE(duracion_estancia, 1) AS duracion_estancia
            FROM programa_dias
            WHERE solicitud_id = ?
            ORDER BY dia_numero ASC",
            [$this->programaId]
        );

        $dias = FechaCalculator::calcularFechasDias($dias, $programa['fecha_llegada']);

        $mapaFechas = [];
        foreach ($dias as $dia) {
            $checkin = $dia['fecha_calculada'];
            $noches = (int) ($dia['duracion_estancia'] ?? 1);

            $mapaFechas[(int) $dia['dia_numero']] = [
                'checkin' => $checkin,
                'checkout' => date('Y-m-d', strtotime($checkin . " +{$noches} days")),
                'noches' => $noches
            ];
        }

        // Trae principal + alternativas; luego se elige por grupo la opción EFECTIVA
        // (la que el cliente marcó como seleccionada, o el principal si no eligió).
        $hotelesRaw = $this->db->fetchAll(
            "SELECT
                pds.id AS servicio_id,
                pds.es_alternativa,
                pds.servicio_principal_id,
                pds.seleccionado,
                bv.nombre AS hotel_nombre,
                bv.ubicacion,
                a.tipo_acomodacion,
                a.descripcion,
                a.acomodacion,
                pd.dia_numero,
                pd.comidas_incluidas,
                pd.desayuno,
                pd.almuerzo,
                pd.cena,
                COALESCE(pd.duracion_estancia, 1) AS noches
            FROM programa_dias pd
            INNER JOIN programa_dias_servicios pds ON pds.programa_dia_id = pd.id
            INNER JOIN biblioteca_alojamientos bv ON bv.id = pds.biblioteca_item_id
            LEFT JOIN acomodaciones a ON a.id = pds.acomodacion_id
            WHERE pd.solicitud_id = ?
            AND pds.tipo_servicio = 'alojamiento'
            AND bv.agencia_id = ?
            ORDER BY pd.dia_numero ASC, pds.es_alternativa ASC, pds.orden_alternativa ASC",
            [$this->programaId, $this->agenciaId]
        );

        // Reducir cada grupo (principal + sus alternativas) a la opción efectiva
        $grupos = [];
        foreach ($hotelesRaw as $row) {
            $gid = !empty($row['es_alternativa']) ? (int) $row['servicio_principal_id'] : (int) $row['servicio_id'];
            if (!isset($grupos[$gid])) {
                $grupos[$gid] = ['principal' => null, 'elegida' => null];
            }
            if (empty($row['es_alternativa'])) {
                $grupos[$gid]['principal'] = $row;
            }
            if ((int) ($row['seleccionado'] ?? 0) === 1) {
                $grupos[$gid]['elegida'] = $row;
            }
        }
        $hoteles = [];
        foreach ($grupos as $g) {
            $efectiva = $g['elegida'] ?: $g['principal'];
            if ($efectiva) {
                $hoteles[] = $efectiva;
            }
        }
        usort($hoteles, fn($a, $b) => (int) $a['dia_numero'] <=> (int) $b['dia_numero']);

        foreach ($hoteles as &$hotel) {
            $fecha = $mapaFechas[(int) $hotel['dia_numero']] ?? null;

            $hotel['checkin'] = $fecha['checkin'] ?? null;
            $hotel['checkout'] = $fecha['checkout'] ?? null;
            $hotel['noches'] = $fecha['noches'] ?? (int) ($hotel['noches'] ?? 1);
        }
        unset($hotel);

        // Agrupar noches CONSECUTIVAS del mismo hotel en un solo voucher coherente.
        // Se agrupa por identidad de hotel y por contigüidad de fechas (el check-in
        // de la siguiente noche cae dentro/al final de la estancia anterior), aunque
        // en el listado haya varios hoteles intercalados por día.
        $agrupados = [];
        $abiertos = []; // identidad => índice del grupo "abierto" para esa identidad
        foreach ($hoteles as $h) {
            $key = mb_strtolower(trim(
                ($h['hotel_nombre'] ?? '') . '|' . ($h['ubicacion'] ?? '') . '|' .
                ($h['acomodacion'] ?? '') . '|' . ($h['tipo_acomodacion'] ?? '')
            ));

            if (isset($abiertos[$key])) {
                $idx = $abiertos[$key];
                $prevOut = $agrupados[$idx]['checkout'] ?? null;
                // Contiguo si la nueva noche empieza en/antes del check-out anterior
                if ($prevOut && !empty($h['checkin']) && $h['checkin'] <= $prevOut) {
                    if (!empty($h['checkout'])) {
                        $agrupados[$idx]['checkout'] = $h['checkout'];
                    }
                    $agrupados[$idx]['dia_fin'] = (int) $h['dia_numero'];
                    foreach (['comidas_incluidas', 'desayuno', 'almuerzo', 'cena'] as $m) {
                        if (!empty($h[$m])) {
                            $agrupados[$idx][$m] = $h[$m];
                        }
                    }
                    continue;
                }
            }

            // Nueva estancia (primera vez o tras un hueco)
            $h['_key'] = $key;
            $h['dia_inicio'] = (int) $h['dia_numero'];
            $h['dia_fin'] = (int) $h['dia_numero'];
            $agrupados[] = $h;
            $abiertos[$key] = count($agrupados) - 1;
        }
        // Recalcular noches de cada grupo según check-in / check-out.
        foreach ($agrupados as &$g) {
            if (!empty($g['checkin']) && !empty($g['checkout'])) {
                $n = (int) round((strtotime($g['checkout']) - strtotime($g['checkin'])) / 86400);
                $g['noches'] = max(1, $n);
            }
        }
        unset($g);
        $hoteles = $agrupados;

        $agencia = $this->db->fetch(
            "SELECT 
                nombre,
                logo_url,
                email_contacto,
                telefono,
                admin_primary_color,
                admin_secondary_color,
                agent_primary_color,
                agent_secondary_color
            FROM agencias
            WHERE id = ? AND activa = 1
            LIMIT 1",
            [$this->agenciaId]
        );

        if (!$agencia) {
            $agencia = [];
        }

        $role = $_SESSION['role'] ?? 'agent';

        return [
            'programa' => $programa,
            'viajeros' => $viajeros,
            'hoteles' => $hoteles,
            'agencia' => [
                'nombre' => $agencia['nombre'] ?? 'Travel Soft',
                'logo_url' => $agencia['logo_url'] ?? '',
                'email_contacto' => $agencia['email_contacto'] ?? '',
                'telefono' => $agencia['telefono'] ?? '',
                'primary_color' => $agencia['agent_primary_color']
                    ?: ($agencia['admin_primary_color'] ?: '#667eea'),

                'secondary_color' => $agencia['agent_secondary_color']
                    ?: ($agencia['admin_secondary_color'] ?: '#764ba2')
            ]
        ];
    }

    private function formatTipoDocumento($tipo): string
    {
        $map = [
            '1' => 'CC/DNI/CI',
            '2' => 'Pasaporte',
            '3' => 'Cédula de extranjería/NIE',
            '4' => 'Tarjeta de identidad',
            '5' => 'Registro civil',
        ];

        $key = (string) $tipo;

        return $map[$key] ?? 'Documento';
    }

    private function getServiciosHotel(array $hotel): string
    {
        $servicios = [];

        if (!empty($hotel['comidas_incluidas'])) {
            if (!empty($hotel['desayuno'])) {
                $servicios[] = 'Breakfast';
            }

            if (!empty($hotel['almuerzo'])) {
                $servicios[] = 'Lunch';
            }

            if (!empty($hotel['cena'])) {
                $servicios[] = 'Dinner';
            }
        }

        if (empty($servicios)) {
            return 'Accommodation according to confirmed reservation.';
        }

        return 'Accommodation with ' . implode(', ', $servicios) . '.';
    }

    public function renderHtml(bool $isPdf = false): string
    {
        $data = $this->getData();

        $programa = $data['programa'];
        $viajeros = $data['viajeros'];
        $hoteles = $data['hoteles'];
        $agencia = $data['agencia'];

        $primary = htmlspecialchars($agencia['primary_color']);
        $secondary = htmlspecialchars($agencia['secondary_color']);
        $logo = $this->resolveLogo($agencia['logo_url']);
        $file = $programa['id_solicitud'] ?: ('Programa #' . $programa['id']);
        $rootPath = dirname(__DIR__, 2);
        $fontRegular = $rootPath . '/assets/fonts/NotoSansThai-Regular.ttf';
        $fontBold = $rootPath . '/assets/fonts/NotoSansThai-Bold.ttf';
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <title>HOTEL RESERVATION VOUCHER</title>
            <style>
                <?php if (is_file($fontRegular)): ?>
                    @font-face {
                        font-family: TravelPdf;
                        src: url('file://<?= $fontRegular ?>') format('truetype');
                        font-weight: 400;
                    }

                <?php endif; ?>
                <?php if (is_file($fontBold)): ?>
                    @font-face {
                        font-family: TravelPdf;
                        src: url('file://<?= $fontBold ?>') format('truetype');
                        font-weight: 700;
                    }

                <?php endif; ?>
                @page {
                    margin: 18mm 14mm 16mm 14mm;
                }

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    padding:
                        <?= $isPdf ? '0' : '28px' ?>
                    ;
                    background:
                        <?= $isPdf ? '#ffffff' : '#f5f6f8' ?>
                    ;
                    font-family: TravelPdf, DejaVu Sans, sans-serif;
                    color: #1f2937;
                    font-size: 11px;
                    line-height: 1.35;
                }

                .actions {
                    max-width: 900px;
                    margin: 0 auto 18px;
                    text-align: right;
                }

                .btn {
                    display: inline-block;
                    border-radius: 6px;
                    padding: 10px 14px;
                    background:
                        <?= $primary ?>
                    ;
                    color: white;
                    text-decoration: none;
                    font-weight: 700;
                    font-size: 13px;
                    margin-left: 8px;
                }

                .btn.secondary {
                    background: #4b5563;
                }

                .voucher-page {
                    max-width: 1200px;
                    margin: 0 auto;
                    background: #ffffff;
                    <?= !$isPdf ? 'padding: 25px; border: 1px solid #d1d5db;' : '' ?>
                }

                .content {
                    <?= !$isPdf ? 'padding-bottom: 28px;' : '' ?>
                }

                .document-header {
                    border-top: 6px solid
                        <?= $primary ?>
                    ;
                    border-bottom: 1px solid #d1d5db;
                    padding: 16px 0 14px;
                    margin-bottom: 14px;
                }

                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .header-table td {
                    vertical-align: middle;
                }

                .logo {
                    max-width: 150px;
                    max-height: 58px;
                    object-fit: contain;
                }

                .agency-name {
                    font-size: 10px;
                    color: #6b7280;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: .4px;
                    margin-top: 6px;
                }

                .doc-title {
                    text-align: right;
                    font-size: 22px;
                    font-weight: 800;
                    color: #111827;
                    margin: 0;
                }

                .doc-subtitle {
                    text-align: right;
                    font-size: 11px;
                    color: #6b7280;
                    margin-top: 4px;
                }

                .summary-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 16px;
                }

                .summary-table td {
                    border: 1px solid #d1d5db;
                    padding: 8px 10px;
                    width: 33.33%;
                    vertical-align: top;
                }

                .label {
                    color: #6b7280;
                    font-size: 8.5px;
                    text-transform: uppercase;
                    letter-spacing: .4px;
                    font-weight: 800;
                    margin-bottom: 3px;
                }

                .value {
                    font-size: 11px;
                    font-weight: 700;
                    color: #111827;
                }

                .hotel-block {
                    border-top: 4px solid
                        <?= $primary ?>
                    ;
                    padding-top: 9px;
                    margin-bottom: 8px;
                    <?= !$isPdf ? 'margin-top: 34px; padding-bottom: 28px; border-bottom: 1px solid #d1d5db;' : '' ?>

                }

                .voucher-sheet {
                    page-break-inside: avoid;
                }

                .voucher-sheet.sheet-break {
                    page-break-before: always;
                }

                .compact-page-header {
                    border-top: 4px solid
                        <?= $primary ?>
                    ;
                    border-bottom: 1px solid #d1d5db;
                    padding: 7px 0 8px;
                    margin-bottom: 8px;
                    font-size: 9px;
                    color: #111827;
                }

                .compact-page-header table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .compact-page-header td {
                    vertical-align: middle;
                }

                .compact-page-title {
                    font-weight: 800;
                    letter-spacing: .3px;
                }

                .compact-page-file {
                    text-align: right;
                    color: #6b7280;
                    font-weight: 700;
                }



                .hotel-header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 12px;
                }

                .hotel-header-table td {
                    vertical-align: top;
                }

                .hotel-name {
                    font-size: 19px;
                    font-weight: 800;
                    color: #111827;
                    margin-bottom: 4px;
                }

                .hotel-location {
                    font-size: 10.5px;
                    color: #4b5563;
                    line-height: 1.35;
                }

                .hotel-number {
                    text-align: right;
                    font-size: 10px;
                    color:
                        <?= $primary ?>
                    ;
                    font-weight: 800;
                    text-transform: uppercase;
                    white-space: nowrap;
                }

                .stay-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 12px 0 14px;
                }

                .stay-table td {
                    border: 1px solid #d1d5db;
                    padding: 9px 10px;
                    width: 25%;
                    vertical-align: top;
                    background: #fafafa;
                }

                .cell-label {
                    color: #6b7280;
                    font-size: 8.5px;
                    text-transform: uppercase;
                    letter-spacing: .4px;
                    font-weight: 800;
                    margin-bottom: 4px;
                }

                .cell-value {
                    font-size: 11.5px;
                    font-weight: 800;
                    color: #111827;
                }

                .section-title {
                    color: #111827;
                    border-left: 4px solid
                        <?= $primary ?>
                    ;
                    padding-left: 7px;
                    font-size: 10px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: .5px;
                    margin: 13px 0 7px;
                }

                .plain-box {
                    border: 1px solid #d1d5db;
                    background: #ffffff;
                    padding: 8px 10px;
                    color: #374151;
                    font-size: 10.5px;
                }

                .guest-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 7px;
                }

                .guest-table th {
                    background: #f3f4f6;
                    color: #374151;
                    font-size: 9.5px;
                    text-align: left;
                    padding: 7px 8px;
                    border: 1px solid #d1d5db;
                }

                .guest-table td {
                    padding: 7px 8px;
                    border: 1px solid #d1d5db;
                    font-size: 10px;
                }

                .important-note {
                    border: 1px solid #d1d5db;
                    border-left: 4px solid
                        <?= $primary ?>
                    ;
                    padding: 8px 10px;
                    color: #374151;
                    background: #ffffff;
                    font-size: 10px;
                    line-height: 1.4;
                }

                .contact {
                    margin-top: 16px;
                    border-top: 1px solid #d1d5db;
                    padding-top: 10px;
                }

                .contact-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .contact-table td {
                    vertical-align: top;
                    font-size: 10px;
                    color: #374151;
                }

                .contact-title {
                    font-weight: 800;
                    font-size: 10px;
                    color: #111827;
                    text-transform: uppercase;
                    letter-spacing: .4px;
                    margin-bottom: 3px;
                }

                @media print {
                    body {
                        padding: 0;
                        background: white;
                    }

                    .actions {
                        display: none;
                    }
                }


                <?php if ($this->hotelsPerPage >= 3): ?>
                    @page {
                        margin: 8mm 8mm 8mm 8mm;
                    }

                    body {
                        font-size: 7.2px;
                        line-height: 1.08;
                    }

                    .document-header {
                        padding: 5px 0 6px;
                        margin-bottom: 6px;
                        border-top-width: 4px;
                    }

                    .logo {
                        max-width: 70px;
                        max-height: 28px;
                    }

                    .agency-name {
                        font-size: 6.8px;
                        margin-top: 3px;
                    }

                    .doc-title {
                        font-size: 14px;
                    }

                    .doc-subtitle {
                        font-size: 7px;
                        margin-top: 2px;
                    }

                    .summary-table {
                        margin-bottom: 6px;
                    }

                    .summary-table td {
                        padding: 3px 4px;
                    }

                    .hotel-block {
                        border-top-width: 3px;
                        padding-top: 5px;
                        margin-bottom: 5px;
                    }

                    .hotel-header-table {
                        margin-bottom: 4px;
                    }

                    .hotel-name {
                        font-size: 9.5px;
                        margin-bottom: 1px;
                    }

                    .hotel-location {
                        font-size: 6.3px;
                        line-height: 1.05;
                    }

                    .hotel-number {
                        font-size: 6.5px;
                    }

                    .stay-table {
                        margin: 3px 0 4px;
                    }

                    .stay-table td {
                        padding: 2px 3px;
                    }

                    .label,
                    .cell-label {
                        font-size: 5.8px;
                        margin-bottom: 1px;
                    }

                    .value,
                    .cell-value {
                        font-size: 6.8px;
                    }

                    .section-title {
                        font-size: 6.2px;
                        margin: 3px 0 2px;
                        padding-left: 4px;
                        border-left-width: 2px;
                    }

                    .plain-box,
                    .important-note {
                        font-size: 6.2px;
                        padding: 2px 3px;
                        line-height: 1.08;
                    }

                    .guest-table {
                        margin-top: 2px;
                    }

                    .guest-table th,
                    .guest-table td {
                        font-size: 5.9px;
                        padding: 1.6px 2px;
                    }

                    .contact {
                        margin-top: 5px;
                        padding-top: 4px;
                    }

                <?php endif; ?>

                <?php if ($this->hotelsPerPage >= 4): ?>
                    @page {
                        margin: 6mm 7mm 6mm 7mm;
                    }

                    body {
                        font-size: 6.4px;
                        line-height: 1.02;
                    }

                    .document-header {
                        padding: 4px 0 5px;
                        margin-bottom: 5px;
                    }

                    .summary-table {
                        margin-bottom: 4px;
                    }

                    .summary-table td {
                        padding: 2px 3px;
                    }

                    .hotel-block {
                        padding-top: 4px;
                        margin-bottom: 4px;
                    }

                    .hotel-name {
                        font-size: 8.2px;
                    }

                    .hotel-location {
                        font-size: 5.6px;
                        line-height: 1;
                    }

                    .hotel-number {
                        font-size: 5.8px;
                    }

                    .stay-table td {
                        padding: 1.4px 2px;
                    }

                    .cell-label {
                        font-size: 5.2px;
                    }

                    .cell-value {
                        font-size: 6px;
                    }

                    .section-title {
                        font-size: 5.5px;
                        margin: 2px 0 1.5px;
                    }

                    .plain-box,
                    .important-note {
                        font-size: 5.6px;
                        padding: 1.5px 2px;
                        line-height: 1.02;
                    }

                    .guest-table th,
                    .guest-table td {
                        font-size: 5.4px;
                        padding: 1.2px 1.8px;
                    }

                <?php endif; ?>
            </style>
        </head>

        <body>

            <?php if (!$isPdf): ?>
                <div class="actions">
                    <form method="GET" style="display:inline-flex; align-items:center; gap:8px; margin-right:10px;">
                        <input type="hidden" name="programa_id" value="<?= (int) $programa['id'] ?>">

                        <label style="font-size:13px; font-weight:700; color:#374151;">
                            Hotels per page
                        </label>

                        <select name="hotels_per_page" onchange="this.form.submit()"
                            style="padding:9px 10px; border:1px solid #d1d5db; border-radius:8px;">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?= $i ?>" <?= $this->hotelsPerPage === $i ? 'selected' : '' ?>>
                                    <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>

                    <a class="btn secondary" href="javascript:window.print()">Print</a>

                    <a class="btn"
                        href="<?= APP_URL ?>/modules/bonos/pdf.php?programa_id=<?= (int) $programa['id'] ?>&hotels_per_page=<?= (int) $this->hotelsPerPage ?>">
                        Download PDF
                    </a>
                </div>
            <?php endif; ?>

            <div class="voucher-page">

                <div class="document-header">
                    <table class="header-table">
                        <tr>
                            <td style="width: 40%;">
                                <?php if ($logo): ?>
                                    <img class="logo" src="<?= htmlspecialchars($logo) ?>" alt="Logo">
                                <?php endif; ?>
                                <div class="agency-name"><?= htmlspecialchars($agencia['nombre']) ?></div>
                            </td>
                            <td style="width: 60%;">
                                <h1 class="doc-title">HOTEL RESERVATION VOUCHER</h1>
                                <div class="doc-subtitle">Operational voucher for hotel reception</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="summary-table">
                    <tr>
                        <td>
                            <div class="label">File / Booking</div>
                            <div class="value"><?= htmlspecialchars($file) ?></div>
                        </td>
                        <td>
                            <div class="label">Program</div>
                            <div class="value"><?= htmlspecialchars($programa['titulo_programa'] ?: $programa['destino']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="label">Issue date</div>
                            <div class="value"><?= date('d/m/Y') ?></div>
                        </td>
                    </tr>
                </table>


                <div class="content">
                    <?php if (empty($hoteles)): ?>
                        <div class="note">Este programa no tiene alojamientos asignados.</div>
                    <?php endif; ?>

                    <?php
                    $hotelChunks = array_chunk($hoteles, $this->hotelsPerPage);
                    $hotelGlobalIndex = 0;
                    ?>

                    <?php foreach ($hotelChunks as $pageIndex => $hotelPage): ?>
                        <div class="voucher-sheet <?= $pageIndex > 0 ? 'sheet-break' : '' ?>">

                            <?php if ($pageIndex > 0): ?>
                                <div class="compact-page-header">
                                    <table>
                                        <tr>
                                            <td class="compact-page-title">HOTEL RESERVATION VOUCHER</td>
                                            <td class="compact-page-file">File / Booking: <?= htmlspecialchars($file) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($hotelPage as $hotel): ?>
                                <?php $hotelGlobalIndex++; ?>

                                <div class="hotel-block">
                                    <table class="hotel-header-table">
                                        <tr>
                                            <td style="width: 76%;">
                                                <div class="hotel-name">
                                                    <?= htmlspecialchars($hotel['hotel_nombre'] ?? 'Hotel without name') ?>
                                                </div>
                                                <div class="hotel-location">
                                                    <?= htmlspecialchars($hotel['ubicacion'] ?? 'Location not registered') ?>
                                                </div>
                                            </td>
                                            <td style="width: 24%;">
                                                <div class="hotel-number"><?= $hotelGlobalIndex ?> out of <?= count($hoteles) ?> Hotels
                                                </div>
                                            </td>
                                        </tr>
                                    </table>

                                    <table class="stay-table">
                                        <tr>
                                            <td style="width: 20%;">
                                                <div class="cell-label">Check-in</div>
                                                <div class="cell-value"><?= $this->formatDate($hotel['checkin'] ?? null) ?></div>
                                            </td>
                                            <td style="width: 20%;">
                                                <div class="cell-label">Check-out</div>
                                                <div class="cell-value"><?= $this->formatDate($hotel['checkout'] ?? null) ?></div>
                                            </td>
                                            <td style="width: 20%;">
                                                <div class="cell-label">Nights</div>
                                                <div class="cell-value"><?= (int) ($hotel['noches'] ?? 1) ?></div>
                                            </td>
                                            <td style="width: 20%;">
                                                <div class="cell-label">Room class</div>
                                                <div class="cell-value">
                                                    <?= htmlspecialchars($hotel['tipo_acomodacion'] ?: 'Subject to availability') ?>
                                                </div>
                                            </td>
                                            <td style="width: 20%;">
                                                <div class="cell-label">Pax / Room</div>
                                                <div class="cell-value"><?= htmlspecialchars($hotel['acomodacion'] ?? 'N/A') ?></div>
                                            </td>
                                        </tr>
                                    </table>

                                    <div class="section-title">Room type / Accommodation</div>
                                    <div class="plain-box">
                                        <?= htmlspecialchars($hotel['descripcion'] ?: 'Subject to hotel availability.') ?>
                                    </div>

                                    <div class="section-title">Guests</div>
                                    <table class="guest-table">
                                        <thead>
                                            <tr>
                                                <th>Full name</th>
                                                <th>Document type</th>
                                                <th>Document number</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viajeros as $viajero): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(trim(($viajero['nombre'] ?? '') . ' ' . ($viajero['apellido'] ?? ''))) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($this->formatTipoDocumento($viajero['tipo_documento'] ?? null)) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($viajero['numero_documento'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                </div>
                            <?php endforeach; ?>

                        </div>
                    <?php endforeach; ?>

                    <div class="contact <?= $this->hotelsPerPage >= 3 ? 'sheet-break' : '' ?>">
                        <table class="contact-table">
                            <tr>
                                <td style="width: 50%;">
                                    <div class="contact-title">Assistance contact</div>
                                    <?= htmlspecialchars($agencia['nombre']) ?>
                                </td>
                                <td style="width: 50%; text-align: right;">
                                    <?php if (!empty($agencia['telefono'])): ?>
                                        <div><strong>Phone</strong> <?= htmlspecialchars($agencia['telefono']) ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($agencia['email_contacto'])): ?>
                                        <div><strong>Email:</strong> <?= htmlspecialchars($agencia['email_contacto']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

        </body>

        </html>
        <?php

        return ob_get_clean();
    }

    private function formatDate(?string $date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return date('d/m/Y', strtotime($date));
    }

    private function resolveLogo(?string $logo): string
    {
        if (!$logo) {
            return '';
        }

        if (preg_match('/^https?:\/\//', $logo)) {
            return $logo;
        }

        $logo = str_replace('\\', '/', $logo);
        return rtrim(APP_URL, '/') . '/' . ltrim($logo, '/');
    }
}