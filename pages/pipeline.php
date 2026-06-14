<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';
App::init();
App::requireLogin();
$user = App::getUser();
if (!in_array($user['role'], ['admin', 'agent'])) {
  App::redirect('/dashboard');
  exit;
}
require_once dirname(__DIR__) . '/config/config_functions.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/ui_components.php';
ConfigManager::init();
$userColors = ConfigManager::getColorsForRole($user['role']);
$companyName = ConfigManager::getCompanyName();
$defaultLang = ConfigManager::getDefaultLanguage() ?? 'es';
$isAdmin = $user['role'] === 'admin';
function pl_rgb($hex)
{
  $h = ltrim(trim($hex), '#');
  if (strlen($h) === 3)
    $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
  if (!preg_match('/^[0-9a-fA-F]{6}$/', $h))
    return '99,102,241';
  return hexdec(substr($h, 0, 2)) . ',' . hexdec(substr($h, 2, 2)) . ',' . hexdec(substr($h, 4, 2));
}
$pRgb = pl_rgb($userColors['primary']);
?>
<!DOCTYPE html>
<html lang="<?= $defaultLang ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pipeline — <?= htmlspecialchars($companyName) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
  <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
  <?= UIComponents::getComponentStyles() ?>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --pr:
        <?= $userColors['primary'] ?>
      ;
      --sc:
        <?= $userColors['secondary'] ?>
      ;
      --pr-rgb:
        <?= $pRgb ?>
      ;
      --grad: linear-gradient(135deg, var(--pr) 0%, var(--sc) 100%);
      --primary-color:
        <?= $userColors['primary'] ?>
      ;
      --secondary-color:
        <?= $userColors['secondary'] ?>
      ;
      --primary-color-rgb:
        <?= $pRgb ?>
      ;
    }

    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: #f1f5f9;
      color: #1e293b;
    }

    /* HEADER */
    .header {
      background: var(--grad);
      color: #fff;
      padding: 0 24px;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1001;
      box-shadow: 0 2px 16px rgba(0, 0, 0, .18);
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .menu-toggle {
      background: rgba(255, 255, 255, .2);
      border: none;
      color: #fff;
      width: 38px;
      height: 38px;
      border-radius: 9px;
      cursor: pointer;
      font-size: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .2s;
    }

    .menu-toggle:hover {
      background: rgba(255, 255, 255, .32);
    }

    .header-title {
      font-size: 17px;
      font-weight: 700;
      letter-spacing: -.2px;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-user {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, .15);
      border-radius: 22px;
      padding: 6px 14px 6px 6px;
      cursor: default;
    }

    .header-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .28);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid rgba(255, 255, 255, .35);
    }

    .header-name {
      color: #fff;
      font-size: 13px;
      font-weight: 500;
    }

    /* OVERLAY */
    #overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .35);
      z-index: 999;
    }

    /* MAIN CONTENT */
    #mainContent {
      margin-top: 70px;
      height: calc(100vh - 70px);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* TOOLBAR */
    .pl-toolbar {
      height: 58px;
      background: #fff;
      border-bottom: 1px solid #e8edf2;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 20px;
      flex-shrink: 0;
      overflow-x: auto;
    }

    .pl-toolbar::-webkit-scrollbar {
      height: 3px;
    }

    .pl-toolbar::-webkit-scrollbar-thumb {
      background: #e2e8f0;
      border-radius: 3px;
    }

    .view-toggle {
      display: flex;
      background: #f1f5f9;
      border-radius: 8px;
      padding: 3px;
      gap: 2px;
      flex-shrink: 0;
    }

    .vbtn {
      padding: 5px 13px;
      border: none;
      background: transparent;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 500;
      color: #64748b;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: all .18s;
    }

    .vbtn.active {
      background: #fff;
      color: var(--pr);
      box-shadow: 0 1px 4px rgba(0, 0, 0, .1);
    }

    .vbtn svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
    }

    .pl-search {
      flex: 1;
      min-width: 140px;
      max-width: 260px;
      display: flex;
      align-items: center;
      gap: 7px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 0 11px;
      height: 36px;
    }

    .pl-search svg {
      width: 14px;
      height: 14px;
      stroke: #94a3b8;
      fill: none;
      stroke-width: 2;
      flex-shrink: 0;
    }

    .pl-search input {
      border: none;
      background: transparent;
      outline: none;
      font-size: 13px;
      color: #1e293b;
      width: 100%;
    }

    .pl-search input::placeholder {
      color: #94a3b8;
    }

    .pl-sel {
      height: 36px;
      padding: 0 10px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #f8fafc;
      font-size: 13px;
      color: #475569;
      outline: none;
      cursor: pointer;
      flex-shrink: 0;
    }

    .pl-sel:focus {
      border-color: var(--pr);
    }

    .toolbar-divider {
      width: 1px;
      height: 24px;
      background: #e8edf2;
      flex-shrink: 0;
    }

    .btn-config {
      height: 36px;
      width: 36px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #f8fafc;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      transition: all .18s;
      flex-shrink: 0;
    }

    .btn-config:hover {
      background: #fff;
      border-color: var(--pr);
      color: var(--pr);
    }

    .btn-config svg {
      width: 16px;
      height: 16px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
    }

    .btn-new {
      height: 36px;
      padding: 0 16px;
      border: none;
      border-radius: 8px;
      background: var(--grad);
      color: #fff;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: opacity .18s;
      flex-shrink: 0;
      white-space: nowrap;
    }

    .btn-new:hover {
      opacity: .88;
    }

    .btn-new svg {
      width: 14px;
      height: 14px;
      stroke: #fff;
      fill: none;
      stroke-width: 2.5;
    }

    /* BOARD */
    .pl-board-wrap {
      flex: 1;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .board-scroll {
      flex: 1;
      overflow-x: auto;
      overflow-y: hidden;
      padding: 18px;
    }

    .board-scroll::-webkit-scrollbar {
      height: 6px;
    }

    .board-scroll::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 3px;
    }

    .board-scroll::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 3px;
    }

    .kanban-board {
      display: flex;
      gap: 14px;
      height: 100%;
      align-items: flex-start;
      min-width: max-content;
    }

    /* COLUMN */
    .k-col {
      width: 284px;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      background: #f8fafc;
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      max-height: calc(100vh - 70px - 58px - 36px);
    }

    .k-col-hd {
      padding: 12px 14px 10px;
      border-radius: 14px 14px 0 0;
      border-top: 4px solid var(--cc, #6366f1);
      background: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
      border-bottom: 1px solid #f1f5f9;
    }

    .k-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--cc, #6366f1);
      flex-shrink: 0;
    }

    .k-name {
      font-size: 13px;
      font-weight: 700;
      color: #0f172a;
      flex: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .k-cnt {
      background: var(--cc, #6366f1);
      color: #fff;
      border-radius: 12px;
      padding: 1px 8px;
      font-size: 11px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .k-body {
      flex: 1;
      overflow-y: auto;
      padding: 8px;
      display: flex;
      flex-direction: column;
      gap: 7px;
      min-height: 50px;
      border-radius: 0 0 14px 14px;
      transition: background .15s;
    }

    .k-body::-webkit-scrollbar {
      width: 3px;
    }

    .k-body::-webkit-scrollbar-thumb {
      background: #e2e8f0;
      border-radius: 3px;
    }

    .k-body.drag-over {
      background: rgba(99, 102, 241, .07);
      outline: 2px dashed rgba(99, 102, 241, .3);
      outline-offset: -4px;
    }

    .k-empty {
      text-align: center;
      padding: 26px 12px;
      color: #cbd5e1;
      font-size: 12px;
    }

    .k-empty svg {
      width: 28px;
      height: 28px;
      stroke: #dde3eb;
      fill: none;
      stroke-width: 1.5;
      display: block;
      margin: 0 auto 6px;
    }

    /* CARD */
    .lead-card {
      background: #fff;
      border-radius: 10px;
      padding: 13px;
      cursor: pointer;
      border: 1px solid #f1f5f9;
      box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
      transition: box-shadow .18s, transform .15s, opacity .15s;
      user-select: none;
      position: relative;
    }

    .lead-card:hover {
      box-shadow: 0 4px 16px rgba(0, 0, 0, .10);
      transform: translateY(-2px);
      border-color: #e2e8f0;
    }

    .lead-card.dragging {
      opacity: .3;
      cursor: grabbing;
      transform: rotate(2deg);
    }

    .c-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 6px;
      margin-bottom: 6px;
    }

    .c-name {
      font-size: 13.5px;
      font-weight: 700;
      color: #0f172a;
      flex: 1;
      min-width: 0;
      line-height: 1.3;
    }

    .c-email-ico {
      width: 18px;
      height: 18px;
      border-radius: 4px;
      background: #eff6ff;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .c-email-ico svg {
      width: 10px;
      height: 10px;
      stroke: #3b82f6;
      fill: none;
      stroke-width: 2;
    }

    .c-sub {
      font-size: 12px;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 4px;
      margin-bottom: 3px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .c-sub svg {
      width: 11px;
      height: 11px;
      stroke: #94a3b8;
      fill: none;
      stroke-width: 2;
      flex-shrink: 0;
    }

    .c-foot {
      display: flex;
      align-items: center;
      gap: 5px;
      margin-top: 10px;
      flex-wrap: wrap;
    }

    .c-budget {
      font-size: 11.5px;
      font-weight: 600;
      color: #16a34a;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      padding: 2px 7px;
      border-radius: 10px;
    }

    .c-tag {
      font-size: 11px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 10px;
    }

    .c-asesor {
      margin-left: auto;
      width: 23px;
      height: 23px;
      border-radius: 50%;
      background: var(--grad);
      color: #fff;
      font-size: 9px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* LIST VIEW */
    .list-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 18px;
    }

    .list-tbl {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 5px;
    }

    .list-tbl thead th {
      padding: 9px 14px;
      text-align: left;
      font-size: 11px;
      font-weight: 600;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .5px;
      cursor: pointer;
      white-space: nowrap;
    }

    .list-tbl thead th:hover {
      color: var(--pr);
    }

    .list-tbl tbody tr {
      background: #fff;
      box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
      border-radius: 10px;
      cursor: pointer;
      transition: box-shadow .15s, transform .1s;
    }

    .list-tbl tbody tr:hover {
      box-shadow: 0 3px 12px rgba(0, 0, 0, .10);
      transform: translateY(-1px);
    }

    .list-tbl tbody td {
      padding: 11px 14px;
      font-size: 13px;
      color: #334155;
    }

    .list-tbl tbody td:first-child {
      border-radius: 10px 0 0 10px;
      font-weight: 600;
      color: #0f172a;
    }

    .list-tbl tbody td:last-child {
      border-radius: 0 10px 10px 0;
    }

    .l-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11.5px;
      font-weight: 600;
    }

    .l-bdot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: currentColor;
    }

    /* ─── LEAD DETAIL MODAL (2 columns) ─── */
    .lm-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .55);
      z-index: 1100;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .lm-overlay.open {
      display: flex;
    }

    .lm-box {
      background: #fff;
      border-radius: 20px;
      width: min(960px, 94vw);
      height: min(700px, 88vh);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 28px 64px rgba(0, 0, 0, .22);
      animation: mdIn .24s cubic-bezier(.4, 0, .2, 1);
    }

    @keyframes mdIn {
      from {
        opacity: 0;
        transform: scale(.94) translateY(18px);
      }

      to {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .lm-header {
      padding: 16px 22px;
      background: var(--grad);
      border-bottom: none;
      display: flex;
      align-items: center;
      gap: 14px;
      flex-shrink: 0;
    }

    .lm-avatar {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .22);
      color: #fff;
      font-size: 16px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border: 2px solid rgba(255, 255, 255, .35);
    }

    .lm-hinfo {
      flex: 1;
      min-width: 0;
    }

    .lm-hname {
      font-size: 17px;
      font-weight: 700;
      color: #fff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .lm-hemail {
      font-size: 12px;
      color: rgba(255, 255, 255, .75);
      margin-top: 2px;
    }

    .lm-hbadge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      flex-shrink: 0;
      background: rgba(255, 255, 255, .2);
      color: #fff;
    }

    .lm-close {
      width: 32px;
      height: 32px;
      border: none;
      background: rgba(255, 255, 255, .18);
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      transition: all .18s;
      flex-shrink: 0;
    }

    .lm-close:hover {
      background: rgba(255, 255, 255, .3);
    }

    .lm-close svg {
      width: 16px;
      height: 16px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2.2;
    }

    .lm-body {
      display: flex;
      flex: 1;
      overflow: hidden;
    }

    /* Left info panel */
    /* Left panel — info-group style (matching chat.php sidebar) */
    .lm-left {
      width: 320px;
      flex-shrink: 0;
      border-right: 1px solid #e2e8f0;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      background: #fff;
    }

    .lm-left::-webkit-scrollbar {
      width: 3px;
    }

    .lm-left::-webkit-scrollbar-thumb {
      background: #e2e8f0;
      border-radius: 3px;
    }

    .lm-ig {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 10px 16px;
      transition: background .2s;
    }

    .lm-ig:hover {
      background: #f8fafc;
    }

    .lm-ig-icon {
      width: 34px;
      height: 34px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: #94a3b8;
    }

    .lm-ig-icon svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .lm-ig-content {
      flex: 1;
      min-width: 0;
    }

    .lm-ig-label {
      font-size: 11px;
      text-transform: uppercase;
      color: #94a3b8;
      font-weight: 600;
      letter-spacing: .05em;
      display: block;
      margin-bottom: 3px;
    }

    .lm-ig-val {
      font-size: 14px;
      color: #1e293b;
      font-weight: 500;
      display: block;
      word-break: break-word;
      line-height: 1.4;
    }

    .lm-ig-val.dim {
      color: #cbd5e1;
      font-weight: 400;
    }

    .lm-ig-divider {
      height: 1px;
      background: #f1f5f9;
      margin: 4px 16px;
    }

    .lm-ig-select {
      font-size: 14px;
      color: #1e293b;
      font-weight: 500;
      background: transparent;
      border: none;
      border-bottom: 1.5px solid transparent;
      padding: 4px;
      margin-left: -4px;
      width: calc(100% + 8px);
      outline: none;
      font-family: inherit;
      cursor: pointer;
      -webkit-appearance: none;
      appearance: none;
      transition: all .2s;
    }

    .lm-ig-select:hover {
      background: #f1f5f9;
      border-radius: 4px;
    }

    .lm-ig-select:focus {
      border-bottom-color: var(--pr);
    }

    .lm-ig-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 3px;
    }

    .lm-vinc-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 9px 14px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      background: #fff;
      font-size: 13px;
      color: #475569;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: all .18s;
    }

    .lm-vinc-btn:hover {
      border-color: var(--pr);
      color: var(--pr);
      background: rgba(var(--pr-rgb), .04);
    }

    .lm-vinc-btn svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      flex-shrink: 0;
    }

    .lm-vinc-badge {
      font-size: 11.5px;
      color: #16a34a;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      padding: 3px 8px;
      border-radius: 8px;
      display: none;
      margin-top: 6px;
    }

    .lm-vinc-badge.show {
      display: block;
    }

    /* Right conversation panel — Chat completo (estilo chat.php) */
    .lm-right {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: #fafaf9;
    }

    .lm-chat-hd {
      padding: 20px 30px;
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      flex-shrink: 0;
    }

    .lm-chat-hd-title {
      font-size: 18px;
      font-weight: 600;
      color: #0f172a;
      margin: 0 0 3px;
    }

    .lm-chat-hd-sub {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: #64748b;
    }

    .lm-gmail-badge {
      font-size: 11px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 20px;
      flex-shrink: 0;
    }

    .lm-gmail-badge.ok {
      background: #dcfce7;
      color: #15803d;
    }

    .lm-gmail-badge.nok {
      background: #fee2e2;
      color: #dc2626;
    }

    .lm-chat-msgs {
      flex: 1;
      overflow-y: auto;
      padding: 30px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .lm-chat-msgs::-webkit-scrollbar {
      width: 4px;
    }

    .lm-chat-msgs::-webkit-scrollbar-thumb {
      background: #e2e8f0;
      border-radius: 4px;
    }

    .lm-msg {
      max-width: 70%;
      padding: 16px;
      border-radius: 16px;
      font-size: 15px;
      line-height: 1.5;
      word-break: break-word;
      animation: fadeIn .3s ease-out;
    }

    .lm-msg.in {
      align-self: flex-start;
      background: #fff;
      border: 1px solid #e2e8f0;
      color: #334155;
      border-bottom-left-radius: 4px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, .02);
    }

    .lm-msg.out {
      align-self: flex-end;
      background: var(--grad);
      color: #fff;
      border-bottom-right-radius: 4px;
      box-shadow: 0 4px 12px rgba(var(--pr-rgb), .2);
    }

    .lm-msg-time {
      display: block;
      font-size: 11px;
      margin-top: 8px;
      opacity: .7;
    }

    .chat-attachments {
      margin-top: 10px;
      padding-top: 8px;
      border-top: 1px solid rgba(148, 163, 184, .3);
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .chat-attachment {
      font-size: 13px;
    }

    .chat-attachment a {
      color: inherit;
      text-decoration: underline;
      word-break: break-all;
    }

    .lm-chat-empty {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px;
      text-align: center;
      color: #cbd5e1;
    }

    .lm-chat-empty svg {
      width: 44px;
      height: 44px;
      stroke: #dde3eb;
      fill: none;
      stroke-width: 1.5;
      margin-bottom: 12px;
    }

    .lm-chat-empty p {
      font-size: 14px;
    }

    .lm-no-gmail {
      display: none;
      padding: 9px 16px;
      background: #fffbeb;
      border-top: 1px solid #fde68a;
      font-size: 12px;
      color: #92400e;
      text-align: center;
      flex-shrink: 0;
    }

    .lm-no-gmail a {
      color: #b45309;
      font-weight: 700;
      text-decoration: underline;
    }

    .lm-no-gmail.show {
      display: block;
    }

    .lm-composer {
      background: #fff;
      border-top: 1px solid #e2e8f0;
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
    }

    .lm-fmt-bar {
      display: flex;
      align-items: center;
      gap: 2px;
      padding: 8px 16px;
      border-bottom: 1px solid #f1f5f9;
      background: #f8fafc;
    }

    .lm-fmt-btn {
      width: 30px;
      height: 28px;
      border: none;
      background: transparent;
      border-radius: 5px;
      cursor: pointer;
      color: #64748b;
      font-size: 13px;
      font-family: 'Georgia', serif;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .15s, color .15s;
      flex-shrink: 0;
    }

    .lm-fmt-btn:hover {
      background: #e2e8f0;
      color: #0f172a;
    }

    .lm-fmt-btn.active {
      background: rgba(var(--pr-rgb), .12);
      color: var(--pr);
    }

    .lm-fmt-divider {
      width: 1px;
      height: 18px;
      background: #e2e8f0;
      margin: 0 4px;
      flex-shrink: 0;
    }

    .lm-editor-wrap {
      padding: 12px 16px;
    }

    .lm-editor {
      width: 100%;
      min-height: 70px;
      max-height: 150px;
      border: none;
      outline: none;
      font-family: inherit;
      font-size: 14px;
      color: #1e293b;
      background: transparent;
      line-height: 1.6;
      overflow-y: auto;
      word-break: break-word;
      white-space: pre-wrap;
    }

    .lm-editor:empty::before {
      content: attr(data-placeholder);
      color: #cbd5e1;
      pointer-events: none;
    }

    .lm-attachments {
      display: none;
      flex-wrap: wrap;
      gap: 6px;
      padding: 0 16px 8px;
    }

    .lm-attachments.has-files {
      display: flex;
    }

    .lm-att-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 4px 10px;
      font-size: 12px;
      color: #475569;
      font-weight: 500;
      max-width: 200px;
    }

    .lm-att-chip span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .lm-att-chip svg {
      width: 13px;
      height: 13px;
      flex-shrink: 0;
      stroke: #64748b;
      fill: none;
    }

    .lm-att-rm {
      border: none;
      background: transparent;
      cursor: pointer;
      color: #94a3b8;
      padding: 0;
      font-size: 15px;
      line-height: 1;
      transition: color .15s;
    }

    .lm-att-rm:hover {
      color: #ef4444;
    }

    .lm-action-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 8px 12px 10px;
    }

    .lm-act-left {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .lm-act-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .lm-act-btn {
      width: 34px;
      height: 34px;
      border: none;
      background: transparent;
      border-radius: 7px;
      cursor: pointer;
      color: #94a3b8;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .15s, color .15s;
      flex-shrink: 0;
      position: relative;
    }

    .lm-act-btn:hover {
      background: #f1f5f9;
      color: #475569;
    }

    .lm-act-btn svg {
      width: 18px;
      height: 18px;
      stroke-width: 1.8;
      stroke: currentColor;
      fill: none;
    }

    .lm-act-div {
      width: 1px;
      height: 20px;
      background: #e2e8f0;
      margin: 0 4px;
    }

    .lm-send-btn {
      background: var(--grad);
      color: #fff;
      border: none;
      padding: 0 20px;
      height: 36px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 7px;
      transition: filter .2s, transform .1s, box-shadow .2s;
      font-family: inherit;
      white-space: nowrap;
    }

    .lm-send-btn:hover:not(:disabled) {
      filter: brightness(.95);
      box-shadow: 0 4px 12px rgba(var(--pr-rgb), .3);
    }

    .lm-send-btn:active:not(:disabled) {
      transform: scale(.97);
    }

    .lm-send-btn:disabled {
      opacity: .55;
      cursor: not-allowed;
    }

    .lm-send-btn svg {
      width: 15px;
      height: 15px;
      stroke: #fff;
      fill: none;
      stroke-width: 2.5;
    }

    .lm-tpl-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .55);
      backdrop-filter: blur(3px);
      z-index: 1300;
      display: none;
      align-items: flex-start;
      justify-content: center;
      padding-top: 80px;
    }

    .lm-tpl-overlay.active {
      display: flex;
    }

    .lm-tpl-modal {
      background: #fff;
      border-radius: 14px;
      width: min(860px, 94vw);
      max-height: 75vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 24px 60px rgba(15, 23, 42, .25);
      overflow: hidden;
      animation: mdIn .22s cubic-bezier(.4, 0, .2, 1);
    }

    .lm-tpl-hd {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 20px 24px;
      border-bottom: 1px solid #f1f5f9;
      flex-shrink: 0;
    }

    .lm-tpl-hd h2 {
      font-size: 22px;
      font-weight: 800;
      color: #1e293b;
      margin: 0;
      white-space: nowrap;
      font-family: inherit;
    }

    .lm-tpl-search {
      flex: 1;
      background: #f4f5f7;
      border: 1px solid transparent;
      border-radius: 8px;
      padding: 11px 14px;
      font-size: 14px;
      color: #1e293b;
      outline: none;
      font-family: inherit;
      transition: border-color .15s, background .15s;
    }

    .lm-tpl-search:focus {
      background: #fff;
      border-color: #cbd5e1;
    }

    .lm-tpl-create-btn {
      background: var(--grad);
      color: #fff;
      border: none;
      padding: 0 18px;
      height: 40px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      white-space: nowrap;
      font-family: inherit;
    }

    .lm-tpl-close {
      width: 34px;
      height: 34px;
      border: none;
      background: #f8fafc;
      border-radius: 8px;
      cursor: pointer;
      font-size: 20px;
      color: #64748b;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .15s;
      flex-shrink: 0;
      line-height: 1;
    }

    .lm-tpl-close:hover {
      background: #fee2e2;
      color: #ef4444;
    }

    .lm-tpl-list {
      flex: 1;
      overflow-y: auto;
      padding: 8px 12px;
    }

    .lm-tpl-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 14px;
      color: #334155;
      font-weight: 500;
      transition: background .15s;
      border-bottom: 1px solid #f8fafc;
    }

    .lm-tpl-row:hover {
      background: #f8fafc;
    }

    .lm-tpl-row-name {
      flex: 1;
      cursor: pointer;
      padding: 2px 0;
    }

    .lm-tpl-row-name:hover {
      color: var(--pr);
    }

    .lm-tpl-row-actions {
      display: flex;
      gap: 4px;
      flex-shrink: 0;
    }

    .lm-tpl-icon-btn {
      width: 28px;
      height: 28px;
      border: none;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #94a3b8;
      transition: background .15s, color .15s;
    }

    .lm-tpl-icon-btn:hover.edit {
      background: #eff6ff;
      color: #3b82f6;
    }

    .lm-tpl-icon-btn:hover.del {
      background: #fee2e2;
      color: #ef4444;
    }

    .lm-tpl-icon-btn svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
    }

    .lm-tpl-empty {
      padding: 36px;
      text-align: center;
      color: #94a3b8;
      font-size: 14px;
    }

    .lm-tpl-form {
      display: none;
      flex-direction: column;
      gap: 10px;
      padding: 16px 24px;
      border-top: 1px solid #f1f5f9;
      background: #fafbfc;
      flex-shrink: 0;
    }

    .lm-tpl-form-title {
      font-size: 13px;
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    .lm-tpl-form input,
    .lm-tpl-form textarea {
      padding: 11px 14px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 13px;
      color: #1e293b;
      outline: none;
      font-family: inherit;
      background: #fff;
      transition: border-color .15s;
    }

    .lm-tpl-form input:focus,
    .lm-tpl-form textarea:focus {
      border-color: var(--pr);
    }

    .lm-tpl-form textarea {
      min-height: 90px;
      resize: vertical;
    }

    .lm-tpl-form-actions {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
    }

    .lm-tpl-save-btn {
      background: var(--grad);
      color: #fff;
      border: none;
      padding: 10px 22px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      font-family: inherit;
    }

    .lm-tpl-cancel-btn {
      background: #f1f5f9;
      color: #475569;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      font-family: inherit;
    }

    /* ─── NEW LEAD MODAL ─── */
    .nl-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .55);
      z-index: 1100;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .nl-overlay.open {
      display: flex;
    }

    .nl-box {
      background: #fff;
      border-radius: 18px;
      width: min(560px, 96vw);
      max-height: 88vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(0, 0, 0, .2);
      animation: mdIn .24s cubic-bezier(.4, 0, .2, 1);
    }

    .nl-hd {
      padding: 18px 22px 14px;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .nl-title {
      font-size: 17px;
      font-weight: 700;
      color: #0f172a;
    }

    .nl-close {
      width: 32px;
      height: 32px;
      border: none;
      background: #f8fafc;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      transition: all .18s;
    }

    .nl-close:hover {
      background: #fee2e2;
      color: #ef4444;
    }

    .nl-close svg {
      width: 15px;
      height: 15px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
    }

    .nl-body {
      padding: 18px 22px;
      overflow-y: auto;
      flex: 1;
    }

    .nl-body::-webkit-scrollbar {
      width: 4px;
    }

    .nl-body::-webkit-scrollbar-thumb {
      background: #e2e8f0;
      border-radius: 4px;
    }

    .f-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 13px;
    }

    .f-field {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .f-field.full {
      grid-column: 1/-1;
    }

    .f-lbl {
      font-size: 11px;
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    .f-inp,
    .f-sel,
    .f-ta {
      height: 38px;
      padding: 0 12px;
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      font-size: 13.5px;
      color: #1e293b;
      background: #f8fafc;
      outline: none;
      font-family: inherit;
      transition: border-color .18s, background .18s;
      width: 100%;
    }

    .f-inp:focus,
    .f-sel:focus,
    .f-ta:focus {
      border-color: var(--pr);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(var(--pr-rgb), .09);
    }

    .f-ta {
      height: 66px;
      padding: 10px 12px;
      resize: none;
    }

    /* intlTelInput override */
    .iti {
      width: 100%;
    }

    .iti input {
      width: 100% !important;
    }

    /* Tag chips in modal */
    .tag-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      min-height: 36px;
      align-items: center;
    }

    .tag-chip {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all .15s;
      opacity: .7;
    }

    .tag-chip.selected {
      opacity: 1;
      border-color: rgba(0, 0, 0, .2);
      box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
    }

    .tag-chip:hover {
      opacity: 1;
      transform: scale(1.04);
    }

    .nl-ft {
      padding: 12px 22px 16px;
      border-top: 1px solid #f1f5f9;
      display: flex;
      gap: 9px;
      justify-content: flex-end;
      flex-shrink: 0;
    }

    .btn-cancel {
      height: 38px;
      padding: 0 18px;
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      background: #fff;
      font-size: 13px;
      font-weight: 500;
      color: #475569;
      cursor: pointer;
      transition: background .18s;
    }

    .btn-cancel:hover {
      background: #f8fafc;
    }

    .btn-submit {
      height: 38px;
      padding: 0 22px;
      border: none;
      border-radius: 9px;
      background: var(--grad);
      color: #fff;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity .18s;
    }

    .btn-submit:hover {
      opacity: .88;
    }

    .btn-submit:disabled {
      opacity: .6;
      cursor: not-allowed;
    }

    /* ─── VINCULAR MODAL — opciones ─── */
    .vinc-opt {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 13px 15px;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      background: #fff;
      cursor: pointer;
      text-align: left;
      transition: all .15s;
      width: 100%;
    }

    .vinc-opt:hover {
      border-color: rgba(var(--pr-rgb), .45);
      background: rgba(var(--pr-rgb), .03);
      box-shadow: 0 3px 12px rgba(0, 0, 0, .07);
    }

    .vinc-opt-ico {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(var(--pr-rgb), .1);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .vinc-opt-ico svg {
      width: 19px;
      height: 19px;
      stroke: var(--pr);
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .vinc-opt-title {
      font-size: 13.5px;
      font-weight: 600;
      color: #0f172a;
      line-height: 1.3;
    }

    .vinc-opt-desc {
      font-size: 11.5px;
      color: #64748b;
      margin-top: 2px;
    }

    .vinc-opt-arr {
      width: 15px;
      height: 15px;
      stroke: #cbd5e1;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      margin-left: auto;
      flex-shrink: 0;
    }

    .vinc-clone-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 10px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      cursor: pointer;
      transition: all .15s;
      margin-bottom: 6px;
    }

    .vinc-clone-row:hover {
      border-color: rgba(var(--pr-rgb), .4);
      background: rgba(var(--pr-rgb), .03);
    }

    .vinc-clone-name {
      font-size: 13px;
      font-weight: 600;
      color: #0f172a;
    }

    .vinc-clone-meta {
      font-size: 11.5px;
      color: #64748b;
      margin-top: 2px;
    }

    /* ─── VINCULAR PICKER (existente / plantilla) ─── */
    .vinc-psearch {
      position: relative;
      margin-bottom: 10px;
    }

    .vinc-psearch svg {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      width: 14px;
      height: 14px;
      stroke: #94a3b8;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .vinc-pinput {
      width: 100%;
      padding: 8px 12px 8px 32px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 13px;
      outline: none;
      transition: border-color .2s;
    }

    .vinc-pinput:focus {
      border-color: rgba(var(--pr-rgb), .6);
    }

    .vinc-pgrid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 7px;
      max-height: 220px;
      overflow-y: auto;
    }

    .vinc-pcard {
      padding: 10px 12px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      cursor: pointer;
      transition: all .15s;
      position: relative;
    }

    .vinc-pcard:hover {
      border-color: rgba(var(--pr-rgb), .5);
      background: rgba(var(--pr-rgb), .03);
    }

    .vinc-pcard.sel {
      border-color: var(--pr);
      background: rgba(var(--pr-rgb), .07);
    }

    .vinc-pcard.sel::after {
      content: '✓';
      position: absolute;
      top: 7px;
      right: 9px;
      color: var(--pr);
      font-weight: 700;
      font-size: 12px;
    }

    .vinc-ptitle {
      font-size: 12.5px;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      padding-right: 16px;
    }

    .vinc-pmeta {
      font-size: 11px;
      color: #64748b;
      line-height: 1.3;
    }

    .vinc-pempty {
      grid-column: 1/-1;
      text-align: center;
      padding: 20px;
      color: #94a3b8;
      font-size: 13px;
    }

    /* ─── CONFIG MODAL (estados + tags) ─── */
    .cfg-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .55);
      z-index: 1100;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .cfg-overlay.open {
      display: flex;
    }

    .cfg-box {
      background: #fff;
      border-radius: 18px;
      width: min(540px, 96vw);
      max-height: 88vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(0, 0, 0, .2);
      animation: mdIn .24s cubic-bezier(.4, 0, .2, 1);
    }

    .cfg-hd {
      padding: 18px 22px 14px;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .cfg-title {
      font-size: 17px;
      font-weight: 700;
      color: #0f172a;
    }

    .cfg-body {
      overflow-y: auto;
      flex: 1;
      padding: 18px 22px;
    }

    .cfg-body::-webkit-scrollbar {
      width: 4px;
    }

    .cfg-body::-webkit-scrollbar-thumb {
      background: #e2e8f0;
      border-radius: 4px;
    }

    .cfg-sec-title {
      font-size: 11px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .6px;
      margin-bottom: 12px;
      margin-top: 20px;
    }

    .cfg-sec-title:first-child {
      margin-top: 0;
    }

    /* Estado rows */
    .est-list {
      display: flex;
      flex-direction: column;
      gap: 7px;
    }

    .est-row {
      display: flex;
      align-items: center;
      gap: 9px;
      background: #f8fafc;
      border: 1px solid #e8edf2;
      border-radius: 10px;
      padding: 9px 12px;
      transition: border-color .15s;
    }

    .est-row:hover {
      border-color: var(--pr);
    }

    .est-drag {
      cursor: grab;
      color: #cbd5e1;
      font-size: 16px;
      flex-shrink: 0;
      line-height: 1;
    }

    .est-color {
      width: 32px;
      height: 32px;
      border-radius: 7px;
      border: 2px solid #e2e8f0;
      cursor: pointer;
      overflow: hidden;
      flex-shrink: 0;
      position: relative;
    }

    .est-color input[type=color] {
      position: absolute;
      inset: -4px;
      width: calc(100%+8px);
      height: calc(100%+8px);
      border: none;
      cursor: pointer;
      opacity: 0;
    }

    .est-color-preview {
      position: absolute;
      inset: 0;
      border-radius: 5px;
      pointer-events: none;
    }

    .est-name-inp {
      flex: 1;
      border: none;
      background: transparent;
      font-size: 13.5px;
      font-weight: 600;
      color: #1e293b;
      outline: none;
      min-width: 0;
    }

    .est-name-inp::placeholder {
      color: #cbd5e1;
    }

    .est-save-btn {
      height: 28px;
      padding: 0 10px;
      border: none;
      border-radius: 7px;
      background: var(--pr);
      color: #fff;
      font-size: 11.5px;
      font-weight: 600;
      cursor: pointer;
      opacity: 0;
      transition: opacity .15s;
      flex-shrink: 0;
    }

    .est-row:hover .est-save-btn,
    .est-row:focus-within .est-save-btn {
      opacity: 1;
    }

    .est-del-btn {
      width: 28px;
      height: 28px;
      border: none;
      border-radius: 7px;
      background: transparent;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #cbd5e1;
      transition: all .15s;
      flex-shrink: 0;
    }

    .est-del-btn:hover {
      background: #fee2e2;
      color: #ef4444;
    }

    .est-del-btn svg {
      width: 13px;
      height: 13px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
    }

    .btn-add-est {
      height: 36px;
      width: 100%;
      border: 2px dashed #e2e8f0;
      border-radius: 9px;
      background: transparent;
      font-size: 13px;
      font-weight: 500;
      color: #94a3b8;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 8px;
      transition: all .18s;
    }

    .btn-add-est:hover {
      border-color: var(--pr);
      color: var(--pr);
      background: rgba(var(--pr-rgb), .04);
    }

    /* Tag management */
    .tag-list {
      display: flex;
      flex-wrap: wrap;
      gap: 7px;
      margin-bottom: 8px;
    }

    .tag-mgr-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px 4px 12px;
      border-radius: 20px;
      font-size: 12.5px;
      font-weight: 600;
      cursor: default;
    }

    .tag-mgr-del {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      border: none;
      background: rgba(0, 0, 0, .12);
      color: inherit;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      line-height: 1;
      transition: background .15s;
    }

    .tag-mgr-del:hover {
      background: rgba(0, 0, 0, .22);
    }

    .tag-new-row {
      display: flex;
      gap: 8px;
    }

    .tag-new-inp {
      flex: 1;
      height: 36px;
      padding: 0 12px;
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      font-size: 13px;
      color: #1e293b;
      background: #f8fafc;
      outline: none;
      font-family: inherit;
      transition: border-color .18s;
    }

    .tag-new-inp:focus {
      border-color: var(--pr);
      background: #fff;
    }

    .btn-add-tag {
      height: 36px;
      padding: 0 14px;
      border: none;
      border-radius: 9px;
      background: var(--grad);
      color: #fff;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      flex-shrink: 0;
      white-space: nowrap;
    }

    /* ─── TOAST ─── */
    .toast-wrap {
      position: fixed;
      bottom: 22px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 300;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 7px;
      pointer-events: none;
    }

    .toast {
      background: #1e293b;
      color: #fff;
      border-radius: 10px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
      pointer-events: all;
      animation: toastIn .22s cubic-bezier(.4, 0, .2, 1);
      max-width: 380px;
    }

    .toast.ok {
      border-left: 3px solid #22c55e;
    }

    .toast.err {
      border-left: 3px solid #ef4444;
      background: #fff1f2;
      color: #1e293b;
    }

    .toast.info {
      border-left: 3px solid #3b82f6;
    }

    .toast-undo {
      background: rgba(255, 255, 255, .16);
      border: none;
      color: #fff;
      padding: 3px 10px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s;
      flex-shrink: 0;
    }

    .toast-undo:hover {
      background: rgba(255, 255, 255, .26);
    }

    @keyframes toastIn {
      from {
        opacity: 0;
        transform: translateY(14px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ─── EDIT LEAD FORM ─── */
    .lm-edit-form {
      display: none;
      border-bottom: 1px solid #f0f4f8;
    }

    .lm-edit-form.active {
      display: block;
    }

    .lm-edit-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      padding: 14px 20px 0;
    }

    .lm-efield {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .lm-efield.full {
      grid-column: 1/-1;
    }

    .lm-elbl {
      font-size: 10px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    .lm-einp {
      height: 34px;
      padding: 0 10px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 13px;
      color: #1e293b;
      background: #f8fafc;
      outline: none;
      font-family: inherit;
      transition: border-color .18s;
      width: 100%;
    }

    .lm-einp:focus {
      border-color: var(--pr);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(var(--pr-rgb), .08);
    }

    .lm-eta {
      height: 56px;
      padding: 8px 10px;
      resize: none;
    }

    .lm-edit-footer {
      padding: 10px 20px 14px;
      display: flex;
      gap: 8px;
    }

    .btn-edit-lead {
      height: 30px;
      padding: 0 13px;
      border: none;
      border-radius: 8px;
      background: #fff;
      color: var(--pr);
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: all .18s;
      flex-shrink: 0;
      box-shadow: 0 1px 5px rgba(0, 0, 0, .15);
    }

    .btn-edit-lead:hover {
      background: #f0f4ff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
      transform: translateY(-1px);
    }

    .btn-edit-lead svg {
      width: 13px;
      height: 13px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
    }

    /* ─── VINCULAR PANEL ─── */
    .lm-vinc-panel {
      display: none;
      margin-top: 8px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 10px;
      gap: 8px;
      flex-direction: column;
    }

    .lm-vinc-panel.open {
      display: flex;
    }

    .lm-vinc-badge {
      font-size: 11.5px;
      color: #16a34a;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      padding: 3px 8px;
      border-radius: 8px;
      display: none;
    }

    .lm-vinc-badge.show {
      display: block;
    }

    /* ─── DATE FILTERS ─── */
    .pl-date-group {
      display: flex;
      align-items: center;
      gap: 5px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 0 10px;
      height: 36px;
      flex-shrink: 0;
    }

    .pl-date-lbl {
      font-size: 11.5px;
      font-weight: 600;
      color: #64748b;
      white-space: nowrap;
    }

    .pl-date-sep {
      font-size: 12px;
      color: #94a3b8;
      flex-shrink: 0;
    }

    .pl-date {
      height: 26px;
      padding: 0 4px;
      border: none;
      background: transparent;
      font-size: 12px;
      color: #475569;
      outline: none;
      cursor: pointer;
      width: 108px;
    }

    .pl-date:focus {
      color: var(--pr);
    }

    .pl-date-clear {
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      font-size: 13px;
      padding: 0 2px;
      line-height: 1;
      display: none;
      transition: color .15s;
    }

    .pl-date-clear.show {
      display: block;
    }

    .pl-date-clear:hover {
      color: #ef4444;
    }

    /* RESPONSIVE */
    @media(max-width:860px) {
      .lm-left {
        width: 280px;
      }

      .pl-sel {
        display: none;
      }
    }

    @media(max-width:680px) {
      .lm-box {
        flex-direction: column;
        height: 92vh;
      }

      .lm-body {
        flex-direction: column;
      }

      .lm-left {
        width: 100%;
        max-height: 45%;
        border-right: none;
        border-bottom: 1px solid #f0f4f8;
      }

      .f-grid {
        grid-template-columns: 1fr;
      }

      .btn-new span {
        display: none;
      }

      .view-toggle {
        display: none;
      }
    }
  </style>
</head>

<body>

  <!-- Header -->
  <div class="header">
    <div class="header-left">
      <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
      <?= UIComponents::renderLogo('small', ['gradient' => 'transparent', 'class' => 'header-logo']) ?>
      <span class="header-title">Pipeline Comercial</span>
    </div>
    <div class="header-right">
      <div class="header-user">
        <div class="header-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?></div>
        <span class="header-name"><?= htmlspecialchars($user['name'] ?? '') ?></span>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <?= UIComponents::renderSidebar($user, '/pipeline') ?>

  <!-- Sidebar overlay -->
  <div id="overlay" onclick="closeSidebar()"></div>

  <!-- Main content -->
  <div id="mainContent">

    <!-- Toolbar -->
    <div class="pl-toolbar">
      <div class="view-toggle">
        <button class="vbtn active" id="btnKanban" onclick="switchView('kanban')">
          <svg viewBox="0 0 24 24">
            <rect x="3" y="3" width="5" height="18" rx="1" />
            <rect x="10" y="3" width="5" height="18" rx="1" />
            <rect x="17" y="3" width="4" height="18" rx="1" />
          </svg>
          Tablero
        </button>
        <button class="vbtn" id="btnList" onclick="switchView('list')">
          <svg viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
          </svg>
          Lista
        </button>
      </div>

      <div class="pl-search">
        <svg viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
        <input id="searchInput" type="text" placeholder="Cliente, email, destino…" oninput="onSearch(this.value)">
      </div>

      <?php if ($isAdmin): ?>
        <select class="pl-sel" id="selAsesor" onchange="applyFilter()">
          <option value="">Todos los asesores</option>
        </select>
      <?php endif; ?>

      <select class="pl-sel" id="selTag" onchange="applyFilter()">
        <option value="">Todos los tags</option>
      </select>

      <div class="pl-date-group">
        <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:#94a3b8;fill:none;stroke-width:2;flex-shrink:0;">
          <rect x="3" y="4" width="18" height="18" rx="2" />
          <line x1="16" y1="2" x2="16" y2="6" />
          <line x1="8" y1="2" x2="8" y2="6" />
          <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
        <span class="pl-date-lbl">Salida</span>
        <input type="date" class="pl-date" id="filtFecDesde" onchange="applyFilter()" title="Fecha de salida desde">
        <span class="pl-date-sep">→</span>
        <input type="date" class="pl-date" id="filtFecHasta" onchange="applyFilter()" title="Fecha de salida hasta">
        <button class="pl-date-clear" id="btnClearDates" onclick="clearDateFilters()" title="Limpiar fechas">✕</button>
      </div>

      <div class="toolbar-divider"></div>

      <?php if ($isAdmin): ?>
        <button class="btn-config" onclick="openConfig()" title="Configurar pipeline">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3" />
            <path
              d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
          </svg>
        </button>
      <?php endif; ?>

      <button class="btn-new" onclick="openNewLead()">
        <svg viewBox="0 0 24 24">
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        <span>Nuevo Lead</span>
      </button>
    </div>

    <!-- Kanban board -->
    <div class="pl-board-wrap" id="boardWrap">
      <div class="board-scroll" id="boardScroll">
        <div class="kanban-board" id="kanbanBoard"></div>
      </div>
    </div>

    <!-- List view (hidden) -->
    <div class="list-scroll" id="listWrap" style="display:none;">
      <table class="list-tbl">
        <thead>
          <tr>
            <th onclick="sortBy('nombre_cliente')">Cliente</th>
            <th onclick="sortBy('destino')">Destino</th>
            <th onclick="sortBy('fecha_salida')">Salida</th>
            <th onclick="sortBy('budget')">Presupuesto</th>
            <th>Estado</th>
            <th>Asesor</th>
            <th>Tag</th>
          </tr>
        </thead>
        <tbody id="listBody"></tbody>
      </table>
    </div>

  </div><!-- /mainContent -->

  <!-- Lead Detail Modal (2 columns) -->
  <div class="lm-overlay" id="leadModal">
    <div class="lm-box">
      <div class="lm-header">
        <div class="lm-avatar" id="lmAvatar"></div>
        <div class="lm-hinfo">
          <div class="lm-hname" id="lmName">—</div>
          <div class="lm-hemail" id="lmEmail">—</div>
        </div>
        <span class="lm-hbadge" id="lmBadge"></span>
        <button class="btn-edit-lead" id="btnEditLead" onclick="openLeadEdit()">
          <svg viewBox="0 0 24 24">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
          </svg>
          Editar
        </button>
        <button class="lm-close" onclick="closeLeadModal()">
          <svg viewBox="0 0 24 24">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>
      <div class="lm-body">
        <!-- LEFT: info-groups (estilo chat.php sidebar) -->
        <div class="lm-left">

          <!-- Info groups (vista) -->
          <div id="lmInfoSection">
            <!-- Destino -->
            <div class="lm-ig">
              <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                  <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z" />
                  <circle cx="12" cy="10" r="3" />
                </svg></div>
              <div class="lm-ig-content">
                <span class="lm-ig-label">Destino</span>
                <span class="lm-ig-val dim" id="lmIgDestino">—</span>
              </div>
            </div>
            <!-- Presupuesto -->
            <div class="lm-ig">
              <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                  <line x1="12" y1="1" x2="12" y2="23" />
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                </svg></div>
              <div class="lm-ig-content">
                <span class="lm-ig-label">Presupuesto</span>
                <span class="lm-ig-val dim" id="lmIgBudget">—</span>
              </div>
            </div>
            <!-- Teléfono -->
            <div class="lm-ig">
              <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                  <path
                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg></div>
              <div class="lm-ig-content">
                <span class="lm-ig-label">Teléfono</span>
                <span class="lm-ig-val dim" id="lmIgPhone">—</span>
              </div>
            </div>
            <!-- Viajeros -->
            <div class="lm-ig">
              <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg></div>
              <div class="lm-ig-content">
                <span class="lm-ig-label">Viajeros (PAX)</span>
                <span class="lm-ig-val dim" id="lmIgViajeros">—</span>
              </div>
            </div>
            <!-- Fechas -->
            <div class="lm-ig">
              <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                  <rect x="3" y="4" width="18" height="18" rx="2" />
                  <line x1="16" y1="2" x2="16" y2="6" />
                  <line x1="8" y1="2" x2="8" y2="6" />
                  <line x1="3" y1="10" x2="21" y2="10" />
                </svg></div>
              <div class="lm-ig-content">
                <span class="lm-ig-label">Fechas</span>
                <span class="lm-ig-val dim" id="lmIgFechas">—</span>
              </div>
            </div>
            <!-- Notas -->
            <div class="lm-ig" id="lmIgNotasRow" style="display:none">
              <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                  <line x1="8" y1="13" x2="16" y2="13" />
                  <line x1="8" y1="17" x2="13" y2="17" />
                </svg></div>
              <div class="lm-ig-content">
                <span class="lm-ig-label">Notas</span>
                <span class="lm-ig-val dim" id="lmIgNotas">—</span>
              </div>
            </div>
          </div>

          <div class="lm-ig-divider"></div>

          <!-- Tags -->
          <div class="lm-ig">
            <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                <line x1="7" y1="7" x2="7.01" y2="7" />
              </svg></div>
            <div class="lm-ig-content">
              <span class="lm-ig-label">Tags <span
                  style="font-weight:400;color:#cbd5e1;font-size:10px;">(clic)</span></span>
              <div class="tag-chips lm-ig-tags" id="lmTagChips"></div>
              <input type="hidden" id="lmTagId">
              <input type="hidden" id="lmTagId2">
            </div>
          </div>

          <!-- Estado -->
          <div class="lm-ig">
            <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg></div>
            <div class="lm-ig-content">
              <span class="lm-ig-label">Estado</span>
              <select class="lm-ig-select" id="lmEstadoSel" onchange="onModalMoverEstado(this)">
                <option value="">Seleccionar…</option>
              </select>
            </div>
          </div>

          <?php if ($isAdmin): ?>
            <!-- Asesor -->
            <div class="lm-ig">
              <div class="lm-ig-icon"><svg viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg></div>
              <div class="lm-ig-content">
                <span class="lm-ig-label">Asesor</span>
                <select class="lm-ig-select" id="lmAsesorSel" onchange="onModalAsignarAsesor(this)">
                  <option value="">Sin asignar</option>
                </select>
              </div>
            </div>
          <?php endif; ?>

          <div class="lm-ig-divider"></div>

          <!-- Vincular -->
          <div style="padding:8px 16px 12px">
            <button class="lm-vinc-btn" onclick="openVincModal()">
              <svg viewBox="0 0 24 24">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
              </svg>
              Vincular itinerario
            </button>
            <div class="lm-vinc-badge-row" id="lmVincBadgeRow"
              style="display:none; align-items:center; gap:8px; margin-top:8px;">
              <div class="lm-vinc-badge" id="lmVincBadge" style="display:block; margin:0; flex:1;"></div>
              <button class="lm-vinc-preview-btn" id="lmPreviewLinkBtn" title="Insertar link de vista previa en el chat"
                onclick="lmInsertarLinkVistaPrevia()"
                style="flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border:1px solid #a7f3d0; background:#ecfdf5; color:#059669; border-radius:8px; cursor:pointer;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Formulario de edición (modo edición) -->
          <div class="lm-edit-form" id="lmEditForm">
            <div class="lm-edit-grid">
              <div class="lm-efield"><label class="lm-elbl">Nombre *</label><input class="lm-einp" id="leNombre"
                  type="text"></div>
              <div class="lm-efield"><label class="lm-elbl">Email *</label><input class="lm-einp" id="leEmail"
                  type="email"></div>
              <div class="lm-efield full"><label class="lm-elbl">Teléfono</label><input class="lm-einp" id="leTel"
                  type="text" placeholder="+57…"></div>
              <div class="lm-efield full"><label class="lm-elbl">Destino *</label><input class="lm-einp" id="leDestino"
                  type="text"></div>
              <div class="lm-efield"><label class="lm-elbl">Fecha salida</label><input class="lm-einp" id="leFecSal"
                  type="date"></div>
              <div class="lm-efield"><label class="lm-elbl">Fecha regreso</label><input class="lm-einp" id="leFecLleg"
                  type="date"></div>
              <div class="lm-efield"><label class="lm-elbl">Viajeros</label><input class="lm-einp" id="leViajeros"
                  type="number" min="1"></div>
              <div class="lm-efield"><label class="lm-elbl">Presupuesto (USD)</label><input class="lm-einp"
                  id="leBudget" type="number" min="0"></div>
              <div class="lm-efield full"><label class="lm-elbl">Origen</label><select class="lm-einp"
                  id="leSource"></select></div>
              <div class="lm-efield full"><label class="lm-elbl">Descripción</label><textarea class="lm-einp lm-eta"
                  id="leDesc"></textarea></div>
            </div>
            <div class="lm-edit-footer">
              <button class="btn-submit" style="flex:1;height:36px;font-size:13px;" id="btnSaveEdit"
                onclick="saveLeadEdit()">Guardar cambios</button>
              <button class="btn-cancel" style="height:36px;font-size:13px;"
                onclick="cancelLeadEdit()">Cancelar</button>
            </div>
          </div>
        </div>
        <!-- RIGHT: Chat panel -->
        <div class="lm-right" id="lmRight">
          <!-- Header — igual a chat.php -->
          <div class="lm-chat-hd">
            <h3 class="lm-chat-hd-title">Conversación (Vía Gmail)</h3>
            <div class="lm-chat-hd-sub">
              <span id="lmChatEmail">—</span>
              <span class="lm-gmail-badge nok" id="lmGmailBadge">Sin Gmail</span>
            </div>
          </div>
          <!-- Mensajes -->
          <div class="lm-chat-msgs" id="lmChatMsgs">
            <div class="lm-chat-empty">
              <svg viewBox="0 0 24 24">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <p>Sin mensajes aún</p>
            </div>
          </div>
          <!-- Compositor — siempre visible -->
          <div class="lm-composer" id="lmComposer">
            <!-- Banner: sin cuenta Gmail -->
            <div class="lm-no-gmail" id="lmNoGmail">
              Gmail no conectado — <a href="<?= APP_URL ?>/administrador" target="_blank">Conectar cuenta Gmail</a>
            </div>
            <!-- Barra de formato -->
            <div class="lm-fmt-bar">
              <button class="lm-fmt-btn" id="lmFmtBold" title="Negrita"
                onclick="lmApplyFormat('bold')"><b>B</b></button>
              <button class="lm-fmt-btn" id="lmFmtItalic" title="Cursiva" onclick="lmApplyFormat('italic')"><i
                  style="font-style:italic">I</i></button>
              <button class="lm-fmt-btn" id="lmFmtUnder" title="Subrayado" onclick="lmApplyFormat('underline')"
                style="text-decoration:underline;font-weight:600">U</button>
              <div class="lm-fmt-divider"></div>
              <button class="lm-fmt-btn" id="lmFmtStrike" title="Tachado" onclick="lmApplyFormat('strikethrough')"
                style="text-decoration:line-through;font-weight:600">S</button>
            </div>
            <!-- Editor -->
            <div class="lm-editor-wrap">
              <div id="lmEditor" class="lm-editor" contenteditable="true"
                data-placeholder="Escribe un mensaje al cliente..."></div>
            </div>
            <!-- Adjuntos preview -->
            <div class="lm-attachments" id="lmAttachments"></div>
            <input type="file" id="lmFileInput" multiple style="display:none" onchange="lmHandleFiles(this.files)">
            <!-- Barra de acciones -->
            <div class="lm-action-bar">
              <div class="lm-act-left">
                <button class="lm-act-btn" title="Adjuntar archivo"
                  onclick="document.getElementById('lmFileInput').click()">
                  <svg viewBox="0 0 24 24">
                    <path
                      d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.41 17.4a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                  </svg>
                </button>
                <button class="lm-act-btn" title="Plantillas de mensaje" onclick="lmAbrirTemplates()">
                  <svg viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="9" y1="13" x2="15" y2="13" />
                    <line x1="9" y1="17" x2="13" y2="17" />
                  </svg>
                </button>
                <div class="lm-act-div"></div>
              </div>
              <div class="lm-act-right">
                <button id="lmSendBtn" class="lm-send-btn" onclick="lmEnviarMensaje()">
                  <svg viewBox="0 0 24 24">
                    <line x1="22" y1="2" x2="11" y2="13" />
                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                  </svg>
                  Enviar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Lead Chat: Templates Modal -->
  <div class="lm-tpl-overlay" id="lmTplOverlay" onclick="lmCerrarTemplates(event)">
    <div class="lm-tpl-modal">
      <div class="lm-tpl-hd">
        <h2>Templates</h2>
        <input type="text" class="lm-tpl-search" id="lmTplSearch" placeholder="Buscar template..."
          oninput="lmFiltrarTemplates()">
        <button class="lm-tpl-create-btn" onclick="lmAbrirFormTemplate(null)">+ Crear template</button>
        <button class="lm-tpl-close" onclick="lmCerrarTemplates()">&times;</button>
      </div>
      <div class="lm-tpl-list" id="lmTplList"></div>
      <div class="lm-tpl-form" id="lmTplForm" style="display:none">
        <input type="hidden" id="lmTplEditId">
        <div class="lm-tpl-form-title" id="lmTplFormTitle">Nuevo template</div>
        <input type="text" id="lmTplNombre" placeholder="Nombre del template">
        <textarea id="lmTplTexto" placeholder="Contenido del mensaje..."></textarea>
        <div class="lm-tpl-form-actions">
          <button class="lm-tpl-cancel-btn" onclick="lmCancelarFormTemplate()">Cancelar</button>
          <button class="lm-tpl-save-btn" onclick="lmGuardarTemplate()">Guardar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Vincular Itinerario Modal -->
  <div id="vincModal"
    style="position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:1200;display:none;align-items:center;justify-content:center;padding:16px;">
    <div
      style="background:#fff;border-radius:18px;width:min(520px,94vw);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.28);animation:mdIn .22s cubic-bezier(.4,0,.2,1);">
      <!-- Header -->
      <div
        style="padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0;">
        <div>
          <div id="vincTitle" style="font-size:16px;font-weight:700;color:#0f172a;">Vincular itinerario</div>
          <div id="vincSubtitle" style="font-size:12px;color:#64748b;margin-top:3px;">¿Cómo quieres asociar el
            itinerario a este lead?</div>
        </div>
        <button class="lm-close" onclick="closeVincModal()"><svg viewBox="0 0 24 24">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg></button>
      </div>
      <!-- Vista principal: 4 opciones -->
      <div id="vincViewMain" style="padding:16px 22px 20px;display:flex;flex-direction:column;gap:8px;">
        <button class="vinc-opt" onclick="vincSelectOption('existente')">
          <div class="vinc-opt-ico"><svg viewBox="0 0 24 24">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
            </svg></div>
          <div>
            <div class="vinc-opt-title">Seleccionar programa existente</div>
            <div class="vinc-opt-desc">Vincula un programa que ya tienes creado</div>
          </div>
          <svg class="vinc-opt-arr" viewBox="0 0 24 24">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
        <button class="vinc-opt" onclick="vincSelectOption('scratch')">
          <div class="vinc-opt-ico"><svg viewBox="0 0 24 24">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg></div>
          <div>
            <div class="vinc-opt-title">Crear programa desde cero</div>
            <div class="vinc-opt-desc">Diseña un nuevo itinerario personalizado</div>
          </div>
          <svg class="vinc-opt-arr" viewBox="0 0 24 24">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
        <button class="vinc-opt" onclick="vincSelectOption('clonar')">
          <div class="vinc-opt-ico"><svg viewBox="0 0 24 24">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
            </svg></div>
          <div>
            <div class="vinc-opt-title">Crear a partir de otro programa</div>
            <div class="vinc-opt-desc">Copia un programa existente como punto de partida</div>
          </div>
          <svg class="vinc-opt-arr" viewBox="0 0 24 24">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
        <button class="vinc-opt" onclick="vincSelectOption('plantilla')">
          <div class="vinc-opt-ico"><svg viewBox="0 0 24 24">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <line x1="3" y1="9" x2="21" y2="9" />
              <line x1="9" y1="3" x2="9" y2="21" />
            </svg></div>
          <div>
            <div class="vinc-opt-title">Crear a partir de plantilla</div>
            <div class="vinc-opt-desc">Usa una de tus plantillas de programa guardadas</div>
          </div>
          <svg class="vinc-opt-arr" viewBox="0 0 24 24">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
      </div>
      <!-- Sub-vista: Existente -->
      <div id="vincViewExistente" style="padding:14px 22px 4px;display:none;flex-direction:column;gap:8px;">
        <div class="vinc-psearch">
          <svg viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input type="text" class="vinc-pinput" id="vincProgSearch" placeholder="Buscar programa..."
            oninput="_renderVincPicker('existente')">
        </div>
        <div class="vinc-pgrid" id="vincProgGrid"></div>
      </div>
      <!-- Sub-vista: Clonar programa -->
      <div id="vincViewClonar"
        style="padding:8px 22px 4px;display:none;flex-direction:column;max-height:280px;overflow-y:auto;">
        <div id="vincCloneList"></div>
      </div>
      <!-- Sub-vista: Plantilla -->
      <div id="vincViewPlantilla" style="padding:14px 22px 4px;display:none;flex-direction:column;gap:8px;">
        <div class="vinc-psearch">
          <svg viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input type="text" class="vinc-pinput" id="vincPlantSearch" placeholder="Buscar plantilla..."
            oninput="_renderVincPicker('plantilla')">
        </div>
        <div class="vinc-pgrid" id="vincPlantGrid"></div>
      </div>
      <!-- Footer sub-vistas -->
      <div id="vincFooter"
        style="padding:12px 22px 18px;display:none;flex-direction:row;justify-content:space-between;align-items:center;gap:9px;flex-shrink:0;border-top:1px solid #f1f5f9;margin-top:8px;">
        <button class="btn-cancel" onclick="vincBack()">← Atrás</button>
        <button id="vincConfirmBtn" class="btn-submit" style="height:38px;padding:0 22px;"
          onclick="confirmarVincular()">Vincular</button>
      </div>
    </div>
  </div>

  <!-- New Lead Modal -->
  <div class="nl-overlay" id="newLeadModal">
    <div class="nl-box">
      <div class="nl-hd">
        <span class="nl-title">Nuevo Lead</span>
        <button class="nl-close" onclick="closeNewLead()"><svg viewBox="0 0 24 24">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg></button>
      </div>
      <div class="nl-body">
        <div class="f-grid">
          <div class="f-field">
            <label class="f-lbl">Nombre *</label>
            <input class="f-inp" id="fNombre" type="text" placeholder="Nombre completo">
          </div>
          <div class="f-field">
            <label class="f-lbl">Email *</label>
            <input class="f-inp" id="fEmail" type="email" placeholder="correo@ejemplo.com">
          </div>
          <div class="f-field full">
            <label class="f-lbl">Teléfono</label>
            <input class="f-inp" id="fTelefono" type="tel">
          </div>
          <div class="f-field">
            <label class="f-lbl">Destino *</label>
            <input class="f-inp" id="fDestino" type="text" placeholder="ej. París, Francia">
          </div>
          <div class="f-field">
            <label class="f-lbl">Fecha de salida *</label>
            <input class="f-inp" id="fFechaSalida" type="date">
          </div>
          <div class="f-field">
            <label class="f-lbl">Fecha de regreso</label>
            <input class="f-inp" id="fFechaLlegada" type="date">
          </div>
          <div class="f-field">
            <label class="f-lbl">Viajeros</label>
            <input class="f-inp" id="fViajeros" type="number" min="1" value="1">
          </div>
          <div class="f-field">
            <label class="f-lbl">Presupuesto (USD)</label>
            <input class="f-inp" id="fBudget" type="number" min="0" placeholder="0">
          </div>
          <div class="f-field">
            <label class="f-lbl">Estado inicial *</label>
            <select class="f-sel" id="fEstado">
              <option value="">Seleccionar…</option>
            </select>
          </div>
          <?php if ($isAdmin): ?>
            <div class="f-field">
              <label class="f-lbl">Asesor</label>
              <select class="f-sel" id="fAsesor">
                <option value="">Sin asignar</option>
              </select>
            </div>
          <?php endif; ?>
          <div class="f-field full">
            <label class="f-lbl">Tags <span style="font-weight:400;color:#94a3b8;">(hasta 2)</span></label>
            <div class="tag-chips" id="fTagChips"></div>
            <input type="hidden" id="fTagId">
            <input type="hidden" id="fTagId2">
          </div>
          <div class="f-field">
            <label class="f-lbl">Origen</label>
            <select class="f-inp" id="fSource"></select>
          </div>
          <div class="f-field full">
            <label class="f-lbl">Descripción</label>
            <textarea class="f-ta" id="fDesc" placeholder="Notas o detalles…"></textarea>
          </div>
        </div>
      </div>
      <div class="nl-ft">
        <button class="btn-cancel" onclick="closeNewLead()">Cancelar</button>
        <button class="btn-submit" id="btnSubmit" onclick="submitNewLead()">Crear Lead</button>
      </div>
    </div>
  </div>

  <!-- Config Modal (estados + tags) — admin only -->
  <?php if ($isAdmin): ?>
    <div class="cfg-overlay" id="cfgModal">
      <div class="cfg-box">
        <div class="cfg-hd">
          <span class="cfg-title">Configurar Pipeline</span>
          <button class="nl-close" onclick="closeConfig()"><svg viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg></button>
        </div>
        <div class="cfg-body">
          <div class="cfg-sec-title">Estados del pipeline</div>
          <div class="est-list" id="estList"></div>
          <button class="btn-add-est" onclick="addEstado()">+ Agregar estado</button>

          <div class="cfg-sec-title">Tags</div>
          <div class="tag-list" id="tagMgrList"></div>
          <div class="tag-new-row">
            <input class="tag-new-inp" id="tagNewInp" type="text" placeholder="Nombre del tag…"
              onkeydown="if(event.key==='Enter')addTag()">
            <button class="btn-add-tag" onclick="addTag()">+ Agregar</button>
          </div>

          <div class="cfg-sec-title">Orígenes</div>
          <div class="tag-list" id="sourceMgrList"></div>
          <div class="tag-new-row">
            <input class="tag-new-inp" id="sourceNewInp" type="text" placeholder="Nombre del origen…"
              onkeydown="if(event.key==='Enter')addSource()">
            <button class="btn-add-tag" onclick="addSource()">+ Agregar</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Toast container -->
  <div class="toast-wrap" id="toastWrap"></div>

  <script>
    // ── CONSTANTS ──
    const APP_URL = <?= json_encode(APP_URL) ?>;
    const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
    const USER_ID = <?= (int) ($user['id'] ?? 0) ?>;
    const TAG_PALETTE = ['#6366f1', '#3b82f6', '#14b8a6', '#f59e0b', '#f97316', '#22c55e', '#ef4444', '#8b5cf6', '#ec4899', '#0ea5e9'];
    function tagColor(id) { return TAG_PALETTE[((id - 1) % TAG_PALETTE.length + TAG_PALETTE.length) % TAG_PALETTE.length]; }

    // ── SIDEBAR ──
    let sidebarOpen = false;
    function toggleSidebar() {
      sidebarOpen = !sidebarOpen;
      document.querySelector('.enhanced-sidebar')?.classList.toggle('open', sidebarOpen);
      const ov = document.getElementById('overlay');
      if (ov) ov.style.display = sidebarOpen ? 'block' : 'none';
    }
    function closeSidebar() {
      sidebarOpen = false;
      document.querySelector('.enhanced-sidebar')?.classList.remove('open');
      const ov = document.getElementById('overlay');
      if (ov) ov.style.display = 'none';
    }
    function toggleUserMenu() { if (confirm('¿Desea cerrar sesión?')) window.location.href = APP_URL + '/auth/logout'; }

    // ── PHONE INPUT ──
    let phoneInstance = null;
    function initPhone() {
      const el = document.getElementById('fTelefono');
      if (!el || !window.intlTelInput || phoneInstance) return;
      phoneInstance = window.intlTelInput(el, {
        initialCountry: 'co',
        preferredCountries: ['co', 'us', 'mx', 'es', 'ar', 'cl', 'pe', 'ec', 'br', 'th'],
        separateDialCode: true,
        nationalMode: false,
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js'
      });
      el.addEventListener('input', function () { this.value = this.value.replace(/[^0-9]/g, ''); });
    }
    function getPhone() {
      if (!phoneInstance) return document.getElementById('fTelefono')?.value || '';
      const d = phoneInstance.getSelectedCountryData();
      const n = document.getElementById('fTelefono').value.replace(/[^0-9]/g, '');
      return n ? '+' + d.dialCode + n : '';
    }

    // ── STATE ──
    const S = {
      estados: [], leads: [], agentes: [], tags: [], source: [],
      currentLeadId: null,
      view: 'kanban',
      sortKey: 'created_at', sortDir: 'desc',
      filters: { buscar: '', usuario_id: '', tag_id: '' }
    };

    // ── API ──
    async function api(action, params = {}, method = 'GET') {
      try {
        let url = APP_URL + '/pipeline/api?action=' + encodeURIComponent(action);
        let opts = { method };
        if (method === 'POST') {
          const fd = new FormData();
          fd.append('action', action);
          Object.entries(params).forEach(([k, v]) => { if (v !== null && v !== undefined) fd.append(k, v); });
          opts.body = fd;
        } else {
          const qs = Object.entries(params).map(([k, v]) => k + '=' + encodeURIComponent(v)).join('&');
          if (qs) url += '&' + qs;
        }
        const r = await fetch(url, opts);
        return await r.json();
      } catch (e) { return { success: false, message: 'Error de red' }; }
    }
    async function apiJ(action, body = {}) {
      try {
        const r = await fetch(APP_URL + '/pipeline/api?action=' + encodeURIComponent(action), {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
        });
        return await r.json();
      } catch (e) { return { success: false, message: 'Error de red' }; }
    }

    // ── LOAD ALL ──
    async function loadAll() {
      const [rE, rL, rA, rT, rS] = await Promise.all([
        api('get_estados'), api('filtrar_pipeline', S.filters),
        api('get_agentes'), api('get_tags'), api("get_source")
      ]);
      S.estados = rE.success ? rE.data : [];
      S.leads = rL.success ? rL.data : [];
      S.agentes = rA.success ? rA.data : [];
      S.tags = rT.success ? rT.data : [];
      S.source = rS.success ? rS.data : [];
      fillSelects();
      render();
    }
    async function reloadLeads() {
      const r = await api('filtrar_pipeline', S.filters);
      S.leads = r.success ? r.data : [];
      render();
    }

    // ── SELECTS ──
    function fillSelects() {
      // toolbar asesor
      const sA = document.getElementById('selAsesor');
      if (sA) { sA.innerHTML = '<option value="">Todos los asesores</option>'; S.agentes.forEach(a => sA.innerHTML += `<option value="${a.id}">${esc(a.username)}</option>`); }
      // toolbar tag
      const sT = document.getElementById('selTag');
      if (sT) { sT.innerHTML = '<option value="">Todos los tags</option>'; S.tags.forEach(t => sT.innerHTML += `<option value="${t.id}">${esc(t.nombre)}</option>`); }
      const sS = document.getElementById('fSource');
      if (sS) { sS.innerHTML = '<option value="">Sin origen</option>'; S.source.forEach(s => sS.innerHTML += `<option value="${s.id}">${esc(s.nombre)}</option>`); }

      const sL = document.getElementById('leSource');
      if (sL) { sL.innerHTML = '<option value="">Sin origen</option>'; S.source.forEach(s => sL.innerHTML += `<option value="${s.id}">${esc(s.nombre)}</option>`); }
      // modal estado
      const fE = document.getElementById('fEstado');
      if (fE) { fE.innerHTML = '<option value="">Seleccionar…</option>'; S.estados.forEach(e => fE.innerHTML += `<option value="${e.id}">${esc(e.nombre)}</option>`); }
      // modal asesor
      const fA = document.getElementById('fAsesor');
      if (fA) { fA.innerHTML = '<option value="">Sin asignar</option>'; S.agentes.forEach(a => fA.innerHTML += `<option value="${a.id}">${esc(a.username)}</option>`); }
      // modal tag chips
      renderTagChips();
      renderModalTagChips();
      // lead modal estado
      const lE = document.getElementById('lmEstadoSel');
      if (lE) { lE.innerHTML = '<option value="">Seleccionar…</option>'; S.estados.forEach(e => lE.innerHTML += `<option value="${e.id}">${esc(e.nombre)}</option>`); }
      // lead modal asesor
      const lA = document.getElementById('lmAsesorSel');
      if (lA) { lA.innerHTML = '<option value="">Sin asignar</option>'; S.agentes.forEach(a => lA.innerHTML += `<option value="${a.id}">${esc(a.username)}</option>`); }
    }
    function renderTagChips() {
      const el = document.getElementById('fTagChips');
      if (!el) return;
      const s1 = document.getElementById('fTagId')?.value || '';
      const s2 = document.getElementById('fTagId2')?.value || '';
      if (!S.tags.length) { el.innerHTML = '<span style="font-size:12px;color:#cbd5e1;">Sin tags creados. Créalos en ⚙ Configurar.</span>'; return; }
      el.innerHTML = S.tags.map(t => {
        const c = tagColor(t.id);
        const active = String(s1) === String(t.id) || String(s2) === String(t.id);
        return `<span class="tag-chip${active ? ' selected' : ''}"
            style="background:${c}20;color:${c};border-color:${active ? c : 'transparent'};"
            onclick="toggleTagChip(${t.id})">${esc(t.nombre)}</span>`;
      }).join('');
    }
    function toggleTagChip(id) {
      const t1 = document.getElementById('fTagId');
      const t2 = document.getElementById('fTagId2');
      const s = String(id);
      if (t1.value === s) { t1.value = t2.value; t2.value = ''; }
      else if (t2.value === s) { t2.value = ''; }
      else if (!t1.value) { t1.value = s; }
      else if (!t2.value) { t2.value = s; }
      else { t1.value = t2.value; t2.value = s; }
      renderTagChips();
    }

    // ── RENDER ──
    function render() {
      if (S.view === 'kanban') renderBoard();
      else renderList();
    }

    // ── KANBAN ──
    function renderBoard() {
      const board = document.getElementById('kanbanBoard');
      const leads = filtered();
      if (!S.estados.length) {
        board.innerHTML = `<div style="text-align:center;padding:60px 30px;color:#94a3b8;min-width:300px;">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#dde3eb" stroke-width="1.3" style="margin-bottom:14px;"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>
            <div style="font-size:16px;font-weight:600;color:#64748b;margin-bottom:6px;">Sin estados</div>
            <div style="font-size:13px;">${IS_ADMIN ? 'Crea los estados del pipeline en ⚙ Configurar.' : 'Contacta al administrador.'}</div></div>`;
        return;
      }
      board.innerHTML = S.estados.map(est => {
        const cl = leads.filter(l => l.estado_id == est.id);
        const c = est.color || '#6366f1';
        return `<div class="k-col" style="--cc:${c};">
            <div class="k-col-hd"><div class="k-dot"></div><span class="k-name">${esc(est.nombre)}</span><span class="k-cnt">${cl.length}</span></div>
            <div class="k-body" id="kb-${est.id}"
                ondragover="onDragOver(event,${est.id})"
                ondragleave="onDragLeave(event,${est.id})"
                ondrop="onDrop(event,${est.id})">
                ${cl.length ? cl.map(l => card(l)).join('') : `<div class="k-empty"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Sin leads</div>`}
            </div>
        </div>`;
      }).join('');
      document.querySelectorAll('.lead-card').forEach(c => {
        c.addEventListener('dragstart', e => { onDragStart(e, +c.dataset.id, +c.dataset.estado); });
        c.addEventListener('dragend', onDragEnd);
      });
    }
    function card(l) {
      const fromEmail = l.created_from_email_message_id;
      const budget = l.budget ? '$' + Number(l.budget).toLocaleString('es-CO') : null;
      const tc = l.tag_id ? tagColor(l.tag_id) : null;
      const tc2 = l.tag_id2 ? tagColor(l.tag_id2) : null;
      return `<div class="lead-card" id="card-${l.id}" draggable="true" data-id="${l.id}" data-estado="${l.estado_id}" onclick="openLeadModal(${l.id})">
        <div class="c-top">
            <div class="c-name">${esc(l.nombre_cliente)}</div>
            ${fromEmail ? `<div class="c-email-ico" title="Desde correo"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>` : ''}
        </div>
        <div class="c-sub"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>${esc(l.email_cliente)}</div>
        ${l.destino ? `<div class="c-sub"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>${esc(l.destino)}</div>` : ''}
        ${l.fecha_salida ? `<div class="c-sub"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>${fmtDate(l.fecha_salida)}</div>` : ''}
        <div class="c-foot">
            ${budget ? `<span class="c-budget">${budget}</span>` : ''}
            ${tc && l.tag_nombre ? `<span class="c-tag" style="background:${tc}18;color:${tc};">${esc(l.tag_nombre)}</span>` : ''}
            ${tc2 && l.tag_nombre2 ? `<span class="c-tag" style="background:${tc2}18;color:${tc2};">${esc(l.tag_nombre2)}</span>` : ''}
            ${l.asesor_nombre ? `<div class="c-asesor" title="${esc(l.asesor_nombre)}">${initials(l.asesor_nombre)}</div>` : ''}
        </div>
    </div>`;
    }

    // ── LIST VIEW ──
    function renderList() {
      const leads = filtered();
      const sorted = [...leads].sort((a, b) => {
        const va = a[S.sortKey] ?? '', vb = b[S.sortKey] ?? '';
        return S.sortDir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
      });
      const tbody = document.getElementById('listBody');
      if (!sorted.length) { tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">Sin leads que mostrar</td></tr>`; return; }
      tbody.innerHTML = sorted.map(l => {
        const est = S.estados.find(e => e.id == l.estado_id);
        const c = est?.color || '#6366f1';
        const tc = l.tag_id ? tagColor(l.tag_id) : null;
        const tc2 = l.tag_id2 ? tagColor(l.tag_id2) : null;
        return `<tr onclick="openLeadModal(${l.id})">
            <td>${esc(l.nombre_cliente)}<br><small style="font-weight:400;color:#64748b;">${esc(l.email_cliente)}</small></td>
            <td>${esc(l.destino || '—')}</td>
            <td>${l.fecha_salida ? fmtDate(l.fecha_salida) : '—'}</td>
            <td>${l.budget ? '$' + Number(l.budget).toLocaleString('es-CO') : '—'}</td>
            <td><span class="l-badge" style="background:${c}18;color:${c};"><span class="l-bdot"></span>${esc(l.estado_nombre || '—')}</span></td>
            <td>${esc(l.asesor_nombre || '—')}</td>
            <td>
                ${tc && l.tag_nombre ? `<span class="c-tag" style="background:${tc}18;color:${tc};">${esc(l.tag_nombre)}</span>` : ''}
                ${tc2 && l.tag_nombre2 ? `<span class="c-tag" style="background:${tc2}18;color:${tc2};">${esc(l.tag_nombre2)}</span>` : ''}
                ${!tc && !tc2 ? '—' : ''}
            </td>
        </tr>`;
      }).join('');
    }
    function sortBy(k) { S.sortDir = (S.sortKey === k && S.sortDir === 'asc') ? 'desc' : 'asc'; S.sortKey = k; renderList(); }

    // ── FILTERS ──
    function filtered() {
      const { buscar, usuario_id, tag_id } = S.filters;
      return S.leads.filter(l => {
        if (buscar) { const q = buscar.toLowerCase(); if (!(l.nombre_cliente || '').toLowerCase().includes(q) && !(l.email_cliente || '').toLowerCase().includes(q) && !(l.destino || '').toLowerCase().includes(q)) return false; }
        if (usuario_id && l.usuario_id != usuario_id) return false;
        if (tag_id && l.tag_id != tag_id && l.tag_id2 != tag_id) return false;
        const desde = document.getElementById('filtFecDesde')?.value || '';
        const hasta = document.getElementById('filtFecHasta')?.value || '';
        if (desde && l.fecha_salida && l.fecha_salida < desde) return false;
        if (hasta && l.fecha_salida && l.fecha_salida > hasta) return false;
        return true;
      });
    }
    let searchTimer;
    function onSearch(v) { clearTimeout(searchTimer); searchTimer = setTimeout(() => { S.filters.buscar = v.trim(); render(); }, 260); }
    function applyFilter() {
      S.filters.usuario_id = document.getElementById('selAsesor')?.value || '';
      S.filters.tag_id = document.getElementById('selTag')?.value || '';
      const desde = document.getElementById('filtFecDesde')?.value || '';
      const hasta = document.getElementById('filtFecHasta')?.value || '';
      const btn = document.getElementById('btnClearDates');
      if (btn) btn.classList.toggle('show', !!(desde || hasta));
      render();
    }
    function clearDateFilters() {
      const d = document.getElementById('filtFecDesde'); if (d) d.value = '';
      const h = document.getElementById('filtFecHasta'); if (h) h.value = '';
      const btn = document.getElementById('btnClearDates'); if (btn) btn.classList.remove('show');
      render();
    }

    // ── DRAG & DROP ──
    let dragId = null, dragSrc = null;
    function onDragStart(e, id, estadoId) { dragId = id; dragSrc = estadoId; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(id)); setTimeout(() => document.getElementById('card-' + id)?.classList.add('dragging'), 0); }
    function onDragEnd() { document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging')); document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over')); }
    function onDragOver(e, estadoId) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; if (estadoId !== dragSrc) document.getElementById('kb-' + estadoId)?.classList.add('drag-over'); }
    function onDragLeave(e, estadoId) { if (!e.currentTarget.contains(e.relatedTarget)) document.getElementById('kb-' + estadoId)?.classList.remove('drag-over'); }
    function onDrop(e, estadoId) {
      e.preventDefault();
      document.getElementById('kb-' + estadoId)?.classList.remove('drag-over');
      if (!dragId || estadoId === dragSrc) { dragId = null; return; }
      const lead = S.leads.find(l => l.id == dragId);
      if (!lead) { dragId = null; return; }
      const prevId = lead.estado_id;
      const prevNm = S.estados.find(e => e.id == prevId)?.nombre || '';
      const newNm = S.estados.find(e => e.id == estadoId)?.nombre || '';
      moveLead(dragId, estadoId, prevId, prevNm, newNm);
      dragId = null;
    }
    async function moveLead(leadId, newEId, prevEId, prevNm, newNm) {
      const lead = S.leads.find(l => l.id == leadId);
      if (!lead) return;
      lead.estado_id = newEId; lead.estado_nombre = newNm;
      renderBoard();
      const fd = new FormData();
      fd.append('action', 'mover_estado'); fd.append('pipeline_id', leadId); fd.append('estado_id', newEId);
      const r = await fetch(APP_URL + '/pipeline/api', { method: 'POST', body: fd });
      const d = await r.json().catch(() => ({ success: false }));
      if (!d.success) { lead.estado_id = prevEId; lead.estado_nombre = prevNm; renderBoard(); showToast(d.message || 'Error al mover', 'err'); }
      else { showToastUndo(`Movido a <strong>${esc(newNm)}</strong>`, () => moveLead(leadId, prevEId, newEId, newNm, prevNm)); if (S.currentLeadId == leadId) syncModalBadge(newEId, newNm); }
    }

    // ── LEAD MODAL ──
    async function openLeadModal(id) {
      S.currentLeadId = id;
      const lead = S.leads.find(l => l.id == id);
      if (!lead) return;
      document.getElementById('lmAvatar').textContent = initials(lead.nombre_cliente);
      document.getElementById('lmName').textContent = lead.nombre_cliente;
      document.getElementById('lmEmail').textContent = lead.email_cliente;
      syncModalBadge(lead.estado_id, lead.estado_nombre);
      const lE = document.getElementById('lmEstadoSel'); if (lE) lE.value = lead.estado_id || '';
      const lA = document.getElementById('lmAsesorSel'); if (lA) lA.value = lead.usuario_id || '';
      const lt1 = document.getElementById('lmTagId'); if (lt1) lt1.value = lead.tag_id || '';
      const lt2 = document.getElementById('lmTagId2'); if (lt2) lt2.value = lead.tag_id2 || '';
      renderModalTagChips();
      renderModalInfo(lead);
      _renderVincBadge(lead);
      document.getElementById('leadModal').classList.add('open');
      _lmLastCount = -1;
      document.getElementById('lmChatMsgs').innerHTML = '<div class="lm-chat-empty"><p style="color:#e2e8f0">Cargando…</p></div>';
      lmLoadChat(id);
      lmStartPolling(id);
    }
    function renderModalTagChips() {
      const el = document.getElementById('lmTagChips'); if (!el) return;
      const s1 = String(document.getElementById('lmTagId')?.value || '');
      const s2 = String(document.getElementById('lmTagId2')?.value || '');
      if (!S.tags.length) { el.innerHTML = '<span style="font-size:11px;color:#cbd5e1;">Sin tags</span>'; return; }
      el.innerHTML = S.tags.map(t => {
        const c = tagColor(t.id);
        const active = s1 === String(t.id) || s2 === String(t.id);
        return `<span class="tag-chip${active ? ' selected' : ''}"
            style="background:${c}20;color:${c};border-color:${active ? c : 'transparent'};"
            onclick="toggleModalTag(${t.id})">${esc(t.nombre)}</span>`;
      }).join('');
    }
    async function toggleModalTag(id) {
      const t1 = document.getElementById('lmTagId');
      const t2 = document.getElementById('lmTagId2');
      const s = String(id);
      if (t1.value === s) { t1.value = t2.value; t2.value = ''; }
      else if (t2.value === s) { t2.value = ''; }
      else if (!t1.value) { t1.value = s; }
      else if (!t2.value) { t2.value = s; }
      else { t1.value = t2.value; t2.value = s; }
      renderModalTagChips();
      if (!S.currentLeadId) return;
      const r = await apiJ('asignar_tag', { pipeline_id: S.currentLeadId, tag_id: t1.value || null, tag_id2: t2.value || null });
      if (r.success) {
        const lead = S.leads.find(l => l.id == S.currentLeadId);
        if (lead) {
          lead.tag_id = t1.value ? +t1.value : null;
          lead.tag_id2 = t2.value ? +t2.value : null;
          lead.tag_nombre = S.tags.find(t => t.id == lead.tag_id)?.nombre || null;
          lead.tag_nombre2 = S.tags.find(t => t.id == lead.tag_id2)?.nombre || null;
        }
        renderModalInfo(lead); render();
        showToast('Tags actualizados', 'ok');
      } else showToast(r.message || 'Error', 'err');
    }
    function syncModalBadge(estadoId, estadoName) {
      const b = document.getElementById('lmBadge');
      if (b) { b.textContent = estadoName || '—'; b.style.background = 'rgba(255,255,255,.22)'; b.style.color = '#fff'; }
    }
    function _igVal(id, val) {
      const el = document.getElementById(id); if (!el) return;
      el.textContent = val || '—';
      el.className = 'lm-ig-val' + (val ? '' : ' dim');
    }
    function renderModalInfo(l) {
      _igVal('lmIgDestino', l.destino ? esc(l.destino) : null);
      _igVal('lmIgBudget', l.budget ? '$' + Number(l.budget).toLocaleString('es-CO') : null);
      _igVal('lmIgPhone', l.telefono_cliente ? esc(l.telefono_cliente) : null);
      _igVal('lmIgViajeros', l.viajeros ? (l.viajeros + ' pax') : null);
      const fSal = l.fecha_salida ? fmtDate(l.fecha_salida) : null;
      const fLleg = l.fecha_llegada ? fmtDate(l.fecha_llegada) : null;
      _igVal('lmIgFechas', fSal ? (fSal + (fLleg ? ' → ' + fLleg : '')) : null);
      const notasRow = document.getElementById('lmIgNotasRow');
      const notasEl = document.getElementById('lmIgNotas');
      if (l.descripcion && notasRow && notasEl) { notasEl.textContent = l.descripcion; notasRow.style.display = ''; }
      else if (notasRow) { notasRow.style.display = 'none'; }
    }
    function closeLeadModal() {
      document.getElementById('leadModal').classList.remove('open');
      cancelLeadEdit();
      closeVincModal();
      _programasLoaded = false;
      S.currentLeadId = null;
      lmStopPolling();
      _lmEmailAccountId = 0;
      _lmLastCount = -1;
      lmClearComposer();
    }
    async function onModalMoverEstado(sel) {
      const newId = parseInt(sel.value); if (!newId || !S.currentLeadId) return;
      const lead = S.leads.find(l => l.id == S.currentLeadId); if (!lead || lead.estado_id == newId) return;
      const prevId = lead.estado_id;
      const prevNm = S.estados.find(e => e.id == prevId)?.nombre || '';
      const newNm = S.estados.find(e => e.id == newId)?.nombre || '';
      await moveLead(S.currentLeadId, newId, prevId, prevNm, newNm);
      syncModalBadge(newId, newNm);
    }
    async function onModalAsignarAsesor(sel) {
      const userId = sel.value; if (!S.currentLeadId) return;
      const fd = new FormData();
      fd.append('action', 'asignar_asesor'); fd.append('pipeline_id', S.currentLeadId); fd.append('usuario_id', userId);
      const r = await fetch(APP_URL + '/pipeline/api', { method: 'POST', body: fd });
      const d = await r.json().catch(() => ({ success: false }));
      if (d.success) {
        const lead = S.leads.find(l => l.id == S.currentLeadId);
        const asesor = S.agentes.find(a => a.id == userId);
        if (lead) { lead.usuario_id = userId ? +userId : null; lead.asesor_nombre = asesor?.username || null; }
        renderModalInfo(lead); render();
        showToast('Asesor asignado', 'ok');
      } else showToast(d.message || 'Error', 'err');
    }
    // ── LEAD MODAL — CHAT PANEL ──
    let _lmEmailAccountId = 0, _lmLastCount = -1, _lmPollTimer = null, _lmAttachedFiles = [], _lmTemplatesData = [];

    function lmStripQuoted(html) {
      const tmp = document.createElement('div'); tmp.innerHTML = html || '';
      tmp.querySelectorAll('.gmail_quote_container,.gmail_quote,.gmail_attr,blockquote').forEach(el => el.remove());
      return tmp.innerHTML.trim();
    }
    function lmFmtTime(ts) {
      if (!ts) return '';
      const d = new Date(String(ts).replace(' ', 'T'));
      if (isNaN(d.getTime())) return ts;
      return d.toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    }
    function lmRenderMessage(msg) {
      const div = document.createElement('div');
      div.className = 'lm-msg ' + (msg.direction === 'outbound' ? 'out' : 'in');
      const content = document.createElement('div');
      content.innerHTML = lmStripQuoted(msg.body || '');
      const time = document.createElement('span');
      time.className = 'lm-msg-time';
      time.textContent = lmFmtTime(msg.received_at);
      div.appendChild(content); div.appendChild(time);
      return div;
    }
    async function lmLoadChat(pipelineId) {
      const msgs = document.getElementById('lmChatMsgs');
      try {
        const r = await fetch(APP_URL + '/modules/gmail/chat_api.php?pipeline_id=' + pipelineId);
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const data = await r.json();
        document.getElementById('lmChatEmail').textContent = data.lead?.email_cliente || '—';
        _lmEmailAccountId = data.email_account_id || 0;
        const badge = document.getElementById('lmGmailBadge');
        const noGmail = document.getElementById('lmNoGmail');
        const sendBtn = document.getElementById('lmSendBtn');
        if (_lmEmailAccountId) {
          badge.textContent = 'Gmail activo'; badge.className = 'lm-gmail-badge ok';
          noGmail.classList.remove('show'); sendBtn.disabled = false;
        } else {
          badge.textContent = 'Sin Gmail'; badge.className = 'lm-gmail-badge nok';
          noGmail.classList.add('show'); sendBtn.disabled = true;
        }
        const all = [];
        if (data.origin) all.push({ direction: 'inbound', body: data.origin.body, received_at: data.origin.received_at });
        (data.messages || []).forEach(m => all.push(m));
        if (all.length === _lmLastCount) return;
        _lmLastCount = all.length;
        msgs.innerHTML = '';
        if (!all.length) { msgs.innerHTML = '<div class="lm-chat-empty"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p>Sin mensajes aún. Escribe el primero.</p></div>'; return; }
        all.forEach(m => msgs.appendChild(lmRenderMessage(m)));
        msgs.scrollTop = msgs.scrollHeight;
      } catch (e) { console.error('lmLoadChat', e); msgs.innerHTML = '<div class="lm-chat-empty"><p>Error cargando mensajes</p></div>'; }
    }
    function lmStartPolling(pid) { lmStopPolling(); _lmPollTimer = setInterval(() => { if (S.currentLeadId === pid) lmLoadChat(pid); }, 15000); }
    function lmStopPolling() { if (_lmPollTimer) { clearInterval(_lmPollTimer); _lmPollTimer = null; } }
    function lmApplyFormat(cmd) { document.getElementById('lmEditor').focus(); document.execCommand(cmd, false, null); }
    function lmGetContent() { return document.getElementById('lmEditor').innerHTML.trim(); }
    function lmClearComposer() {
      const ed = document.getElementById('lmEditor'); if (ed) ed.innerHTML = '';
      const att = document.getElementById('lmAttachments'); if (att) { att.innerHTML = ''; att.classList.remove('has-files'); }
      const noGmail = document.getElementById('lmNoGmail'); if (noGmail) noGmail.classList.remove('show');
      const sendBtn = document.getElementById('lmSendBtn'); if (sendBtn) sendBtn.disabled = false;
      _lmAttachedFiles = [];
    }
    // Límites de tamaño (Gmail tope ~25MB por correo)
    const LM_MAX_FILE_SIZE = 20 * 1024 * 1024;  // 20 MB por archivo
    const LM_MAX_TOTAL_SIZE = 25 * 1024 * 1024; // 25 MB en total
    function lmHandleFiles(files) {
      Array.from(files).forEach(f => {
        if (f.size > LM_MAX_FILE_SIZE) { showToast(`"${f.name}" supera 20 MB`, 'err'); return; }
        const totalActual = _lmAttachedFiles.reduce((s, x) => s + x.size, 0);
        if (totalActual + f.size > LM_MAX_TOTAL_SIZE) { showToast('Adjuntos superan 25 MB en total', 'err'); return; }
        _lmAttachedFiles.push(f);
        const chip = document.createElement('div'); chip.className = 'lm-att-chip';
        chip.innerHTML = `<span>${esc(f.name)}</span><button class="lm-att-rm" onclick="lmRemoveFile(this,'${esc(f.name)}')">&times;</button>`;
        document.getElementById('lmAttachments').appendChild(chip);
      });
      document.getElementById('lmAttachments').classList.toggle('has-files', _lmAttachedFiles.length > 0);
    }
    function lmRemoveFile(btn, name) {
      _lmAttachedFiles = _lmAttachedFiles.filter(f => f.name !== name);
      btn.closest('.lm-att-chip').remove();
      document.getElementById('lmAttachments').classList.toggle('has-files', _lmAttachedFiles.length > 0);
    }
    async function lmEnviarMensaje() {
      const msg = lmGetContent();
      if (!msg) { showToast('El mensaje no puede estar vacío', 'err'); return; }
      if (!_lmEmailAccountId) { showToast('No hay cuenta Gmail activa', 'err'); return; }
      const btn = document.getElementById('lmSendBtn');
      const orig = btn.innerHTML; btn.innerHTML = 'Enviando…'; btn.disabled = true;
      try {
        let r;
        if (_lmAttachedFiles.length > 0) {
          // Con adjuntos: multipart/form-data (sin Content-Type manual; el navegador pone el boundary)
          const fd = new FormData();
          fd.append('pipeline_id', S.currentLeadId);
          fd.append('message_body', msg);
          fd.append('email_account_id', _lmEmailAccountId);
          _lmAttachedFiles.forEach(f => fd.append('attachments[]', f, f.name));
          r = await fetch(APP_URL + '/modules/gmail/chat_api.php?action=send', { method: 'POST', body: fd });
        } else {
          r = await fetch(APP_URL + '/modules/gmail/chat_api.php?action=send', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pipeline_id: S.currentLeadId, message_body: msg, email_account_id: _lmEmailAccountId })
          });
        }
        const d = await r.json();
        if (r.ok && d.success) { lmClearComposer(); _lmLastCount = -1; await lmLoadChat(S.currentLeadId); showToast('Mensaje enviado', 'ok'); }
        else showToast(d.error || 'Error al enviar', 'err');
      } catch (e) { console.error(e); showToast('Error de red al enviar', 'err'); }
      finally { btn.innerHTML = orig; btn.disabled = false; }
    }
    function lmAbrirTemplates() {
      document.getElementById('lmTplOverlay').classList.add('active');
      document.getElementById('lmTplSearch').value = '';
      document.getElementById('lmTplForm').style.display = 'none';
      lmLlamarTemplates();
    }
    function lmCerrarTemplates(e) {
      if (e && e.target !== document.getElementById('lmTplOverlay')) return;
      document.getElementById('lmTplOverlay').classList.remove('active');
    }
    async function lmLlamarTemplates() {
      try {
        const r = await fetch(APP_URL + '/pipeline/api?action=get_templates');
        const d = await r.json();
        _lmTemplatesData = d.data || [];
        lmRenderTemplates(_lmTemplatesData);
      } catch (e) { console.error(e); }
    }
    function lmRenderTemplates(lista) {
      const el = document.getElementById('lmTplList'); el.innerHTML = '';
      if (!lista.length) { el.innerHTML = '<div class="lm-tpl-empty">No hay templates todavía. Crea el primero.</div>'; return; }
      lista.forEach(t => {
        const row = document.createElement('div'); row.className = 'lm-tpl-row';
        const name = document.createElement('div'); name.className = 'lm-tpl-row-name'; name.textContent = t.nombre; name.title = 'Insertar en el mensaje'; name.onclick = () => lmUsarTemplate(t);
        const acts = document.createElement('div'); acts.className = 'lm-tpl-row-actions';
        const editBtn = document.createElement('button'); editBtn.className = 'lm-tpl-icon-btn edit'; editBtn.title = 'Editar';
        editBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
        editBtn.onclick = (e) => { e.stopPropagation(); lmAbrirFormTemplate(t); };
        const delBtn = document.createElement('button'); delBtn.className = 'lm-tpl-icon-btn del'; delBtn.title = 'Eliminar';
        delBtn.innerHTML = '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>';
        delBtn.onclick = (e) => { e.stopPropagation(); lmEliminarTemplate(t); };
        acts.appendChild(editBtn); acts.appendChild(delBtn);
        row.appendChild(name); row.appendChild(acts); el.appendChild(row);
      });
    }
    function lmFiltrarTemplates() { const q = document.getElementById('lmTplSearch').value.toLowerCase(); lmRenderTemplates(_lmTemplatesData.filter(t => (t.nombre || '').toLowerCase().includes(q))); }
    function lmUsarTemplate(t) { const ed = document.getElementById('lmEditor'); ed.innerHTML = t.texto || ''; ed.focus(); document.getElementById('lmTplOverlay').classList.remove('active'); }
    function lmAbrirFormTemplate(t) {
      const f = document.getElementById('lmTplForm');
      const isEdit = t && t.id;
      document.getElementById('lmTplFormTitle').textContent = isEdit ? 'Editar template' : 'Nuevo template';
      document.getElementById('lmTplEditId').value = isEdit ? t.id : '';
      document.getElementById('lmTplNombre').value = isEdit ? t.nombre : '';
      document.getElementById('lmTplTexto').value = isEdit ? t.texto : '';
      f.style.display = 'flex';
      document.getElementById('lmTplNombre').focus();
    }
    function lmCancelarFormTemplate() {
      const f = document.getElementById('lmTplForm'); f.style.display = 'none';
      document.getElementById('lmTplNombre').value = ''; document.getElementById('lmTplTexto').value = ''; document.getElementById('lmTplEditId').value = '';
    }
    async function lmGuardarTemplate() {
      const nombre = document.getElementById('lmTplNombre').value.trim();
      const texto = document.getElementById('lmTplTexto').value.trim();
      const editId = document.getElementById('lmTplEditId').value;
      if (!nombre || !texto) { showToast('Nombre y contenido requeridos', 'err'); return; }
      const fd = new FormData(); fd.append('nombre', nombre); fd.append('texto', texto);
      const action = editId ? 'update_template' : 'crear_template';
      if (editId) fd.append('id', editId);
      try {
        const r = await fetch(APP_URL + '/pipeline/api?action=' + action, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) { lmCancelarFormTemplate(); lmLlamarTemplates(); showToast(editId ? 'Template actualizado' : 'Template guardado', 'ok'); }
        else showToast(d.message || 'Error', 'err');
      } catch (e) { console.error(e); }
    }
    async function lmEliminarTemplate(t) {
      if (!confirm(`¿Eliminar el template "${t.nombre}"?`)) return;
      const fd = new FormData(); fd.append('id', t.id);
      try {
        const r = await fetch(APP_URL + '/pipeline/api?action=delete_template', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) { lmLlamarTemplates(); showToast('Template eliminado', 'ok'); }
        else showToast(d.message || 'Error', 'err');
      } catch (e) { console.error(e); }
    }

    // ── VIEW SWITCH ──
    function switchView(v) {
      S.view = v;
      document.getElementById('btnKanban').classList.toggle('active', v === 'kanban');
      document.getElementById('btnList').classList.toggle('active', v === 'list');
      document.getElementById('boardWrap').style.display = v === 'kanban' ? '' : 'none';
      document.getElementById('listWrap').style.display = v === 'list' ? 'block' : 'none';
      render();
    }

    // ── NEW LEAD MODAL ──
    function openNewLead() {
      document.getElementById('newLeadModal').classList.add('open');
      setTimeout(initPhone, 50);
      document.getElementById('fNombre')?.focus();
    }
    function closeNewLead() {
      document.getElementById('newLeadModal').classList.remove('open');
      ['fNombre', 'fEmail', 'fDestino', 'fFechaSalida', 'fFechaLlegada', 'fSource', 'fDesc'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
      if (phoneInstance) { phoneInstance.setNumber(''); }
      document.getElementById('fViajeros').value = '1';
      document.getElementById('fBudget').value = '';
      document.getElementById('fTagId').value = '';
      document.getElementById('fTagId2').value = '';
      renderTagChips();
    }
    async function submitNewLead() {
      const nombre = document.getElementById('fNombre')?.value.trim();
      const email = document.getElementById('fEmail')?.value.trim();
      const destino = document.getElementById('fDestino')?.value.trim();
      const fecha = document.getElementById('fFechaSalida')?.value;
      const estadoId = document.getElementById('fEstado')?.value;
      if (!nombre) return showToast('El nombre es obligatorio', 'err');
      if (!email) return showToast('El email es obligatorio', 'err');
      if (!destino) return showToast('El destino es obligatorio', 'err');
      if (!fecha) return showToast('La fecha de salida es obligatoria', 'err');
      if (!estadoId) return showToast('Selecciona un estado inicial', 'err');
      const btn = document.getElementById('btnSubmit');
      btn.disabled = true; btn.textContent = 'Creando…';
      const fd = new FormData();
      fd.append('action', 'crear_lead');
      fd.append('nombre_cliente', nombre);
      fd.append('email_cliente', email);
      fd.append('telefono_cliente', getPhone());
      fd.append('destino', destino);
      fd.append('fecha_salida', fecha);
      fd.append('fecha_llegada', document.getElementById('fFechaLlegada')?.value || '');
      fd.append('viajeros', document.getElementById('fViajeros')?.value || 1);
      fd.append('budget', document.getElementById('fBudget')?.value || '');
      fd.append('estado_id', estadoId);
      fd.append('source', document.getElementById('fSource')?.value.trim() || '');
      fd.append('descripcion', document.getElementById('fDesc')?.value.trim() || '');
      const fA = document.getElementById('fAsesor'); if (fA) fd.append('usuario_id', fA.value || '');
      fd.append('tag_id', document.getElementById('fTagId')?.value || '');
      fd.append('tag_id2', document.getElementById('fTagId2')?.value || '');
      const r = await fetch(APP_URL + '/pipeline/api', { method: 'POST', body: fd });
      const d = await r.json().catch(() => ({ success: false }));
      btn.disabled = false; btn.textContent = 'Crear Lead';
      if (d.success) { closeNewLead(); showToast('Lead creado', 'ok'); await reloadLeads(); }
      else showToast(d.message || 'Error al crear', 'err');
    }

    // ── CONFIG MODAL (ESTADOS + TAGS) ──
    function openConfig() {
      renderEstList();
      renderTagMgr();
      renderSourceMgr();
      document.getElementById('cfgModal')?.classList.add('open');
    }
    function closeConfig() { document.getElementById('cfgModal')?.classList.remove('open'); }

    function renderEstList() {
      const el = document.getElementById('estList'); if (!el) return;
      el.innerHTML = S.estados.map(est => {
        const c = est.color || '#6366f1';
        return `<div class="est-row" data-id="${est.id}" draggable="true"
            ondragstart="estDragStart(event,${est.id})"
            ondragover="estDragOver(event)"
            ondragleave="estDragLeave(event)"
            ondrop="estDrop(event,${est.id})">
            <span class="est-drag" style="cursor:grab;">⠿</span>
            <div class="est-color">
                <div class="est-color-preview" style="background:${c};"></div>
                <input type="color" value="${c}" oninput="onEstColorInput(this,${est.id})" onchange="saveEstado(${est.id})">
            </div>
            <input class="est-name-inp" type="text" value="${esc(est.nombre)}" placeholder="Nombre del estado"
                data-orig="${esc(est.nombre)}" onchange="saveEstado(${est.id})" oninput="markEstDirty(this)">
            <button class="est-save-btn" onclick="saveEstado(${est.id})">Guardar</button>
            <button class="est-del-btn" onclick="deleteEstado(${est.id})" title="Eliminar">
                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
        </div>`;
      }).join('');
    }
    function onEstColorInput(input, id) {
      // Update preview live
      const preview = input.parentElement.querySelector('.est-color-preview');
      if (preview) preview.style.background = input.value;
      // Update in state so board re-renders live
      const est = S.estados.find(e => e.id == id);
      if (est) est.color = input.value;
      render();
    }
    function markEstDirty(inp) { inp.classList.add('dirty'); }
    async function saveEstado(id) {
      const row = document.querySelector(`.est-row[data-id="${id}"]`); if (!row) return;
      const nombre = row.querySelector('.est-name-inp').value.trim();
      const color = row.querySelector('input[type=color]').value;
      if (!nombre) return showToast('El nombre no puede estar vacío', 'err');
      const r = await apiJ('update_estados', { id, nombre, color });
      if (r.success) {
        const est = S.estados.find(e => e.id == id);
        if (est) { est.nombre = nombre; est.color = color; }
        fillSelects(); render();
        showToast(`"${esc(nombre)}" guardado`, 'ok');
      } else showToast(r.message || 'Error', 'err');
    }
    async function deleteEstado(id) {
      const confirmed = await showConfirmModal({ title: 'Eliminar estado', message: '¿Seguro que deseas eliminar este estado? Solo se puede si no tiene leads asignados.', icon: '🗑️', confirmText: 'Eliminar', confirmButtonStyle: 'danger' });
      if (!confirmed) return;
      const r = await apiJ('delete_estados', { id });
      if (r.success) {
        S.estados = S.estados.filter(e => e.id != id);
        renderEstList(); fillSelects(); render();
        showToast('Estado eliminado', 'ok');
      } else showToast(r.message || 'No se puede eliminar: tiene leads asignados', 'err');
    }
    function addEstado() {
      // Insert a new blank row at the bottom, let user fill name and color then save
      const list = document.getElementById('estList');
      const tempDiv = document.createElement('div');
      tempDiv.className = 'est-row';
      tempDiv.innerHTML = `
        <span class="est-drag">⠿</span>
        <div class="est-color">
            <div class="est-color-preview" style="background:#6366f1;"></div>
            <input type="color" value="#6366f1" oninput="this.parentElement.querySelector('.est-color-preview').style.background=this.value">
        </div>
        <input class="est-name-inp" type="text" placeholder="Nombre del estado…" id="newEstNombre">
        <button class="est-save-btn" style="opacity:1;" onclick="saveNewEstado(this)">Guardar</button>`;
      list.appendChild(tempDiv);
      tempDiv.querySelector('#newEstNombre')?.focus();
    }
    async function saveNewEstado(btn) {
      const row = btn.closest('.est-row');
      const nombre = row.querySelector('.est-name-inp').value.trim();
      const color = row.querySelector('input[type=color]').value;
      if (!nombre) return showToast('Escribe un nombre para el estado', 'err');
      btn.disabled = true; btn.textContent = '…';
      const r = await apiJ('save_estados', { nombre, color, descripcion: '', es_final: 0 });
      if (r.success) {
        const [rE] = await Promise.all([api('get_estados')]);
        S.estados = rE.success ? rE.data : S.estados;
        renderEstList(); fillSelects(); render();
        showToast(`"${esc(nombre)}" creado`, 'ok');
      } else { btn.disabled = false; btn.textContent = 'Guardar'; showToast(r.message || 'Error', 'err'); }
    }

    function renderTagMgr() {
      const el = document.getElementById('tagMgrList'); if (!el) return;
      if (!S.tags.length) { el.innerHTML = '<span style="font-size:12px;color:#94a3b8;">Sin tags aún</span>'; return; }
      el.innerHTML = S.tags.map(t => {
        const c = tagColor(t.id);
        return `<span class="tag-mgr-chip" style="background:${c}20;color:${c};">
            <span id="tag-lbl-${t.id}">${esc(t.nombre)}</span>
            <button class="tag-mgr-del" title="Renombrar" onclick="renameTagInline(${t.id})" style="background:rgba(0,0,0,.1);margin-right:2px;">✎</button>
            <button class="tag-mgr-del" onclick="deleteTag(${t.id})">✕</button>
        </span>`;
      }).join('');
    }
    async function addTag() {
      const inp = document.getElementById('tagNewInp');
      const nombre = inp?.value.trim();
      if (!nombre) return showToast('Escribe un nombre para el tag', 'err');
      const r = await apiJ('save_tags', { nombre });
      if (r.success) {
        inp.value = '';
        const rT = await api('get_tags');
        S.tags = rT.success ? rT.data : S.tags;
        renderTagMgr(); fillSelects();
        showToast(`Tag "${esc(nombre)}" creado`, 'ok');
      } else showToast(r.message || 'Error', 'err');
    }
    async function deleteTag(id) {
      const r = await apiJ('delete_tags', { id });
      if (r.success) {
        S.tags = S.tags.filter(t => t.id != id);
        renderTagMgr(); fillSelects(); render();
        showToast('Tag eliminado', 'ok');
      } else showToast(r.message || 'Error', 'err');
    }

    function renderSourceMgr() {
      const el = document.getElementById('sourceMgrList'); if (!el) return;
      if (!S.source.length) { el.innerHTML = '<span style="font-size:12px;color:#94a3b8;">Sin orígenes aún</span>'; return; }
      el.innerHTML = S.source.map(s => {
        return `<span class="tag-mgr-chip" style="background:#64748b20;color:#475569;">
            <span id="src-lbl-${s.id}">${esc(s.nombre)}</span>
            <button class="tag-mgr-del" title="Renombrar" onclick="renameSourceInline(${s.id})" style="background:rgba(0,0,0,.1);margin-right:2px;">✎</button>
            <button class="tag-mgr-del" onclick="deleteSource(${s.id})">✕</button>
        </span>`;
      }).join('');
    }
    async function addSource() {
      const inp = document.getElementById('sourceNewInp');
      const nombre = inp?.value.trim();
      if (!nombre) return showToast('Escribe un nombre para el origen', 'err');
      const r = await apiJ('save_source', { nombre });
      if (r.success) {
        inp.value = '';
        const rS = await api('get_source');
        S.source = rS.success ? rS.data : S.source;
        renderSourceMgr(); fillSelects();
        showToast(`Origen "${esc(nombre)}" creado`, 'ok');
      } else showToast(r.message || 'Error', 'err');
    }
    async function deleteSource(id) {
      const r = await apiJ('delete_source', { id });
      if (r.success) {
        S.source = S.source.filter(s => s.id != id);
        renderSourceMgr(); fillSelects(); render();
        showToast('Origen eliminado', 'ok');
      } else showToast(r.message || 'Error', 'err');
    }
    function renameSourceInline(id) {
      const src = S.source.find(s => s.id == id); if (!src) return;
      const lbl = document.getElementById('src-lbl-' + id); if (!lbl) return;
      const oldName = src.nombre;
      const inp = document.createElement('input');
      inp.value = oldName;
      inp.style.cssText = 'border:none;background:transparent;color:inherit;font-size:12.5px;font-weight:600;width:90px;outline:none;font-family:inherit;';
      lbl.replaceWith(inp); inp.focus(); inp.select();
      async function save() {
        const newName = inp.value.trim();
        if (!newName || newName === oldName) { renderSourceMgr(); return; }
        const r = await apiJ('update_source', { id, nombre: newName });
        if (r.success) {
          src.nombre = newName;
          fillSelects(); render();
          showToast(`Origen renombrado a "${esc(newName)}"`, 'ok');
        } else showToast(r.message || 'Error', 'err');
        renderSourceMgr();
      }
      inp.addEventListener('blur', save);
      inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); save(); } if (e.key === 'Escape') renderSourceMgr(); });
    }

    // ── TOAST ──
    function showToast(msg, type = 'ok', dur = 3500) {
      const wrap = document.getElementById('toastWrap');
      const t = document.createElement('div');
      t.className = 'toast ' + type; t.innerHTML = msg;
      wrap.appendChild(t);
      setTimeout(() => t.remove(), dur);
    }
    function showToastUndo(msg, fn, dur = 4500) {
      const wrap = document.getElementById('toastWrap');
      const t = document.createElement('div');
      t.className = 'toast ok'; t.innerHTML = msg;
      const btn = document.createElement('button');
      btn.className = 'toast-undo'; btn.textContent = 'Deshacer';
      t.appendChild(btn);
      wrap.appendChild(t);
      const timer = setTimeout(() => t.remove(), dur);
      btn.onclick = () => { clearTimeout(timer); t.remove(); fn(); };
    }

    // ── HELPERS ──
    function esc(s) { if (!s) return ''; return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function initials(n) { if (!n) return '?'; const p = n.trim().split(/\s+/); return p.length >= 2 ? (p[0][0] + p[1][0]).toUpperCase() : n.substring(0, 2).toUpperCase(); }
    function fmtDate(d) { if (!d) return ''; const [y, m, day] = d.split('-'); return `${day}/${m}/${y}`; }
    function fmtDT(dt) { if (!dt) return ''; const d = new Date(dt); return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }); }

    // ── EDITAR LEAD ──
    function openLeadEdit() {
      const lead = S.leads.find(l => l.id == S.currentLeadId); if (!lead) return;
      document.getElementById('leNombre').value = lead.nombre_cliente || '';
      document.getElementById('leEmail').value = lead.email_cliente || '';
      document.getElementById('leTel').value = lead.telefono_cliente || '';
      document.getElementById('leDestino').value = lead.destino || '';
      document.getElementById('leFecSal').value = lead.fecha_salida || '';
      document.getElementById('leFecLleg').value = lead.fecha_llegada || '';
      document.getElementById('leViajeros').value = lead.viajeros || 1;
      document.getElementById('leBudget').value = lead.budget || '';
      document.getElementById('leSource').value = lead.source || '';
      document.getElementById('leDesc').value = lead.descripcion || '';
      document.getElementById('lmInfoSection').style.display = 'none';
      document.getElementById('lmEditForm').classList.add('active');
      document.getElementById('btnEditLead').style.display = 'none';
      document.getElementById('leNombre').focus();
    }
    function cancelLeadEdit() {
      document.getElementById('lmInfoSection').style.display = '';
      document.getElementById('lmEditForm').classList.remove('active');
      document.getElementById('btnEditLead').style.display = '';
    }
    async function saveLeadEdit() {
      const nombre = document.getElementById('leNombre').value.trim();
      const email = document.getElementById('leEmail').value.trim();
      const destino = document.getElementById('leDestino').value.trim();
      if (!nombre) return showToast('El nombre es obligatorio', 'err');
      if (!email || !email.includes('@')) return showToast('Email inválido', 'err');
      if (!destino) return showToast('El destino es obligatorio', 'err');
      const btn = document.getElementById('btnSaveEdit');
      btn.disabled = true; btn.textContent = 'Guardando…';
      const r = await apiJ('editar_lead', {
        pipeline_id: S.currentLeadId,
        nombre_cliente: nombre, email_cliente: email,
        telefono_cliente: document.getElementById('leTel').value.trim(),
        destino,
        fecha_salida: document.getElementById('leFecSal').value,
        fecha_llegada: document.getElementById('leFecLleg').value,
        viajeros: document.getElementById('leViajeros').value,
        budget: document.getElementById('leBudget').value,
        source: document.getElementById('leSource').value.trim(),
        descripcion: document.getElementById('leDesc').value.trim(),
      });
      btn.disabled = false; btn.textContent = 'Guardar cambios';
      if (r.success) {
        const lead = S.leads.find(l => l.id == S.currentLeadId);
        if (lead) {
          lead.nombre_cliente = nombre; lead.email_cliente = email;
          lead.telefono_cliente = document.getElementById('leTel').value.trim();
          lead.destino = destino;
          lead.fecha_salida = document.getElementById('leFecSal').value;
          lead.fecha_llegada = document.getElementById('leFecLleg').value;
          lead.viajeros = document.getElementById('leViajeros').value;
          lead.budget = document.getElementById('leBudget').value;
          lead.source = document.getElementById('leSource').value.trim();
          lead.descripcion = document.getElementById('leDesc').value.trim();
        }
        document.getElementById('lmName').textContent = nombre;
        document.getElementById('lmEmail').textContent = email;
        document.getElementById('lmAvatar').textContent = initials(nombre);
        cancelLeadEdit();
        renderModalInfo(lead); render();
        showToast('Lead actualizado', 'ok');
      } else showToast(r.message || 'Error al guardar', 'err');
    }

    // ── VINCULAR ITINERARIO ──
    let _programasLoaded = false, _plantillasLoaded = false, _vincMode = null;
    let _vincProgramasData = [], _vincPlantillasData = [], _selectedVincProgram = null;

    function openVincModal() {
      document.getElementById('vincModal').style.display = 'flex';
      vincShowView('main');
    }
    function closeVincModal() {
      document.getElementById('vincModal').style.display = 'none';
      _vincMode = null;
    }
    function vincShowView(view) {
      ['vincViewMain', 'vincViewExistente', 'vincViewClonar', 'vincViewPlantilla'].forEach(id => {
        const el = document.getElementById(id);
        el.style.display = 'none';
      });
      document.getElementById('vincFooter').style.display = 'none';
      document.getElementById('vincConfirmBtn').style.display = '';
      if (view === 'main') {
        document.getElementById('vincViewMain').style.display = 'flex';
        document.getElementById('vincTitle').textContent = 'Vincular itinerario';
        document.getElementById('vincSubtitle').textContent = '¿Cómo quieres asociar el itinerario a este lead?';
        _vincMode = null;
      } else if (view === 'existente') {
        document.getElementById('vincViewExistente').style.display = 'flex';
        document.getElementById('vincFooter').style.display = 'flex';
        document.getElementById('vincConfirmBtn').disabled = true;
        document.getElementById('vincTitle').textContent = 'Seleccionar programa';
        document.getElementById('vincSubtitle').textContent = 'Elige un programa ya creado para vincular';
        _selectedVincProgram = null;
        const si = document.getElementById('vincProgSearch'); if (si) si.value = '';
        _vincMode = 'existente';
      } else if (view === 'clonar') {
        document.getElementById('vincViewClonar').style.display = 'flex';
        document.getElementById('vincFooter').style.display = 'flex';
        document.getElementById('vincConfirmBtn').style.display = 'none';
        document.getElementById('vincTitle').textContent = 'Clonar programa';
        document.getElementById('vincSubtitle').textContent = 'Elige un programa como base — se creará una copia';
        _vincMode = 'clonar';
      } else if (view === 'plantilla') {
        document.getElementById('vincViewPlantilla').style.display = 'flex';
        document.getElementById('vincFooter').style.display = 'flex';
        document.getElementById('vincConfirmBtn').disabled = true;
        document.getElementById('vincTitle').textContent = 'Desde plantilla';
        document.getElementById('vincSubtitle').textContent = 'Elige una plantilla para crear el itinerario';
        _selectedVincProgram = null;
        const sp = document.getElementById('vincPlantSearch'); if (sp) sp.value = '';
        _vincMode = 'plantilla';
      }
    }
    function vincBack() { vincShowView('main'); }

    async function vincSelectOption(opt) {
      if (opt === 'scratch') {
        closeVincModal();
        window.location.href = APP_URL + '/programa?pipeline_id=' + S.currentLeadId;
        return;
      }
      vincShowView(opt);
      if (opt === 'existente') {
        if (!_programasLoaded) {
          _programasLoaded = true;
          const grid = document.getElementById('vincProgGrid');
          if (grid) grid.innerHTML = '<div class="vinc-pempty">Cargando programas…</div>';
          try {
            const r = await api('get_programas');
            if (r.success && r.data) {
              _vincProgramasData = r.data;
              _renderVincPicker('existente');
            } else {
              if (grid) grid.innerHTML = `<div class="vinc-pempty">⚠ ${esc(r.message || 'Error')}</div>`;
              _programasLoaded = false;
            }
          } catch (e) {
            const g = document.getElementById('vincProgGrid');
            if (g) g.innerHTML = '<div class="vinc-pempty">Error de red</div>';
            _programasLoaded = false;
          }
        } else {
          _renderVincPicker('existente');
        }
      }
      if (opt === 'clonar') await _loadCloneList();
      if (opt === 'plantilla') await _loadPlantillasList();
    }

    async function _loadCloneList() {
      const cont = document.getElementById('vincCloneList');
      cont.innerHTML = '<div style="text-align:center;padding:20px;color:#64748b;font-size:13px;">Cargando…</div>';
      try {
        const r = await api('get_programas');
        if (r.success && r.data && r.data.length) {
          cont.innerHTML = r.data.map(p => `
                <div class="vinc-clone-row" onclick="_confirmarClone(${p.id},this)">
                    <div>
                        <div class="vinc-clone-name">${esc(p.nombre || 'Sin nombre')}</div>
                        <div class="vinc-clone-meta">${esc(p.destino || '')}${p.fecha_salida ? ' · ' + fmtDate(p.fecha_salida) : ''}</div>
                    </div>
                    <svg style="width:15px;height:15px;stroke:rgba(var(--pr-rgb),.5);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                </div>`).join('');
        } else {
          cont.innerHTML = '<div style="text-align:center;padding:20px;color:#64748b;font-size:13px;">No tienes programas disponibles</div>';
        }
      } catch (e) { cont.innerHTML = '<div style="text-align:center;padding:20px;color:#ef4444;font-size:13px;">Error al cargar</div>'; }
    }

    async function _loadPlantillasList() {
      if (_plantillasLoaded) { _renderVincPicker('plantilla'); return; }
      _plantillasLoaded = true;
      const grid = document.getElementById('vincPlantGrid');
      if (grid) grid.innerHTML = '<div class="vinc-pempty">Cargando plantillas…</div>';
      try {
        const r = await fetch(APP_URL + '/programa/api?action=list_plantillas').then(x => x.json());
        if (r.success && r.data) {
          _vincPlantillasData = r.data;
          _renderVincPicker('plantilla');
        } else if (!r.success) {
          if (grid) grid.innerHTML = `<div class="vinc-pempty">⚠ ${esc(r.message || 'Error')}</div>`;
          _plantillasLoaded = false;
        } else {
          if (grid) grid.innerHTML = '<div class="vinc-pempty">No hay plantillas creadas aún</div>';
        }
      } catch (e) {
        if (grid) grid.innerHTML = '<div class="vinc-pempty">Error de red</div>';
        _plantillasLoaded = false;
      }
    }

    function _renderVincPicker(tipo) {
      const isPlant = tipo === 'plantilla';
      const data = isPlant ? _vincPlantillasData : _vincProgramasData;
      const gridId = isPlant ? 'vincPlantGrid' : 'vincProgGrid';
      const searchId = isPlant ? 'vincPlantSearch' : 'vincProgSearch';
      const grid = document.getElementById(gridId);
      if (!grid) return;
      const term = ((document.getElementById(searchId) || {}).value || '').toLowerCase().trim();
      const filtered = term ? data.filter(p => {
        const fields = [p.titulo_programa, p.nombre, p.destino, 'Viaje a ' + (p.destino || '')];
        return fields.some(f => f && f.toLowerCase().includes(term));
      }) : data;
      if (!filtered.length) { grid.innerHTML = '<div class="vinc-pempty">No se encontraron programas</div>'; return; }
      grid.innerHTML = filtered.map(p => {
        const titulo = p.titulo_programa || p.nombre || 'Sin nombre';
        const isSel = _selectedVincProgram && _selectedVincProgram.id == p.id;
        const parts = [p.destino, p.total_dias_real ? p.total_dias_real + ' días' : '', p.fecha_salida ? fmtDate(p.fecha_salida) : ''].filter(Boolean);
        return `<div class="vinc-pcard${isSel ? ' sel' : ''}" onclick="_selectVincProgram(${p.id},'${tipo}')">
            <div class="vinc-ptitle">${esc(titulo)}</div>
            <div class="vinc-pmeta">${esc(parts.join(' · '))}</div>
        </div>`;
      }).join('');
    }
    function _selectVincProgram(id, tipo) {
      const data = tipo === 'plantilla' ? _vincPlantillasData : _vincProgramasData;
      _selectedVincProgram = data.find(p => p.id == id) || null;
      const btn = document.getElementById('vincConfirmBtn');
      if (btn) btn.disabled = !_selectedVincProgram;
      _renderVincPicker(tipo);
    }

    async function _confirmarClone(programaId, rowEl) {
      rowEl.style.opacity = '.5'; rowEl.style.pointerEvents = 'none';
      try {
        const fd = new FormData();
        fd.append('action', 'duplicate_programa');
        fd.append('programa_id', programaId);
        const cloneR = await fetch(APP_URL + '/programa/api', { method: 'POST', body: fd }).then(x => x.json());
        if (!cloneR.success) { showToast(cloneR.error || cloneR.message || 'Error al clonar', 'err'); rowEl.style.opacity = ''; rowEl.style.pointerEvents = ''; return; }
        const linkR = await apiJ('asignar_itinerario', { pipeline_id: S.currentLeadId, solicitud_id: cloneR.new_programa_id });
        if (linkR.success) {
          _afterVincular(cloneR.new_programa_id, cloneR.new_title || 'Copia de programa');
          showToast('Programa clonado y vinculado', 'ok');
        } else { showToast(linkR.message || 'Error al vincular', 'err'); rowEl.style.opacity = ''; rowEl.style.pointerEvents = ''; }
      } catch (e) { showToast('Error de red', 'err'); rowEl.style.opacity = ''; rowEl.style.pointerEvents = ''; }
    }

    function _renderVincBadge(lead) {
      const tieneItinerario = !!(lead && lead.solicitud_id);
      const badge = document.getElementById('lmVincBadge');
      if (badge && tieneItinerario) {
        badge.textContent = '✓ ' + (lead.itinerario_titulo || 'Programa vinculado');
      }
      // La fila (badge verde + botón de vista previa) solo se muestra si hay itinerario vinculado.
      const row = document.getElementById('lmVincBadgeRow');
      if (row) row.style.display = tieneItinerario ? 'flex' : 'none';
    }

    // Inserta el link de vista previa del itinerario vinculado en el editor del chat.
    function lmInsertarLinkVistaPrevia() {
      const lead = S.leads.find(l => l.id == S.currentLeadId);
      if (!lead || !lead.solicitud_id) { showToast('Este lead no tiene un itinerario vinculado', 'err'); return; }
      const url = APP_URL + '/itinerary?id=' + lead.solicitud_id + '&public=1';
      const editor = document.getElementById('lmEditor');
      if (!editor) return;
      editor.focus();
      // Insertar el URL como TEXTO PLANO (sin etiquetas <a>); Gmail lo vuelve enlace al mostrarlo.
      const actual = editor.innerHTML.trim();
      if (actual && !actual.endsWith('<br>')) editor.appendChild(document.createElement('br'));
      editor.appendChild(document.createTextNode(url + ' '));
      const range = document.createRange(); range.selectNodeContents(editor); range.collapse(false);
      const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(range);
    }

    function _afterVincular(solicitud_id, label) {
      closeVincModal();
      const lead = S.leads.find(l => l.id == S.currentLeadId);
      if (lead) { lead.solicitud_id = solicitud_id; lead.itinerario_titulo = label; }
      _renderVincBadge(lead);
    }

    async function confirmarVincular() {
      if (_vincMode === 'existente') {
        if (!_selectedVincProgram) return showToast('Selecciona un programa', 'err');
        const r = await apiJ('asignar_itinerario', { pipeline_id: S.currentLeadId, solicitud_id: _selectedVincProgram.id });
        if (r.success) { _afterVincular(_selectedVincProgram.id, _selectedVincProgram.titulo_programa || _selectedVincProgram.nombre || 'Programa'); showToast('Itinerario vinculado', 'ok'); }
        else showToast(r.message || 'Error', 'err');
      } else if (_vincMode === 'plantilla') {
        if (!_selectedVincProgram) return showToast('Selecciona una plantilla', 'err');
        const btn = document.getElementById('vincConfirmBtn');
        btn.disabled = true; btn.textContent = 'Creando…';
        try {
          const fd = new FormData();
          fd.append('action', 'duplicate_programa');
          fd.append('programa_id', _selectedVincProgram.id);
          const cloneR = await fetch(APP_URL + '/programa/api', { method: 'POST', body: fd }).then(x => x.json());
          if (!cloneR.success) { showToast(cloneR.error || cloneR.message || 'Error al crear', 'err'); btn.disabled = false; btn.textContent = 'Vincular'; return; }
          const linkR = await apiJ('asignar_itinerario', { pipeline_id: S.currentLeadId, solicitud_id: cloneR.new_programa_id });
          if (linkR.success) { _afterVincular(cloneR.new_programa_id, cloneR.new_title || _selectedVincProgram.titulo_programa || _selectedVincProgram.nombre || 'Programa'); showToast('Programa creado desde plantilla y vinculado', 'ok'); }
          else { showToast(linkR.message || 'Error al vincular', 'err'); btn.disabled = false; btn.textContent = 'Vincular'; }
        } catch (e) { showToast('Error de red', 'err'); btn.disabled = false; btn.textContent = 'Vincular'; }
      }
    }

    // ── REORDENAR ESTADOS (drag-and-drop en config) ──
    let _estDragId = null;
    function estDragStart(e, id) { _estDragId = id; e.dataTransfer.effectAllowed = 'move'; e.currentTarget.style.opacity = '.4'; }
    function estDragOver(e) { e.preventDefault(); e.currentTarget.style.background = 'rgba(var(--pr-rgb),.06)'; e.currentTarget.style.borderColor = 'rgba(var(--pr-rgb),.3)'; }
    function estDragLeave(e) { e.currentTarget.style.background = ''; e.currentTarget.style.borderColor = ''; }
    async function estDrop(e, targetId) {
      e.preventDefault();
      document.querySelectorAll('.est-row').forEach(r => { r.style.opacity = ''; r.style.background = ''; r.style.borderColor = ''; });
      if (_estDragId === null || _estDragId === targetId) { _estDragId = null; return; }
      const srcIdx = S.estados.findIndex(s => s.id == _estDragId);
      const tgtIdx = S.estados.findIndex(s => s.id == targetId);
      if (srcIdx < 0 || tgtIdx < 0) { _estDragId = null; return; }
      const moved = S.estados.splice(srcIdx, 1)[0];
      S.estados.splice(tgtIdx, 0, moved);
      renderEstList();
      const orden = S.estados.map(s => s.id);
      const r = await apiJ('reordenar_estados', { orden });
      if (r.success) { fillSelects(); render(); showToast('Orden guardado', 'ok'); }
      else { showToast(r.message || 'Error al reordenar', 'err'); const rE = await api('get_estados'); S.estados = rE.success ? rE.data : S.estados; renderEstList(); }
      _estDragId = null;
    }

    // ── RENOMBRAR TAG ──
    function renameTagInline(id) {
      const tag = S.tags.find(t => t.id == id); if (!tag) return;
      const lbl = document.getElementById('tag-lbl-' + id); if (!lbl) return;
      const oldName = tag.nombre;
      const inp = document.createElement('input');
      inp.value = oldName;
      inp.style.cssText = 'border:none;background:transparent;color:inherit;font-size:12.5px;font-weight:600;width:80px;outline:none;font-family:inherit;';
      lbl.replaceWith(inp); inp.focus(); inp.select();
      async function save() {
        const newName = inp.value.trim();
        if (!newName || newName === oldName) { renderTagMgr(); return; }
        const r = await apiJ('update_tags', { id, nombre: newName });
        if (r.success) {
          tag.nombre = newName;
          S.leads.forEach(l => {
            if (l.tag_id == id) l.tag_nombre = newName;
            if (l.tag_id2 == id) l.tag_nombre2 = newName;
          });
          fillSelects(); render();
          showToast(`Tag renombrado a "${newName}"`, 'ok');
        } else showToast(r.message || 'Error', 'err');
        renderTagMgr();
      }
      inp.addEventListener('blur', save);
      inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); save(); } if (e.key === 'Escape') renderTagMgr(); });
    }

    // ── KEYBOARD & CLICK OUTSIDE ──
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLeadModal(); closeNewLead(); closeConfig(); } });
    document.getElementById('leadModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeLeadModal(); });
    document.getElementById('newLeadModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeNewLead(); });
    document.getElementById('cfgModal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeConfig(); });

    // ── INIT ──
    loadAll();
  </script>
</body>

</html>