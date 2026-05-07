#!/usr/bin/env python3
"""
Sentinel Attack Simulation Suite — Bot Traffic

Simulates automated bot activity:
- Machine-like request timing (near-zero interval variance)
- Suspicious user-agents
- Hammering same endpoint repeatedly (low page diversity)
- Datacenter IP range

Expected detection:
- BotDetectionRule: timing entropy analysis detects machine regularity
- Session depth anomaly: many requests, few unique pages
- User-agent pattern matching
"""

import requests
import time
import sys

API_URL = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8585"
API_KEY = sys.argv[2] if len(sys.argv) > 2 else "sk_test_key"
BOT_USER = "usr_bot_scraper_001"
BOT_IP = "167.99.100.50"  # DigitalOcean datacenter

HEADERS = {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
}

BOT_USER_AGENTS = [
    "python-requests/2.31.0",
    "Go-http-client/1.1",
    "Mozilla/5.0 (compatible; Googlebot/2.1)",
    "Scrapy/2.11.0",
    "axios/1.6.2",
]


def send_event(event_type, url, ua):
    payload = {
        "event_type": event_type,
        "user_id": BOT_USER,
        "ip": BOT_IP,
        "user_agent": ua,
        "url": url,
        "metadata": {"is_datacenter": True},
    }
    try:
        r = requests.post(f"{API_URL}/api/v1/events", json=payload, headers=HEADERS, timeout=10)
        return r.json()
    except Exception as e:
        return {"error": str(e)}


def main():
    print("=" * 60)
    print("  SENTINEL ATTACK SIMULATION: Bot Traffic")
    print("=" * 60)
    print(f"  Bot user:  {BOT_USER}")
    print(f"  Bot IP:    {BOT_IP} (datacenter)")
    print(f"  API:       {API_URL}")
    print("=" * 60)
    print()

    # Phase 1: Regular-interval scraping (machine-like timing)
    print("[Phase 1] Machine-like scraping (fixed 100ms intervals)...")
    print("  Real humans have irregular timing (σ > 2s)")
    print("  Bots have near-zero variance (σ < 0.3s)")
    print()

    target_url = "/api/products"  # Hammering same endpoint
    ua = BOT_USER_AGENTS[0]

    for i in range(30):
        result = send_event("page_view", target_url, ua)
        risk = result.get('risk_score', '?')
        level = result.get('risk_level', '?')
        
        icon = "🤖" if isinstance(risk, (int, float)) and risk >= 30 else "📡"
        print(f"  {icon} Request {i+1:3d}/30 | URL: {target_url} | Risk: {risk} | {level}")
        
        factors = result.get('risk_factors', [])
        if factors and i % 10 == 9:
            print(f"      ┌─ Detected signals:")
            for f in factors[:3]:
                print(f"      │  {f.get('rule')}: +{f.get('score', 0):.0f}")
                for d in f.get('details', [])[:1]:
                    print(f"      │    └─ {d[:70]}")
            print(f"      └─")

        time.sleep(0.1)  # Fixed 100ms interval — machine signature

    # Phase 2: Different UA, still same pattern
    print()
    print("[Phase 2] Rotating user-agents (evasion attempt)...")
    for i, ua in enumerate(BOT_USER_AGENTS):
        for j in range(5):
            result = send_event("page_view", "/api/users", ua)
            risk = result.get('risk_score', '?')
            print(f"  🤖 UA: {ua[:40]:40s} | Risk: {risk}")
            time.sleep(0.1)

    print()
    print("=" * 60)
    print("  SIMULATION COMPLETE")
    print()
    print("  Detection signals:")
    print("  • Request timing σ < 0.3s (machine regularity)")
    print("  • Low page diversity ratio")
    print("  • Suspicious user-agent patterns")
    print("  • Datacenter IP origin")
    print("  • Compound weak-signal aggregation")
    print("=" * 60)


if __name__ == "__main__":
    main()
