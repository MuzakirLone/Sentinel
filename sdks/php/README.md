# Sentinel PHP Tracker

Lightweight PHP SDK for sending security events to your Sentinel instance.

## Installation

Copy `SentinelTracker.php` to your project, or install via Composer:

```bash
composer require sentinel/tracker
```

## Quick Start

```php
<?php
require_once 'SentinelTracker.php';

$sentinel = new SentinelTracker('http://localhost:8585', 'sk_your_api_key');

// Track a login
$sentinel->trackLogin('usr_12345', true, [
    'email' => 'user@example.com',
]);

// Track any custom event
$sentinel->track('page_view', [
    'user_id' => 'usr_12345',
    'url'     => '/dashboard',
]);

// Track a field change (audit trail)
$sentinel->trackFieldChange('usr_12345', 'user', 1, 'email', 'old@mail.com', 'new@mail.com');

// Check if a user is blacklisted
$result = $sentinel->checkBlacklist(['user_id' => 'usr_12345', 'ip' => '1.2.3.4']);
if ($result['blocked']) {
    // Block the request
}
```

## Batch Mode

```php
// Queue events (auto-flushes at 50)
for ($i = 0; $i < 100; $i++) {
    $sentinel->queue('page_view', ['user_id' => 'usr_' . $i]);
}
$sentinel->flush(); // Send remaining
```

## Event Types

| Event Type | Description |
|---|---|
| `login_success` | Successful login |
| `login_failed` | Failed login attempt |
| `signup` | New account registration |
| `password_change` | Password modification |
| `email_change` | Email modification |
| `page_view` | Page view |
| `post_create` | Content creation |
| `data_export` | Data export action |
| `promo_apply` | Promotional code usage |

## Requirements

- PHP 7.4+
- cURL extension
