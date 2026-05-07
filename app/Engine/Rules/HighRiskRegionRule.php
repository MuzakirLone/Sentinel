<?php

namespace Sentinel\Engine\Rules;

/**
 * Geo Anomaly Detection — Impossible Travel & High-Risk Region Analysis
 *
 * Goes beyond simple country blocklists to detect:
 * - Impossible travel (Haversine distance / time = speed > physically possible)
 * - Country-hop velocity anomalies
 * - TOR/VPN/proxy/datacenter connections
 * - First-time country access for established users
 * - Sanctioned region access
 */
class HighRiskRegionRule implements RuleInterface
{
    private array $flaggedCountries = ['KP', 'IR', 'SY', 'CU', 'SD'];

    /**
     * Speed thresholds in km/h:
     * - Commercial flight: ~900 km/h
     * - Anything above this in a short window is "impossible travel"
     * - Between 500-900: suspicious but possible (connecting flights)
     */
    private const IMPOSSIBLE_SPEED_KMH = 900;
    private const SUSPICIOUS_SPEED_KMH = 500;
    private const MIN_DISTANCE_KM = 100; // Ignore tiny distances (same metro area)

    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        $isTor = $context['is_tor'] ?? false;
        $isVpn = $context['is_vpn'] ?? false;
        $isProxy = $context['is_proxy'] ?? false;
        $isDatacenter = $context['is_datacenter'] ?? false;
        $country = $context['ip_country'] ?? '';

        // ─── 1. Anonymization Layer Detection ──────────────

        if ($isTor) {
            $score += 35;
            $details[] = 'Connection via TOR exit node — identity fully anonymized';
        }

        if ($isVpn || $isProxy) {
            $penalty = ($isVpn && $isProxy) ? 20 : 15;
            $score += $penalty;
            $label = $isVpn ? 'VPN' : 'proxy';
            $details[] = "Connection via {$label} — true origin masked";
        }

        if ($isDatacenter) {
            $score += 10;
            $details[] = 'Connection from datacenter IP range (non-residential)';
        }

        // ─── 2. Sanctioned/Flagged Region ──────────────────

        if ($country && in_array(strtoupper($country), $this->flaggedCountries)) {
            $score += 30;
            $details[] = "Access from sanctioned/high-risk region: {$country}";
        }

        // ─── 3. Impossible Travel Detection (Killer Feature) ──

        $travelDistance = $context['travel_distance_km'] ?? null;
        $travelSpeed = $context['travel_speed_kmh'] ?? null;
        $previousCountry = $context['previous_ip_country'] ?? '';
        $previousEventTime = $context['previous_event_time'] ?? null;

        if ($travelDistance !== null && $travelSpeed !== null && $travelDistance >= self::MIN_DISTANCE_KM) {
            if ($travelSpeed > self::IMPOSSIBLE_SPEED_KMH) {
                // Physically impossible travel
                $timeDiffMin = $previousEventTime
                    ? round((time() - strtotime($previousEventTime)) / 60, 1)
                    : '?';

                $score += 40;
                $details[] = sprintf(
                    'IMPOSSIBLE TRAVEL: %s → %s (%.0f km in %s min = %.0f km/h, max plausible: %d km/h)',
                    $previousCountry ?: '??',
                    $country ?: '??',
                    $travelDistance,
                    $timeDiffMin,
                    $travelSpeed,
                    self::IMPOSSIBLE_SPEED_KMH
                );
            } elseif ($travelSpeed > self::SUSPICIOUS_SPEED_KMH) {
                // Suspicious but possible (connecting flights)
                $score += 20;
                $details[] = sprintf(
                    'Suspicious travel velocity: %s → %s (%.0f km at %.0f km/h)',
                    $previousCountry ?: '??',
                    $country ?: '??',
                    $travelDistance,
                    $travelSpeed
                );
            }
        }

        // ─── 4. First-Time Country for Established User ────

        $isNewCountry = $context['is_new_country_for_user'] ?? false;
        $knownCountries = $context['baseline_known_countries'] ?? [];
        $userAgeHours = $context['user_age_hours'] ?? 0;

        if ($isNewCountry && $userAgeHours > 168 && count($knownCountries) > 0) {
            // User is >7 days old, has country history, and this is a new country
            $score += 15;
            $details[] = sprintf(
                'First access from %s — user previously seen from: %s',
                $country,
                implode(', ', array_slice($knownCountries, 0, 5))
            );
        }

        // ─── 5. Country Change Velocity ────────────────────
        // Even without lat/lng, detect rapid country changes
        if (!$travelDistance && $previousCountry && $country && $previousCountry !== $country) {
            $hoursSinceLastSeen = $context['hours_since_last_seen'] ?? 999;
            if ($hoursSinceLastSeen < 1) {
                $score += 25;
                $details[] = sprintf(
                    'Rapid country change: %s → %s in %.0f minutes',
                    $previousCountry,
                    $country,
                    $hoursSinceLastSeen * 60
                );
            } elseif ($hoursSinceLastSeen < 4) {
                $score += 12;
                $details[] = sprintf(
                    'Country changed from %s to %s in %.1f hours',
                    $previousCountry,
                    $country,
                    $hoursSinceLastSeen
                );
            }
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 15,
            'Geographical anomaly detected',
            $details
        );
    }

    public function getWeight(): float { return 1.2; }
    public function getSlug(): string { return 'high_risk_region'; }
    public function getCategory(): string { return 'geo'; }
}
