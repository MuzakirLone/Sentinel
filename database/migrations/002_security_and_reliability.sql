-- ═══════════════════════════════════════════════════════════
-- API Nonces (Replay Attack Protection)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS api_nonces (
    nonce VARCHAR(128) PRIMARY KEY,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- Failed Jobs (Dead Letter Queue)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS failed_jobs (
    id SERIAL PRIMARY KEY,
    payload JSONB NOT NULL,
    exception_message TEXT,
    failed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
