-- Sentinel Database Schema
-- PostgreSQL 12+

-- ═══════════════════════════════════════════════════════════
-- Admin Users (dashboard access)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS admin_users (
    id              SERIAL PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    display_name    VARCHAR(100) NOT NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    last_login_at   TIMESTAMP WITH TIME ZONE,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- API Keys (for event ingestion authentication)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS api_keys (
    id              SERIAL PRIMARY KEY,
    key_hash        VARCHAR(255) NOT NULL UNIQUE,
    key_prefix      VARCHAR(20) NOT NULL,
    label           VARCHAR(100) NOT NULL,
    admin_user_id   INTEGER REFERENCES admin_users(id) ON DELETE CASCADE,
    api_secret      VARCHAR(255),
    is_active       BOOLEAN DEFAULT TRUE,
    last_used_at    TIMESTAMP WITH TIME ZONE,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_api_keys_hash ON api_keys(key_hash);

-- ═══════════════════════════════════════════════════════════
-- Tracked Users (users from YOUR application)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS users (
    id              SERIAL PRIMARY KEY,
    external_id     VARCHAR(255) NOT NULL UNIQUE,
    email           VARCHAR(255),
    username        VARCHAR(255),
    phone           VARCHAR(50),
    first_seen_at   TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    last_seen_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    total_events    INTEGER DEFAULT 0,
    status          VARCHAR(20) DEFAULT 'active',
    risk_score      REAL DEFAULT 0.0,
    risk_level      VARCHAR(20) DEFAULT 'low',
    country         VARCHAR(2),
    metadata        JSONB DEFAULT '{}',
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_users_external_id ON users(external_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_risk_score ON users(risk_score DESC);
CREATE INDEX idx_users_last_seen ON users(last_seen_at DESC);

-- ═══════════════════════════════════════════════════════════
-- IP Addresses
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS ip_addresses (
    id              SERIAL PRIMARY KEY,
    ip_address      VARCHAR(45) NOT NULL UNIQUE,
    country         VARCHAR(2),
    city            VARCHAR(100),
    region          VARCHAR(100),
    latitude        REAL,
    longitude       REAL,
    isp             VARCHAR(255),
    is_tor          BOOLEAN DEFAULT FALSE,
    is_vpn          BOOLEAN DEFAULT FALSE,
    is_proxy        BOOLEAN DEFAULT FALSE,
    is_datacenter   BOOLEAN DEFAULT FALSE,
    threat_score    REAL DEFAULT 0.0,
    first_seen_at   TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    last_seen_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_ip_address ON ip_addresses(ip_address);

-- ═══════════════════════════════════════════════════════════
-- Devices (browser/OS fingerprints)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS devices (
    id              SERIAL PRIMARY KEY,
    fingerprint     VARCHAR(64) NOT NULL UNIQUE,
    user_agent      TEXT,
    browser         VARCHAR(100),
    browser_version VARCHAR(50),
    os              VARCHAR(100),
    os_version      VARCHAR(50),
    device_type     VARCHAR(50),
    is_bot          BOOLEAN DEFAULT FALSE,
    first_seen_at   TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    last_seen_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_devices_fingerprint ON devices(fingerprint);

-- ═══════════════════════════════════════════════════════════
-- Sessions
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS sessions (
    id              SERIAL PRIMARY KEY,
    session_id      VARCHAR(128) NOT NULL UNIQUE,
    user_id         INTEGER REFERENCES users(id) ON DELETE CASCADE,
    ip_address_id   INTEGER REFERENCES ip_addresses(id),
    device_id       INTEGER REFERENCES devices(id),
    started_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    last_activity   TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    event_count     INTEGER DEFAULT 0,
    is_suspicious   BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_sessions_user ON sessions(user_id);
CREATE INDEX idx_sessions_session_id ON sessions(session_id);

-- ═══════════════════════════════════════════════════════════
-- Events (the core event log)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS events (
    id              SERIAL PRIMARY KEY,
    event_type      VARCHAR(50) NOT NULL,
    user_id         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    session_id      INTEGER REFERENCES sessions(id) ON DELETE SET NULL,
    ip_address_id   INTEGER REFERENCES ip_addresses(id),
    device_id       INTEGER REFERENCES devices(id),
    url             TEXT,
    http_method     VARCHAR(10),
    risk_score      REAL DEFAULT 0.0,
    risk_flags      TEXT[] DEFAULT '{}',
    metadata        JSONB DEFAULT '{}',
    processed       BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_events_type ON events(event_type);
CREATE INDEX idx_events_user ON events(user_id);
CREATE INDEX idx_events_created ON events(created_at DESC);
CREATE INDEX idx_events_risk ON events(risk_score DESC);
CREATE INDEX idx_events_processed ON events(processed);

-- ═══════════════════════════════════════════════════════════
-- Email Domains (reputation tracking)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS email_domains (
    id              SERIAL PRIMARY KEY,
    domain          VARCHAR(255) NOT NULL UNIQUE,
    is_disposable   BOOLEAN DEFAULT FALSE,
    is_free         BOOLEAN DEFAULT FALSE,
    user_count      INTEGER DEFAULT 0,
    risk_score      REAL DEFAULT 0.0,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_email_domains_domain ON email_domains(domain);

-- ═══════════════════════════════════════════════════════════
-- Rules (risk engine configuration)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS rules (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    slug            VARCHAR(100) NOT NULL UNIQUE,
    description     TEXT,
    category        VARCHAR(50) NOT NULL,
    weight          REAL DEFAULT 1.0,
    is_enabled      BOOLEAN DEFAULT TRUE,
    config          JSONB DEFAULT '{}',
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert default rules
INSERT INTO rules (name, slug, description, category, weight, is_enabled, config) VALUES
('Account Takeover', 'account_takeover', 'Detects potential account takeover attempts through unusual login patterns, new device/IP combinations, and credential changes.', 'authentication', 1.5, TRUE, '{"max_new_ips_per_day": 5, "max_new_devices_per_day": 3}'),
('Credential Stuffing', 'credential_stuffing', 'Identifies patterns of rapid failed login attempts across multiple accounts from the same IP.', 'authentication', 2.0, TRUE, '{"max_failed_logins_per_hour": 10, "max_accounts_per_ip": 5}'),
('Bot Detection', 'bot_detection', 'Detects automated bot activity through user-agent analysis, request timing, and behavioral patterns.', 'automation', 1.5, TRUE, '{"suspicious_ua_patterns": ["bot", "crawler", "spider", "headless"]}'),
('Content Spam', 'content_spam', 'Identifies high-frequency content posting and suspicious content patterns.', 'content', 1.0, TRUE, '{"max_posts_per_hour": 20, "min_post_interval_seconds": 5}'),
('Multi-Accounting', 'multi_accounting', 'Detects multiple accounts created from the same IP address or device.', 'identity', 1.0, TRUE, '{"max_accounts_per_ip": 3, "max_accounts_per_device": 2}'),
('Dormant Account', 'dormant_account', 'Flags sudden activity from accounts inactive for extended periods.', 'behavior', 0.8, TRUE, '{"dormant_days": 90}'),
('High-Risk Region', 'high_risk_region', 'Flags logins from TOR exit nodes, VPNs, proxies, or sanctioned regions.', 'geo', 1.2, TRUE, '{"flagged_countries": ["KP", "IR", "SY"]}'),
('Promo Abuse', 'promo_abuse', 'Detects repeated promotional code usage and referral fraud patterns.', 'fraud', 1.0, TRUE, '{"max_promos_per_user": 3}'),
('Insider Threat', 'insider_threat', 'Monitors admin and privileged user access for unusual patterns.', 'access', 1.8, TRUE, '{"sensitive_actions": ["admin_login", "data_export", "user_delete"]}'),
('Brute Force', 'brute_force', 'Detects excessive failed authentication attempts from a single source.', 'authentication', 2.0, TRUE, '{"max_attempts_per_minute": 5, "lockout_duration_minutes": 30}')
ON CONFLICT (slug) DO NOTHING;

-- ═══════════════════════════════════════════════════════════
-- Rule Results (per-event evaluation)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS rule_results (
    id              SERIAL PRIMARY KEY,
    event_id        INTEGER REFERENCES events(id) ON DELETE CASCADE,
    rule_id         INTEGER REFERENCES rules(id) ON DELETE CASCADE,
    user_id         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    score           REAL DEFAULT 0.0,
    triggered       BOOLEAN DEFAULT FALSE,
    details         JSONB DEFAULT '{}',
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_rule_results_event ON rule_results(event_id);
CREATE INDEX idx_rule_results_user ON rule_results(user_id);
CREATE INDEX idx_rule_results_triggered ON rule_results(triggered);

-- ═══════════════════════════════════════════════════════════
-- Risk Scores (aggregated per-user)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS risk_scores (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE UNIQUE,
    overall_score   REAL DEFAULT 0.0,
    auth_score      REAL DEFAULT 0.0,
    behavior_score  REAL DEFAULT 0.0,
    identity_score  REAL DEFAULT 0.0,
    geo_score       REAL DEFAULT 0.0,
    risk_level      VARCHAR(20) DEFAULT 'low',
    factors         JSONB DEFAULT '[]',
    calculated_at   TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_risk_scores_user ON risk_scores(user_id);
CREATE INDEX idx_risk_scores_level ON risk_scores(risk_level);

-- ═══════════════════════════════════════════════════════════
-- Review Queue
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS review_queue (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reason          TEXT NOT NULL,
    risk_score      REAL DEFAULT 0.0,
    status          VARCHAR(20) DEFAULT 'pending',
    priority        VARCHAR(20) DEFAULT 'medium',
    triggered_rules TEXT[] DEFAULT '{}',
    reviewed_by     INTEGER REFERENCES admin_users(id),
    reviewed_at     TIMESTAMP WITH TIME ZONE,
    action_taken    VARCHAR(50),
    notes           TEXT,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_review_queue_status ON review_queue(status);
CREATE INDEX idx_review_queue_priority ON review_queue(priority);
CREATE INDEX idx_review_queue_user ON review_queue(user_id);

-- ═══════════════════════════════════════════════════════════
-- Audit Trail (field-level changes)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS audit_trail (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    entity_type     VARCHAR(50) NOT NULL,
    entity_id       INTEGER NOT NULL,
    field_name      VARCHAR(100) NOT NULL,
    old_value       TEXT,
    new_value       TEXT,
    changed_by      VARCHAR(255),
    ip_address      VARCHAR(45),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_audit_trail_user ON audit_trail(user_id);
CREATE INDEX idx_audit_trail_entity ON audit_trail(entity_type, entity_id);
CREATE INDEX idx_audit_trail_created ON audit_trail(created_at DESC);

-- ═══════════════════════════════════════════════════════════
-- Suspicious Patterns (lookup tables)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS suspicious_patterns (
    id              SERIAL PRIMARY KEY,
    pattern_type    VARCHAR(50) NOT NULL,
    pattern_value   TEXT NOT NULL,
    description     TEXT,
    severity        VARCHAR(20) DEFAULT 'medium',
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert common suspicious patterns
INSERT INTO suspicious_patterns (pattern_type, pattern_value, description, severity) VALUES
('user_agent', 'sqlmap', 'SQL injection scanning tool', 'critical'),
('user_agent', 'nikto', 'Web server scanner', 'critical'),
('user_agent', 'nmap', 'Network scanner', 'high'),
('user_agent', 'masscan', 'Mass port scanner', 'high'),
('user_agent', 'gobuster', 'Directory brute forcer', 'high'),
('user_agent', 'dirbuster', 'Directory brute forcer', 'high'),
('user_agent', 'hydra', 'Password brute forcer', 'critical'),
('url_pattern', 'UNION SELECT', 'SQL injection attempt', 'critical'),
('url_pattern', '<script>', 'XSS attempt', 'critical'),
('url_pattern', '../', 'Path traversal attempt', 'high'),
('url_pattern', 'etc/passwd', 'File inclusion attempt', 'critical'),
('url_pattern', 'cmd=', 'Command injection attempt', 'critical'),
('email_pattern', 'mailinator.com', 'Disposable email provider', 'medium'),
('email_pattern', 'tempmail.com', 'Disposable email provider', 'medium'),
('email_pattern', 'guerrillamail.com', 'Disposable email provider', 'medium'),
('email_pattern', 'throwaway.email', 'Disposable email provider', 'medium')
ON CONFLICT DO NOTHING;
