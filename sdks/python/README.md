# Sentinel Python Tracker

Lightweight Python SDK for sending security events to your Sentinel instance. Zero external dependencies.

## Installation

Copy `sentinel_tracker.py` to your project, or:

```bash
pip install sentinel-tracker
```

## Quick Start

```python
from sentinel_tracker import SentinelTracker

tracker = SentinelTracker("http://localhost:8585", "sk_your_api_key")

# Track a login
tracker.track_login("usr_12345", success=True, email="user@example.com")

# Track any custom event
tracker.track("page_view", {
    "user_id": "usr_12345",
    "url": "/dashboard",
    "ip": "203.0.113.42",
})

# Check blacklist
result = tracker.check_blacklist({"user_id": "usr_12345", "ip": "1.2.3.4"})
if result and result.get("blocked"):
    raise PermissionError("User is blocked")
```

## Django Integration

```python
# middleware.py
from sentinel_tracker import SentinelTracker, get_client_ip, get_user_agent

tracker = SentinelTracker("http://localhost:8585", "sk_your_api_key")

class SentinelMiddleware:
    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        response = self.get_response(request)

        if request.user.is_authenticated:
            tracker.queue("page_view", {
                "user_id": str(request.user.id),
                "email": request.user.email,
                "ip": get_client_ip(request),
                "user_agent": get_user_agent(request),
                "url": request.path,
            })

        return response
```

## Flask Integration

```python
from sentinel_tracker import SentinelTracker

tracker = SentinelTracker("http://localhost:8585", "sk_your_api_key")

@app.after_request
def track_request(response):
    if hasattr(g, "user"):
        tracker.queue("page_view", {
            "user_id": g.user.id,
            "ip": request.remote_addr,
            "user_agent": request.headers.get("User-Agent"),
            "url": request.path,
        })
    return response
```

## Requirements

- Python 3.7+
- No external dependencies
