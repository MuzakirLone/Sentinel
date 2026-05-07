<?php

namespace Sentinel\Engine\Rules;

/**
 * Bot Detection — Behavioral & Timing Entropy Analysis
 *
 * Improvements over simple UA-string matching:
 * - Request timing entropy: bots have regular intervals (low stddev), humans don't
 * - Session depth analysis: bots hit many pages but with narrow pattern
 * - Behavioral fingerprinting: combine multiple weak signals into strong classification
 * - User-agent anomaly scoring (not just blocklist matching)
 */
class BotDetectionRule implements RuleInterface
{
    private array $botPatterns = [
        'bot', 'crawler', 'spider', 'headless', 'phantom',
        'selenium', 'puppeteer', 'playwright', 'wget', 'curl',
        'python-requests', 'axios', 'node-fetch', 'go-http-client',
        'scrapy', 'httpclient', 'java/', 'perl', 'ruby',
    ];

    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        $userAgent = $context['user_agent'] ?? '';
        $isBot = $context['is_bot'] ?? false;

        // ─── 1. Known Bot Signature ────────────────────────
        if ($isBot) {
            $score += 40;
            $details[] = 'Known bot user-agent signature detected';
        }

        // Check for suspicious UA patterns
        $uaLower = strtolower($userAgent);
        foreach ($this->botPatterns as $pattern) {
            if (str_contains($uaLower, $pattern)) {
                $score += 20;
                $details[] = "Suspicious user-agent contains: '{$pattern}'";
                break;
            }
        }

        // Empty or impossibly short user-agent
        if (strlen($userAgent) < 10) {
            $score += 25;
            $details[] = 'Empty or impossibly short user-agent (missing browser fingerprint)';
        }

        // ─── 2. Request Timing Entropy (KEY INNOVATION) ───
        // Real humans have irregular intervals between requests.
        // Bots/scripts have suspiciously regular intervals (low standard deviation).
        $intervalStddev = $context['request_interval_stddev'] ?? -1;
        $eventsPerMinute = $context['events_per_minute'] ?? 0;

        if ($intervalStddev >= 0 && $eventsPerMinute > 5) {
            if ($intervalStddev < 0.3) {
                // Near-zero variance = machine-like regularity
                $score += 35;
                $details[] = sprintf(
                    'Request timing entropy: σ=%.3fs — machine-like regularity (human baseline: σ>2s)',
                    $intervalStddev
                );
            } elseif ($intervalStddev < 1.0) {
                $score += 15;
                $details[] = sprintf(
                    'Suspiciously regular request timing: σ=%.3fs',
                    $intervalStddev
                );
            }
        }

        // ─── 3. Inhuman Request Speed ──────────────────────
        if ($eventsPerMinute > 60) {
            $score += 35;
            $details[] = sprintf(
                '%.0f events/minute — physically impossible for human interaction',
                $eventsPerMinute
            );
        } elseif ($eventsPerMinute > 30) {
            $score += 20;
            $details[] = sprintf('%.0f events/minute — highly suspicious speed', $eventsPerMinute);
        }

        // ─── 4. Session Depth Analysis ─────────────────────
        // Bots: many events but few unique pages (hammering same endpoint)
        // Humans: fewer events but diverse page visits
        $sessionEvents = $context['session_event_count'] ?? 0;
        $uniquePages = $context['session_unique_pages'] ?? 0;

        if ($sessionEvents > 10 && $uniquePages > 0) {
            $diversityRatio = $uniquePages / $sessionEvents;
            if ($diversityRatio < 0.1) {
                $score += 20;
                $details[] = sprintf(
                    'Session depth anomaly: %d events but only %d unique pages (diversity ratio: %.2f — repeating same endpoint)',
                    $sessionEvents,
                    $uniquePages,
                    $diversityRatio
                );
            }
        }

        // ─── 5. Compound Bot Signal ────────────────────────
        // If we have 3+ weak signals, compound them
        $weakSignals = 0;
        if (strlen($userAgent) < 50 && strlen($userAgent) > 10) $weakSignals++;
        if ($eventsPerMinute > 10) $weakSignals++;
        if ($intervalStddev >= 0 && $intervalStddev < 2.0) $weakSignals++;
        if ($context['is_datacenter'] ?? false) $weakSignals++;

        if ($weakSignals >= 3 && $score < 40) {
            $score += 15;
            $details[] = "Compound bot signal: {$weakSignals} weak indicators combined (datacenter + timing + speed + UA)";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 25,
            'Bot or automated activity detected',
            $details
        );
    }

    public function getWeight(): float { return 1.5; }
    public function getSlug(): string { return 'bot_detection'; }
    public function getCategory(): string { return 'automation'; }
}
