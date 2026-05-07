#!/usr/bin/env python3
"""
Sentinel Attack Simulation Suite — Account Takeover

Simulates a full account takeover sequence:
1. Normal user activity (baseline)
2. Attacker gains credentials
3. Login from new device + new country
4. Rapid credential changes (password → email → MFA disable)

Expected detection:
- AccountTakeoverRule: compound novelty signal + credential change chain
- HighRiskRegionRule: first-time country access
- Risk score escalates through the attack stages
"""

import requests
import time
import sys

API_URL = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8585"
API_KEY = sys.argv[2] if len(sys.argv) > 2 else "sk_test_key"
TARGET_USER = "usr_ato_victim_001"

HEADERS = {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
}

LEGIT_IP = "103.21.244.10"    # Victim's normal IP (India)
ATTACKER_IP = "45.33.32.156"  # Attacker IP (US, datacenter)

LEGIT_UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36"
ATTACKER_UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0"


def send_event(event_type, ip, ua, extra=None):
    payload = {
        "event_type": event_type,
        "user_id": TARGET_USER,
        "email": "victim.ato@company.com",
        "ip": ip,
        "user_agent": ua,
    }
    if extra:
        payload.update(extra)

    try:
        r = requests.post(f"{API_URL}/api/v1/events", json=payload, headers=HEADERS, timeout=10)
        return r.json()
    except Exception as e:
        return {"error": str(e)}


def print_result(label, result):
    risk = result.get('risk_score', '?')
    level = result.get('risk_level', '?')
    icon = "🟢" if risk == '?' or (isinstance(risk, (int, float)) and risk < 30) else \
           "🟡" if isinstance(risk, (int, float)) and risk < 60 else "🔴"
    print(f"  {icon} {label} | Risk: {risk} | Level: {level}")
    
    factors = result.get('risk_factors', [])
    if factors:
        for f in factors[:3]:
            print(f"      → {f.get('rule')}: +{f.get('score', 0):.0f} — {', '.join(f.get('details', [])[:1])}")


def main():
    print("=" * 60)
    print("  SENTINEL ATTACK SIMULATION: Account Takeover")
    print("=" * 60)
    print(f"  Victim:     {TARGET_USER}")
    print(f"  Legit IP:   {LEGIT_IP} (India)")
    print(f"  Attacker:   {ATTACKER_IP} (US datacenter)")
    print("=" * 60)
    print()

    # ─── Stage 1: Normal user activity ──────────────────
    print("[Stage 1] Normal user activity (building baseline)...")
    for i in range(8):
        events = ["login_success", "page_view", "page_view", "page_view",
                  "page_view", "login_success", "page_view", "page_view"]
        result = send_event(events[i], LEGIT_IP, LEGIT_UA)
        print_result(f"Normal activity #{i+1}", result)
        time.sleep(0.3)

    print()
    print("[Stage 2] Attacker phishing succeeded — credential compromised")
    print("  ⏳ Waiting 3s (simulating time passing)...")
    time.sleep(3)

    # ─── Stage 3: Attacker logs in ──────────────────────
    print()
    print("[Stage 3] Attacker logs in from different device + country...")
    result = send_event("login_success", ATTACKER_IP, ATTACKER_UA)
    print_result("🚨 Attacker login (new device + new IP)", result)
    time.sleep(0.5)

    # ─── Stage 4: Credential change chain ───────────────
    print()
    print("[Stage 4] Attacker changes credentials rapidly...")
    
    ato_sequence = [
        ("password_change", "Password changed"),
        ("email_change", "Email changed to attacker@evil.com"),
        ("mfa_disable", "MFA disabled"),
        ("recovery_email_change", "Recovery email changed"),
    ]

    for event_type, label in ato_sequence:
        result = send_event(event_type, ATTACKER_IP, ATTACKER_UA, {
            "metadata": {"action": label}
        })
        print_result(f"🔴 {label}", result)
        time.sleep(0.3)

    # ─── Stage 5: Attacker exfiltrates data ─────────────
    print()
    print("[Stage 5] Attacker exports data...")
    result = send_event("data_export", ATTACKER_IP, ATTACKER_UA)
    print_result("🔴 DATA EXPORT", result)

    print()
    print("=" * 60)
    print("  SIMULATION COMPLETE — Full ATO Chain")
    print()
    print("  Attack stages detected:")
    print("  1. New device + new IP login")
    print("  2. Rapid credential change chain")
    print("  3. password → email → MFA disable sequence")
    print("  4. Data exfiltration from compromised account")
    print()
    print("  Check dashboard for:")
    print("  • User risk score timeline")
    print("  • Review queue with 'critical' priority")
    print("  • Audit trail of all credential changes")
    print("=" * 60)


if __name__ == "__main__":
    main()
