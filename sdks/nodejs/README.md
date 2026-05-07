# Sentinel Node.js Tracker

Lightweight Node.js SDK for sending security events to your Sentinel instance. Zero external dependencies.

## Installation

Copy `sentinel-tracker.js` to your project, or:

```bash
npm install sentinel-tracker
```

## Quick Start

```javascript
const SentinelTracker = require('./sentinel-tracker');

const tracker = new SentinelTracker('http://localhost:8585', 'sk_your_api_key');

// Track a login
await tracker.trackLogin('usr_12345', true, { email: 'user@example.com' });

// Track any custom event
await tracker.track('page_view', {
    userId: 'usr_12345',
    url: '/dashboard',
    ip: '203.0.113.42',
});

// Check blacklist
const result = await tracker.checkBlacklist({ userId: 'usr_12345', ip: '1.2.3.4' });
if (result?.blocked) {
    throw new Error('User is blocked');
}
```

## Express.js Middleware

```javascript
const SentinelTracker = require('sentinel-tracker');

const tracker = new SentinelTracker('http://localhost:8585', 'sk_your_api_key', {
    autoFlushMs: 10000, // Auto-flush every 10 seconds
});

// Automatically track all authenticated requests
app.use(tracker.expressMiddleware({
    getUserId: (req) => req.user?.id,
    getEmail: (req) => req.user?.email,
}));

// Graceful shutdown
process.on('SIGTERM', async () => {
    await tracker.destroy();
    process.exit(0);
});
```

## Batch Mode

```javascript
// Queue events (auto-flushes at 50)
for (let i = 0; i < 100; i++) {
    tracker.queue('page_view', { userId: `usr_${i}` });
}
await tracker.flush(); // Send remaining
```

## Requirements

- Node.js 14+
- No external dependencies
