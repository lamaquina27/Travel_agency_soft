<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';
App::init();
App::requireLogin();
$user = App::getUser();
require_once dirname(__DIR__) . '/config/config_functions.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/ui_components.php';
ConfigManager::init();

// Control de acceso: operador y admin siempre; agente solo si la agencia lo habilita.
$isAdmin    = $user['role'] === 'admin';
$isOperador = $user['role'] === 'operador';
$isGestor   = in_array($user['role'], ['admin', 'agent'], true);
$puedeVer = $isAdmin || $isOperador || ($user['role'] === 'agent' && ConfigManager::roomingAgentesVisible());
if (!$puedeVer) { App::redirect('/dashboard'); exit; }

$userColors  = ConfigManager::getColorsForRole($user['role']);
$companyName = ConfigManager::getCompanyName();
$defaultLang = ConfigManager::getDefaultLanguage() ?? 'es';
function rm_rgb($hex){ $h=ltrim(trim($hex),'#'); if(strlen($h)===3)$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; if(!preg_match('/^[0-9a-fA-F]{6}$/',$h))return'99,102,241'; return hexdec(substr($h,0,2)).','.hexdec(substr($h,2,2)).','.hexdec(substr($h,4,2)); }
$pRgb = rm_rgb($userColors['primary']);
?>
<!DOCTYPE html>
<html lang="<?= $defaultLang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<title>Traslados / Rooming — <?= htmlspecialchars($companyName) ?></title>
<?= UIComponents::getComponentStyles() ?>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --pr:<?= $userColors['primary'] ?>;
  --sc:<?= $userColors['secondary'] ?>;
  --pr-rgb:<?= $pRgb ?>;
  --grad:linear-gradient(135deg,var(--pr) 0%,var(--sc) 100%);
}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;}

/* HEADER */
.header{background:var(--grad);color:#fff;padding:0 24px;height:70px;display:flex;align-items:center;justify-content:space-between;position:fixed;top:0;left:0;right:0;z-index:1001;box-shadow:0 2px 16px rgba(0,0,0,.18);}
.header-left{display:flex;align-items:center;gap:14px;}
.menu-toggle{background:rgba(255,255,255,.2);border:none;color:#fff;width:38px;height:38px;border-radius:9px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:background .2s;}
.menu-toggle:hover{background:rgba(255,255,255,.32);}
.header-title{font-size:17px;font-weight:700;letter-spacing:-.2px;}
.header-right{display:flex;align-items:center;gap:10px;}
.header-user{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border-radius:22px;padding:6px 14px 6px 6px;}
.header-avatar{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.28);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid rgba(255,255,255,.35);}
.header-name{color:#fff;font-size:13px;font-weight:500;}

#overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:999;}
#mainContent{margin-top:70px;height:calc(100vh - 70px);display:flex;flex-direction:column;overflow:hidden;}

/* TOOLBAR */
.rm-toolbar{background:#fff;border-bottom:1px solid #e8edf2;padding:12px 20px;flex-shrink:0;display:flex;flex-direction:column;gap:10px;}
.rm-toolbar-top{display:flex;align-items:center;gap:12px;}
.rm-title{font-size:18px;font-weight:800;color:#0f172a;margin-right:auto;display:flex;align-items:center;gap:9px;}
.rm-title svg{width:20px;height:20px;stroke:var(--pr);fill:none;stroke-width:2;}
.rm-count{background:rgba(var(--pr-rgb),.12);color:var(--pr);border-radius:20px;padding:2px 10px;font-size:12px;font-weight:700;}
.rm-btn{height:36px;padding:0 15px;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .18s;white-space:nowrap;}
.rm-btn svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.2;}
.rm-btn-primary{background:var(--grad);color:#fff;}
.rm-btn-primary:hover{opacity:.9;}
.rm-btn-ghost{background:#f1f5f9;color:#475569;}
.rm-btn-ghost:hover{background:#e2e8f0;}

/* FILTROS */
.rm-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.rm-search{display:flex;align-items:center;gap:7px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0 11px;height:34px;min-width:200px;}
.rm-search svg{width:14px;height:14px;stroke:#94a3b8;fill:none;stroke-width:2;flex-shrink:0;}
.rm-search input{border:none;background:transparent;outline:none;font-size:13px;color:#1e293b;width:100%;}
.rm-f{height:34px;padding:0 9px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:12.5px;color:#475569;outline:none;}
.rm-f:focus{border-color:var(--pr);}
.rm-f-label{font-size:11px;color:#94a3b8;font-weight:600;margin-right:-3px;}
.rm-clear{background:none;border:none;color:var(--pr);font-size:12.5px;font-weight:600;cursor:pointer;padding:4px 6px;}
.rm-clear:hover{text-decoration:underline;}

/* BARRA DE ACCIONES EN LOTE */
.rm-bulkbar{display:none;align-items:center;gap:10px;flex-wrap:wrap;background:rgba(var(--pr-rgb),.07);border:1px solid rgba(var(--pr-rgb),.25);border-radius:10px;padding:8px 12px;margin-top:2px;}
.rm-bulkbar.show{display:flex;}
.rm-bulk-count{font-size:13px;font-weight:700;color:var(--pr);white-space:nowrap;}
.rm-bulk-grp{display:flex;align-items:center;gap:5px;}
.rm-bulk-grp .rm-f{height:32px;}
.rm-bulk-apply{height:32px;padding:0 12px;border:none;border-radius:7px;background:var(--grad);color:#fff;font-size:12.5px;font-weight:600;cursor:pointer;white-space:nowrap;}
.rm-bulk-apply:hover{opacity:.9;}
.rm-bulk-sep{width:1px;height:22px;background:rgba(var(--pr-rgb),.25);}
.rm-chk{width:16px;height:16px;cursor:pointer;accent-color:var(--pr);}
.rm-table thead th.rm-chkcol{width:36px;text-align:center;}
.rm-table tbody td.rm-chkcell{text-align:center;}

/* TABLA */
.rm-table-wrap{flex:1;overflow:auto;padding:0;}
.rm-table-wrap::-webkit-scrollbar{width:9px;height:9px;}
.rm-table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:5px;}
.rm-table{border-collapse:separate;border-spacing:0;width:100%;font-size:13px;white-space:nowrap;}
.rm-table thead th{position:sticky;top:0;z-index:5;background:#f8fafc;color:#475569;font-weight:700;font-size:11.5px;text-transform:uppercase;letter-spacing:.3px;text-align:left;padding:11px 12px;border-bottom:2px solid #e2e8f0;cursor:pointer;user-select:none;}
.rm-table thead th:hover{background:#eef2f7;color:var(--pr);}
.rm-table thead th .rm-arrow{opacity:.35;margin-left:3px;font-size:10px;}
.rm-table thead th.sorted .rm-arrow{opacity:1;color:var(--pr);}
.rm-table thead th.rm-noact{cursor:default;}
.rm-table thead th.rm-noact:hover{background:#f8fafc;color:#475569;}
.rm-table tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155;}
.rm-table tbody tr{cursor:pointer;transition:background .12s;}
.rm-table tbody tr:hover{background:#f8fafc;}
.rm-code{font-weight:700;color:#0f172a;font-size:12px;}
.rm-code small{display:block;font-weight:500;color:#94a3b8;font-size:11px;}
.rm-tipo{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;}
.rm-tipo.in{background:#dcfce7;color:#16a34a;}
.rm-tipo.out{background:#dbeafe;color:#2563eb;}
.rm-tipo.na{background:#f1f5f9;color:#94a3b8;}
.rm-aero{font-weight:700;letter-spacing:.5px;color:#0f172a;}
.rm-ops{display:flex;flex-wrap:wrap;gap:3px;}
.rm-op-chip{background:rgba(var(--pr-rgb),.1);color:var(--pr);border-radius:10px;padding:1px 8px;font-size:11px;font-weight:600;}
.rm-op-none{color:#cbd5e1;}
.rm-status{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;cursor:pointer;border:1px solid transparent;}
.rm-status .dot{width:7px;height:7px;border-radius:50%;}
.st-proc{background:#fef9c3;color:#a16207;} .st-proc .dot{background:#eab308;}
.st-done{background:#dcfce7;color:#15803d;} .st-done .dot{background:#22c55e;}
.st-canc{background:#fee2e2;color:#b91c1c;} .st-canc .dot{background:#ef4444;}
.rm-empty{padding:60px 20px;text-align:center;color:#94a3b8;}
.rm-empty svg{width:46px;height:46px;stroke:#cbd5e1;fill:none;stroke-width:1.5;margin-bottom:10px;}
.rm-empty h3{font-size:15px;color:#64748b;font-weight:600;margin-bottom:4px;}

/* INLINE STATUS MENU */
.rm-pop{position:fixed;z-index:1300;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 12px 32px rgba(15,23,42,.18);padding:5px;display:none;min-width:150px;}
.rm-pop.open{display:block;}
.rm-pop-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;font-size:13px;color:#334155;cursor:pointer;}
.rm-pop-item:hover{background:#f1f5f9;}
.rm-pop-item .dot{width:8px;height:8px;border-radius:50%;}

/* MODAL */
.rm-ov{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);z-index:1200;display:none;align-items:flex-start;justify-content:center;padding:40px 16px;overflow-y:auto;}
.rm-ov.open{display:flex;}
.rm-modal{background:#fff;border-radius:16px;width:100%;max-width:760px;box-shadow:0 24px 60px rgba(15,23,42,.3);animation:rmUp .25s cubic-bezier(.34,1.56,.64,1);overflow:hidden;}
.rm-modal.sm{max-width:480px;}
@keyframes rmUp{from{transform:translateY(24px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.rm-mhd{background:var(--grad);color:#fff;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;}
.rm-mhd h2{font-size:16px;font-weight:700;}
.rm-mhd .sub{font-size:12px;opacity:.85;margin-top:2px;}
.rm-x{background:rgba(255,255,255,.18);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:17px;line-height:1;}
.rm-x:hover{background:rgba(255,255,255,.32);}
.rm-mbody{padding:20px 22px;max-height:62vh;overflow-y:auto;}
.rm-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px 16px;}
.rm-fld{display:flex;flex-direction:column;gap:4px;}
.rm-fld.full{grid-column:1 / -1;}
.rm-fld label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;}
.rm-fld input,.rm-fld select,.rm-fld textarea{padding:9px 11px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;font-family:inherit;background:#fff;transition:border-color .15s;}
.rm-fld input:focus,.rm-fld select:focus,.rm-fld textarea:focus{border-color:var(--pr);}
.rm-fld textarea{resize:vertical;min-height:60px;}
.rm-sec{font-size:12px;font-weight:800;color:var(--pr);text-transform:uppercase;letter-spacing:.4px;margin:18px 0 10px;padding-bottom:6px;border-bottom:1px solid #f1f5f9;}
/* Operadores en modal */
.rm-ops-box{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;}
.rm-ops-box .chip{display:inline-flex;align-items:center;gap:6px;background:rgba(var(--pr-rgb),.1);color:var(--pr);border-radius:16px;padding:4px 10px;font-size:12px;font-weight:600;}
.rm-ops-box .chip button{background:none;border:none;color:var(--pr);cursor:pointer;font-size:14px;line-height:1;opacity:.7;}
.rm-ops-box .chip button:hover{opacity:1;}
.rm-ops-add{display:flex;gap:8px;}
.rm-ops-add select{flex:1;}
.rm-mfoot{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 22px;border-top:1px solid #f1f5f9;background:#fafbfc;}
.rm-del{background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;}
.rm-del:hover{background:#fef2f2;}
.rm-save{background:var(--grad);border:none;color:#fff;border-radius:8px;padding:9px 22px;font-size:13px;font-weight:700;cursor:pointer;}
.rm-save:hover{opacity:.9;}
.rm-cancel{background:#fff;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;}

/* TOAST */
#rmToast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:#0f172a;color:#fff;padding:11px 20px;border-radius:10px;font-size:13px;font-weight:500;z-index:2000;opacity:0;transition:all .25s;box-shadow:0 8px 24px rgba(0,0,0,.25);}
#rmToast.show{opacity:1;transform:translateX(-50%) translateY(0);}
#rmToast.ok{background:#16a34a;} #rmToast.err{background:#dc2626;}
</style>
</head>
<body>

<div class="header">
  <div class="header-left">
    <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <?= UIComponents::renderLogo('small',['gradient'=>'transparent','class'=>'header-logo']) ?>
    <span class="header-title"><?= $isOperador ? 'Mis Traslados' : 'Traslados / Rooming' ?></span>
  </div>
  <div class="header-right">
    <div id="google_translate_element"></div>
    <div class="header-user">
      <div class="header-avatar"><?= strtoupper(substr($user['name']??'U',0,2)) ?></div>
      <span class="header-name"><?= htmlspecialchars($user['name']??'') ?></span>
    </div>
  </div>
</div>

<?= UIComponents::renderSidebar($user, '/rooming') ?>
<div id="overlay" onclick="closeSidebar()"></div>

<div id="mainContent">

  <div class="rm-toolbar">
    <div class="rm-toolbar-top">
      <div class="rm-title">
        <svg viewBox="0 0 24 24"><path d="M3 17h2l1-5h12l1 5h2"/><path d="M5 17a2 2 0 1 0 4 0"/><path d="M15 17a2 2 0 1 0 4 0"/><path d="M6 12V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v5"/></svg>
        <?= $isOperador ? 'Mis traslados' : 'Traslados' ?> <span class="rm-count" id="rmCount">0</span>
      </div>
      <button class="rm-btn rm-btn-ghost" onclick="rmExportCsv()">
        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exportar CSV
      </button>
      <?php if ($isGestor): ?>
      <button class="rm-btn rm-btn-ghost" onclick="rmAbrirReglas()">
        <svg viewBox="0 0 24 24"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>
        Reglas
      </button>
      <button class="rm-btn rm-btn-ghost" onclick="rmAplicarReglas()" title="Aplica las reglas activas a los traslados que estás viendo (según los filtros)">
        <svg viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        Aplicar reglas
      </button>
      <button class="rm-btn rm-btn-ghost" onclick="rmAbrirGenerar()">
        <svg viewBox="0 0 24 24"><path d="M12 2v20M2 12h20"/></svg>
        Generar desde programa
      </button>
      <button class="rm-btn rm-btn-primary" onclick="rmNuevo()">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo traslado
      </button>
      <?php endif; ?>
    </div>

    <div class="rm-filters">
      <div class="rm-search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="fSearch" placeholder="Buscar (vuelo, ciudad, titular, lugar…)" oninput="rmApplyFilters()">
      </div>
      <?php if ($isGestor): ?>
      <select class="rm-f" id="fOperador" onchange="rmApplyFilters()"><option value="">Operador: todos</option></select>
      <?php endif; ?>
      <input class="rm-f" id="fCiudad" placeholder="Ciudad" oninput="rmApplyFilters()" style="width:120px;">
      <input class="rm-f" id="fAero" placeholder="Aeropuerto" oninput="rmApplyFilters()" style="width:110px;text-transform:uppercase;">
      <select class="rm-f" id="fTipo" onchange="rmApplyFilters()"><option value="">Tipo: todos</option><option value="llevada_al_hotel">IN</option><option value="llevada_al_aeropuerto">OUT</option><option value="por_asignar">Por asignar</option></select>
      <select class="rm-f" id="fEstado" onchange="rmApplyFilters()"><option value="">Estado: todos</option><option value="en_proceso">En proceso</option><option value="completado">Completado</option><option value="cancelado">Cancelado</option></select>
      <span class="rm-f-label">Fecha</span>
      <input class="rm-f" type="date" id="fDesde" onchange="rmApplyFilters()">
      <input class="rm-f" type="date" id="fHasta" onchange="rmApplyFilters()">
      <span class="rm-f-label">Hora</span>
      <input class="rm-f" type="time" id="fHoraD" onchange="rmApplyFilters()">
      <input class="rm-f" type="time" id="fHoraH" onchange="rmApplyFilters()">
      <button class="rm-clear" onclick="rmClearFilters()">Limpiar</button>
    </div>

    <?php if ($isGestor): ?>
    <div class="rm-bulkbar" id="rmBulkBar">
      <span class="rm-bulk-count" id="rmBulkCount">0 seleccionados</span>
      <div class="rm-bulk-sep"></div>
      <div class="rm-bulk-grp">
        <select class="rm-f" id="bkOperador"><option value="">Operador…</option></select>
        <button class="rm-bulk-apply" onclick="rmBulkAsignar()">Asignar</button>
      </div>
      <div class="rm-bulk-grp">
        <input class="rm-f" type="time" id="bkPickup">
        <button class="rm-bulk-apply" onclick="rmBulkPickup()">Fijar recogida</button>
      </div>
      <div class="rm-bulk-grp">
        <select class="rm-f" id="bkEstado"><option value="en_proceso">En proceso</option><option value="completado">Completado</option><option value="cancelado">Cancelado</option></select>
        <button class="rm-bulk-apply" onclick="rmBulkEstado()">Fijar estado</button>
      </div>
      <div class="rm-bulk-sep"></div>
      <button class="rm-clear" onclick="rmClearSel()">Limpiar selección</button>
    </div>
    <?php endif; ?>
  </div>

  <div class="rm-table-wrap">
    <table class="rm-table">
      <thead>
        <tr id="rmHead">
          <?php if ($isGestor): ?><th class="rm-noact rm-chkcol"><input type="checkbox" class="rm-chk" id="rmSelAll" onclick="rmToggleSelAll(this.checked)"></th><?php endif; ?>
          <th data-k="codigo">Código <span class="rm-arrow">↕</span></th>
          <th data-k="titular">Titular <span class="rm-arrow">↕</span></th>
          <th data-k="tipo">Tipo <span class="rm-arrow">↕</span></th>
          <th data-k="fecha">Fecha <span class="rm-arrow">↕</span></th>
          <th data-k="ciudad">Ciudad <span class="rm-arrow">↕</span></th>
          <th data-k="aeropuerto">Aerop. <span class="rm-arrow">↕</span></th>
          <th data-k="vuelo">Vuelo <span class="rm-arrow">↕</span></th>
          <th data-k="hvuelo">H. vuelo <span class="rm-arrow">↕</span></th>
          <th data-k="hrecogida">H. recogida <span class="rm-arrow">↕</span></th>
          <th data-k="recogida">Recogida <span class="rm-arrow">↕</span></th>
          <th data-k="destino">Destino <span class="rm-arrow">↕</span></th>
          <th data-k="pax">Pax <span class="rm-arrow">↕</span></th>
          <th data-k="operadores">Operadores <span class="rm-arrow">↕</span></th>
          <th data-k="estado">Estado <span class="rm-arrow">↕</span></th>
        </tr>
      </thead>
      <tbody id="rmBody"></tbody>
    </table>
    <div id="rmEmpty" class="rm-empty" style="display:none;">
      <svg viewBox="0 0 24 24"><path d="M3 17h2l1-5h12l1 5h2"/><path d="M6 12V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v5"/></svg>
      <h3>Sin traslados</h3>
      <p>Genera traslados desde un programa vendido o crea uno manualmente.</p>
    </div>
  </div>
</div>

<!-- Popover estado inline -->
<div class="rm-pop" id="rmStatusPop">
  <div class="rm-pop-item" onclick="rmSetStatusInline('en_proceso')"><span class="dot" style="background:#eab308"></span> En proceso</div>
  <div class="rm-pop-item" onclick="rmSetStatusInline('completado')"><span class="dot" style="background:#22c55e"></span> Completado</div>
  <div class="rm-pop-item" onclick="rmSetStatusInline('cancelado')"><span class="dot" style="background:#ef4444"></span> Cancelado</div>
</div>

<!-- Modal detalle/edición/creación -->
<div class="rm-ov" id="rmModal">
  <div class="rm-modal">
    <div class="rm-mhd">
      <div>
        <h2 id="rmModalTitle">Traslado</h2>
        <div class="sub" id="rmModalSub"></div>
      </div>
      <button class="rm-x" onclick="rmCerrarModal()">&times;</button>
    </div>
    <div class="rm-mbody">
      <input type="hidden" id="mId">
      <div class="rm-grid">
        <div class="rm-fld full" id="mProgramaWrap" style="display:none;">
          <label>Programa / Reserva</label>
          <select id="mPrograma"></select>
        </div>
        <div class="rm-fld">
          <label>Tipo</label>
          <select id="mTipo">
            <option value="llevada_al_hotel">IN — recogida en aeropuerto</option>
            <option value="llevada_al_aeropuerto">OUT — llevar al aeropuerto</option>
            <option value="por_asignar">Por asignar</option>
          </select>
        </div>
        <div class="rm-fld">
          <label>Estado</label>
          <select id="mStatus">
            <option value="en_proceso">En proceso</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>
        <div class="rm-fld"><label>Fecha del servicio</label><input type="date" id="mFecha"></div>
        <div class="rm-fld"><label>Ciudad</label><input type="text" id="mCiudad"></div>
        <div class="rm-fld"><label>Aeropuerto origen (código)</label><input type="text" id="mAeroO" style="text-transform:uppercase;" maxlength="10"></div>
        <div class="rm-fld"><label>Aeropuerto destino (código)</label><input type="text" id="mAeroD" style="text-transform:uppercase;" maxlength="10"></div>
        <div class="rm-fld"><label>Código de vuelo</label><input type="text" id="mVuelo"></div>
        <div class="rm-fld"><label>Guía</label><input type="text" id="mGuia"></div>
        <div class="rm-fld"><label>Hora llegada vuelo</label><input type="time" id="mLlegada"></div>
        <div class="rm-fld"><label>Hora salida vuelo</label><input type="time" id="mSalida"></div>
        <div class="rm-fld"><label>Hora de recogida</label><input type="time" id="mPickupT"></div>
        <div class="rm-fld"><label>Pasajeros</label><input type="text" id="mPax" disabled style="background:#f8fafc;color:#94a3b8;"></div>
        <div class="rm-fld full"><label>Lugar de recogida</label><input type="text" id="mPickupL"></div>
        <div class="rm-fld full"><label>Lugar de destino</label><input type="text" id="mDropoff"></div>
        <div class="rm-fld full"><label>Notas internas</label><textarea id="mNotasInt"></textarea></div>
        <div class="rm-fld full"><label>Notas para el operador</label><textarea id="mNotasOp"></textarea></div>
      </div>

      <div id="mOpsSection">
        <div class="rm-sec">Operadores asignados</div>
        <div class="rm-ops-box" id="mOpsBox"></div>
        <div class="rm-ops-add">
          <select id="mOpsSelect" onchange="rmAddOperadorChip()"><option value="">+ Añadir operador…</option></select>
        </div>
        <p style="font-size:11px;color:#94a3b8;margin-top:6px;">Los cambios de operadores se aplican al pulsar <strong>Guardar</strong>.</p>
      </div>
    </div>
    <div class="rm-mfoot">
      <button class="rm-del" id="mDelBtn" onclick="rmEliminar()">Eliminar</button>
      <div style="display:flex;gap:8px;">
        <button class="rm-cancel" onclick="rmCerrarModal()">Cancelar</button>
        <button class="rm-save" onclick="rmGuardar()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal generar desde programa -->
<div class="rm-ov" id="rmGenModal">
  <div class="rm-modal sm">
    <div class="rm-mhd">
      <div><h2>Generar traslados</h2><div class="sub">Crea los traslados desde los vuelos del itinerario</div></div>
      <button class="rm-x" onclick="rmCerrarGenerar()">&times;</button>
    </div>
    <div class="rm-mbody">
      <div class="rm-fld full">
        <label>Programa vendido</label>
        <select id="genPrograma"><option value="">Selecciona un programa…</option></select>
      </div>
      <p style="font-size:12px;color:#94a3b8;margin-top:10px;">Solo se listan programas marcados como vendidos. Los traslados se crean a partir de los vuelos del itinerario (no se duplican los ya existentes).</p>
    </div>
    <div class="rm-mfoot">
      <span></span>
      <div style="display:flex;gap:8px;">
        <button class="rm-cancel" onclick="rmCerrarGenerar()">Cancelar</button>
        <button class="rm-save" onclick="rmGenerar()">Generar</button>
      </div>
    </div>
  </div>
</div>

<?php if ($isGestor): ?>
<!-- Modal Reglas de asignación -->
<div class="rm-ov" id="rmReglasModal">
  <div class="rm-modal">
    <div class="rm-mhd">
      <div><h2>Reglas de asignación</h2><div class="sub">Asignan operadores automáticamente a los traslados que cumplan las condiciones</div></div>
      <button class="rm-x" onclick="rmCerrarReglas()">&times;</button>
    </div>
    <div class="rm-mbody">
      <div class="rm-sec" style="margin-top:0;" id="rgFormTitle">Nueva regla</div>
      <input type="hidden" id="rgEditId" value="">
      <div class="rm-grid">
        <div class="rm-fld"><label>Operador a asignar</label><select id="rgOperador"><option value="">Selecciona…</option></select></div>
        <div class="rm-fld"><label>Tipo (opcional)</label><select id="rgTipo"><option value="">Cualquiera</option><option value="llevada_al_hotel">IN — recogida en aeropuerto</option><option value="llevada_al_aeropuerto">OUT — llevar al aeropuerto</option></select></div>
        <div class="rm-fld"><label>Aeropuerto (opcional)</label><input type="text" id="rgAero" maxlength="10" style="text-transform:uppercase;" placeholder="p.ej. BOG"></div>
        <div class="rm-fld"><label>Ciudad (opcional)</label><input type="text" id="rgCity" placeholder="p.ej. Bogotá"></div>
      </div>
      <p style="font-size:11px;color:#94a3b8;margin:8px 0 0;">El aeropuerto se compara con el relevante de cada traslado (destino si IN, origen si OUT). Define al menos una condición. Si varias reglas coinciden, gana la de mayor prioridad (más arriba).</p>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
        <button class="rm-cancel" id="rgCancelBtn" style="display:none;" onclick="rmCancelarFormRegla()">Cancelar</button>
        <button class="rm-save" onclick="rmGuardarRegla()">Guardar regla</button>
      </div>
      <div class="rm-sec">Reglas existentes (orden = prioridad)</div>
      <div id="rgList"></div>
    </div>
    <div class="rm-mfoot"><span></span><button class="rm-cancel" onclick="rmCerrarReglas()">Cerrar</button></div>
  </div>
</div>
<?php endif; ?>

<div id="rmToast"></div>

<script>
const APP_URL = '<?= APP_URL ?>';
const API = APP_URL + '/rooming/api';
const IS_OPERADOR = <?= $isOperador ? 'true' : 'false' ?>;
const IS_GESTOR   = <?= $isGestor ? 'true' : 'false' ?>;

let R = { all:[], filtered:[], operadores:[], programas:[], sortKey:'fecha', sortDir:'asc', currentId:null, current:null, statusTarget:null, modalOps:[], origOps:[], selected:new Set(), reglas:[] };

const TIPO = { llevada_al_hotel:{l:'IN',c:'in'}, llevada_al_aeropuerto:{l:'OUT',c:'out'}, por_asignar:{l:'—',c:'na'} };
const STT  = { en_proceso:{l:'En proceso',c:'st-proc'}, completado:{l:'Completado',c:'st-done'}, cancelado:{l:'Cancelado',c:'st-canc'} };

const esc = s => String(s??'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
const trCode = id => 'TR-' + String(id).padStart(6,'0');
const isOut = r => r.service_type === 'llevada_al_aeropuerto';
const aeroCol = r => isOut(r) ? (r.airport_code_origen||'') : (r.airport_code_destino||'');
const horaVuelo = r => isOut(r) ? (r.departure_time||'') : (r.arrival_time||'');
const hhmm = t => t ? String(t).slice(0,5) : '';
const titular = r => ((r.titular_nombre||'') + ' ' + (r.titular_apellido||'')).trim();
// Fecha y hora de recogida "efectivas": el valor guardado o el calculado por el backend
const fechaEff  = r => r.service_date_eff || r.service_date || '';
const pickupEff = r => r.pickup_time_eff  || r.pickup_time  || '';
function fmtFecha(d){ if(!d) return ''; const x=new Date(d+'T00:00:00'); if(isNaN(x)) return d; return x.toLocaleDateString('es-ES',{day:'2-digit',month:'short',year:'numeric'}); }

// ── Toast ──
let toastT;
function toast(msg, type){ const t=document.getElementById('rmToast'); t.textContent=msg; t.className=(type||'')+' show'; clearTimeout(toastT); toastT=setTimeout(()=>t.classList.remove('show'),2600); }

// ── API helpers ──
// Endurecidos (#25): timeout + try/catch para que un fallo de red NO deje la pantalla
// vacía en silencio; devuelven {success:false} (los callers ya lo manejan) y avisan.
async function apiGet(action, params={}){
  const q=new URLSearchParams({action,...params});
  const ctrl=new AbortController(); const killer=setTimeout(()=>{ try{ctrl.abort();}catch(_){} },12000);
  try{ const r=await fetch(API+'?'+q.toString(),{signal:ctrl.signal}); if(!r.ok) throw new Error('HTTP '+r.status); return await r.json(); }
  catch(e){ toast('No se pudo cargar la información (revisa tu conexión)','err'); return {success:false,message:String((e&&e.message)||e)}; }
  finally{ clearTimeout(killer); }
}
async function apiPost(action, data={}){
  const ctrl=new AbortController(); const killer=setTimeout(()=>{ try{ctrl.abort();}catch(_){} },12000);
  try{ const r=await fetch(API+'?action='+action,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data),signal:ctrl.signal}); if(!r.ok) throw new Error('HTTP '+r.status); return await r.json(); }
  catch(e){ toast('No se pudo guardar (revisa tu conexión)','err'); return {success:false,message:String((e&&e.message)||e)}; }
  finally{ clearTimeout(killer); }
}

// ── Carga inicial ──
async function rmInit(){
  const lst = await apiGet('listar');
  R.all = (lst && lst.success) ? lst.data : [];
  // get_operadores es solo para gestores (el operador no tiene ese permiso)
  if (IS_GESTOR) {
    const ops = await apiGet('get_operadores');
    R.operadores = (ops && ops.success) ? ops.data : [];
    const fo=document.getElementById('fOperador'), mo=document.getElementById('mOpsSelect'), bk=document.getElementById('bkOperador');
    R.operadores.forEach(o=>{
      const opt=`<option value="${o.operador_id}">${esc(o.full_name)}</option>`;
      if(fo) fo.insertAdjacentHTML('beforeend', opt);
      if(mo) mo.insertAdjacentHTML('beforeend', opt);
      if(bk) bk.insertAdjacentHTML('beforeend', opt);
    });
  }
  rmApplyFilters();
}

// Normaliza texto para búsquedas flexibles: quita acentos/diacríticos y pasa a minúsculas.
function normalizar(s){ return (s==null?'':String(s)).normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase().trim(); }

// ── Filtros + orden ──
function rmApplyFilters(){
  const q=normalizar(document.getElementById('fSearch').value);
  const opEl=document.getElementById('fOperador'); const op=opEl?opEl.value:'';
  const ciudad=normalizar(document.getElementById('fCiudad').value);
  const aero=normalizar(document.getElementById('fAero').value);
  const tipo=document.getElementById('fTipo').value;
  const estado=document.getElementById('fEstado').value;
  const desde=document.getElementById('fDesde').value;
  const hasta=document.getElementById('fHasta').value;
  const horaD=document.getElementById('fHoraD').value;
  const horaH=document.getElementById('fHoraH').value;

  R.filtered = R.all.filter(r=>{
    if(q){
      const blob=normalizar([trCode(r.id),r.reserva_codigo,titular(r),r.flight_code,r.city,r.pickup_location,r.dropoff_location,aeroCol(r)].join(' '));
      if(!blob.includes(q)) return false;
    }
    if(op){ const ids=(r.operadores_ids||'').split(','); if(!ids.includes(String(op))) return false; }
    if(ciudad && !normalizar(r.city).includes(ciudad)) return false;
    if(aero){ if(!normalizar(aeroCol(r)).includes(aero)) return false; }
    if(tipo && r.service_type!==tipo) return false;
    if(estado && r.status!==estado) return false;
    if(desde && (!r.service_date || r.service_date < desde)) return false;
    if(hasta && (!r.service_date || r.service_date > hasta)) return false;
    if(horaD || horaH){
      const hv=hhmm(horaVuelo(r)), hp=hhmm(r.pickup_time);
      const inRange = t => t && (!horaD || t>=horaD) && (!horaH || t<=horaH);
      if(!inRange(hv) && !inRange(hp)) return false;
    }
    return true;
  });

  rmSort();
}

function sortVal(r,k){
  switch(k){
    case 'codigo': return (r.reserva_codigo||'')+' '+trCode(r.id);
    case 'titular': return titular(r).toLowerCase();
    case 'tipo': return TIPO[r.service_type]?.l||'';
    case 'fecha': return (fechaEff(r)||'')+' '+hhmm(horaVuelo(r));
    case 'ciudad': return (r.city||'').toLowerCase();
    case 'aeropuerto': return aeroCol(r);
    case 'vuelo': return r.flight_code||'';
    case 'hvuelo': return hhmm(horaVuelo(r));
    case 'hrecogida': return hhmm(pickupEff(r));
    case 'recogida': return (r.pickup_location||'').toLowerCase();
    case 'destino': return (r.dropoff_location||'').toLowerCase();
    case 'pax': return parseInt(r.cantidad_pasajeros||0,10);
    case 'operadores': return (r.operadores_nombres||'').toLowerCase();
    case 'estado': return r.status||'';
    default: return '';
  }
}
function rmSort(){
  const k=R.sortKey, dir=R.sortDir==='asc'?1:-1;
  R.filtered.sort((a,b)=>{ const va=sortVal(a,k), vb=sortVal(b,k); if(va<vb) return -1*dir; if(va>vb) return 1*dir; return 0; });
  rmRender();
}
document.getElementById('rmHead').addEventListener('click', e=>{
  const th=e.target.closest('th'); if(!th||!th.dataset.k) return;
  const k=th.dataset.k;
  if(R.sortKey===k) R.sortDir = R.sortDir==='asc'?'desc':'asc';
  else { R.sortKey=k; R.sortDir='asc'; }
  document.querySelectorAll('#rmHead th').forEach(t=>{ t.classList.remove('sorted'); const a=t.querySelector('.rm-arrow'); if(a)a.textContent='↕'; });
  th.classList.add('sorted'); th.querySelector('.rm-arrow').textContent = R.sortDir==='asc'?'↑':'↓';
  rmSort();
});

function rmRender(){
  const body=document.getElementById('rmBody');
  document.getElementById('rmCount').textContent = R.filtered.length;
  if(!R.filtered.length){ body.innerHTML=''; document.getElementById('rmEmpty').style.display='block'; rmUpdateBulkBar(); return; }
  document.getElementById('rmEmpty').style.display='none';
  body.innerHTML = R.filtered.map(r=>{
    const tp=TIPO[r.service_type]||TIPO.por_asignar;
    const st=STT[r.status]||STT.en_proceso;
    const ops = r.operadores_nombres
      ? `<div class="rm-ops">${r.operadores_nombres.split(', ').map(n=>`<span class="rm-op-chip">${esc(n)}</span>`).join('')}</div>`
      : `<span class="rm-op-none">—</span>`;
    return `<tr onclick="rmAbrirDetalle(${r.id})">
      ${IS_GESTOR ? `<td class="rm-chkcell" onclick="event.stopPropagation()"><input type="checkbox" class="rm-chk rm-rowsel" ${R.selected.has(r.id)?'checked':''} onclick="rmToggleSel(${r.id},this.checked)"></td>` : ''}
      <td><span class="rm-code">${esc(r.reserva_codigo||'—')}<small>${trCode(r.id)}</small></span></td>
      <td>${esc(titular(r))||'<span class="rm-op-none">—</span>'}</td>
      <td><span class="rm-tipo ${tp.c}">${tp.l}</span></td>
      <td>${esc(fmtFecha(fechaEff(r)))}</td>
      <td>${esc(r.city||'')}</td>
      <td><span class="rm-aero">${esc(aeroCol(r))}</span></td>
      <td>${esc(r.flight_code||'')}</td>
      <td>${esc(hhmm(horaVuelo(r)))}</td>
      <td>${esc(hhmm(pickupEff(r)))}</td>
      <td>${esc(r.pickup_location||'')}</td>
      <td>${esc(r.dropoff_location||'')}</td>
      <td>${esc(r.cantidad_pasajeros??'')}</td>
      <td onclick="event.stopPropagation()">${ops}</td>
      <td onclick="event.stopPropagation()"><span class="rm-status ${st.c}" onclick="rmOpenStatusPop(event,${r.id})"><span class="dot"></span>${st.l}</span></td>
    </tr>`;
  }).join('');
  rmUpdateBulkBar();
}

// ── Estado inline ──
function rmOpenStatusPop(e, id){
  e.stopPropagation();
  R.statusTarget=id;
  const pop=document.getElementById('rmStatusPop');
  const rect=e.currentTarget.getBoundingClientRect();
  pop.style.left=Math.min(rect.left, window.innerWidth-170)+'px';
  pop.style.top=(rect.bottom+4)+'px';
  pop.classList.add('open');
}
async function rmSetStatusInline(status){
  const id=R.statusTarget; document.getElementById('rmStatusPop').classList.remove('open');
  if(!id) return;
  const d=await apiPost('actualizar_estado',{id,status});
  if(d.success){ const row=R.all.find(x=>x.id==id); if(row) row.status=status; rmApplyFilters(); toast('Estado actualizado','ok'); }
  else toast(d.message||'Error','err');
}
document.addEventListener('click', e=>{ if(!e.target.closest('#rmStatusPop') && !e.target.closest('.rm-status')) document.getElementById('rmStatusPop').classList.remove('open'); });

// ── Modal detalle/edición ──
async function rmAbrirDetalle(id){
  const d=await apiGet('detalle',{id});
  if(!d.success){ toast(d.message||'No se pudo cargar','err'); return; }
  R.current=d.data; R.currentId=id;
  document.getElementById('mProgramaWrap').style.display='none';
  document.getElementById('rmModalTitle').textContent='Traslado '+trCode(id);
  document.getElementById('rmModalSub').textContent=(d.data.reserva_codigo?('Reserva '+d.data.reserva_codigo+' · '):'')+(titular(d.data)||'');
  rmFillForm(d.data);
  if (IS_OPERADOR) {
    // El operador solo edita estado y notas del operador
    document.getElementById('mDelBtn').style.display='none';
    document.getElementById('mOpsSection').style.display='none';
    rmLockForOperador();
  } else {
    document.getElementById('mDelBtn').style.display='';
    document.getElementById('mOpsSection').style.display='';
    R.modalOps=(d.data.operadores||[]).map(o=>({operador_id:Number(o.operador_id), full_name:o.full_name}));
    R.origOps=R.modalOps.map(o=>o.operador_id);
    rmRenderOps(R.modalOps);
  }
  document.getElementById('rmModal').classList.add('open');
}
function rmFillForm(r){
  document.getElementById('mId').value=r.id||'';
  document.getElementById('mTipo').value=r.service_type||'por_asignar';
  document.getElementById('mStatus').value=r.status||'en_proceso';
  document.getElementById('mFecha').value=(fechaEff(r)||'').slice(0,10);
  document.getElementById('mCiudad').value=r.city||'';
  document.getElementById('mAeroO').value=r.airport_code_origen||'';
  document.getElementById('mAeroD').value=r.airport_code_destino||'';
  document.getElementById('mVuelo').value=r.flight_code||'';
  document.getElementById('mGuia').value=r.guide_name||'';
  document.getElementById('mLlegada').value=hhmm(r.arrival_time);
  document.getElementById('mSalida').value=hhmm(r.departure_time);
  document.getElementById('mPickupT').value=hhmm(pickupEff(r));
  document.getElementById('mPax').value=r.cantidad_pasajeros??'';
  document.getElementById('mPickupL').value=r.pickup_location||'';
  document.getElementById('mDropoff').value=r.dropoff_location||'';
  document.getElementById('mNotasInt').value=r.internal_notes||'';
  document.getElementById('mNotasOp').value=r.operator_notes||'';
}
function rmFormData(){
  return {
    service_type:document.getElementById('mTipo').value,
    status:document.getElementById('mStatus').value,
    service_date:document.getElementById('mFecha').value,
    city:document.getElementById('mCiudad').value,
    airport_code_origen:document.getElementById('mAeroO').value.toUpperCase(),
    airport_code_destino:document.getElementById('mAeroD').value.toUpperCase(),
    flight_code:document.getElementById('mVuelo').value,
    guide_name:document.getElementById('mGuia').value,
    arrival_time:document.getElementById('mLlegada').value,
    departure_time:document.getElementById('mSalida').value,
    pickup_time:document.getElementById('mPickupT').value,
    pickup_location:document.getElementById('mPickupL').value,
    dropoff_location:document.getElementById('mDropoff').value,
    internal_notes:document.getElementById('mNotasInt').value,
    operator_notes:document.getElementById('mNotasOp').value
  };
}
// Para el operador: deja editable solo Estado y Notas del operador; el resto en solo lectura.
function rmLockForOperador(){
  const editables = ['mStatus','mNotasOp'];
  ['mTipo','mStatus','mFecha','mCiudad','mAeroO','mAeroD','mVuelo','mGuia','mLlegada','mSalida','mPickupT','mPickupL','mDropoff','mNotasInt','mNotasOp']
    .forEach(id=>{ const el=document.getElementById(id); if(el) el.disabled = !editables.includes(id); });
}

function rmCerrarModal(){ document.getElementById('rmModal').classList.remove('open'); R.currentId=null; R.current=null; }

async function rmGuardar(){
  // El operador solo envía estado + notas del operador
  const data = IS_OPERADOR
    ? { status:document.getElementById('mStatus').value, operator_notes:document.getElementById('mNotasOp').value }
    : rmFormData();
  if(R.currentId){
    const d=await apiPost('actualizar',{id:R.currentId,...data});
    if(d.success){
      if(IS_GESTOR){ try{ await rmReconcileOperadores(); }catch(e){ console.error(e); } }
      toast('Traslado actualizado','ok'); rmCerrarModal(); await rmReload();
    } else toast(d.message||'Error','err');
  } else {
    const solicitud_id=document.getElementById('mPrograma').value;
    if(!solicitud_id){ toast('Selecciona un programa','err'); return; }
    const d=await apiPost('crear',{solicitud_id,...data});
    if(d.success){ toast('Traslado creado','ok'); rmCerrarModal(); await rmReload(); }
    else toast(d.message||'Error','err');
  }
}
async function rmEliminar(){
  if(!R.currentId) return;
  if(!confirm('¿Eliminar este traslado? Esta acción no se puede deshacer.')) return;
  const d=await apiPost('eliminar',{id:R.currentId});
  if(d.success){ toast('Traslado eliminado','ok'); rmCerrarModal(); await rmReload(); }
  else toast(d.message||'Error','err');
}

// ── Operadores en modal ──
function rmRenderOps(ops){
  const box=document.getElementById('mOpsBox');
  box.innerHTML = ops.length
    ? ops.map(o=>`<span class="chip">${esc(o.full_name)}<button title="Quitar" onclick="rmRemoveOperadorChip(${o.operador_id})">&times;</button></span>`).join('')
    : '<span class="rm-op-none" style="font-size:12px;">Sin operadores asignados</span>';
}
// Añade/quita operadores en estado local; se persisten al Guardar (rmGuardar reconcilia)
function rmAddOperadorChip(){
  const sel=document.getElementById('mOpsSelect'); const id=sel.value; sel.value='';
  if(!id) return;
  if(!R.modalOps.some(o=>String(o.operador_id)===String(id))){
    const op=R.operadores.find(o=>String(o.operador_id)===String(id));
    R.modalOps.push({operador_id:Number(id), full_name: op ? op.full_name : ('#'+id)});
    rmRenderOps(R.modalOps);
  }
}
function rmRemoveOperadorChip(id){
  R.modalOps=R.modalOps.filter(o=>String(o.operador_id)!==String(id));
  rmRenderOps(R.modalOps);
}
// Persiste la diferencia de operadores (añadidos/quitados) del traslado actual
async function rmReconcileOperadores(){
  const now=R.modalOps.map(o=>Number(o.operador_id));
  const orig=(R.origOps||[]).map(Number);
  const toAdd=now.filter(id=>!orig.includes(id));
  const toRemove=orig.filter(id=>!now.includes(id));
  for(const id of toAdd)    await apiPost('asignar_operador',{rooming_id:R.currentId,operador_id:id});
  for(const id of toRemove) await apiPost('quitar_operador',{rooming_id:R.currentId,operador_id:id});
}

// ── Nuevo (creación manual) ──
async function rmNuevo(){
  R.currentId=null; R.current=null;
  await rmCargarProgramas();
  const sel=document.getElementById('mPrograma'); sel.innerHTML='<option value="">Selecciona un programa…</option>';
  R.programas.forEach(p=>sel.insertAdjacentHTML('beforeend',`<option value="${p.id}">${esc(p.nombre)} — ${esc(p.destino||'')}</option>`));
  document.getElementById('mProgramaWrap').style.display='';
  document.getElementById('mDelBtn').style.display='none';
  document.getElementById('mOpsSection').style.display='none'; // se asignan tras crear
  document.getElementById('rmModalTitle').textContent='Nuevo traslado';
  document.getElementById('rmModalSub').textContent='';
  rmFillForm({service_type:'llevada_al_hotel',status:'en_proceso'});
  document.getElementById('mPax').value='';
  document.getElementById('rmModal').classList.add('open');
}

// ── Generar desde programa ──
async function rmCargarProgramas(){
  if(R.programas.length) return;
  const d=await apiGet('get_programas_vendidos');
  R.programas = (d && d.success) ? d.data : [];
}
async function rmAbrirGenerar(){
  await rmCargarProgramas();
  const sel=document.getElementById('genPrograma');
  sel.innerHTML='<option value="">Selecciona un programa…</option>';
  R.programas.forEach(p=>sel.insertAdjacentHTML('beforeend',`<option value="${p.id}">${esc(p.nombre)} — ${esc(p.destino||'')}</option>`));
  document.getElementById('rmGenModal').classList.add('open');
}
function rmCerrarGenerar(){ document.getElementById('rmGenModal').classList.remove('open'); }
async function rmGenerar(){
  const solicitud_id=document.getElementById('genPrograma').value;
  if(!solicitud_id){ toast('Selecciona un programa','err'); return; }
  const d=await apiPost('generar',{solicitud_id});
  if(d.success){
    const msg = d.motivo==='ok' ? `${d.generados} traslado(s) generados`
              : d.motivo==='ya_generado' ? 'Ya se habían generado'
              : d.motivo==='ya_existian' ? 'Los traslados ya existían'
              : d.motivo==='sin_vuelos' ? 'El programa no tiene vuelos' : 'Listo';
    toast(msg, d.generados>0?'ok':'');
    rmCerrarGenerar(); await rmReload();
  } else toast(d.message||'Error','err');
}

// ── Recargar datos ──
async function rmReload(){ const lst=await apiGet('listar'); R.all=(lst&&lst.success)?lst.data:[]; rmApplyFilters(); }
async function rmReloadSilent(){ const lst=await apiGet('listar'); R.all=(lst&&lst.success)?lst.data:[]; rmApplyFilters(); }

function rmClearFilters(){
  ['fSearch','fCiudad','fAero','fDesde','fHasta','fHoraD','fHoraH','fOperador','fTipo','fEstado']
    .forEach(i=>{ const el=document.getElementById(i); if(el) el.value=''; });
  rmApplyFilters();
}

// ── Selección y acciones en lote (gestor) ──
function rmToggleSel(id, checked){ if(checked) R.selected.add(id); else R.selected.delete(id); rmUpdateBulkBar(); }
function rmToggleSelAll(checked){
  R.filtered.forEach(r=>{ if(checked) R.selected.add(r.id); else R.selected.delete(r.id); });
  rmRender();
}
function rmUpdateBulkBar(){
  const bar=document.getElementById('rmBulkBar'); if(!bar) return;
  const n=R.selected.size;
  bar.classList.toggle('show', n>0);
  const c=document.getElementById('rmBulkCount'); if(c) c.textContent=n+(n===1?' seleccionado':' seleccionados');
  const all=document.getElementById('rmSelAll');
  if(all){ const vis=R.filtered.length; const selVis=R.filtered.filter(r=>R.selected.has(r.id)).length; all.checked = vis>0 && selVis===vis; all.indeterminate = selVis>0 && selVis<vis; }
}
function rmClearSel(){ R.selected.clear(); rmRender(); }
function rmSelectedIds(){ return [...R.selected]; }
async function rmBulkAsignar(){
  const operador_id=document.getElementById('bkOperador').value;
  if(!operador_id){ toast('Elige un operador','err'); return; }
  const ids=rmSelectedIds(); if(!ids.length){ toast('No hay traslados seleccionados','err'); return; }
  const d=await apiPost('bulk_asignar_operador',{ids,operador_id});
  if(d.success){ toast(`Operador asignado a ${d.afectados} traslado(s)`,'ok'); R.selected.clear(); await rmReload(); rmUpdateBulkBar(); }
  else toast(d.message||'Error','err');
}
async function rmBulkPickup(){
  const pickup_time=document.getElementById('bkPickup').value;
  if(!pickup_time){ toast('Indica una hora de recogida','err'); return; }
  const ids=rmSelectedIds(); if(!ids.length){ toast('No hay traslados seleccionados','err'); return; }
  const d=await apiPost('bulk_update',{ids,pickup_time});
  if(d.success){ toast(`Recogida fijada en ${d.afectados} traslado(s)`,'ok'); R.selected.clear(); await rmReload(); rmUpdateBulkBar(); }
  else toast(d.message||'Error','err');
}
async function rmBulkEstado(){
  const status=document.getElementById('bkEstado').value;
  const ids=rmSelectedIds(); if(!ids.length){ toast('No hay traslados seleccionados','err'); return; }
  const d=await apiPost('bulk_update',{ids,status});
  if(d.success){ toast(`Estado aplicado a ${d.afectados} traslado(s)`,'ok'); R.selected.clear(); await rmReload(); rmUpdateBulkBar(); }
  else toast(d.message||'Error','err');
}

// ── Export CSV (respeta filtros actuales) ──
function rmExportCsv(){
  const cols=['Reserva','Traslado','Titular','Tipo','Fecha','Ciudad','Aeropuerto','Vuelo','Hora vuelo','Hora recogida','Recogida','Destino','Hotel','Pax','Operadores','Estado'];
  const rows=R.filtered.map(r=>[
    r.reserva_codigo||'', trCode(r.id), titular(r), TIPO[r.service_type]?.l||'', fechaEff(r), r.city||'',
    aeroCol(r), r.flight_code||'', hhmm(horaVuelo(r)), hhmm(pickupEff(r)), r.pickup_location||'', r.dropoff_location||'',
    r.hotel_nombre||'', r.cantidad_pasajeros??'', r.operadores_nombres||'', STT[r.status]?.l||''
  ]);
  // Separador ';' + directiva 'sep=;' al inicio: Excel y LibreOffice lo abren
  // como tabla por columnas en cualquier locale (con ',' se veía todo en una celda).
  const SEP=';';
  const cell=c=>{ const s=String(c??''); return /[";\n,\r]/.test(s) ? '"'+s.replace(/"/g,'""')+'"' : s; };
  const csv='sep='+SEP+'\r\n'+[cols,...rows].map(row=>row.map(cell).join(SEP)).join('\r\n');
  const blob=new Blob(['﻿'+csv],{type:'text/csv;charset=utf-8;'});
  const a=document.createElement('a'); a.href=URL.createObjectURL(blob);
  a.download='traslados_'+new Date().toISOString().slice(0,10)+'.csv'; a.click(); URL.revokeObjectURL(a.href);
}

// ── Reglas de asignación automática (gestor) ──
function rmAbrirReglas(){
  const sel=document.getElementById('rgOperador'); sel.innerHTML='<option value="">Selecciona…</option>';
  R.operadores.forEach(o=>sel.insertAdjacentHTML('beforeend',`<option value="${o.operador_id}">${esc(o.full_name)}</option>`));
  document.getElementById('rmReglasModal').classList.add('open');
  rmCancelarFormRegla();
  rmCargarReglas();
}
function rmCerrarReglas(){ document.getElementById('rmReglasModal')?.classList.remove('open'); }
async function rmCargarReglas(){ const d=await apiGet('get_reglas'); R.reglas=(d&&d.success)?d.data:[]; rmRenderReglas(); }
function rmReglaResumen(r){
  const p=[];
  if(r.airport_code) p.push('Aerop. '+esc(r.airport_code));
  if(r.service_type) p.push(TIPO[r.service_type]?.l||esc(r.service_type));
  if(r.city) p.push('Ciudad: '+esc(r.city));
  return p.length ? p.join(' · ') : '(sin condiciones)';
}
function rmRenderReglas(){
  const list=R.reglas, el=document.getElementById('rgList');
  if(!list.length){ el.innerHTML='<div style="color:#94a3b8;font-size:13px;padding:10px 0;">Aún no hay reglas.</div>'; return; }
  el.innerHTML=list.map((r,i)=>`<div style="display:flex;align-items:center;gap:8px;padding:10px 0;border-bottom:1px solid #f1f5f9;${r.activa==1?'':'opacity:.5;'}">
    <div style="display:flex;flex-direction:column;line-height:1;">
      <button class="rm-clear" style="padding:0 4px;${i===0?'opacity:.25;cursor:default;':''}" ${i===0?'disabled':''} onclick="rmMoverRegla(${i},-1)" title="Subir prioridad">▲</button>
      <button class="rm-clear" style="padding:0 4px;${i===list.length-1?'opacity:.25;cursor:default;':''}" ${i===list.length-1?'disabled':''} onclick="rmMoverRegla(${i},1)" title="Bajar prioridad">▼</button>
    </div>
    <div style="width:22px;text-align:center;font-size:12px;font-weight:700;color:var(--pr);">${i+1}</div>
    <div style="flex:1;min-width:0;">
      <div style="font-size:13px;color:#0f172a;"><strong>${esc(r.operador_nombre)}</strong></div>
      <div style="font-size:12px;color:#64748b;">${rmReglaResumen(r)}</div>
    </div>
    <label style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:5px;cursor:pointer;white-space:nowrap;"><input type="checkbox" ${r.activa==1?'checked':''} onchange="rmToggleRegla(${r.id},this.checked)"> Activa</label>
    <button class="rm-cancel" style="padding:5px 10px;" onclick="rmEditarRegla(${r.id})">Editar</button>
    <button class="rm-del" style="padding:5px 10px;" onclick="rmEliminarRegla(${r.id})">Eliminar</button>
  </div>`).join('');
}
function rmEditarRegla(id){
  const r=R.reglas.find(x=>x.id==id); if(!r) return;
  document.getElementById('rgEditId').value=r.id;
  document.getElementById('rgOperador').value=r.operador_id;
  document.getElementById('rgTipo').value=r.service_type||'';
  document.getElementById('rgAero').value=r.airport_code||'';
  document.getElementById('rgCity').value=r.city||'';
  document.getElementById('rgFormTitle').textContent='Editar regla';
  document.getElementById('rgCancelBtn').style.display='';
}
function rmCancelarFormRegla(){
  document.getElementById('rgEditId').value='';
  document.getElementById('rgOperador').value='';
  document.getElementById('rgTipo').value='';
  document.getElementById('rgAero').value='';
  document.getElementById('rgCity').value='';
  document.getElementById('rgFormTitle').textContent='Nueva regla';
  document.getElementById('rgCancelBtn').style.display='none';
}
async function rmGuardarRegla(){
  const id=document.getElementById('rgEditId').value;
  const operador_id=document.getElementById('rgOperador').value;
  const service_type=document.getElementById('rgTipo').value;
  const airport_code=document.getElementById('rgAero').value.trim();
  const city=document.getElementById('rgCity').value.trim();
  if(!operador_id){ toast('Selecciona el operador','err'); return; }
  if(!airport_code && !service_type && !city){ toast('Define al menos una condición','err'); return; }
  const payload={operador_id,service_type,airport_code,city}; if(id) payload.id=id;
  const d=await apiPost(id?'update_regla':'crear_regla',payload);
  if(d.success){ toast(id?'Regla actualizada':'Regla creada','ok'); rmCancelarFormRegla(); rmCargarReglas(); }
  else toast(d.message||'Error','err');
}
async function rmMoverRegla(index,dir){
  const j=index+dir; if(j<0||j>=R.reglas.length) return;
  const list=R.reglas.slice(); const t=list[index]; list[index]=list[j]; list[j]=t;
  R.reglas=list; rmRenderReglas();
  const d=await apiPost('reordenar_reglas',{orden:list.map(r=>r.id)});
  if(!d.success){ toast(d.message||'Error al reordenar','err'); rmCargarReglas(); }
}
async function rmToggleRegla(id,activa){ const d=await apiPost('toggle_regla',{id,activa:activa?1:0}); if(d.success){ const r=R.reglas.find(x=>x.id==id); if(r) r.activa=activa?1:0; } else toast(d.message||'Error','err'); }
async function rmEliminarRegla(id){ if(!confirm('¿Eliminar esta regla?')) return; const d=await apiPost('eliminar_regla',{id}); if(d.success){ toast('Regla eliminada','ok'); rmCargarReglas(); } else toast(d.message||'Error','err'); }
async function rmAplicarReglas(){
  const ids=R.filtered.map(r=>r.id);
  if(!ids.length){ toast('No hay traslados en la vista','err'); return; }
  if(!confirm(`¿Aplicar las reglas activas a los ${ids.length} traslado(s) que estás viendo (según filtros)?`)) return;
  const d=await apiPost('aplicar_reglas',{ids});
  if(d.success){ toast(`Reglas aplicadas: ${d.asignados} asignación(es) nueva(s)`,'ok'); await rmReload(); }
  else toast(d.message||'Error','err');
}

// ── Sidebar ──
let sidebarOpen=false;
function toggleSidebar(){ sidebarOpen=!sidebarOpen; document.querySelector('.enhanced-sidebar')?.classList.toggle('open',sidebarOpen); document.getElementById('overlay').style.display=sidebarOpen?'block':'none'; }
function closeSidebar(){ sidebarOpen=false; document.querySelector('.enhanced-sidebar')?.classList.remove('open'); document.getElementById('overlay').style.display='none'; }

// Cerrar modales con overlay / Escape
document.getElementById('rmModal')?.addEventListener('click',e=>{ if(e.target===e.currentTarget) rmCerrarModal(); });
document.getElementById('rmGenModal')?.addEventListener('click',e=>{ if(e.target===e.currentTarget) rmCerrarGenerar(); });
document.getElementById('rmReglasModal')?.addEventListener('click',e=>{ if(e.target===e.currentTarget) rmCerrarReglas(); });
document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ rmCerrarModal(); rmCerrarGenerar(); rmCerrarReglas(); document.getElementById('rmStatusPop')?.classList.remove('open'); } });

rmInit();
</script>
<!-- Google Translate -->
<script type="text/javascript">
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'es',
        includedLanguages: 'en,fr,pt,it,de,es',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
    }, 'google_translate_element');
}
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
