-- MIGRACIÓN 014: Agrega message_type para diferenciar entre chat o pipeline

ALTER TABLE email_messages
  ADD COLUMN message_type ENUM('lead','chat') NOT NULL DEFAULT 'lead';