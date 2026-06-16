<?php
// =====================================================================
// pages/reportes.php — Reportes del admin (mini-dashboard de métricas).
// Solo lectura: consume modules/reportes/api.php (COUNT/GROUP BY).
// El router (index.php) ya hace App::requireRole('admin').
// =====================================================================
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';
App::init();
App::requireLogin();
$user = App::getUser();
require_once dirname(__DIR__) . '/config/config_functions.php';
require_once dirname(__DIR__) . '/includes/ui_components.php';
ConfigManager::init();

$cfg = ConfigManager::get();
$primary   = (preg_match('/^#[0-9a-fA-F]{6}$/', $cfg['admin_primary_color'] ?? '') ? $cfg['admin_primary_color'] : '#4f46e5');
$secondary = (preg_match('/^#[0-9a-fA-F]{6}$/', $cfg['admin_secondary_color'] ?? '') ? $cfg['admin_secondary_color'] : '#7c3aed');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= UIComponents::getComponentStyles() ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --pr: <?= $primary ?>; --sc: <?= $secondary ?>; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; color: #1e293b; }

        .header { background: linear-gradient(135deg, var(--pr), var(--sc)); color: #fff; padding: 0 24px; height: 70px; display: flex; align-items: center; gap: 14px; position: fixed; top: 0; left: 0; right: 0; z-index: 1001; box-shadow: 0 2px 16px rgba(0, 0, 0, .18); }
        .menu-toggle { background: rgba(255, 255, 255, .2); border: none; color: #fff; width: 38px; height: 38px; border-radius: 9px; cursor: pointer; font-size: 18px; }
        .header-title { font-size: 17px; font-weight: 700; }

        #overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, .35); z-index: 999; }
        #mainContent { margin-top: 70px; padding: 24px; max-width: 1180px; margin-left: auto; margin-right: auto; }

        .rp-title { font-size: 22px; font-weight: 800; letter-spacing: -.3px; margin-bottom: 4px; }
        .rp-sub { color: #64748b; font-size: 14px; margin-bottom: 22px; }

        .rp-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 22px; }
        .rp-kpi { background: #fff; border: 1px solid #e8edf2; border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(15, 23, 42, .05); }
        .rp-kpi .l { font-size: 12.5px; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 7px; }
        .rp-kpi .n { font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 8px; line-height: 1; }
        .rp-kpi .n small { font-size: 15px; color: #64748b; font-weight: 700; }

        .rp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 18px; }
        .rp-card { background: #fff; border: 1px solid #e8edf2; border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(15, 23, 42, .05); }
        .rp-card h3 { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 14px; }
        .rp-canvas-wrap { position: relative; height: 260px; }
        .rp-empty { height: 260px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 14px; gap: 8px; }
        .rp-empty i { font-size: 28px; color: #cbd5e1; }

        .rp-loading { text-align: center; color: #94a3b8; padding: 60px 0; }

        @media (max-width: 640px) { #mainContent { padding: 16px; } }
    </style>
</head>

<body>
    <div class="header">
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <span class="header-title">Reportes</span>
    </div>

    <?= UIComponents::renderSidebar($user, '/reportes') ?>
    <div id="overlay" onclick="closeSidebar()"></div>

    <div id="mainContent">
        <div class="rp-title">Reportes</div>
        <div class="rp-sub">Resumen de tu actividad comercial. Los datos se actualizan en tiempo real.</div>

        <div id="rpBody">
            <div class="rp-loading"><i class="fas fa-spinner fa-spin"></i> Cargando reportes…</div>
        </div>
    </div>

    <script>
        const APP_URL = '<?= APP_URL ?>';
        const PR = '<?= $primary ?>', SC = '<?= $secondary ?>';
        let sidebarOpen = false;
        function toggleSidebar() { sidebarOpen = !sidebarOpen; document.querySelector('.enhanced-sidebar')?.classList.toggle('open', sidebarOpen); document.getElementById('overlay').style.display = sidebarOpen ? 'block' : 'none'; }
        function closeSidebar() { sidebarOpen = false; document.querySelector('.enhanced-sidebar')?.classList.remove('open'); document.getElementById('overlay').style.display = 'none'; }

        const fmt = n => { try { return new Intl.NumberFormat('es-ES', { maximumFractionDigits: 0 }).format(n || 0); } catch (e) { return n || 0; } };
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        // Paleta para categorías (dona/barras)
        const PALETTE = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'];

        function card(title, inner) {
            return `<div class="rp-card"><h3>${esc(title)}</h3>${inner}</div>`;
        }
        function emptyState(msg) {
            return `<div class="rp-empty"><i class="fas fa-chart-simple"></i>${esc(msg || 'Sin datos aún')}</div>`;
        }

        async function cargar() {
            let d;
            try {
                const r = await fetch(`${APP_URL}/reportes/api`);
                d = await r.json();
            } catch (e) { d = null; }
            const body = document.getElementById('rpBody');
            if (!d || !d.success) {
                body.innerHTML = `<div class="rp-empty"><i class="fas fa-triangle-exclamation"></i>No se pudieron cargar los reportes.</div>`;
                return;
            }

            const k = d.kpis || {};
            const kpis = `
                <div class="rp-kpis">
                    <div class="rp-kpi"><div class="l"><i class="fas fa-user-group"></i> Leads totales</div><div class="n">${fmt(k.leads)}</div></div>
                    <div class="rp-kpi"><div class="l"><i class="fas fa-circle-check"></i> Programas vendidos</div><div class="n">${fmt(k.vendidos)}</div></div>
                    <div class="rp-kpi"><div class="l"><i class="fas fa-percent"></i> Tasa de conversión</div><div class="n">${Number(k.conversion ?? 0)}<small>%</small></div></div>
                    <div class="rp-kpi"><div class="l"><i class="fas fa-sack-dollar"></i> Ingresos (vendidos)</div><div class="n">${fmt(k.ingresos)}</div></div>
                </div>`;

            const hasEstado  = (d.por_estado || []).some(x => Number(x.total) > 0);
            const hasOrigen  = (d.por_origen || []).some(x => Number(x.total) > 0);
            const hasDest    = (d.top_destinos || []).length > 0;
            const hasVentas  = (d.ventas || []).some(x => Number(x.total) > 0);

            body.innerHTML = kpis + `
                <div class="rp-grid">
                    ${card('Leads por estado', hasEstado ? '<div class="rp-canvas-wrap"><canvas id="cEstado"></canvas></div>' : emptyState('Aún no hay leads'))}
                    ${card('Ventas por mes', hasVentas ? '<div class="rp-canvas-wrap"><canvas id="cVentas"></canvas></div>' : emptyState('Aún no hay ventas registradas'))}
                    ${card('Leads por origen', hasOrigen ? '<div class="rp-canvas-wrap"><canvas id="cOrigen"></canvas></div>' : emptyState('Aún no hay leads'))}
                    ${card('Top destinos', hasDest ? '<div class="rp-canvas-wrap"><canvas id="cDestinos"></canvas></div>' : emptyState('Aún no hay destinos'))}
                </div>`;

            Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
            Chart.defaults.color = '#64748b';

            if (hasEstado) {
                const e = d.por_estado;
                new Chart(document.getElementById('cEstado'), {
                    type: 'bar',
                    data: { labels: e.map(x => x.nombre), datasets: [{ label: 'Leads', data: e.map(x => Number(x.total)), backgroundColor: e.map((x, i) => x.color || PALETTE[i % PALETTE.length]), borderRadius: 6 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });
            }
            if (hasVentas) {
                const v = d.ventas;
                new Chart(document.getElementById('cVentas'), {
                    type: 'line',
                    data: { labels: v.map(x => x.label), datasets: [{ label: 'Vendidos', data: v.map(x => Number(x.total)), borderColor: PR, backgroundColor: 'rgba(99,102,241,.12)', fill: true, tension: .35, pointRadius: 3 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });
            }
            if (hasOrigen) {
                const o = d.por_origen;
                new Chart(document.getElementById('cOrigen'), {
                    type: 'doughnut',
                    data: { labels: o.map(x => x.nombre), datasets: [{ data: o.map(x => Number(x.total)), backgroundColor: o.map((x, i) => PALETTE[i % PALETTE.length]) }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                });
            }
            if (hasDest) {
                const t = d.top_destinos;
                new Chart(document.getElementById('cDestinos'), {
                    type: 'bar',
                    data: { labels: t.map(x => x.destino), datasets: [{ label: 'Leads', data: t.map(x => Number(x.total)), backgroundColor: SC, borderRadius: 6 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', cargar);
    </script>
</body>

</html>
