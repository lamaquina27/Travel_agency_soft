<?php

/**
 * Gmail Worker — TravelSoft
 *
 * Descarga correos nuevos de todas las cuentas Gmail activas,
 * evalúa reglas y crea leads automáticamente en el pipeline.
 *
 * Usos:
 *   CLI:  php modules/gmail/worker.php
 *   HTTP: GET /gmail/worker?secret=WORKER_SECRET
 *
 * Cron (cPanel cada 5 min):
 *   *\/5 * * * * curl -s "https://dominio.com/gmail/worker?secret=TOKEN" >> /dev/null
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$isCli = (php_sapi_name() === 'cli');

// Paths absolutos para que funcione tanto desde CLI como HTTP
$root = dirname(__DIR__, 2);

// Cargar .env antes de cualquier include, para que config/gmail.php
// encuentre GMAIL_CLIENT_ID etc. en $_ENV desde el primer require_once.
$_envFile = $root . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k); $_v = trim($_v);
        if (!isset($_ENV[$_k])) {
            $_ENV[$_k] = $_v;
            putenv("$_k=$_v");
        }
    }
}
unset($_envFile, $_line, $_k, $_v);

require_once $root . '/config/database.php';
require_once $root . '/config/app.php';
require_once __DIR__ . '/EmailSyncService.php';
require_once __DIR__ . '/RuleEngine.php';

// ── Autenticación ─────────────────────────────────────────────────────────────
if (!$isCli) {
    App::init();
    $secret          = $_GET['secret'] ?? '';
    $configuredSecret = $_ENV['WORKER_SECRET'] ?? getenv('WORKER_SECRET') ?? '';

    if (empty($configuredSecret) || $secret !== $configuredSecret) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// ── Logger ────────────────────────────────────────────────────────────────────
$logDir  = $root . '/storage/logs';
$logFile = $logDir . '/email_worker.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function wlog(string $level, string $message): void {
    global $logFile, $isCli;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper(str_pad($level, 11)) . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    if ($isCli) echo $line;
}

// ── Main ──────────────────────────────────────────────────────────────────────
wlog('WORKER', 'START ─────────────────────────────');

try {
    $syncService = new EmailSyncService();
    $ruleEngine  = new RuleEngine();

    $accounts = $syncService->getActiveAccounts();

    if (empty($accounts)) {
        wlog('WORKER', 'No active Gmail accounts found');
        wlog('WORKER', 'END ───────────────────────────────');
        exit(0);
    }

    wlog('WORKER', 'Accounts to sync: ' . count($accounts));

    $totalNew     = 0;
    $totalLeads   = 0;
    $totalSkipped = 0;
    $totalErrors  = 0;

    foreach ($accounts as $account) {
        $accountEmail = $account['email'];
        $accountId    = (int) $account['id'];
        $agencyId     = (int) $account['agencia_id'];

        wlog('SYNC START', "account=$accountEmail agency_id=$agencyId");

        try {
            // 1. Sincronizar correos nuevos
            $syncResult = $syncService->syncAccount($accountId);

            $newCount     = $syncResult['new_messages'];
            $skippedCount = $syncResult['skipped'];
            $newDbIds     = $syncResult['new_message_ids'];   // IDs reales de esta pasada
            $totalNew    += $newCount;
            $totalSkipped += $skippedCount;

            wlog('SYNC END', "account=$accountEmail new=$newCount skipped=$skippedCount history_id={$syncResult['new_history_id']}");

            if ($newCount === 0) continue;

            // 2. Cargar solo los mensajes descargados en ESTA ejecución
            $db = Database::getInstance();
            if (empty($newDbIds)) continue;

            $placeholders = implode(',', array_fill(0, count($newDbIds), '?'));
            $newMessages  = $db->fetchAll(
                "SELECT id, from_email, subject
                 FROM email_messages
                 WHERE id IN ($placeholders)
                   AND message_type = 'lead'
                 ORDER BY received_at DESC",
                $newDbIds
            );

            // 3. Pasar cada mensaje por el motor de reglas
            foreach ($newMessages as $msg) {
                wlog('EMAIL NEW', "msg_id={$msg['id']} from={$msg['from_email']} subject=\"{$msg['subject']}\"");

                try {
                    $result = $ruleEngine->process($msg['id'], $agencyId, $accountId);

                    switch ($result['action']) {
                        case 'lead_created':
                            wlog('LEAD CREATED', "pipeline_id={$result['lead_id']} {$result['detail']}");
                            $totalLeads++;
                            break;

                        case 'lead_existing':
                            wlog('LEAD LINKED', "pipeline_id={$result['lead_id']} {$result['detail']}");
                            break;

                        case 'ignored':
                            wlog('EMAIL SKIP', "msg_id={$msg['id']} reason=rule:ignore {$result['detail']}");
                            break;

                        case 'parse_error':
                            wlog('PARSE ERROR', "msg_id={$msg['id']} {$result['detail']}");
                            break;

                        case 'no_action':
                            wlog('EMAIL SKIP', "msg_id={$msg['id']} reason={$result['detail']}");
                            break;
                    }

                } catch (\Exception $e) {
                    wlog('ERROR', "msg_id={$msg['id']} " . $e->getMessage());
                    $totalErrors++;
                }
            }

        } catch (\Exception $e) {
            wlog('ERROR', "account=$accountEmail " . $e->getMessage());
            $totalErrors++;
        }
    }

    wlog('SUMMARY', "new_emails=$totalNew leads_created=$totalLeads skipped=$totalSkipped errors=$totalErrors");

} catch (\Exception $e) {
    wlog('FATAL', $e->getMessage());
    exit(1);
}

wlog('WORKER', 'END ───────────────────────────────');
exit(0);
