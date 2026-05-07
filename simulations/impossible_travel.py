#!/usr/bin/env python3
"""
Sentinel Attack Simulation Suite — Impossible Travel

Simulates impossible travel scenario:
- User logs in normally from India (Mumbai)
- 5 minutes later, same user logs in from Russia (Moscow)
- Distance: ~4,700 km in 5 minutes = 56,400 km/h (impossible)

Expected detection:
- HighRiskRegionRule triggers impossible travel detection
- Haversine distance calculation shows exact km traveled
- Travel speed flagged as exceeding commercial flight speed (900 km/h)
"""

import requests
import time
import sys

API_URL = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8585"
API_KEY = sys.argv[2] if len(sys.argv) > 2 else "sk_test_key"
TARGET_USER = "usr_travel_victim_001"

HEADERS = {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
}

# Location data (would normally come from IP geolocation service)
LOCATIONS = [
    {
        "name": "Mumbai, India",
        "ip": "103.21.244.10",
        "country": "IN",
        "city": "Mumbai",
        "latitude": 19.0760,
        "longitude": 72.8777,
    },
    {
        "name": "Moscow, Russia",
        "ip": "91.108.56.130",
        "country": "RU",
        "city": "Moscow",
        "latitude": 55.7558,
        "longitude": 37.6173,
    },
    {
        "name": "São Paulo, Brazil",
        "ip": "200.160.2.10",
        "country": "BR",
        "city": "Sao Paulo",
        "latitude": -23.5505,
        "longitude": -46.6333,
    },
]


def send_event(event_type, user_id, location, extra=None):
    payload = {
        "event_type": event_type,
        "user_id": user_id,
        "email": "traveler@company.com",
        "ip": location["ip"],
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
    }
    if extra:
        payload.update(extra)

    try:
        r = requests.post(f"{API_URL}/api/v1/events", json=payload, headers=HEADERS, timeout=10)
        return r.json()
    except Exception as e:
        return {"error": str(e)}


def haversine(lat1, lon1, lat2, lon2):
    """Calculate distance in km between two lat/lng points."""
    import math
    R = 6371  # Earth radius in km
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = math.sin(dlat/2)**2 + math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) * math.sin(dlon/2)**2
    return R * 2 * math.atan2(math.sqrt(a), math.sqrt(1-a))


def main():
    print("=" * 60)
    print("  SENTINEL ATTACK SIMULATION: Impossible Travel")
    print("=" * 60)
    print(f"  Target user: {TARGET_USER}")
    print(f"  API:         {API_URL}")
    print("=" * 60)
    print()

    india = LOCATIONS[0]
    russia = LOCATIONS[1]
    brazil = LOCATIONS[2]
    
    distance_km = haversine(india["latitude"], india["longitude"],
                            russia["latitude"], russia["longitude"])
    
    # Phase 1: Establish baseline from India
    print("[Phase 1] Establishing baseline from India...")
    for i in range(5):
        result = send_event("login_success", TARGET_USER, india)
        print(f"  ✓ Login from {india['name']} — Risk: {result.get('risk_score', '?')}")
        time.sleep(0.5)

    print()
    print("[Phase 2] Normal browsing from India...")
    for i in range(3):
        result = send_event("page_view", TARGET_USER, india)
        print(f"  ✓ Page view from {india['name']} — Risk: {result.get('risk_score', '?')}")
        time.sleep(0.3)

    print()
    wait_seconds = 5
    print(f"[Phase 3] Waiting {wait_seconds}s (simulating 5 minutes)...")
    time.sleep(wait_seconds)

    print()
    print(f"[Phase 4] IMPOSSIBLE TRAVEL: Login from Russia!")
    print(f"  📍 {india['name']} → {russia['name']}")
    print(f"  📏 Distance: {distance_km:,.0f} km")
    print(f"  ⚡ If traveled in 5 min: {distance_km / (5/60):,.0f} km/h")
    print(f"  ✈️  Max plausible (commercial flight): 900 km/h")
    print()

    result = send_event("login_success", TARGET_USER, russia)
    risk = result.get('risk_score', '?')
    level = result.get('risk_level', '?')
    confidence = result.get('confidence', '?')

    print(f"  🔴 Login from {russia['name']}:")
    print(f"     Risk Score:      {risk}")
    print(f"     Risk Level:      {level}")
    print(f"     Confidence:      {confidence}%")

    factors = result.get('risk_factors', [])
    if factors:
        print(f"     Risk Factors:")
        for f in factors:
            print(f"       • {f.get('rule', '?')}: +{f.get('score', 0):.0f} — {f.get('reason', '')}")
            for d in f.get('details', []):
                print(f"         └─ {d}")

    # Phase 5: Continue from Brazil (triple impossible travel)
    print()
    print(f"[Phase 5] SECOND IMPOSSIBLE TRAVEL: Login from Brazil!")
    time.sleep(3)

    dist2 = haversine(russia["latitude"], russia["longitude"],
                      brazil["latitude"], brazil["longitude"])
    print(f"  📍 {russia['name']} → {brazil['name']}")
    print(f"  📏 Distance: {dist2:,.0f} km")
    print()

    result = send_event("login_success", TARGET_USER, brazil)
    print(f"  🔴 Risk Score: {result.get('risk_score', '?')}")
    print(f"     Risk Level: {result.get('risk_level', '?')}")
    
    factors = result.get('risk_factors', [])
    if factors:
        print(f"     Factors:")
        for f in factors:
            print(f"       • {f.get('rule')}: +{f.get('score', 0):.0f}")
            for d in f.get('details', [])[:2]:
                print(f"         └─ {d}")

    print()
    print("=" * 60)
    print("  SIMULATION COMPLETE")
    print("  Expected detections:")
    print("  • IMPOSSIBLE TRAVEL alert (IN→RU→BR)")
    print("  • New country for established user")
    print("  • Haversine distance + velocity calculation")
    print("  • Auto-escalation to critical risk level")
    print("=" * 60)


if __name__ == "__main__":
    main()
