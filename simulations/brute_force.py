#!/usr/bin/env python3
"""
Sentinel Attack Simulation Suite — Brute Force Attack

Simulates a brute force authentication attack:
- 50+ rapid failed login attempts from a single IP
- Escalating attempt frequency
- Finally succeeds on one attempt (compromised credential)

Expected detection:
- BruteForceRule triggers with exponential decay scoring
- Risk score climbs to critical levels
- User gets auto-flagged/suspended
- Review queue item created
"""

import requests
import time
import sys
import random
import string

# ─── Configuration ──────────────────────────────────────
API_URL = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8585"
API_KEY = sys.argv[2] if len(sys.argv) > 2 else "sk_test_key"
TARGET_USER = "usr_victim_bf_001"
ATTACKER_IP = "185.220.101.42"  # Known TOR exit node range

HEADERS = {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
}

def send_event(event_type, user_id, ip, extra=None):
    """Send a single event to the Sentinel API."""
    payload = {
        "event_type": event_type,
        "user_id": user_id,
        "ip": ip,
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) hydra/9.4",
    }
    if extra:
        payload.update(extra)

    try:
        r = requests.post(f"{API_URL}/api/v1/events", json=payload, headers=HEADERS, timeout=10)
        return r.json()
    except Exception as e:
        return {"error": str(e)}


def main():
    print("=" * 60)
    print("  SENTINEL ATTACK SIMULATION: Brute Force")
    print("=" * 60)
    print(f"  Target:    {TARGET_USER}")
    print(f"  Attacker:  {ATTACKER_IP}")
    print(f"  API:       {API_URL}")
    print("=" * 60)
    print()

    # Phase 1: Establish normal user baseline (legitimate activity)
    print("[Phase 1] Establishing normal user baseline...")
    for i in range(5):
        result = send_event("login_success", TARGET_USER, "203.0.113.10", {
            "email": "victim@company.com",
        })
        print(f"  ✓ Normal login #{i+1} — Risk: {result.get('risk_score', '?')}")
        time.sleep(0.3)

    print()
    print("[Phase 2] Starting brute force attack...")
    print("-" * 60)

    # Phase 2: Rapid failed login attempts
    for i in range(40):
        password_attempt = ''.join(random.choices(string.ascii_lowercase, k=8))
        result = send_event("login_failed", TARGET_USER, ATTACKER_IP, {
            "email": "victim@company.com",
            "metadata": {
                "password_attempt": f"attempt_{i+1}",
                "failure_reason": "invalid_password",
            }
        })

        risk = result.get('risk_score', '?')
        level = result.get('risk_level', '?')
        factors = result.get('risk_factors', [])

        status_icon = "🟢" if risk == '?' or (isinstance(risk, (int, float)) and risk < 30) else \
                     "🟡" if isinstance(risk, (int, float)) and risk < 60 else \
                     "🔴"

        print(f"  {status_icon} Attempt {i+1:3d}/40 | Risk: {risk:>6} | Level: {level}")

        # Show triggered rules periodically
        if factors and (i % 10 == 9):
            print(f"      ┌─ Triggered rules:")
            for f in factors[:3]:
                print(f"      │  {f.get('rule', '?')}: +{f.get('score', 0):.0f} — {f.get('reason', '')[:60]}")
            print(f"      └─")

        time.sleep(0.1)  # Rapid fire

    print()
    print("[Phase 3] Attacker succeeds (compromised credential)...")
    result = send_event("login_success", TARGET_USER, ATTACKER_IP, {
        "email": "victim@company.com",
    })
    print(f"  🔴 Successful login from attacker IP!")
    print(f"     Risk Score: {result.get('risk_score', '?')}")
    print(f"     Risk Level: {result.get('risk_level', '?')}")
    print(f"     Confidence: {result.get('confidence', '?')}%")

    factors = result.get('risk_factors', [])
    if factors:
        print(f"     Risk Factors:")
        for f in factors:
            print(f"       • {f.get('rule', '?')}: +{f.get('score', 0):.0f}")
            for d in f.get('details', [])[:2]:
                print(f"         └─ {d}")

    print()
    print("=" * 60)
    print("  SIMULATION COMPLETE")
    print("  Check the Sentinel dashboard to see:")
    print("  • Risk score timeline for this user")
    print("  • Review queue items created")
    print("  • Rule trigger history")
    print("=" * 60)


if __name__ == "__main__":
    main()
