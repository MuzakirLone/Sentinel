#!/usr/bin/env python3
"""
Sentinel Attack Simulation Suite — Credential Stuffing

Simulates a credential stuffing attack:
- Same IP attempts login across 20+ different user accounts
- Mix of stolen credentials (low success rate)
- Varying user-agents to simulate botnet

Expected detection:
- CredentialStuffingRule triggers on low success/failure ratio
- Cross-account velocity detected
- IP gets flagged across multiple users
"""

import requests
import time
import sys
import random

API_URL = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8585"
API_KEY = sys.argv[2] if len(sys.argv) > 2 else "sk_test_key"
ATTACKER_IP = "192.99.44.101"  # OVH datacenter range

USER_AGENTS = [
    "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1)",
    "Mozilla/5.0 (X11; Linux x86_64) python-requests/2.28.0",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "python-requests/2.31.0",
    "curl/7.88.1",
]

HEADERS = {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
}

STOLEN_CREDS = [
    ("usr_john_doe", "john@example.com"),
    ("usr_jane_smith", "jane@corp.com"),
    ("usr_bob_wilson", "bob.w@mail.com"),
    ("usr_alice_chen", "alice.c@work.com"),
    ("usr_mike_brown", "mike.b@test.com"),
    ("usr_sarah_jones", "sarah.j@office.com"),
    ("usr_david_lee", "david.l@company.com"),
    ("usr_emma_davis", "emma.d@startup.com"),
    ("usr_chris_taylor", "chris.t@web.com"),
    ("usr_laura_white", "laura.w@service.com"),
    ("usr_james_martin", "james.m@app.com"),
    ("usr_kate_thompson", "kate.t@site.com"),
    ("usr_ryan_garcia", "ryan.g@platform.com"),
    ("usr_lisa_anderson", "lisa.a@biz.com"),
    ("usr_tom_jackson", "tom.j@dev.com"),
    ("usr_amy_robinson", "amy.r@tech.com"),
    ("usr_nick_harris", "nick.h@cloud.com"),
    ("usr_megan_clark", "megan.c@data.com"),
    ("usr_paul_lewis", "paul.l@api.com"),
    ("usr_anna_walker", "anna.w@net.com"),
]


def send_event(event_type, user_id, email, ua):
    payload = {
        "event_type": event_type,
        "user_id": user_id,
        "email": email,
        "ip": ATTACKER_IP,
        "user_agent": ua,
        "metadata": {"attack_simulation": "credential_stuffing"},
    }
    try:
        r = requests.post(f"{API_URL}/api/v1/events", json=payload, headers=HEADERS, timeout=10)
        return r.json()
    except Exception as e:
        return {"error": str(e)}


def main():
    print("=" * 60)
    print("  SENTINEL ATTACK SIMULATION: Credential Stuffing")
    print("=" * 60)
    print(f"  Attacker IP:    {ATTACKER_IP}")
    print(f"  Target accounts: {len(STOLEN_CREDS)}")
    print(f"  API:            {API_URL}")
    print("=" * 60)
    print()

    successes = 0
    failures = 0

    for i, (user_id, email) in enumerate(STOLEN_CREDS):
        ua = random.choice(USER_AGENTS)

        # Most attempts fail (credential stuffing has ~0.1-2% success rate)
        # Simulate 3 failures per account, then 5% chance of success
        for attempt in range(3):
            result = send_event("login_failed", user_id, email, ua)
            failures += 1
            risk = result.get('risk_score', '?')
            print(f"  ✗ [{i+1:2d}/{len(STOLEN_CREDS)}] {user_id} — FAILED (attempt {attempt+1}) | Risk: {risk}")
            time.sleep(0.05)

        # 5% success rate
        if random.random() < 0.05:
            result = send_event("login_success", user_id, email, ua)
            successes += 1
            risk = result.get('risk_score', '?')
            level = result.get('risk_level', '?')
            print(f"  ✓ [{i+1:2d}/{len(STOLEN_CREDS)}] {user_id} — SUCCESS 🔴 | Risk: {risk} | Level: {level}")

            factors = result.get('risk_factors', [])
            if factors:
                for f in factors[:2]:
                    print(f"      → {f.get('rule')}: +{f.get('score', 0):.0f}")
        
        time.sleep(0.1)

    print()
    print("=" * 60)
    print(f"  RESULTS: {failures} failures, {successes} successes")
    print(f"  Success rate: {successes/(successes+failures)*100:.1f}%")
    print(f"  (Real stuffing attacks: 0.1-2% success rate)")
    print("=" * 60)
    print()
    print("  Expected detections in dashboard:")
    print("  • CredentialStuffingRule: low success/failure ratio")
    print("  • Cross-account velocity from single IP")
    print("  • Multiple users flagged from same attacker IP")
    print("=" * 60)


if __name__ == "__main__":
    main()
