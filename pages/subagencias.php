<?php
// =====================================================================
// pages/subagencias.php — Panel de la SUBAGENCIA (revendedor B2B)
// El router (index.php) ya hace App::requireRole('subagencia').
// Secciones: mini-dashboard, Mis Tours (editar precios + link), Mi Marca.
// =====================================================================
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';
App::init();
App::requireLogin();
$user = App::getUser();
require_once dirname(__DIR__) . '/config/config_functions.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/ui_components.php';
ConfigManager::init();

// Sólo subagencia (defensa extra; el router ya lo controla)
if ($user['role'] !== 'subagencia') { App::redirect('/dashboard'); exit; }

// Marca propia de la subagencia (config_sub_agencias)
$db = Database::getInstance();
$subCfg = $db->fetch(
    "SELECT nombre, logo_url, primary_color, secondary_color, divisa, email_contacto, telefono
     FROM config_sub_agencias WHERE user_id = ?",
    [(int)$user['id']]
) ?: [];

function sa_hex($v, $def) {
    $v = trim((string)$v);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $def;
}
function sa_rgb($hex) {
    $h = ltrim(trim($hex), '#');
    if (strlen($h) === 3) $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $h)) return '124,58,237';
    return hexdec(substr($h,0,2)).','.hexdec(substr($h,2,2)).','.hexdec(substr($h,4,2));
}

$primary   = sa_hex($subCfg['primary_color']   ?? '', '#7c3aed');
$secondary = sa_hex($subCfg['secondary_color'] ?? '', '#a855f7');
$pRgb      = sa_rgb($primary);
$nombreComercial = $subCfg['nombre'] ?? ($user['name'] ?? 'Mi Subagencia');
$defaultLang = ConfigManager::getDefaultLanguage() ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= $defaultLang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Tours — <?= htmlspecialchars($nombreComercial) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?= UIComponents::getComponentStyles() ?>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --pr:<?= $primary ?>;
  --sc:<?= $secondary ?>;
  --pr-rgb:<?= $pRgb ?>;
  --grad:linear-gradient(135deg,var(--pr) 0%,var(--sc) 100%);
  --primary-color:<?= $primary ?>;
  --secondary-color:<?= $secondary ?>;
  --primary-color-rgb:<?= $pRgb ?>;
  --primary-gradient:linear-gradient(135deg,var(--pr) 0%,var(--sc) 100%);
}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;}

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
#mainContent{margin-top:70px;padding:24px;max-width:1180px;margin-left:auto;margin-right:auto;}

/* HERO / mini-dashboard */
.sa-hero{background:var(--grad);color:#fff;border-radius:18px;padding:26px 28px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:0 10px 30px rgba(var(--pr-rgb),.25);}
.sa-hero h1{font-size:24px;font-weight:800;letter-spacing:-.4px;}
.sa-hero p{opacity:.9;font-size:14px;margin-top:4px;}
.sa-hero-stats{margin-left:auto;display:flex;gap:14px;flex-wrap:wrap;}
.sa-stat{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:14px;padding:14px 20px;text-align:center;min-width:120px;}
.sa-stat .n{font-size:28px;font-weight:800;line-height:1;}
.sa-stat .l{font-size:12px;opacity:.9;margin-top:6px;}

/* TABS */
.sa-tabs{display:flex;gap:8px;margin:22px 0 18px;border-bottom:1px solid #e2e8f0;}
.sa-tab{background:none;border:none;padding:12px 18px;font-size:14px;font-weight:600;color:#64748b;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-1px;}
.sa-tab.active{color:var(--pr);border-bottom-color:var(--pr);}
.sa-panel{display:none;}
.sa-panel.active{display:block;}

/* CARDS de tours */
.sa-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;}
.sa-card{background:#fff;border:1px solid #e8edf2;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 1px 4px rgba(15,23,42,.05);transition:box-shadow .2s,transform .2s;}
.sa-card:hover{box-shadow:0 10px 26px rgba(15,23,42,.10);transform:translateY(-2px);}
.sa-card-img{height:130px;background:#e2e8f0 center/cover no-repeat;position:relative;}
.sa-card-img .ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:30px;}
.sa-card-body{padding:14px 16px;display:flex;flex-direction:column;gap:8px;flex:1;}
.sa-card-title{font-size:15px;font-weight:700;color:#0f172a;}
.sa-card-meta{font-size:12.5px;color:#64748b;display:flex;align-items:center;gap:6px;}
.sa-card-price{font-size:18px;font-weight:800;color:var(--pr);margin-top:2px;}
.sa-card-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:auto;padding-top:8px;}
.sa-btn{height:34px;padding:0 12px;border:none;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.sa-btn-primary{background:var(--grad);color:#fff;}
.sa-btn-primary:hover{opacity:.9;}
.sa-btn-ghost{background:#f1f5f9;color:#475569;}
.sa-btn-ghost:hover{background:#e2e8f0;}

/* FORM marca */
.sa-form-card{background:#fff;border:1px solid #e8edf2;border-radius:14px;padding:24px;max-width:640px;box-shadow:0 1px 4px rgba(15,23,42,.05);}
.sa-fg{margin-bottom:16px;}
.sa-fg label{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;}
.sa-fg input{width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;font-size:14px;}
.sa-fg input[type=color]{padding:2px;height:42px;cursor:pointer;}
.sa-row{display:flex;gap:14px;flex-wrap:wrap;}
.sa-row .sa-fg{flex:1;min-width:160px;}

/* MODAL editar precios */
.sa-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:1200;align-items:flex-start;justify-content:center;overflow-y:auto;padding:40px 16px;}
.sa-overlay.open{display:flex;}
.sa-box{background:#fff;border-radius:16px;width:100%;max-width:620px;box-shadow:0 20px 60px rgba(0,0,0,.3);}
.sa-box-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #eef2f6;}
.sa-box-hd h3{font-size:17px;font-weight:700;color:#0f172a;}
.sa-box-hd .x{background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:16px;color:#475569;}
.sa-box-body{padding:22px;}
.sa-box-ft{display:flex;justify-content:flex-end;gap:10px;padding:16px 22px;border-top:1px solid #eef2f6;}
textarea.sa-ta{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px;font-size:13.5px;font-family:inherit;resize:vertical;min-height:64px;}

.sa-empty{background:#fff;border:1px dashed #cbd5e1;border-radius:14px;padding:48px 24px;text-align:center;color:#64748b;}
.sa-empty i{font-size:34px;color:#cbd5e1;margin-bottom:12px;display:block;}

/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:#0f172a;color:#fff;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:500;opacity:0;pointer-events:none;transition:all .3s;z-index:2000;box-shadow:0 8px 24px rgba(0,0,0,.25);}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
#toast.err{background:#dc2626;}
#toast.ok{background:#16a34a;}

@media(max-width:640px){
  #mainContent{padding:16px;}
  .sa-hero{padding:20px;}
  .sa-hero-stats{margin-left:0;width:100%;}
}
</style>
</head>
<body>

<div class="header">
  <div class="header-left">
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <span class="header-title">Mis Tours</span>
  </div>
  <div class="header-right">
    <div class="header-user">
      <div class="header-avatar"><?= strtoupper(substr($nombreComercial,0,2)) ?></div>
      <span class="header-name"><?= htmlspecialchars($nombreComercial) ?></span>
    </div>
  </div>
</div>

<?= UIComponents::renderSidebar($user, '/subagencias') ?>
<div id="overlay" onclick="closeSidebar()"></div>

<div id="mainContent">

  <div class="sa-hero">
    <div>
      <h1><?= htmlspecialchars($nombreComercial) ?></h1>
      <p>Gestiona tus tours asignados, define tus precios y comparte tu propio enlace.</p>
    </div>
    <div class="sa-hero-stats">
      <div class="sa-stat"><div class="n" id="statTours">—</div><div class="l">Tours asignados</div></div>
    </div>
  </div>

  <div class="sa-tabs">
    <button class="sa-tab active" data-tab="tours" onclick="saTab('tours')">Mis Tours</button>
    <button class="sa-tab" data-tab="marca" onclick="saTab('marca')">Mi Marca</button>
  </div>

  <!-- PANEL: MIS TOURS -->
  <div class="sa-panel active" id="panel-tours">
    <div class="sa-grid" id="toursGrid"></div>
    <div class="sa-empty" id="toursEmpty" style="display:none;">
      <i class="fas fa-suitcase-rolling"></i>
      Todavía no tienes tours asignados.<br>La agencia te asignará tours para que puedas revenderlos.
    </div>
  </div>

  <!-- PANEL: MI MARCA -->
  <div class="sa-panel" id="panel-marca">
    <div class="sa-form-card">
      <div class="sa-fg">
        <label>Logo</label>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
          <div id="logoPreview" style="width:88px;height:88px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc center/contain no-repeat;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:24px;"><i class="fas fa-image"></i></div>
          <div>
            <input type="file" id="m_logo_file" accept="image/*" style="display:none;" onchange="subirLogo(this)">
            <button type="button" class="sa-btn sa-btn-ghost" onclick="document.getElementById('m_logo_file').click()"><i class="fas fa-upload"></i> Subir logo</button>
            <p style="font-size:12px;color:#94a3b8;margin-top:6px;">PNG, JPG, SVG o WEBP · máx 5MB</p>
          </div>
        </div>
      </div>
      <div class="sa-fg">
        <label for="m_nombre">Nombre comercial</label>
        <input type="text" id="m_nombre" maxlength="200" placeholder="Viajes Acme S.A.">
      </div>
      <div class="sa-row">
        <div class="sa-fg">
          <label for="m_divisa">Divisa (ISO 4217)</label>
          <input type="text" id="m_divisa" maxlength="3" placeholder="USD" style="text-transform:uppercase;">
        </div>
        <div class="sa-fg">
          <label for="m_telefono">Teléfono de contacto</label>
          <input type="text" id="m_telefono" maxlength="50" placeholder="+57 300 000 0000">
        </div>
      </div>
      <div class="sa-fg">
        <label for="m_email">Email de contacto</label>
        <input type="email" id="m_email" maxlength="100" placeholder="contacto@misubagencia.com">
      </div>
      <div class="sa-row">
        <div class="sa-fg">
          <label for="m_primary">Color primario</label>
          <input type="color" id="m_primary" value="<?= $primary ?>">
        </div>
        <div class="sa-fg">
          <label for="m_secondary">Color secundario</label>
          <input type="color" id="m_secondary" value="<?= $secondary ?>">
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:8px;">
        <button class="sa-btn sa-btn-primary" onclick="guardarMarca()"><i class="fas fa-save"></i> Guardar marca</button>
      </div>
      <p style="font-size:12px;color:#94a3b8;margin-top:12px;">Estos datos y colores se aplican al enlace público que compartes con tus clientes.</p>
    </div>
  </div>

</div>

<!-- MODAL: editar precios de un tour -->
<div class="sa-overlay" id="precioModal">
  <div class="sa-box">
    <div class="sa-box-hd">
      <h3 id="pmTitle">Editar</h3>
      <button class="x" onclick="cerrarPrecioModal()">&times;</button>
    </div>
    <div class="sa-box-body">
      <input type="hidden" id="pm_solicitud_id">
      <div class="sa-fg">
        <label>Nombre del cliente</label>
        <input type="text" id="pm_nombre_cliente" maxlength="150" placeholder="Para quién es este itinerario">
      </div>
      <div class="sa-row">
        <div class="sa-fg"><label>Precio adulto</label><input type="number" step="0.01" id="pm_precio_adulto" oninput="recalcTotalSub()"></div>
        <div class="sa-fg"><label>Precio niño</label><input type="number" step="0.01" id="pm_precio_nino" oninput="recalcTotalSub()"></div>
      </div>
      <div class="sa-row">
        <div class="sa-fg"><label>Cantidad adultos</label><input type="number" id="pm_cantidad_adultos" oninput="recalcTotalSub()"></div>
        <div class="sa-fg"><label>Cantidad niños</label><input type="number" id="pm_cantidad_ninos" oninput="recalcTotalSub()"></div>
      </div>
      <div class="sa-row">
        <div class="sa-fg"><label>Precio total</label><input type="number" step="0.01" id="pm_precio_total"><small style="color:#94a3b8;font-size:11px;">Se calcula solo; puedes sobrescribirlo.</small></div>
        <div class="sa-fg"><label>Noches incluidas</label><input type="number" id="pm_noches_incluidas"></div>
      </div>
      <div class="sa-fg"><label>Incluye</label><textarea class="sa-ta" id="pm_precio_incluye"></textarea></div>
      <div class="sa-fg"><label>No incluye</label><textarea class="sa-ta" id="pm_precio_no_incluye"></textarea></div>
      <div class="sa-fg"><label>Condiciones generales</label><textarea class="sa-ta" id="pm_condiciones_generales"></textarea></div>
      <div class="sa-fg"><label>Información de pasaporte</label><textarea class="sa-ta" id="pm_info_pasaporte"></textarea></div>
      <div class="sa-fg"><label>Información de seguros</label><textarea class="sa-ta" id="pm_info_seguros"></textarea></div>
      <div class="sa-fg"><label>Visados y requisitos de entrada</label><textarea class="sa-ta" id="pm_visados_entrada"></textarea></div>
      <div class="sa-fg"><label>Requisitos sanitarios</label><textarea class="sa-ta" id="pm_requisitos_sanitarios"></textarea></div>
      <div class="sa-fg"><label>Llegada y punto de encuentro</label><textarea class="sa-ta" id="pm_llegada_punto_encuentro"></textarea></div>
      <div class="sa-fg"><label>Asistencia y emergencias</label><textarea class="sa-ta" id="pm_asistencia_emergencia"></textarea></div>
      <div class="sa-fg"><label>Información de hoteles y servicios</label><textarea class="sa-ta" id="pm_info_hoteles_servicios"></textarea></div>
      <div class="sa-fg"><label>Información práctica</label><textarea class="sa-ta" id="pm_informacion_practica"></textarea></div>
      <div class="sa-fg" style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" id="pm_movilidad_reducida" style="width:auto;height:auto;">
        <label for="pm_movilidad_reducida" style="margin:0;">Apto para movilidad reducida</label>
      </div>
    </div>
    <div class="sa-box-ft">
      <button class="sa-btn sa-btn-ghost" onclick="cerrarPrecioModal()">Cancelar</button>
      <button class="sa-btn sa-btn-primary" onclick="guardarPrecios()"><i class="fas fa-save"></i> Guardar precios</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const APP_URL = '<?= APP_URL ?>';
let sidebarOpen = false;
function toggleSidebar(){ sidebarOpen=!sidebarOpen; document.querySelector('.enhanced-sidebar')?.classList.toggle('open',sidebarOpen); document.getElementById('overlay').style.display=sidebarOpen?'block':'none'; }
function closeSidebar(){ sidebarOpen=false; document.querySelector('.enhanced-sidebar')?.classList.remove('open'); document.getElementById('overlay').style.display='none'; }

function showToast(msg, type='ok'){
  const t=document.getElementById('toast');
  t.textContent=msg; t.className=type==='err'?'err show':'ok show';
  clearTimeout(window._tt); window._tt=setTimeout(()=>t.classList.remove('show'),3000);
}
function esc(s){ return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

// API helper (modules/subagencias/api.php)
async function subApi(action, params={}, method='GET'){
  try{
    if(method==='GET'){
      const qs=new URLSearchParams({action,...params}).toString();
      const r=await fetch(`${APP_URL}/subagencias/api?${qs}`);
      return await r.json();
    }
    const fd=new FormData(); fd.append('action',action);
    Object.entries(params).forEach(([k,v])=>fd.append(k,v));
    const r=await fetch(`${APP_URL}/subagencias/api`,{method:'POST',body:fd});
    return await r.json();
  }catch(e){ return {success:false,message:'Error de red'}; }
}

function saTab(name){
  document.querySelectorAll('.sa-tab').forEach(b=>b.classList.toggle('active',b.dataset.tab===name));
  document.querySelectorAll('.sa-panel').forEach(p=>p.classList.toggle('active',p.id==='panel-'+name));
}

// ───── MIS TOURS ─────
let misTours = [];
async function cargarTours(){
  const r=await subApi('get_my_tours');
  misTours=(r&&r.success)?(r.data||[]):[];
  document.getElementById('statTours').textContent=misTours.length;
  const grid=document.getElementById('toursGrid');
  const empty=document.getElementById('toursEmpty');
  if(!misTours.length){ grid.innerHTML=''; empty.style.display='block'; return; }
  empty.style.display='none';
  grid.innerHTML=misTours.map(t=>{
    const titulo=esc(t.titulo||t.destino||'Tour');
    const precio=t.precio_total!=null?Number(t.precio_total).toLocaleString():(t.precio_adulto!=null?Number(t.precio_adulto).toLocaleString():'—');
    const fechas=[t.fecha_inicio,t.fecha_fin].filter(Boolean).join(' → ');
    const token = esc(t.public_token||'');
    return `<div class="sa-card" onclick="abrirPrecioModal(${t.solicitud_id})" title="Clic para editar">
      <div class="sa-card-img"><div class="ph"><i class="fas fa-map-marked-alt"></i></div></div>
      <div class="sa-card-body">
        <div class="sa-card-title">${titulo}</div>
        <div class="sa-card-meta"><i class="fas fa-map-marker-alt"></i> ${esc(t.destino||'')}</div>
        ${fechas?`<div class="sa-card-meta"><i class="far fa-calendar"></i> ${esc(fechas)}</div>`:''}
        <div class="sa-card-price">${precio!=='—'?precio:'Sin precio'}</div>
        <div class="sa-card-actions" onclick="event.stopPropagation();">
          <button class="sa-btn sa-btn-primary" onclick="abrirPrecioModal(${t.solicitud_id})"><i class="fas fa-pen"></i> Editar</button>
          <a class="sa-btn sa-btn-ghost" href="${APP_URL}/share?t=${token}" target="_blank"><i class="fas fa-eye"></i> Ver</a>
          <button class="sa-btn sa-btn-ghost" onclick="copiarLink('${token}')"><i class="fas fa-link"></i> Copiar enlace</button>
        </div>
      </div>
    </div>`;
  }).join('');
}

function copiarLink(token){
  if(!token){ showToast('Este tour aún no tiene enlace','err'); return; }
  const url=`${APP_URL}/share?t=${token}`;
  const fallback=()=>{
    const ta=document.createElement('textarea');
    ta.value=url; ta.style.position='fixed'; ta.style.opacity='0';
    document.body.appendChild(ta); ta.focus(); ta.select();
    let okc=false; try{ okc=document.execCommand('copy'); }catch(e){}
    document.body.removeChild(ta);
    showToast(okc?'Enlace copiado':('Copia manual: '+url), okc?'ok':'err');
  };
  // navigator.clipboard solo existe en contexto seguro (https/localhost)
  if(navigator.clipboard && window.isSecureContext){
    navigator.clipboard.writeText(url).then(()=>showToast('Enlace copiado'), fallback);
  } else {
    fallback();
  }
}

// ───── EDITAR PRECIOS ─────
// Auto-cálculo del precio total al cambiar precio/cantidad (igual que programa.php).
// El campo total queda editable: el último cambio gana (override manual permitido).
function recalcTotalSub(){
  const num = id => parseFloat(document.getElementById(id).value) || 0;
  const total = (num('pm_cantidad_adultos') * num('pm_precio_adulto'))
              + (num('pm_cantidad_ninos')   * num('pm_precio_nino'));
  document.getElementById('pm_precio_total').value = total ? total.toFixed(2) : '';
}

async function abrirPrecioModal(solicitudId){
  const r=await subApi('get_tour_detail',{solicitud_id:solicitudId});
  if(!r||!r.success){ showToast((r&&r.message)||'No se pudo cargar','err'); return; }
  const p=r.precios||{};
  document.getElementById('pm_solicitud_id').value=solicitudId;
  document.getElementById('pmTitle').textContent='Editar — '+((r.programa&&(r.programa.titulo||r.programa.destino))||'Tour');
  const set=(id,v)=>document.getElementById(id).value=(v??'');
  set('pm_nombre_cliente',p.nombre_cliente);
  set('pm_precio_adulto',p.precio_adulto); set('pm_precio_nino',p.precio_nino);
  set('pm_cantidad_adultos',p.cantidad_adultos); set('pm_cantidad_ninos',p.cantidad_ninos);
  set('pm_precio_total',p.precio_total); set('pm_noches_incluidas',p.noches_incluidas);
  set('pm_precio_incluye',p.precio_incluye); set('pm_precio_no_incluye',p.precio_no_incluye);
  set('pm_condiciones_generales',p.condiciones_generales);
  set('pm_info_pasaporte',p.info_pasaporte); set('pm_info_seguros',p.info_seguros);
  set('pm_visados_entrada',p.visados_entrada); set('pm_requisitos_sanitarios',p.requisitos_sanitarios);
  set('pm_llegada_punto_encuentro',p.llegada_punto_encuentro); set('pm_asistencia_emergencia',p.asistencia_emergencia);
  set('pm_info_hoteles_servicios',p.info_hoteles_servicios); set('pm_informacion_practica',p.informacion_practica);
  document.getElementById('pm_movilidad_reducida').checked=Number(p.movilidad_reducida)===1;
  document.getElementById('precioModal').classList.add('open');
}
function cerrarPrecioModal(){ document.getElementById('precioModal').classList.remove('open'); }

async function guardarPrecios(){
  const g=id=>document.getElementById(id).value;
  const params={
    solicitud_id:document.getElementById('pm_solicitud_id').value,
    nombre_cliente:g('pm_nombre_cliente'),
    precio_adulto:g('pm_precio_adulto'), precio_nino:g('pm_precio_nino'),
    cantidad_adultos:g('pm_cantidad_adultos'), cantidad_ninos:g('pm_cantidad_ninos'),
    precio_total:g('pm_precio_total'), noches_incluidas:g('pm_noches_incluidas'),
    precio_incluye:g('pm_precio_incluye'), precio_no_incluye:g('pm_precio_no_incluye'),
    condiciones_generales:g('pm_condiciones_generales'),
    info_pasaporte:g('pm_info_pasaporte'), info_seguros:g('pm_info_seguros'),
    visados_entrada:g('pm_visados_entrada'), requisitos_sanitarios:g('pm_requisitos_sanitarios'),
    llegada_punto_encuentro:g('pm_llegada_punto_encuentro'), asistencia_emergencia:g('pm_asistencia_emergencia'),
    info_hoteles_servicios:g('pm_info_hoteles_servicios'), informacion_practica:g('pm_informacion_practica'),
    movilidad_reducida:document.getElementById('pm_movilidad_reducida').checked?1:0
  };
  const r=await subApi('update_precio',params,'POST');
  if(r&&r.success){ showToast('Precios actualizados'); cerrarPrecioModal(); cargarTours(); }
  else showToast((r&&r.message)||'Error al guardar','err');
}
document.getElementById('precioModal').addEventListener('click',e=>{ if(e.target===e.currentTarget) cerrarPrecioModal(); });

// ───── MI MARCA ─────
async function cargarMarca(){
  const r=await subApi('get_config');
  if(!r||!r.success) return;
  const c=r.data||{};
  document.getElementById('m_nombre').value=c.nombre||'';
  document.getElementById('m_divisa').value=c.divisa||'';
  document.getElementById('m_telefono').value=c.telefono||'';
  document.getElementById('m_email').value=c.email_contacto||'';
  if(c.primary_color) document.getElementById('m_primary').value=c.primary_color;
  if(c.secondary_color) document.getElementById('m_secondary').value=c.secondary_color;
  setLogoPreview(c.logo_url);
}

function setLogoPreview(url){
  const el=document.getElementById('logoPreview');
  if(url){ el.style.backgroundImage=`url('${url}')`; el.innerHTML=''; }
  else { el.style.backgroundImage='none'; el.innerHTML='<i class="fas fa-image"></i>'; }
}

async function subirLogo(input){
  const file=input.files && input.files[0];
  if(!file) return;
  if(file.size > 5*1024*1024){ showToast('El logo supera 5MB','err'); input.value=''; return; }
  const fd=new FormData();
  fd.append('action','upload_logo');
  fd.append('logo',file);
  try{
    const r=await fetch(`${APP_URL}/subagencias/api`,{method:'POST',body:fd});
    const data=await r.json();
    if(data && data.success){ setLogoPreview(data.url); showToast('Logo actualizado'); }
    else showToast((data && data.message)||'Error al subir el logo','err');
  }catch(e){ showToast('Error de red al subir el logo','err'); }
  input.value='';
}
async function guardarMarca(){
  const params={
    nombre:document.getElementById('m_nombre').value,
    divisa:document.getElementById('m_divisa').value.toUpperCase(),
    telefono:document.getElementById('m_telefono').value,
    email_contacto:document.getElementById('m_email').value,
    primary_color:document.getElementById('m_primary').value,
    secondary_color:document.getElementById('m_secondary').value
  };
  const r=await subApi('save_config',params,'POST');
  if(r&&r.success){ showToast('Marca guardada'); }
  else showToast((r&&r.message)||'Error al guardar','err');
}

document.addEventListener('DOMContentLoaded',()=>{ cargarTours(); cargarMarca(); });
</script>
</body>
</html>
