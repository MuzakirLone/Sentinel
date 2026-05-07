"""
Sentinel Python Tracker SDK

Lightweight SDK for sending security events to your Sentinel instance.

Usage:
    from sentinel_tracker import SentinelTracker

    tracker = SentinelTracker("http://localhost:8585", "sk_your_api_key")
    tracker.track("login_success", {
        "user_id": "usr_12345",
        "email": "user@example.com",
        "ip": "203.0.113.42",
    })
"""

import json
import logging
import hashlib
import hmac
import time
from typing import Any, Dict, List, Optional
from urllib.request import Request, urlopen
from urllib.error import URLError, HTTPError

logger = logging.getLogger("sentinel")


class SentinelTracker:
    """Sentinel security event tracker."""

    def __init__(
        self,
        base_url: str,
        api_key: str,
        api_secret: Optional[str] = None,
        timeout: int = 5,
        batch_size: int = 50,
    ):
        self.base_url = base_url.rstrip("/")
        self.api_key = api_key
        self.api_secret = api_secret
        self.timeout = timeout
        self.batch_size = batch_size
        self._queue: List[Dict] = []

    def track(self, event_type: str, data: Optional[Dict[str, Any]] = None) -> Optional[Dict]:
        """Track a single event."""
        payload = {**(data or {}), "event_type": event_type}
        return self._post("/api/v1/events", payload)

    def queue(self, event_type: str, data: Optional[Dict[str, Any]] = None) -> None:
        """Add event to batch queue."""
        self._queue.append({**(data or {}), "event_type": event_type})
        if len(self._queue) >= self.batch_size:
            self.flush()

    def flush(self) -> Optional[Dict]:
        """Send all queued events."""
        if not self._queue:
            return None
        payload = {"events": self._queue}
        self._queue = []
        return self._post("/api/v1/events/batch", payload)

    def check_blacklist(self, params: Dict[str, str]) -> Optional[Dict]:
        """Check if a user/IP is blacklisted."""
        return self._post("/api/v1/blacklist/check", params)

    def track_login(self, user_id: str, success: bool = True, **kwargs) -> Optional[Dict]:
        """Track a login event."""
        event_type = "login_success" if success else "login_failed"
        return self.track(event_type, {"user_id": user_id, **kwargs})

    def track_signup(self, user_id: str, email: str, **kwargs) -> Optional[Dict]:
        """Track a signup event."""
        return self.track("signup", {"user_id": user_id, "email": email, **kwargs})

    def track_field_change(
        self,
        user_id: str,
        entity_type: str,
        entity_id: int,
        field: str,
        old_value: str,
        new_value: str,
        **kwargs,
    ) -> Optional[Dict]:
        """Track a field change for audit trail."""
        return self.track("field_change", {
            "user_id": user_id,
            "field_changes": [{
                "entity_type": entity_type,
                "entity_id": entity_id,
                "field": field,
                "old_value": str(old_value),
                "new_value": str(new_value),
            }],
            **kwargs,
        })

    def _post(self, endpoint: str, data: Dict) -> Optional[Dict]:
        """Send a POST request to the Sentinel API."""
        url = f"{self.base_url}{endpoint}"
        payload = json.dumps(data).encode("utf-8")

        req = Request(url, data=payload, method="POST")
        req.add_header("Content-Type", "application/json")
        req.add_header("X-API-Key", self.api_key)

        # HMAC-SHA256 request signing
        if self.api_secret:
            timestamp = str(int(time.time()))
            body_hash = hashlib.sha256(payload).hexdigest()
            sign_payload = f"{timestamp}\nPOST\n{endpoint}\n{body_hash}"
            signature = hmac.new(
                self.api_secret.encode("utf-8"),
                sign_payload.encode("utf-8"),
                hashlib.sha256,
            ).hexdigest()
            req.add_header("X-Timestamp", timestamp)
            req.add_header("X-Signature", signature)

        try:
            with urlopen(req, timeout=self.timeout) as resp:
                return json.loads(resp.read().decode("utf-8"))
        except HTTPError as e:
            body = e.read().decode("utf-8") if e.fp else ""
            logger.error(f"Sentinel API HTTP {e.code}: {body}")
            return None
        except URLError as e:
            logger.error(f"Sentinel API Error: {e.reason}")
            return None
        except Exception as e:
            logger.error(f"Sentinel SDK Error: {e}")
            return None

    def __del__(self):
        """Flush remaining events on destruction."""
        try:
            self.flush()
        except Exception:
            pass


# ── Django / Flask middleware helpers ────────────────────

def get_client_ip(request) -> str:
    """Extract client IP from common framework request objects."""
    # Django
    if hasattr(request, "META"):
        return (
            request.META.get("HTTP_X_FORWARDED_FOR", "").split(",")[0].strip()
            or request.META.get("REMOTE_ADDR", "")
        )
    # Flask
    if hasattr(request, "remote_addr"):
        return request.headers.get("X-Forwarded-For", request.remote_addr)
    return ""


def get_user_agent(request) -> str:
    """Extract user agent from common framework request objects."""
    if hasattr(request, "META"):
        return request.META.get("HTTP_USER_AGENT", "")
    if hasattr(request, "headers"):
        return request.headers.get("User-Agent", "")
    return ""
