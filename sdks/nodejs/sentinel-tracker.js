/**
 * Sentinel Node.js Tracker SDK
 *
 * Lightweight SDK for sending security events to your Sentinel instance.
 * Zero external dependencies — uses native Node.js http/https modules.
 *
 * Usage:
 *   const SentinelTracker = require('./sentinel-tracker');
 *   const tracker = new SentinelTracker('http://localhost:8585', 'sk_your_api_key');
 *   tracker.track('login_success', { userId: 'usr_12345', email: 'user@example.com' });
 */

const http = require('http');
const https = require('https');
const crypto = require('crypto');
const { URL } = require('url');

class SentinelTracker {
    /**
     * @param {string} baseUrl - Sentinel instance URL
     * @param {string} apiKey - API key for authentication
     * @param {object} options - Configuration options
     */
    constructor(baseUrl, apiKey, options = {}) {
        this.baseUrl = baseUrl.replace(/\/+$/, '');
        this.apiKey = apiKey;
        this.apiSecret = options.apiSecret || null;
        this.timeout = options.timeout || 5000;
        this.batchSize = options.batchSize || 50;
        this._queue = [];
        this._flushTimer = null;

        // Auto-flush interval
        if (options.autoFlushMs) {
            this._flushTimer = setInterval(() => this.flush(), options.autoFlushMs);
        }
    }

    /**
     * Track a single event.
     * @param {string} eventType
     * @param {object} data
     * @returns {Promise<object>}
     */
    async track(eventType, data = {}) {
        const payload = { ...this._snakeCaseKeys(data), event_type: eventType };
        return this._post('/api/v1/events', payload);
    }

    /**
     * Add event to batch queue.
     * @param {string} eventType
     * @param {object} data
     */
    queue(eventType, data = {}) {
        this._queue.push({ ...this._snakeCaseKeys(data), event_type: eventType });
        if (this._queue.length >= this.batchSize) {
            this.flush();
        }
    }

    /**
     * Send all queued events.
     * @returns {Promise<object>}
     */
    async flush() {
        if (this._queue.length === 0) return null;
        const events = [...this._queue];
        this._queue = [];
        return this._post('/api/v1/events/batch', { events });
    }

    /**
     * Check if a user/IP is blacklisted.
     * @param {object} params - { userId, ip, email }
     * @returns {Promise<object>}
     */
    async checkBlacklist(params) {
        return this._post('/api/v1/blacklist/check', this._snakeCaseKeys(params));
    }

    /**
     * Track a login event.
     */
    async trackLogin(userId, success = true, extra = {}) {
        const eventType = success ? 'login_success' : 'login_failed';
        return this.track(eventType, { userId, ...extra });
    }

    /**
     * Track a signup event.
     */
    async trackSignup(userId, email, extra = {}) {
        return this.track('signup', { userId, email, ...extra });
    }

    /**
     * Track a field change for audit trail.
     */
    async trackFieldChange(userId, entityType, entityId, field, oldValue, newValue, extra = {}) {
        return this.track('field_change', {
            userId,
            fieldChanges: [{
                entity_type: entityType,
                entity_id: entityId,
                field,
                old_value: String(oldValue),
                new_value: String(newValue),
            }],
            ...extra,
        });
    }

    /**
     * Express.js middleware for automatic request tracking.
     * @param {object} options
     * @returns {Function} Express middleware
     */
    expressMiddleware(options = {}) {
        const getUserId = options.getUserId || ((req) => req.user?.id || req.session?.userId);
        const getEmail = options.getEmail || ((req) => req.user?.email);

        return (req, res, next) => {
            res.on('finish', () => {
                const userId = getUserId(req);
                if (userId) {
                    this.queue('page_view', {
                        userId: String(userId),
                        email: getEmail(req),
                        ip: req.headers['x-forwarded-for']?.split(',')[0]?.trim() || req.ip,
                        userAgent: req.headers['user-agent'],
                        url: req.originalUrl,
                        httpMethod: req.method,
                    });
                }
            });
            next();
        };
    }

    /**
     * Cleanup resources.
     */
    async destroy() {
        if (this._flushTimer) {
            clearInterval(this._flushTimer);
        }
        await this.flush();
    }

    // ── Private ─────────────────────────────────────────

    _post(endpoint, data) {
        return new Promise((resolve, reject) => {
            const url = new URL(this.baseUrl + endpoint);
            const payload = JSON.stringify(data);
            const isHttps = url.protocol === 'https:';

            const options = {
                hostname: url.hostname,
                port: url.port || (isHttps ? 443 : 80),
                path: url.pathname,
                method: 'POST',
                timeout: this.timeout,
                headers: {
                    'Content-Type': 'application/json',
                    'Content-Length': Buffer.byteLength(payload),
                    'X-API-Key': this.apiKey,
                },
            };

            // HMAC-SHA256 request signing
            if (this.apiSecret) {
                const timestamp = Math.floor(Date.now() / 1000).toString();
                const bodyHash = crypto.createHash('sha256').update(payload).digest('hex');
                const signPayload = [timestamp, 'POST', endpoint, bodyHash].join('\n');
                const signature = crypto.createHmac('sha256', this.apiSecret)
                    .update(signPayload).digest('hex');

                options.headers['X-Timestamp'] = timestamp;
                options.headers['X-Signature'] = signature;
            }

            const lib = isHttps ? https : http;
            const req = lib.request(options, (res) => {
                let body = '';
                res.on('data', (chunk) => body += chunk);
                res.on('end', () => {
                    try {
                        resolve(JSON.parse(body));
                    } catch {
                        resolve({ raw: body, statusCode: res.statusCode });
                    }
                });
            });

            req.on('error', (err) => {
                console.error('[Sentinel SDK]', err.message);
                resolve(null); // Don't reject — fail silently
            });

            req.on('timeout', () => {
                req.destroy();
                console.error('[Sentinel SDK] Request timeout');
                resolve(null);
            });

            req.write(payload);
            req.end();
        });
    }

    _snakeCaseKeys(obj) {
        if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return obj;
        const result = {};
        for (const [key, value] of Object.entries(obj)) {
            const snakeKey = key.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
            result[snakeKey] = value;
        }
        return result;
    }
}

module.exports = SentinelTracker;
