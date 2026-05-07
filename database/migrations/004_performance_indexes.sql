-- Indexing heavily filtered core querying paths
CREATE INDEX IF NOT EXISTS idx_events_created_at ON events (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_events_user_id ON events (user_id);
CREATE INDEX IF NOT EXISTS idx_events_ip_address_id ON events (ip_address_id);
CREATE INDEX IF NOT EXISTS idx_events_event_type ON events (event_type);

CREATE INDEX IF NOT EXISTS idx_api_keys_active ON api_keys (is_active);

-- Composite indices serving the Dashboard Controller explicitly
-- Index for faster review queue polling
CREATE INDEX IF NOT EXISTS idx_review_queue_status ON review_queue (status) WHERE status = 'pending';
CREATE INDEX IF NOT EXISTS idx_audit_log_entity ON audit_trail (entity_type, entity_id);
