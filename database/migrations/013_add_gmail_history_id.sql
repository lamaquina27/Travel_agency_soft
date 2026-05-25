-- MIGRACIÓN 013: Agrega historyId de Gmail para sincronización incremental

ALTER TABLE email_accounts
    ADD COLUMN gmail_history_id VARCHAR(30) NULL DEFAULT NULL
    COMMENT 'Último ID de Mail procesado (sync incremental)'
    AFTER last_synced_at;
