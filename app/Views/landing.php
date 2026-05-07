<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel — Enterprise Security Monitoring Framework</title>
    <meta name="description" content="Self-hosted security monitoring framework for threat detection and risk scoring.">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

        :root {
            --bg-base: #0a0e1a;
            --bg-surface: #0f1423;
            --bg-surface-hover: #1e2840;
            --text-main: #e8eaed;
            --text-muted: #9aa0b4;
            --cta-primary: #6366f1;
            --cta-hover: #5558e6;
            --border-color: rgba(255, 255, 255, 0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            line-height: 1.6;
            font-weight: 500;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, .code-font {
            font-family: 'IBM Plex Sans', sans-serif;
            font-weight: 700;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ── Navigation ────────────────────────────────────────── */
        header {
            padding: 24px 0;
            border-bottom: 1px solid var(--border-color);
            background: rgba(10, 14, 26, 0.8);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #6366f1, #22d3ee);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.1rem;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }

        .brand-text {
            background: linear-gradient(135deg, #ffffff, #22d3ee);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links a {
            margin-left: 24px;
            font-weight: 500;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--text-main);
        }

        .btn {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--cta-primary);
            color: #ffffff;
            border: 1px solid var(--cta-primary);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
        }

        .btn-primary:hover {
            background-color: var(--cta-hover);
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--bg-surface);
            border-color: var(--text-muted);
        }

        /* ── Hero Section ─────────────────────────────────────── */
        .hero {
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .hero h1 {
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 24px;
            background: linear-gradient(to right, #ffffff, #22d3ee);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto 40px;
        }

        .hero-cta {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        /* ── Comparison Section ───────────────────────────────── */
        .comparison {
            padding: 80px 0;
            background-color: var(--bg-surface);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 16px;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .matrix-container {
            overflow-x: auto;
        }

        .matrix {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .matrix th, .matrix td {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .matrix th {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .matrix th.sentinel-col {
            color: var(--cta-primary);
            background: rgba(99, 102, 241, 0.05);
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            border-bottom: none;
        }

        .matrix td.sentinel-col {
            background: rgba(99, 102, 241, 0.05);
            font-weight: 600;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }

        .matrix tr:last-child td.sentinel-col {
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            border-bottom: none;
        }

        .matrix td.feature-name {
            text-align: left;
            font-weight: 500;
            color: var(--text-main);
        }

        .icon-check { color: #34d399; font-size: 1.5rem; }
        .icon-cross { color: #ef4444; font-size: 1.2rem; }
        .icon-warn { color: #fbbf24; font-size: 1.2rem; }

        /* ── Feature Deep Dive ────────────────────────────────── */
        .features {
            padding: 100px 0;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .feature-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            padding: 32px;
            border-radius: 16px;
            transition: all 0.3s;
        }

        .feature-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--cta-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 24px;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* ── Winner CTA ───────────────────────────────────────── */
        .winner-cta {
            padding: 100px 0;
            text-align: center;
            background: linear-gradient(180deg, var(--bg-base) 0%, var(--bg-surface) 100%);
            border-top: 1px solid var(--border-color);
        }

        .winner-cta h2 {
            font-size: 3rem;
            margin-bottom: 24px;
        }

        .winner-cta p {
            font-size: 1.2rem;
            color: var(--text-muted);
            margin-bottom: 40px;
        }

        footer {
            padding: 40px 0;
            text-align: center;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.9rem;
            background: var(--bg-surface);
        }
        /* ── Custom Cursor ──────────────────────────────────────── */
        html.has-custom-cursor,
        html.has-custom-cursor *,
        body.has-custom-cursor, 
        body.has-custom-cursor * {
            cursor: none !important;
        }

        body.has-custom-cursor {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        body.has-custom-cursor input,
        body.has-custom-cursor textarea {
            -webkit-user-select: auto;
            -moz-user-select: auto;
            -ms-user-select: auto;
            user-select: auto;
        }

        .custom-cursor-dot,
        .custom-cursor-outline {
            position: fixed;
            top: 0;
            left: 0;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            z-index: 999999;
            pointer-events: none;
        }

        .custom-cursor-dot {
            width: 6px;
            height: 6px;
            background-color: var(--cta-primary);
            transition: transform 0.2s ease-out;
        }

        .custom-cursor-outline {
            width: 30px;
            height: 30px;
            border: 1.5px solid rgba(99, 102, 241, 0.5);
            transition: width 0.2s ease-out, height 0.2s ease-out, background-color 0.2s ease-out;
        }

        .custom-cursor-outline.cursor-hover {
            width: 45px;
            height: 45px;
            background-color: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.8);
        }

        .custom-cursor-outline.cursor-click {
            transform: translate(-50%, -50%) scale(0.8);
            background-color: rgba(99, 102, 241, 0.2);
        }

        .custom-cursor-dot.cursor-hover {
            transform: translate(-50%, -50%) scale(1.5);
            background-color: var(--text-main);
        }
    </style>
</head>
<body>

    <header>
        <div class="container nav-container">
            <div class="brand">
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <span class="brand-text">Sentinel</span>
            </div>
            <div class="nav-links">
                <a href="#comparison">Compare</a>
                <a href="#features">Features</a>
                <a href="/login" class="btn btn-secondary" style="margin-left: 16px; padding: 6px 16px;">Sign In</a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Stop Blind Spots.<br>Start Monitoring Real Threats.</h1>
            <p>The self-hosted, exactly-once idempotent security framework for enterprise web applications. Detect account takeover, brute force, and fraud instantly.</p>
            <div class="hero-cta">
                <a href="/signup" class="btn btn-primary">Deploy Now</a>
                <a href="#comparison" class="btn btn-secondary">See How We Compare</a>
            </div>
        </div>
    </section>

    <section id="comparison" class="comparison">
        <div class="container">
            <div class="section-header">
                <h2>Why Teams Choose Sentinel</h2>
                <p>Enterprise-grade threat detection without the enterprise SaaS pricing.</p>
            </div>
            <div class="matrix-container">
                <table class="matrix">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Capabilities</th>
                            <th>Legacy WAFs</th>
                            <th>Cloud SIEMs</th>
                            <th class="sentinel-col">Sentinel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="feature-name">Data Privacy (Self-Hosted)</td>
                            <td><span class="icon-warn">~</span></td>
                            <td><span class="icon-cross">✗</span></td>
                            <td class="sentinel-col"><span class="icon-check">✓</span></td>
                        </tr>
                        <tr>
                            <td class="feature-name">Behavioral Risk Scoring</td>
                            <td><span class="icon-cross">✗</span></td>
                            <td><span class="icon-warn">~</span></td>
                            <td class="sentinel-col"><span class="icon-check">✓</span></td>
                        </tr>
                        <tr>
                            <td class="feature-name">Exactly-Once Idempotency</td>
                            <td><span class="icon-cross">✗</span></td>
                            <td><span class="icon-warn">~</span></td>
                            <td class="sentinel-col"><span class="icon-check">✓</span></td>
                        </tr>
                        <tr>
                            <td class="feature-name">Impossible Travel Detection</td>
                            <td><span class="icon-cross">✗</span></td>
                            <td><span class="icon-check">✓</span></td>
                            <td class="sentinel-col"><span class="icon-check">✓</span></td>
                        </tr>
                        <tr>
                            <td class="feature-name">Pricing</td>
                            <td>$2,000+/mo</td>
                            <td>Usage-based</td>
                            <td class="sentinel-col">Open Source</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="text-align: center; margin-top: 40px;">
                <a href="/signup" class="btn btn-primary">Start Monitoring for Free</a>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Deep Space Data Visibility</h2>
                <p>Built for the modern, high-throughput enterprise.</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
                    <h3>Zero-Loss Ingestion</h3>
                    <p>API requests are buffered in Redis instantly with sub-10ms latency, ensuring your main application thread is never blocked during an attack.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 1.98-3A2.5 2.5 0 0 1 9.5 2Z"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-1.98-3A2.5 2.5 0 0 0 14.5 2Z"/></svg></div>
                    <h3>Contextual Risk Engine</h3>
                    <p>Calculates compound risk using exponential decay, ensuring new events spike risk while older behavioral anomalies smoothly age out.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                    <h3>HMAC-SHA256 Signed</h3>
                    <p>Every payload is cryptographically verified. Reject replay attacks and payload tampering before they even reach the database layer.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="winner-cta">
        <div class="container">
            <h2>Ready to secure your application?</h2>
            <p>Deploy Sentinel in under 5 minutes with our Docker integration.</p>
            <a href="/signup" class="btn btn-primary" style="font-size: 1.2rem; padding: 16px 40px;">Launch Sentinel Dashboard</a>
        </div>
    </section>

    <footer>
        <div class="container">
            &copy; <?= date('Y') ?> Sentinel Security Framework. All rights reserved.
        </div>
    </footer>

    <script src="/public/js/cursor.js"></script>
</body>
</html>
