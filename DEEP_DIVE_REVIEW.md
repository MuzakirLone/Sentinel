# Sentinel Deep Dive Review & Cybersecurity Career Mapping

This document is a complete reviewer-facing walkthrough of Sentinel as a portfolio project. It explains what the system does, how the major components fit together, what security concepts it demonstrates, how to review each part of the codebase, and how to translate the project into cybersecurity analyst, SOC analyst, cybersecurity engineer, and internship conversations.

---

## 1. Executive Summary

Sentinel is a self-hosted security monitoring and risk-scoring platform for web applications. It accepts application security events through an API, normalizes users, sessions, devices, and IP addresses, evaluates events against detection rules, assigns risk scores, and exposes operational views for dashboards, review queues, cases, audit trails, rules, users, integrations, and settings.

At a high level, the project demonstrates four cybersecurity capabilities:

1. **Telemetry ingestion** — collecting security-relevant events from applications through API endpoints and SDKs.
2. **Detection engineering** — evaluating events with rule logic for brute force, credential stuffing, account takeover, bot activity, promo abuse, insider threat, and related abuse cases.
3. **SOC operations workflow** — surfacing risky activity through dashboards, queues, cases, review items, and audit records.
4. **Secure engineering controls** — including API key authentication, optional HMAC signatures, CSRF protection, security headers, rate limiting, environment validation, idempotency, and test coverage.

This makes Sentinel strongest as a portfolio artifact for entry-level cybersecurity, SOC, detection engineering, application security, and security engineering roles because it shows both security reasoning and implementation depth.

---

## 2. System Purpose and Threat Model

### 2.1 Problem Sentinel Solves

Modern applications produce large volumes of authentication, account, device, IP, and behavior data. Sentinel turns that raw telemetry into security decisions by answering questions such as:

- Is this login behavior normal for the user?
- Is one IP failing authentication across many accounts?
- Did a dormant account suddenly become active?
- Is a device or IP shared across too many accounts?
- Is activity coming from infrastructure associated with proxies, VPNs, TOR, or data centers?
- Does a privileged user action look abnormal enough to require review?

### 2.2 Primary Users

| User | What Sentinel Helps Them Do |
| --- | --- |
| SOC analyst | Triage high-risk events, review timelines, escalate cases, and document investigation outcomes. |
| Cybersecurity analyst | Interpret security signals, map rules to attack behaviors, and explain risk factors. |
| Cybersecurity engineer | Maintain secure ingestion, backend workflows, persistence, dashboards, and reliability patterns. |
| Detection engineer | Tune rules, adjust thresholds, map detections to MITRE ATT&CK, and reduce false positives. |
| Application owner | Integrate SDKs, send events, and block risky actors through blacklist checks. |

### 2.3 Threat Categories Covered

| Threat Category | Sentinel Examples |
| --- | --- |
| Credential attacks | Brute force and credential stuffing detections. |
| Account abuse | Account takeover, dormant account reactivation, and multi-accounting detections. |
| Fraud and spam | Promo abuse and content spam detections. |
| Automation | Bot detection through user-agent and request behavior signals. |
| Suspicious infrastructure | High-risk region, VPN, proxy, TOR, data center, and geographic anomalies. |
| Insider risk | Privileged action monitoring and abnormal sensitive activity. |

---

## 3. Repository Map

| Area | Path | Review Focus |
| --- | --- | --- |
| Application entry | `index.php` | Bootstrapping, routing, middleware attachment, and request lifecycle. |
| Core framework | `app/Core/` | Router, request/response wrappers, auth, database, cache, queue, logging, and environment validation. |
| API controllers | `app/Controllers/Api/` | Event ingestion and blacklist lookups. |
| Dashboard controllers | `app/Controllers/Dashboard/` | UI workflows for events, review queue, cases, users, audit, rules, settings, integrations, and authentication. |
| Detection engine | `app/Engine/` | Risk engine, score calculator, and rule contract. |
| Detection rules | `app/Engine/Rules/` | Individual abuse and anomaly detection logic. |
| Services | `app/Services/` | Event processing pipeline, dashboard metrics, and user timelines. |
| Models | `app/Models/` | Database access abstractions for domain entities. |
| Views | `app/Views/` | Dashboard UI and presentation logic. |
| Schema | `database/migrations/` | Persistence model, indexes, idempotency, reliability, and security tables. |
| SDKs | `sdks/` | PHP, Python, and Node.js event-tracking clients. |
| Simulations | `simulations/` | Attack traffic generators for demos and validation. |
| Tests | `tests/` | Unit and integration test coverage. |
| Public assets | `public/` | CSS and JavaScript for UI interactions and charts. |
| Deployment | `Dockerfile`, `docker-compose.yml`, `docker-compose.prod.yml` | Local and production-style runtime packaging. |

---

## 4. Request and Event Lifecycle

### 4.1 Event Ingestion Flow

1. A client application or SDK sends an event to `POST /api/v1/events` or `POST /api/v1/events/batch`.
2. API middleware validates access controls such as API key authentication and, when supplied, HMAC request signatures.
3. The event controller validates required fields and forwards the event to the event processing service.
4. The processing service normalizes related entities, including user, session, IP address, and device information.
5. The risk engine builds context from historical user, IP, device, and event data.
6. Enabled rules evaluate the event and return rule results.
7. The score calculator combines rule output into an overall score, level, confidence, deviation score, and risk factors.
8. Results are persisted and made available to dashboards, user timelines, review queues, cases, blacklist checks, and audit workflows.

### 4.2 Batch Ingestion Flow

Batch ingestion accepts up to 100 events per request. This is useful for backfilling telemetry or reducing API overhead. The controller processes each event, returns per-event results, and records validation errors by index so reviewers can see partial success and failure behavior.

### 4.3 Asynchronous Queue Context

The codebase includes a Redis-backed queue manager with reliable queue, delayed retry, and dead-letter concepts. Current event ingestion comments indicate synchronous processing is used in the event controller, while the queue infrastructure remains valuable to discuss as a production scalability and reliability path.

When explaining this in an interview, be transparent:

> “The live ingestion path processes events synchronously, while the repository also contains queue infrastructure that demonstrates how I would evolve the system toward asynchronous workers, retries, and dead-letter handling.”

---

## 5. Detection Engine Review

### 5.1 Risk Engine Responsibilities

The risk engine is the orchestration layer for detection logic. It:

- Loads enabled rules from the database.
- Applies configured rule weights.
- Builds a rich evaluation context from historical telemetry.
- Runs every rule against the current event and user.
- Continues evaluating if one rule fails, which prevents one faulty rule from breaking the entire detection pipeline.

### 5.2 Rule Design Pattern

Each rule follows a common interface and returns a rule result containing:

- Rule slug.
- Score.
- Triggered state.
- Human-readable description.
- Structured details for investigation.

This pattern matters because it makes detections modular. A new detection can be added without rewriting the full risk engine.

### 5.3 Preset Detection Rules

| Rule | What It Demonstrates | Career Talking Point |
| --- | --- | --- |
| Account Takeover | New device, new IP, impossible travel, and unusual login behavior. | Explains user behavior analytics and account compromise triage. |
| Credential Stuffing | Failed logins across multiple accounts from common sources. | Maps to SOC alerts involving password spraying and credential reuse. |
| Brute Force | High-velocity failed authentication attempts. | Demonstrates threshold-based detection plus context enrichment. |
| Bot Detection | Automation indicators such as suspicious user agents and timing patterns. | Connects app telemetry to bot and abuse defense. |
| Content Spam | Rapid content creation and suspicious content behavior. | Shows fraud/spam detection beyond traditional endpoint logs. |
| Multi-Accounting | Shared IP or device across many accounts. | Useful for fraud, abuse, and identity investigations. |
| Dormant Account | Sudden activity after long inactivity. | Common account takeover indicator. |
| High-Risk Region | TOR, VPN, proxy, data center, and risky geography signals. | Demonstrates enrichment and infrastructure reputation concepts. |
| Promo Abuse | Repeated promotional or referral abuse. | Shows business-risk-aligned security detection. |
| Insider Threat | Sensitive admin actions and unusual privileged activity. | Connects least privilege, monitoring, and investigation workflows. |

### 5.4 Scoring Concepts

Sentinel demonstrates multiple concepts that are useful in analyst and engineering interviews:

- **Weighted rule scoring**: more important detections can contribute more to final risk.
- **Confidence scoring**: limited telemetry should reduce certainty.
- **Behavioral baselines**: “normal” user behavior improves anomaly detection.
- **Compound signals**: multiple weak indicators can form a stronger alert.
- **Risk levels**: numeric scores become operational severity categories for triage.

---

## 6. Data Model Review

Sentinel’s schema supports security operations rather than only raw event storage. The main entities are:

| Entity | Purpose |
| --- | --- |
| Users | Tracks account identity, risk, status, and profile attributes. |
| Events | Stores security and behavior telemetry submitted by applications. |
| Sessions | Connects events to session-level activity. |
| IP addresses | Stores IP metadata, reputation, country, city, and infrastructure indicators. |
| Devices | Stores browser/device fingerprints and reuse patterns. |
| Rules | Stores detection configuration, weights, enabled state, and MITRE mappings. |
| Rule results | Stores per-event detection output for explainability. |
| Risk scores | Stores calculated score history. |
| Review items | Supports manual analyst triage. |
| Cases | Supports investigation lifecycle management. |
| Case events | Tracks case notes and status changes. |
| Audit entries | Records important field-level changes. |
| API keys | Supports authenticated telemetry ingestion. |
| Integrations | Represents downstream SIEM, chat, webhook, or ticketing connections. |

A strong portfolio point is that Sentinel preserves explainability. It does not simply label a user “bad”; it stores why a rule fired, what score it produced, and what details an analyst should review.

---

## 7. Security Controls Review

### 7.1 API Authentication

Sentinel supports API-key-based ingestion so external applications can submit events. This is important because event collection endpoints are sensitive: an attacker who can spoof events can poison analytics, hide activity in noise, or generate false positives.

### 7.2 HMAC Request Signing

The HMAC middleware verifies signed requests when signature headers are present. The design protects against:

- Request body tampering.
- Replay attacks through timestamp drift checks.
- Stolen or misused API keys when the secret is not known.
- Nonce reuse when nonce persistence is enforced.

This is a strong cybersecurity engineering talking point because it shows knowledge of message authentication, replay protection, and secure API design.

### 7.3 CSRF Protection

Dashboard interactions are separate from API ingestion. CSRF protection matters for authenticated browser sessions because it prevents a malicious page from causing a logged-in analyst or admin to perform unwanted actions.

### 7.4 Security Headers

Security headers reduce browser-side attack surface. In interviews, connect this to defense in depth: headers do not replace secure code, but they help mitigate clickjacking, MIME sniffing, cross-site scripting impact, and unsafe transport behavior.

### 7.5 Rate Limiting

Rate limiting protects authentication, ingestion, and dashboard endpoints from abuse and accidental overload. For SOC use cases, rate limiting also reduces alert flooding and improves service resilience.

### 7.6 Environment Validation

Environment validation helps prevent insecure deployments. A production security system should fail closed when required secrets or configuration values are missing.

### 7.7 Idempotency

Idempotency prevents duplicate event submissions from creating duplicate detections and inflated risk scores. This is especially important when clients retry requests after network failures.

---

## 8. SOC Workflow Review

Sentinel is not only a detection engine; it also models an investigation workflow.

### 8.1 Dashboard

The dashboard gives a top-level operational view of alerts, risk distribution, event volume, and system state. This is the “SOC overview” layer.

### 8.2 Events

The events view allows analysts to inspect raw and enriched telemetry. A good review should ask:

- What happened?
- Who did it affect?
- Which IP, device, and session were involved?
- Which rules fired?
- What risk factors explain the score?

### 8.3 Users

The single-user view supports user-centric investigation. This is useful for account takeover and insider-risk triage because analysts often need to reconstruct account behavior over time.

### 8.4 Review Queue

The review queue models alert triage. Analysts can inspect high-risk items, determine whether they are true positives or false positives, and escalate when needed.

### 8.5 Cases

Cases support deeper investigations. A case workflow helps show that you understand SOC work is not just alert generation; it includes ownership, status, evidence, timelines, resolution, and communication.

### 8.6 Audit Trail

The audit trail records meaningful changes. This matters for accountability, compliance, and post-incident review.

---

## 9. SDK and Simulation Review

### 9.1 SDKs

The PHP, Python, and Node.js SDKs make Sentinel easier to integrate into real applications. For your career story, this shows you can build tools that other developers can actually use.

When reviewing SDKs, look for:

- Simple initialization.
- Clear event-tracking methods.
- Error handling behavior.
- Consistent payload fields.
- Documentation examples.
- Signature support if enabled.

### 9.2 Attack Simulations

The simulation scripts are important because they let you demonstrate detections without waiting for real attacks. They support demo scenarios such as:

- Brute force attempts.
- Credential stuffing.
- Impossible travel.
- Account takeover behavior.
- Bot-like traffic.

For interviews, simulations are valuable because you can explain how you validated detections and tuned risk output.

---

## 10. How Sentinel Fits Your Cybersecurity Career

### 10.1 Cybersecurity Analyst Fit

Sentinel helps position you as a cybersecurity analyst because it demonstrates that you can:

- Interpret security events.
- Explain attack patterns in plain language.
- Connect technical signals to user risk.
- Understand alert severity and prioritization.
- Review timelines, IPs, devices, and account behavior.
- Document investigation outcomes.

Resume bullet examples:

- Built a self-hosted security monitoring platform that ingests application events, evaluates behavioral detections, and surfaces risk-scored alerts for analyst review.
- Implemented detections for credential stuffing, brute force, account takeover, bot activity, and insider-risk scenarios using contextual telemetry.
- Created investigation workflows with review queues, case management, user timelines, and audit trails to mirror SOC triage processes.

Interview framing:

> “This project helped me practice the full analyst loop: collect telemetry, detect suspicious behavior, enrich alerts with context, triage severity, and document the investigation path.”

### 10.2 SOC Analyst Fit

Sentinel is especially relevant for SOC analyst roles because it models alert triage and escalation. You can use it to discuss:

- Alert queues.
- Severity assignment.
- False positive reduction.
- User and entity behavior analytics.
- Case escalation.
- MITRE ATT&CK alignment.
- Evidence collection from logs and timelines.

SOC interview story:

> “I designed Sentinel so an analyst can move from a high-risk event to the affected user, related IPs and devices, rule results, timeline, and case notes. That mirrors how I would triage an alert in a SOC: validate the signal, gather context, determine scope, and decide whether to escalate.”

Hands-on demo path for SOC interviews:

1. Run the application with Docker.
2. Trigger a brute force or credential stuffing simulation.
3. Open the dashboard and event views.
4. Explain which rules fired and why.
5. Review the affected user timeline.
6. Escalate or discuss how the case workflow would be used.
7. Explain what additional evidence you would collect in a real environment.

### 10.3 Cybersecurity Engineer Fit

Sentinel fits cybersecurity engineering roles because it includes secure design and implementation controls. You can discuss:

- Secure API ingestion.
- HMAC signatures and replay prevention.
- API keys and secret handling.
- CSRF protection and browser security headers.
- Rate limiting and abuse resistance.
- Database schema design for security telemetry.
- Dockerized deployment.
- Automated tests.
- Queue and retry design for resilient event processing.

Resume bullet examples:

- Engineered secure event-ingestion APIs with API key authentication, optional HMAC-SHA256 request signing, replay protection, and idempotency safeguards.
- Designed a PostgreSQL-backed security telemetry model covering users, sessions, devices, IP intelligence, rules, rule results, review items, cases, and audit entries.
- Built modular detection rules and scoring logic in PHP with test coverage for core risk calculations and request handling.

Interview framing:

> “The engineering focus was to make the detection pipeline explainable and maintainable: every rule has a consistent interface, every score has supporting details, and operational workflows are backed by persisted investigation data.”

### 10.4 Internship Fit

For internships, Sentinel shows initiative and breadth. You do not need to oversell it as a commercial SIEM. Instead, frame it as a learning project that combines secure coding, detection logic, and SOC process.

Internship resume bullet examples:

- Developed a portfolio security monitoring project that detects suspicious login, account, device, and IP behavior using a modular rule engine.
- Built dashboards and investigation workflows for reviewing high-risk activity, user timelines, and case notes.
- Added tests, SDK examples, Docker setup, and attack simulations to demonstrate practical security validation.

Internship interview framing:

> “I built Sentinel to understand how security tools are created, not just used. It helped me practice backend development, secure API design, detection rules, and analyst workflows.”

---

## 11. Role-by-Role Skill Matrix

| Skill | Analyst | SOC Analyst | Cybersecurity Engineer | Internship |
| --- | --- | --- | --- | --- |
| Log/event analysis | High | High | Medium | High |
| Alert triage | High | High | Medium | High |
| Detection logic | High | High | High | Medium |
| MITRE mapping | Medium | High | Medium | Medium |
| Secure API design | Medium | Medium | High | Medium |
| HMAC/replay protection | Medium | Medium | High | Medium |
| Database design | Medium | Medium | High | Medium |
| Case management | High | High | Medium | Medium |
| Docker/deployment | Low | Medium | High | Medium |
| Testing discipline | Medium | Medium | High | Medium |
| SDK integration | Low | Low | High | Medium |

---

## 12. How to Review Every Major Part

Use this checklist when reviewing Sentinel yourself or presenting it to another person.

### 12.1 Application Bootstrap

Review questions:

- How are routes registered?
- Which middleware protects dashboard routes?
- Which middleware protects API routes?
- How is configuration loaded?
- How are errors surfaced?

What to say:

> “The bootstrap layer matters because security controls must be applied consistently before requests reach business logic.”

### 12.2 Core Classes

Review questions:

- Does the router clearly map HTTP methods and paths?
- Does the request wrapper normalize input safely?
- Does the response wrapper return consistent JSON and status codes?
- Does auth separate session-based dashboard auth from API ingestion auth?
- Does logging produce structured output useful for SIEM ingestion?

### 12.3 Middleware

Review questions:

- Does API key middleware reject missing or invalid keys?
- Does HMAC middleware validate timestamp, nonce, body hash, and signature?
- Does CSRF middleware protect unsafe dashboard methods?
- Does rate limiting use meaningful keys and windows?
- Do security headers apply to browser responses?

### 12.4 Event Processing Service

Review questions:

- Which fields are required?
- How are users created or updated?
- How are IP and device records enriched?
- How is duplicate ingestion handled?
- How are risk scores and rule results persisted?
- What happens when a rule fails?

### 12.5 Risk Engine and Rules

Review questions:

- Are all rules loaded from configuration?
- Can rules be disabled without code changes?
- Are weights applied consistently?
- Is each rule explainable?
- Are thresholds visible and tunable?
- Does the context include enough historical data?

### 12.6 Database Migrations

Review questions:

- Are key lookup fields indexed?
- Are foreign keys appropriate?
- Are JSONB fields used for flexible event details?
- Is idempotency represented?
- Are audit and failed-job concepts stored?
- Are rule mappings persisted?

### 12.7 Dashboard Views

Review questions:

- Can an analyst move from summary to detail?
- Are risk levels visually clear?
- Are timestamps, IPs, users, and scores easy to inspect?
- Are case and review actions obvious?
- Does the UI avoid hiding important evidence?

### 12.8 SDKs

Review questions:

- Can a developer send an event with minimal setup?
- Are examples realistic?
- Is the same payload shape used across SDKs?
- Are network failures handled safely?
- Is sensitive material kept out of logs?

### 12.9 Simulations

Review questions:

- Does each simulation map to a specific detection scenario?
- Can simulations be run locally against Docker?
- Do they generate enough events to demonstrate rule behavior?
- Are expected outcomes documented?

### 12.10 Tests

Review questions:

- Are core scoring behaviors tested?
- Are request and auth helpers tested?
- Are integration paths covered?
- Are tests isolated from production secrets?
- Are there gaps around middleware, SDK signatures, and UI workflows?

---

## 13. Strengths

1. **Strong career relevance** — combines analyst workflows, detection engineering, and secure backend engineering.
2. **Modular rule engine** — makes detections easier to understand, test, tune, and extend.
3. **Explainable scoring** — stores rule results and risk factors rather than only final labels.
4. **SOC-style workflow** — includes review, case, audit, dashboard, and timeline concepts.
5. **Secure ingestion concepts** — API keys, HMAC signing, replay prevention, rate limiting, and idempotency are highly relevant.
6. **Practical demos** — SDKs and simulations make the project presentable in interviews.
7. **Deployability** — Docker and migrations support repeatable local demonstration.

---

## 14. Gaps and Improvement Opportunities

These are not failures; they are useful future roadmap items and interview talking points.

| Gap | Why It Matters | Suggested Improvement |
| --- | --- | --- |
| Synchronous event path | High-volume production systems usually need async ingestion. | Re-enable queue-backed processing behind a feature flag and document worker operation. |
| Limited external enrichment | IP reputation and geo signals are strongest when enriched from trusted feeds. | Add optional MaxMind, AbuseIPDB, or internal threat-intel adapters. |
| SIEM exports need hardening | Security teams often need normalized downstream logs. | Add Elastic, Splunk HEC, or generic webhook export tests and examples. |
| Rule tuning workflow | Analysts need safe threshold changes. | Add versioned rule configs and tuning notes for false positive review. |
| Detection validation metrics | Mature detections need precision/recall or test corpora. | Add expected simulation outcomes and detection regression tests. |
| Authentication hardening | Dashboard auth can be strengthened for real deployments. | Add MFA/TOTP, password reset token expiration review, and stronger session controls. |
| RBAC depth | SOC teams use role separation. | Add analyst, admin, read-only, and integration roles. |
| Observability | Production operations need service health and traces. | Add health checks, metrics endpoints, and structured log examples. |
| Privacy controls | Security telemetry can contain sensitive data. | Add retention policies, PII minimization, and redaction guidance. |

Use this wording in interviews:

> “I can also speak to what I would improve next. For production, I would focus on async ingestion, RBAC, retention controls, stronger external enrichment, and detection validation metrics.”

---

## 15. Demo Script

### 15.1 Short Demo for Recruiters

1. Explain that Sentinel monitors application events and calculates risk.
2. Show the dashboard.
3. Open events and point to risk score, user, IP, device, and rule output.
4. Open a user timeline.
5. Explain how a SOC analyst would triage and escalate.
6. Mention SDKs and simulations as proof the project is testable.

Time target: 3–5 minutes.

### 15.2 Technical Demo for Hiring Managers

1. Start with the ingestion API.
2. Show the event controller and processing service.
3. Show the risk engine and one detection rule.
4. Show the schema for events, rules, rule results, cases, and audit entries.
5. Run or describe an attack simulation.
6. Show the dashboard or review queue.
7. Discuss security controls: HMAC, CSRF, rate limiting, idempotency, and environment validation.
8. Discuss what you would improve for production.

Time target: 10–15 minutes.

---

## 16. STAR Interview Stories

### 16.1 Detection Engineering Story

- **Situation:** Web applications need a way to detect suspicious account behavior beyond simple login success and failure logs.
- **Task:** Build a system that could ingest events and identify risky behavior patterns.
- **Action:** Implemented a modular rule engine with detections for brute force, credential stuffing, account takeover, bots, dormant accounts, and insider-risk actions.
- **Result:** Produced explainable risk scores and rule details that can be reviewed through dashboard, user timeline, and case workflows.

### 16.2 Secure API Engineering Story

- **Situation:** Security telemetry ingestion endpoints must resist spoofing, tampering, replay, and abuse.
- **Task:** Protect API ingestion while keeping SDK integration practical.
- **Action:** Added API key authentication, optional HMAC-SHA256 request signatures, timestamp drift checks, nonce-based replay protection, rate limiting, and idempotency concepts.
- **Result:** Created a stronger ingestion design suitable for discussing secure service-to-service communication.

### 16.3 SOC Workflow Story

- **Situation:** Alerts are only useful if analysts can validate, prioritize, and document them.
- **Task:** Build workflows that go beyond raw detections.
- **Action:** Added dashboard, review queue, case management, audit trail, and user timeline concepts.
- **Result:** Demonstrated understanding of how SOC work moves from signal to investigation to resolution.

---

## 17. Resume Section Options

### Option A: Cybersecurity Analyst / SOC Analyst

**Sentinel — Security Monitoring and Risk Scoring Platform**

- Built a self-hosted security monitoring platform that ingests web application events and generates explainable risk scores for analyst triage.
- Implemented detection logic for brute force, credential stuffing, account takeover, bot traffic, dormant accounts, high-risk regions, promo abuse, and insider-risk activity.
- Created SOC-style workflows including dashboards, event review, user timelines, case management, and audit trails.
- Added attack simulations to validate detections and demonstrate investigation workflows.

### Option B: Cybersecurity Engineer

**Sentinel — Secure Event Ingestion and Detection Platform**

- Engineered a PHP/PostgreSQL security platform with modular detection rules, risk scoring, API ingestion, dashboard workflows, and Dockerized deployment.
- Implemented security controls including API keys, optional HMAC-SHA256 request signing, replay protection, CSRF middleware, rate limiting, security headers, and environment validation.
- Designed persistence for security telemetry, users, sessions, devices, IP intelligence, rule results, cases, review items, audit entries, and integrations.
- Built SDKs and simulations to support integration testing and repeatable attack demonstrations.

### Option C: Internship

**Sentinel — Cybersecurity Portfolio Project**

- Developed a web application security monitoring project to practice detection engineering, secure API design, and SOC investigation workflows.
- Created modular rules for common account abuse scenarios and displayed risk-scored results in a dashboard.
- Used Docker, PHPUnit tests, SDK examples, and simulation scripts to make the project reproducible and demonstrable.

---

## 18. Final Review Verdict

Sentinel is a strong cybersecurity portfolio project because it connects three areas that employers value:

1. **Security analysis:** understanding threats, risk, alerts, and investigations.
2. **Security engineering:** building secure APIs, data models, controls, and deployment paths.
3. **Detection engineering:** transforming raw telemetry into explainable, tunable detections.

For analyst and SOC roles, emphasize triage, investigation, MITRE mapping, event review, user timelines, and cases. For cybersecurity engineering roles, emphasize HMAC signing, API security, idempotency, schema design, Docker, tests, and modular architecture. For internships, emphasize curiosity, hands-on learning, and your ability to explain every component clearly.

The most important way to present Sentinel is with honesty and confidence: it is a portfolio security monitoring platform that demonstrates practical understanding of how detections are built, how alerts are investigated, and how secure ingestion systems are engineered.
