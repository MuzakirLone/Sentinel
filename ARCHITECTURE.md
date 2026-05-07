# Architecture

## System Overview

Sentinel follows a **lightweight MVC architecture** built on pure PHP without heavy frameworks. The design prioritizes simplicity, minimal dependencies, and ease of deployment.

## Component Architecture

```
┌─────────────────────────────────────────────────────┐
│                     index.php                        │
│               (Front Controller + Router)             │
├──────────────────┬──────────────────────────────────┤
│   API Layer      │        Dashboard Layer            │
│                  │                                    │
│ EventController  │  AuthController                    │
│ BlacklistCtrl    │  DashboardController               │
│                  │  UsersController                   │
│                  │  EventsController                  │
│                  │  ReviewController                  │
│                  │  RulesController                   │
│                  │  SettingsController                │
│                  │  AuditController                   │
├──────────────────┴──────────────────────────────────┤
│                   Risk Engine                         │
│                                                       │
│  ┌────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ RiskEngine │──│ 10 Rules     │──│ ScoreCalc    │ │
│  └────────────┘  └──────────────┘  └──────────────┘ │
├───────────────────────────────────────────────────────┤
│                   Model Layer                         │
│  User │ Event │ Session │ Device │ IpAddress │ ...    │
├───────────────────────────────────────────────────────┤
│                   Core Framework                      │
│  Router │ Database │ Request │ Response │ Auth        │
│  Middleware: AuthMW │ ApiKeyMW │ CorsMW │ RateMW      │
├───────────────────────────────────────────────────────┤
│              PostgreSQL Database                      │
│  14 tables with indexes and constraints               │
└───────────────────────────────────────────────────────┘
```

## Event Processing Pipeline

```
Client App → API Request → ApiKey Validation
    → User Resolution (find or create)
    → IP Resolution (find or create)
    → Device Resolution (parse UA, find or create)
    → Session Resolution (find or create)
    → Event Record Creation
    → Risk Engine Evaluation
        → Load enabled rules
        → Build context (historical data)
        → Evaluate each rule
        → Calculate weighted score
    → Save rule results
    → Update user risk score
    → Auto-flag/suspend if thresholds exceeded
    → Create review queue entry if needed
    → Return response with risk assessment
```

## Risk Scoring

The score calculator uses a **weighted category aggregation** approach:

1. Each rule evaluates an event and returns a score (0–100)
2. Scores are grouped by category (auth, behavior, identity, geo, etc.)
3. Category weights are applied (auth: 30%, identity: 15%, etc.)
4. Final score blends weighted average (60%) with maximum category score (40%)
5. Risk levels: Low (0-19), Moderate (20-39), Elevated (40-59), High (60-79), Critical (80-100)

## Database Schema

### Key Tables

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `users` | Tracked user profiles | external_id, email, risk_score, status |
| `events` | Raw event log | event_type, user_id, ip_address_id, risk_score |
| `sessions` | Session tracking | session_id, user_id, event_count |
| `ip_addresses` | IP metadata | ip_address, country, is_tor, is_vpn |
| `devices` | Device fingerprints | fingerprint, browser, os, is_bot |
| `rules` | Rule configuration | slug, weight, is_enabled, config |
| `rule_results` | Per-event evaluations | event_id, rule_id, score, triggered |
| `risk_scores` | Aggregated per-user | overall_score, auth_score, factors |
| `review_queue` | Flagged accounts | user_id, reason, priority, status |
| `audit_trail` | Field changes | entity_type, field_name, old_value, new_value |
| `api_keys` | API authentication | key_hash, label, is_active |
| `admin_users` | Dashboard accounts | email, password_hash |

## Technology Stack

- **PHP 8.1+** — No framework, custom lightweight MVC
- **PostgreSQL 12+** — All data storage
- **Chart.js 4** — Dashboard visualizations
- **Vanilla CSS** — Custom dark-mode design system
- **Docker + Docker Compose** — Containerized deployment
