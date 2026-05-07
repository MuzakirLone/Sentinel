# Sentinel API Reference

Complete REST API documentation for the Sentinel security monitoring framework.

## Authentication

All API requests require authentication via an API key. Include it in one of:

- **Header:** `X-API-Key: sk_your_api_key`
- **Bearer Token:** `Authorization: Bearer sk_your_api_key`

API keys can be created from the dashboard at **Settings → API Keys**.

---

## Endpoints

### 1. Event Ingestion

#### `POST /api/v1/events`

Send a single security event.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `event_type` | string | ✅ | Type of event (see Event Types below) |
| `user_id` | string | Recommended | Your app's unique user identifier |
| `email` | string | Optional | User's email address |
| `username` | string | Optional | User's username |
| `phone` | string | Optional | User's phone number |
| `ip` | string | Recommended | Client IP address |
| `user_agent` | string | Recommended | Client user-agent string |
| `url` | string | Optional | Request URL/path |
| `http_method` | string | Optional | HTTP method (GET, POST, etc.) |
| `session_id` | string | Optional | Session identifier |
| `metadata` | object | Optional | Additional custom data |
| `field_changes` | array | Optional | Field audit trail entries |

**Example:**

```json
{
  "event_type": "login_success",
  "user_id": "usr_12345",
  "email": "user@example.com",
  "ip": "203.0.113.42",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
  "url": "/api/login",
  "session_id": "sess_abc123",
  "metadata": {
    "method": "password",
    "mfa_used": true
  }
}
```

**Response (201):**

```json
{
  "status": "accepted",
  "event_id": 42,
  "risk_score": 15.5,
  "risk_level": "low",
  "rules_triggered": []
}
```

---

#### `POST /api/v1/events/batch`

Send multiple events in a single request (max 100).

**Request Body:**

```json
{
  "events": [
    { "event_type": "page_view", "user_id": "usr_001", "url": "/home" },
    { "event_type": "page_view", "user_id": "usr_002", "url": "/profile" }
  ]
}
```

**Response (201):**

```json
{
  "status": "accepted",
  "processed": 2,
  "errors": [],
  "results": [
    { "event_id": 43, "risk_score": 0, "risk_level": "low" },
    { "event_id": 44, "risk_score": 5.2, "risk_level": "low" }
  ]
}
```

---

### 2. Blacklist Check

#### `POST /api/v1/blacklist/check`

Real-time check whether a user or IP should be blocked.

**Request Body:**

| Field | Type | Description |
|-------|------|-------------|
| `user_id` | string | User's external ID |
| `ip` | string | IP address to check |
| `email` | string | Email to check domain reputation |

**Response:**

```json
{
  "blocked": false,
  "risk_score": 25.0,
  "risk_level": "moderate",
  "reasons": []
}
```

If blocked:

```json
{
  "blocked": true,
  "risk_score": 92.5,
  "risk_level": "critical",
  "reasons": [
    "User status: suspended",
    "TOR exit node"
  ]
}
```

---

## Event Types

### Authentication
| Type | Description |
|------|-------------|
| `login_success` | Successful login |
| `login_failed` | Failed login attempt |
| `signup` / `register` | New account registration |
| `password_change` | Password modification |
| `email_change` | Email modification |
| `mfa_enable` | MFA enabled |
| `mfa_disable` | MFA disabled |

### Content
| Type | Description |
|------|-------------|
| `post_create` | Content post created |
| `comment_create` | Comment posted |
| `message_send` | Message sent |
| `review_create` | Review submitted |

### Administrative
| Type | Description |
|------|-------------|
| `admin_login` | Admin panel login |
| `data_export` | Data export/download |
| `user_delete` | User account deletion |
| `config_change` | Configuration change |
| `permission_change` | Permission modification |

### Commerce
| Type | Description |
|------|-------------|
| `promo_apply` | Promotional code used |
| `coupon_use` | Coupon redeemed |
| `referral_claim` | Referral claimed |

### General
| Type | Description |
|------|-------------|
| `page_view` | Page view |
| `api_call` | API call |
| `field_change` | Field value change (with `field_changes` array) |

You can use **any custom event type** — the system will track it even if it's not in this list.

---

## Field Changes (Audit Trail)

Include `field_changes` in your event to track field-level modifications:

```json
{
  "event_type": "field_change",
  "user_id": "usr_12345",
  "field_changes": [
    {
      "entity_type": "user",
      "entity_id": 123,
      "field": "email",
      "old_value": "old@example.com",
      "new_value": "new@example.com"
    }
  ]
}
```

---

## Error Responses

| Code | Description |
|------|-------------|
| `400` | Bad request — missing required fields |
| `401` | Unauthorized — missing or invalid API key |
| `403` | Forbidden — API key revoked |
| `429` | Rate limited — too many requests |
| `500` | Server error |

```json
{
  "error": "event_type is required"
}
```

---

## Rate Limits

Default: **120 requests per minute** per IP address. Configurable via `RATE_LIMIT_PER_MINUTE` environment variable.
