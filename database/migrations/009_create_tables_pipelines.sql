-- ============================================================
-- 1. ESTADOS  (columnas Kanban customizables por agencia)
-- ============================================================
CREATE TABLE IF NOT EXISTS pipeline_estados (
    id            int(11)    NOT NULL AUTO_INCREMENT,
    agencia_id    int(11)    NOT NULL,
    nombre        VARCHAR(100)      NOT NULL,
    descripcion   TEXT                  NULL,
    posicion      SMALLINT  NOT NULL DEFAULT 0,
    created_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_estados_agencia
        FOREIGN KEY (agencia_id) REFERENCES agencias (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_estado_posicion (agencia_id, posicion),
    INDEX idx_estados_agencia (agencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ============================================================
-- 2. TAGS
-- ============================================================
CREATE TABLE IF NOT EXISTS tags (
    id            int(11)  NOT NULL AUTO_INCREMENT,
    agencia_id    int(11)  NOT NULL,
    nombre        VARCHAR(80)     NOT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_tags_agencia
        FOREIGN KEY (agencia_id) REFERENCES agencias (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_tag_nombre (agencia_id, nombre),
    INDEX idx_tags_agencia (agencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. PIPELINE
-- ============================================================
CREATE TABLE IF NOT EXISTS pipeline (
    id                            int(11)   NOT NULL AUTO_INCREMENT,
    agencia_id                    int(11)   NOT NULL,
    usuario_id                    int(11)       NULL ,
    solicitud_id                  int(11)       NULL,
    estado_id                     int(11)   NOT NULL,
    tag_id                        int(11)       NULL,
    -- Datos del cliente
    nombre_cliente                   VARCHAR(150)     NOT NULL,
    email_cliente                  VARCHAR(254)     NOT NULL,
    telefono_cliente                  VARCHAR(30)          NULL,
    -- Datos del viaje
    destino                       VARCHAR(150)     NOT NULL,
    descripcion                   TEXT                 NULL,
    viajeros                    TINYINT  NOT NULL DEFAULT 1,
    fecha_salida                  DATE             NOT NULL,
    fecha_llegada                 DATE                 NULL,
    budget                        DECIMAL(12,2)        NULL,
    -- Origen
    source                        VARCHAR(80)          NULL,
    created_from_email_message_id int(11)       NULL ,
    created_at                    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_pipeline_agencia
        FOREIGN KEY (agencia_id)   REFERENCES agencias    (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pipeline_usuario
        FOREIGN KEY (usuario_id)   REFERENCES users    (id) ON DELETE SET NULL  ON UPDATE CASCADE,
    CONSTRAINT fk_pipeline_solicitud
        FOREIGN KEY (solicitud_id) REFERENCES programa_solicitudes (id) ON DELETE SET NULL  ON UPDATE CASCADE,
    CONSTRAINT fk_pipeline_estado
        FOREIGN KEY (estado_id)    REFERENCES pipeline_estados     (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pipeline_tag
        FOREIGN KEY (tag_id)       REFERENCES tags        (id) ON DELETE SET NULL  ON UPDATE CASCADE,
    INDEX idx_pipeline_agencia      (agencia_id),
    INDEX idx_pipeline_estado       (estado_id),
    INDEX idx_pipeline_usuario      (usuario_id),
    INDEX idx_pipeline_email (email_cliente),
    INDEX idx_pipeline_fecha_salida (fecha_salida),
    INDEX idx_pipeline_created_at   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ============================================================
-- 4. TEMPLATE_MENSAJE
-- ============================================================
CREATE TABLE IF NOT EXISTS template_mensaje (
    id            int(11)  NOT NULL AUTO_INCREMENT,
    agencia_id    int(11)  NOT NULL,
    nombre        VARCHAR(150)    NOT NULL,
    texto         TEXT            NOT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_template_agencia
        FOREIGN KEY (agencia_id) REFERENCES agencias (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_template_agencia (agencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ============================================================
-- 5. EMAIL_ACCOUNTS
-- ============================================================
CREATE TABLE IF NOT EXISTS email_accounts (
    id              int(11)    NOT NULL AUTO_INCREMENT,
    user_id         int(11)    NOT NULL,
    provider        ENUM('gmail','outlook','imap') NOT NULL DEFAULT 'imap',
    email           VARCHAR(254)      NOT NULL,
    access_token    TEXT                  NULL ,
    refresh_token   TEXT                  NULL ,
    imap_host       VARCHAR(150)          NULL,
    imap_port       SMALLINT      NULL,
    smtp_host       VARCHAR(150)          NULL,
    smtp_port       SMALLINT      NULL,
    status          ENUM('active','inactive','error') NOT NULL DEFAULT 'active',
    es_principal    TINYINT(1)        NOT NULL DEFAULT 0,
    last_synced_at  TIMESTAMP             NULL,
    created_at      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_email_accounts_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_email_account (user_id, email),
    INDEX idx_email_accounts_user   (user_id),
    INDEX idx_email_accounts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ============================================================
-- 6. EMAIL_RULES
-- ============================================================
CREATE TABLE IF NOT EXISTS email_rules (
    id                  int(11)    NOT NULL AUTO_INCREMENT,
    agency_id           int(11)    NOT NULL,
    email_account_id    int(11)    NOT NULL,
    nombre                VARCHAR(150)      NOT NULL,
    condition_type      ENUM('all','any') NOT NULL DEFAULT 'all',
    condition_field     ENUM('from','to','subject','body','has_attachment') NOT NULL,
    operator            ENUM('contains','not_contains','equals','starts_with','ends_with') NOT NULL,
    value               VARCHAR(255)      NOT NULL,
    action_type         ENUM('assign_status','assign_user','add_tag','create_lead','ignore') NOT NULL,
    pipeline_estado_id  int(11)        NULL,
    usuario_asignado_id    int(11)        NULL,
    tag                 VARCHAR(80)           NULL,
    is_active           TINYINT(1)        NOT NULL DEFAULT 1,
    priority            SMALLINT  NOT NULL DEFAULT 0 ,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_email_rules_agency
        FOREIGN KEY (agency_id)          REFERENCES agencias       (id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_email_rules_account
        FOREIGN KEY (email_account_id)   REFERENCES email_accounts (id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_email_rules_status
        FOREIGN KEY (pipeline_estado_id) REFERENCES pipeline_estados        (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_email_rules_user
        FOREIGN KEY (usuario_asignado_id)   REFERENCES users       (id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_email_rules_agency  (agency_id),
    INDEX idx_email_rules_account (email_account_id),
    INDEX idx_email_rules_active  (is_active, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ============================================================
-- 7. EMAIL_MESSAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS email_messages (
    id                  int(11)   NOT NULL AUTO_INCREMENT,
    agency_id           int(11)   NOT NULL,
    email_account_id    int(11)   NOT NULL,
    pipeline_id    int(11)       NULL,
    provider_message_id VARCHAR(255)         NULL ,
    thread_id           VARCHAR(255)         NULL,
    from_email          VARCHAR(254)     NOT NULL,
    to_email            VARCHAR(254)     NOT NULL,
    subject             VARCHAR(998)         NULL,
    body                LONGTEXT             NULL,
    direction           ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
    received_at         TIMESTAMP            NULL,
    created_at          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_email_messages_agency
        FOREIGN KEY (agency_id)        REFERENCES agencias       (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_email_messages_account
        FOREIGN KEY (email_account_id) REFERENCES email_accounts (id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_email_messages_lead
        FOREIGN KEY (pipeline_id) REFERENCES pipeline       (id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uq_provider_message  (email_account_id, provider_message_id),
    INDEX idx_email_messages_agency   (agency_id),
    INDEX idx_email_messages_pipeline     (pipeline_id),
    INDEX idx_email_messages_thread   (thread_id),
    INDEX idx_email_messages_from     (from_email),
    INDEX idx_email_messages_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ============================================================
-- FK CIRCULAR: pipeline -> email_messages
-- Se agrega después para evitar dependencia circular
-- ============================================================
ALTER TABLE pipeline
    ADD CONSTRAINT fk_pipeline_email_msg
        FOREIGN KEY (created_from_email_message_id)
        REFERENCES email_messages (id)
        ON DELETE SET NULL ON UPDATE CASCADE;
