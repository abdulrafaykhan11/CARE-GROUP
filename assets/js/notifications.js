/**
 * CARE Nexus — Real-Time Notification & Continuous Login Notification Cycle Engine
 * Features:
 *  - Immediate 2-notification pop-up sequence on user login
 *  - Automated interval loop sending 2 notifications every 30-40s (35s loop)
 *  - Native Browser Desktop Notification permission request + dual Toast delivery
 *  - Session state persistence & user dismissal controls
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'care_dismissed_notifs';
    const INTERVAL_MS = 35000; // 35 seconds (loop between 30 and 40s)
    let notifQueue = [];
    let queueIndex = 0;
    let cycleIntervalTimer = null;

    function getDismissed() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch { return []; }
    }

    function addDismissed(id) {
        const list = getDismissed();
        if (!list.includes(id)) list.push(id);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    }

    /* ── Native Browser Notification Request ────────────────── */
    function requestBrowserNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }
    }

    function triggerNativeNotification(notif) {
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                const cleanTitle = notif.title.replace(/^[^\s]+\s/, '');
                new Notification(`CARE Nexus: ${cleanTitle}`, {
                    body: notif.message,
                    icon: '/assets/uploads/care_icon.png',
                    tag: 'care-notif-' + notif.id
                });
            } catch (e) {}
        }
    }

    /* ── CSS Injection ─────────────────────────────────────── */
    function injectStyles() {
        if (document.getElementById('care-notif-styles')) return;
        const style = document.createElement('style');
        style.id = 'care-notif-styles';
        style.textContent = `
        #care-notif-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 14px;
            pointer-events: none;
            max-width: 380px;
            width: calc(100% - 48px);
        }

        .care-notif-toast {
            pointer-events: all;
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.18);
            border-left: 4px solid var(--cyan-neon, #06b6d4);
            border-radius: 14px;
            padding: 16px 18px 16px 16px;
            box-shadow:
                0 4px 16px rgba(0,0,0,0.08),
                0 1px 4px rgba(2,132,199,0.08),
                0 0 0 1px rgba(255,255,255,0.9) inset;
            display: flex;
            align-items: flex-start;
            gap: 13px;
            transform: translateX(110%);
            opacity: 0;
            transition: transform 0.45s cubic-bezier(.22,1,.36,1), opacity 0.35s ease;
            max-width: 100%;
            backdrop-filter: blur(12px);
            position: relative;
        }

        .care-notif-toast.care-notif-in {
            transform: translateX(0);
            opacity: 1;
        }

        .care-notif-toast.care-notif-out {
            transform: translateX(110%);
            opacity: 0;
        }

        .care-notif-icon {
            font-size: 28px;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .care-notif-body {
            flex: 1;
            min-width: 0;
        }

        .care-notif-eyebrow {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: var(--cyan-neon, #06b6d4);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .care-notif-type-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--cyan-neon, #06b6d4);
            display: inline-block;
            animation: care-pulse 1.6s infinite;
        }

        @keyframes care-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }

        .care-notif-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
            line-height: 1.3;
        }

        .care-notif-message {
            font-size: 12.5px;
            color: #475569;
            line-height: 1.55;
            margin-bottom: 10px;
        }

        .care-notif-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .care-notif-btn {
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .care-notif-btn-primary {
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            color: #fff;
        }
        .care-notif-btn-primary:hover {
            background: linear-gradient(135deg, #0891b2, #2563eb);
            transform: translateY(-1px);
            color: #fff;
        }

        .care-notif-btn-dismiss {
            background: transparent;
            color: #94a3b8;
            border: 1px solid rgba(148,163,184,0.35);
        }
        .care-notif-btn-dismiss:hover {
            background: #f1f5f9;
            color: #64748b;
        }

        .care-notif-close {
            position: absolute;
            top: 10px;
            right: 12px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(148,163,184,0.15);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #94a3b8;
            transition: all 0.2s;
            line-height: 1;
        }

        .care-notif-close:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .care-notif-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            background: rgba(6, 182, 212, 0.12);
            border-radius: 0 0 14px 14px;
            overflow: hidden;
        }

        .care-notif-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #06b6d4, #3b82f6);
            width: 100%;
            transform-origin: left;
            animation: care-progress-shrink linear forwards;
        }

        @keyframes care-progress-shrink {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0); }
        }

        .care-notif-date {
            font-size: 11px;
            color: #94a3b8;
            margin-left: auto;
        }

        /* Type-based border colors */
        .care-notif-toast[data-type="Alert"] { border-left-color: #ef4444; }
        .care-notif-toast[data-type="Alert"] .care-notif-eyebrow { color: #ef4444; }
        .care-notif-toast[data-type="Alert"] .care-notif-type-dot { background: #ef4444; }
        .care-notif-toast[data-type="Alert"] .care-notif-btn-primary { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .care-notif-toast[data-type="Announcement"] { border-left-color: #8b5cf6; }
        .care-notif-toast[data-type="Announcement"] .care-notif-eyebrow { color: #8b5cf6; }
        .care-notif-toast[data-type="Announcement"] .care-notif-type-dot { background: #8b5cf6; }
        .care-notif-toast[data-type="Announcement"] .care-notif-btn-primary { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .care-notif-toast[data-type="News"] { border-left-color: #10b981; }
        .care-notif-toast[data-type="News"] .care-notif-eyebrow { color: #10b981; }
        .care-notif-toast[data-type="News"] .care-notif-type-dot { background: #10b981; }
        .care-notif-toast[data-type="News"] .care-notif-btn-primary { background: linear-gradient(135deg, #10b981, #059669); }

        .care-notif-toast[data-type="Promotion"] { border-left-color: #f59e0b; }
        .care-notif-toast[data-type="Promotion"] .care-notif-eyebrow { color: #d97706; }
        .care-notif-toast[data-type="Promotion"] .care-notif-type-dot { background: #f59e0b; }
        .care-notif-toast[data-type="Promotion"] .care-notif-btn-primary { background: linear-gradient(135deg, #f59e0b, #d97706); }

        @media (max-width: 480px) {
            #care-notif-container {
                bottom: 16px;
                right: 12px;
                left: 12px;
                max-width: none;
                width: auto;
            }
        }
        `;
        document.head.appendChild(style);
    }

    /* ── Toast Builder ─────────────────────────────────────── */
    function buildToast(notif, autoHideMs) {
        let container = document.getElementById('care-notif-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'care-notif-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'care-notif-toast';
        toast.dataset.id = notif.id;
        toast.dataset.type = notif.type;

        const actionUrl = notif.action_url && notif.action_url !== '#'
            ? (notif.action_url.startsWith('http') ? notif.action_url : '/' + notif.action_url.replace(/^\//, ''))
            : null;

        toast.innerHTML = `
            <div class="care-notif-icon">${notif.icon || '🔔'}</div>
            <div class="care-notif-body">
                <div class="care-notif-eyebrow">
                    <span class="care-notif-type-dot"></span>
                    CARE NEXUS — ${notif.type}
                    <span class="care-notif-date">${notif.date || 'Just now'}</span>
                </div>
                <div class="care-notif-title">${escapeHtml(notif.title.replace(/^[^\s]+\s/, ''))}</div>
                <div class="care-notif-message">${escapeHtml(notif.message)}</div>
                <div class="care-notif-actions">
                    ${actionUrl ? `<a href="${actionUrl}" class="care-notif-btn care-notif-btn-primary">Learn More →</a>` : ''}
                    <button class="care-notif-btn care-notif-btn-dismiss js-dismiss-forever">Don't show again</button>
                </div>
            </div>
            <button class="care-notif-close js-close" title="Close">✕</button>
            <div class="care-notif-progress">
                <div class="care-notif-progress-bar" style="animation-duration: ${autoHideMs}ms;"></div>
            </div>
        `;

        container.appendChild(toast);

        // Fire native desktop notification if permitted
        triggerNativeNotification(notif);

        // Animate in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('care-notif-in'));
        });

        // Auto-hide timer
        let timer = setTimeout(() => dismissToast(toast, false), autoHideMs);

        // Close button
        toast.querySelector('.js-close').addEventListener('click', () => {
            clearTimeout(timer);
            dismissToast(toast, false);
        });

        // "Don't show again" button
        toast.querySelector('.js-dismiss-forever').addEventListener('click', () => {
            clearTimeout(timer);
            dismissToast(toast, true);
        });

        // Pause progress on hover
        toast.addEventListener('mouseenter', () => {
            clearTimeout(timer);
            toast.querySelector('.care-notif-progress-bar').style.animationPlayState = 'paused';
        });
        toast.addEventListener('mouseleave', () => {
            toast.querySelector('.care-notif-progress-bar').style.animationPlayState = 'running';
            timer = setTimeout(() => dismissToast(toast, false), 2500);
        });
    }

    function dismissToast(toast, permanently) {
        if (permanently) {
            addDismissed(parseInt(toast.dataset.id));
        }
        toast.classList.remove('care-notif-in');
        toast.classList.add('care-notif-out');
        setTimeout(() => toast.remove(), 500);
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    /* ── Send Pair of Notifications (Simultaneously / Staggered 400ms) ── */
    function sendNotificationPair() {
        if (!notifQueue.length) return;

        const dismissed = getDismissed();
        const available = notifQueue.filter(n => !dismissed.includes(n.id));
        if (!available.length) return;

        // Take 2 notifications from available
        const first = available[queueIndex % available.length];
        const second = available[(queueIndex + 1) % available.length];

        queueIndex = (queueIndex + 2) % available.length;

        // Fire first notification
        buildToast(first, 9000);

        // Fire second notification simultaneously (350ms delay for visual layout stacking)
        if (second && second.id !== first.id) {
            setTimeout(() => {
                buildToast(second, 9000);
            }, 350);
        }
    }

    /* ── Continuous Cycle Controller ────────────────────────── */
    function startLoginNotificationCycle(role) {
        requestBrowserNotificationPermission();

        const pathPrefix = (window.location.pathname.includes('/admin/') ||
                            window.location.pathname.includes('/doctor/') ||
                            window.location.pathname.includes('/patient/')) ? '../' : '';

        fetch(`${pathPrefix}api/get_notifications.php?limit=10&role=${encodeURIComponent(role)}`)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success' || !data.data || !data.data.length) return;

                notifQueue = data.data;

                // 1. Send 2 notifications immediately on login / init
                sendNotificationPair();

                // 2. Clear old interval if any and start loop every 35 seconds
                if (cycleIntervalTimer) clearInterval(cycleIntervalTimer);
                cycleIntervalTimer = setInterval(() => {
                    sendNotificationPair();
                }, INTERVAL_MS);
            })
            .catch(() => {});
    }

    /* ── Init ──────────────────────────────────────────────── */
    function init() {
        injectStyles();

        const roleMeta = document.querySelector('meta[name="care-user-role"]');
        const role = roleMeta ? roleMeta.content : 'All';

        const loggedInMeta = document.querySelector('meta[name="care-user-logged-in"]');
        const justLoggedInMeta = document.querySelector('meta[name="care-just-logged-in"]');

        const isLoggedIn = loggedInMeta ? loggedInMeta.content === 'true' : false;
        const justLoggedIn = justLoggedInMeta ? justLoggedInMeta.content === 'true' : false;

        if (justLoggedIn) {
            sessionStorage.setItem('care_just_logged_in', '1');
            sessionStorage.setItem('care_active_session', '1');
        }

        const isSessionActive = isLoggedIn || sessionStorage.getItem('care_active_session') === '1';

        if (isSessionActive) {
            // Trigger login notification cycle immediately on login & maintain loop
            startLoginNotificationCycle(role);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
