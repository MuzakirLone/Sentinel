-- ═══════════════════════════════════════════════════════════
-- Idempotency Keys (Exactly-Once Event Processing)
-- ═══════════════════════════════════════════════════════════
ALTER TABLE events ADD COLUMN idempotency_key VARCHAR(128) UNIQUE;
