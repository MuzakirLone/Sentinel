# Sentinel — Attack Simulation Suite

## Overview

These Python scripts simulate real-world cyber attacks against the Sentinel API to validate that the risk engine detects them correctly. Each script generates realistic event patterns and shows detection results in real-time.

## Prerequisites

```bash
pip install requests
```

## Usage

All scripts accept two optional arguments:

```bash
python <script>.py [API_URL] [API_KEY]

# Defaults:
#   API_URL = http://localhost:8585
#   API_KEY = sk_test_key
```

## Available Simulations

### 1. 🔐 Brute Force Attack
```bash
python brute_force.py
```
**Simulates:** 40 rapid failed login attempts from a single IP, followed by a successful compromise.

**Expected detections:**
- Exponential decay scoring (recent failures weigh more)
- Attack classification (hydra/medusa-class tool)
- Failed-then-success pattern
- Auto-suspend at risk threshold

---

### 2. 📋 Credential Stuffing
```bash
python credential_stuffing.py
```
**Simulates:** Single IP testing 20 stolen credential pairs across different accounts with ~5% success rate.

**Expected detections:**
- Low success/failure ratio (< 15%)
- Cross-account velocity (many usernames per minute)
- Multi-account spread from single IP
- Confidence scoring based on sample size

---

### 3. 🌍 Impossible Travel
```bash
python impossible_travel.py
```
**Simulates:** User login from Mumbai, India → Moscow, Russia → São Paulo, Brazil within minutes.

**Expected detections:**
- Haversine distance calculation (4,700+ km)
- Travel speed exceeds 900 km/h (commercial flight maximum)
- First-time country access for established user
- Triple impossible travel chain

---

### 4. 🔓 Account Takeover
```bash
python account_takeover.py
```
**Simulates:** Full ATO chain — normal activity → attacker login from new device/country → rapid credential changes (password → email → MFA disable → recovery email → data export).

**Expected detections:**
- Compound novelty signal (new device + new IP + new country)
- Credential change chain detection
- Unusual login hour
- IP velocity anomaly vs baseline

---

### 5. 🤖 Bot Traffic
```bash
python bot_traffic.py
```
**Simulates:** Automated scraping bot with machine-like request timing, suspicious user-agents, and endpoint hammering.

**Expected detections:**
- Request timing entropy: σ < 0.3s (machine regularity vs human σ > 2s)
- Session depth anomaly: many events, few unique pages
- Suspicious user-agent patterns
- Compound weak-signal aggregation (datacenter + timing + speed + UA)

---

## Running All Simulations

```bash
# Run all simulations sequentially
for script in brute_force credential_stuffing impossible_travel account_takeover bot_traffic; do
    echo "=== Running $script ==="
    python $script.py http://localhost:8585 YOUR_API_KEY
    echo ""
    sleep 2
done
```

## Validation Runbook

Use the simulation suite as a repeatable detection validation harness:

1. Run the full set of simulations.
2. Confirm alerts appear in **Alert Queue** with correct priorities.
3. Escalate at least one alert into a **Case** and verify SLA timers.
4. Review evidence timelines to validate rule triggers and MITRE mappings.
5. Capture findings in resolution notes to close the loop on tuning.

## Observing Results

After running simulations, check the Sentinel dashboard:

1. **Dashboard** — Overall risk metrics and event timeline
2. **Users** — Individual user risk scores and triggered rules
3. **Alert Queue** — Auto-created alerts for high-risk users
4. **Events** — Full event log with risk scores
5. **Audit Trail** — Credential change records from ATO simulation

## Architecture

```
┌──────────────────────┐
│   Simulation Script   │  ← Python (requests library)
└──────────┬───────────┘
           │ HTTP POST /api/v1/events
           ▼
┌──────────────────────┐
│    Sentinel API       │  ← Validates, queues or processes
├──────────────────────┤
│    Risk Engine        │  ← 10 rules evaluate each event
│  (behavioral analysis)│
├──────────────────────┤
│   Score Calculator    │  ← Weighted category aggregation
│ (confidence + decay)  │
├──────────────────────┤
│    PostgreSQL         │  ← Persistent storage
└──────────────────────┘
```
