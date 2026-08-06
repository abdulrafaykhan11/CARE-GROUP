/**
 * CARE Nexus — Login Notification Engine
 * Shows ONE notification once per login session.
 * - Triggers after user logs in (detected via meta tag)
 * - Never shows again until user logs out and logs back in
 * - "Learn More" links correctly resolved for sub-directory pages
 */
(function () {
    'use strict';

    const DISMISSED_KEY = 'care_dismissed_notifs';
    const SESSION_SHOWN_KEY = 'care_notif_fired';

    /* ── Helpers ──────────────────────────────────────────────── */
    function getDismissed() {
        try { return JSON.parse(localStorage.getItem(DISMISSED_KEY) || '[]'); } catch { return []; }
    }

    function addDismissed(id) {
        const list = getDismissed();
        if (!list.includes(id)) list.push(id);
        localStorage.setItem(DISMISSED_KEY, JSON.stringify(list));
    }

    function hasShownThisSession() {
        return sessionStorage.getItem(SESSION_SHOWN_KEY) === '1';
    }

    function markShownThisSession() {
        sessionStorage.setItem(SESSION_SHOWN_KEY, '1');
    }

    // Called on fresh login — clears session flag so notification fires again
    function resetSessionFlag() {
        sessionStorage.removeItem(SESSION_SHOWN_KEY);
    }

    /* ── Resolve Action URL (handles subdir pages) ─────────────── */
    function resolveUrl(url) {
        if (!url || url === '#' || url.trim() === '') return null;
        if (url.startsWith('http://') || url.startsWith('https://')) return url;

        // Detect if we're inside a subdirectory
        const path = window.location.pathname;
        const inSubdir = path.includes('/patient/') || path.includes('/doctor/') || path.includes('/admin/');
        const cleaned = url.replace(/^\/+/, '');

        return inSubdir ? '../' + cleaned : cleaned;
    }

    /* ── CSS Styles ───────────────────────────────────────────── */
    function injectStyles() {
        if (document.getElementById('care-notif-styles')) return;
        const s = document.createElement('style');
        s.id = 'care-notif-styles';
        s.textContent = `
            #care-notif-container {
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                pointer-events: none;
                max-width: 380px;
                width: calc(100% - 48px);
            }
            .care-toast {
                pointer-events: all;
                background: #fff;
                border: 1px solid rgba(2,132,199,0.15);
                border-left: 4px solid #06b6d4;
                border-radius: 14px;
                padding: 16px 40px 16px 16px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(2,132,199,0.08);
                display: flex;
                align-items: flex-start;
                gap: 13px;
                transform: translateX(120%);
                opacity: 0;
                transition: transform 0.4s cubic-bezier(.22,1,.36,1), opacity 0.3s ease;
                position: relative;
            }
            .care-toast.in  { transform: translateX(0); opacity: 1; }
            .care-toast.out { transform: translateX(120%); opacity: 0; }
            .care-toast[data-type="Alert"]        { border-left-color: #ef4444; }
            .care-toast[data-type="Announcement"] { border-left-color: #8b5cf6; }
            .care-toast[data-type="News"]         { border-left-color: #10b981; }
            .care-toast[data-type="Promotion"]    { border-left-color: #f59e0b; }
            .care-toast-icon { font-size: 26px; flex-shrink: 0; margin-top: 2px; }
            .care-toast-body { flex: 1; min-width: 0; }
            .care-toast-tag {
                font-size: 10px; font-weight: 800; letter-spacing: 1.2px;
                text-transform: uppercase; color: #06b6d4;
                display: flex; align-items: center; gap: 5px; margin-bottom: 4px;
            }
            .care-toast[data-type="Alert"]        .care-toast-tag { color: #ef4444; }
            .care-toast[data-type="Announcement"] .care-toast-tag { color: #8b5cf6; }
            .care-toast[data-type="News"]         .care-toast-tag { color: #10b981; }
            .care-toast[data-type="Promotion"]    .care-toast-tag { color: #d97706; }
            .care-toast-dot {
                width: 6px; height: 6px; border-radius: 50%;
                background: currentColor; display: inline-block;
                animation: care-blink 1.5s infinite;
            }
            @keyframes care-blink { 0%,100% { opacity:1; } 50% { opacity:0.3; } }
            .care-toast-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px; line-height: 1.3; }
            .care-toast-msg   { font-size: 12.5px; color: #475569; line-height: 1.55; margin-bottom: 10px; }
            .care-toast-actions { display: flex; gap: 8px; flex-wrap: wrap; }
            .care-toast-btn {
                font-size: 12px; font-weight: 600; padding: 5px 14px;
                border-radius: 999px; border: none; cursor: pointer;
                text-decoration: none; display: inline-flex; align-items: center;
                transition: all 0.2s;
            }
            .care-toast-btn-main {
                background: linear-gradient(135deg,#06b6d4,#3b82f6);
                color: #fff !important;
            }
            .care-toast-btn-main:hover { background: linear-gradient(135deg,#0891b2,#2563eb); transform: translateY(-1px); }
            .care-toast[data-type="Alert"]        .care-toast-btn-main { background: linear-gradient(135deg,#ef4444,#dc2626); }
            .care-toast[data-type="Announcement"] .care-toast-btn-main { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
            .care-toast[data-type="News"]         .care-toast-btn-main { background: linear-gradient(135deg,#10b981,#059669); }
            .care-toast[data-type="Promotion"]    .care-toast-btn-main { background: linear-gradient(135deg,#f59e0b,#d97706); }
            .care-toast-btn-skip { background: transparent; color: #94a3b8; border: 1px solid rgba(148,163,184,.3); }
            .care-toast-btn-skip:hover { background: #f1f5f9; color: #64748b; }
            .care-toast-close {
                position: absolute; top: 10px; right: 12px;
                width: 22px; height: 22px; border-radius: 50%;
                background: rgba(148,163,184,.15); border: none; cursor: pointer;
                display: flex; align-items: center; justify-content: center;
                font-size: 14px; color: #94a3b8; transition: background 0.2s;
            }
            .care-toast-close:hover { background: #f1f5f9; color: #475569; }
            .care-toast-bar {
                position: absolute; bottom: 0; left: 0; height: 3px;
                width: 100%; background: rgba(6,182,212,.1);
                border-radius: 0 0 14px 14px; overflow: hidden;
            }
            .care-toast-bar-fill {
                height: 100%; background: linear-gradient(90deg,#06b6d4,#3b82f6);
                width: 100%; transform-origin: left;
                animation: care-shrink linear forwards;
            }
            @keyframes care-shrink { from { transform:scaleX(1); } to { transform:scaleX(0); } }
            @media (max-width: 480px) {
                #care-notif-container { bottom:14px; right:12px; left:12px; max-width:none; width:auto; }
            }
        `;
        document.head.appendChild(s);
    }

    /* ── Build & Show One Toast ───────────────────────────────── */
    function showToast(notif, autoMs) {
        let container = document.getElementById('care-notif-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'care-notif-container';
            document.body.appendChild(container);
        }

        const actionUrl = resolveUrl(notif.action_url);

        const toast = document.createElement('div');
        toast.className = 'care-toast';
        toast.dataset.type = notif.type || 'General';
        toast.dataset.id   = notif.id;

        const cleanTitle = notif.title.replace(/^[\S]+\s/, ''); // strip leading emoji

        toast.innerHTML = `
            <div class="care-toast-icon">${notif.icon || '🔔'}</div>
            <div class="care-toast-body">
                <div class="care-toast-tag">
                    <span class="care-toast-dot"></span>
                    CARE NEXUS — ${notif.type || 'Update'}
                </div>
                <div class="care-toast-title">${esc(cleanTitle)}</div>
                <div class="care-toast-msg">${esc(notif.message)}</div>
                <div class="care-toast-actions">
                    ${actionUrl
                        ? `<a href="${actionUrl}" class="care-toast-btn care-toast-btn-main">View Details →</a>`
                        : ''}
                    <button class="care-toast-btn care-toast-btn-skip js-skip">Don't show again</button>
                </div>
            </div>
            <button class="care-toast-close js-close" title="Close">✕</button>
            <div class="care-toast-bar">
                <div class="care-toast-bar-fill" style="animation-duration:${autoMs}ms;"></div>
            </div>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('in')));

        let timer = setTimeout(() => dismiss(toast, false), autoMs);

        toast.querySelector('.js-close').onclick = () => { clearTimeout(timer); dismiss(toast, false); };
        toast.querySelector('.js-skip').onclick  = () => { clearTimeout(timer); dismiss(toast, true); };

        toast.addEventListener('mouseenter', () => {
            clearTimeout(timer);
            toast.querySelector('.care-toast-bar-fill').style.animationPlayState = 'paused';
        });
        toast.addEventListener('mouseleave', () => {
            toast.querySelector('.care-toast-bar-fill').style.animationPlayState = 'running';
            timer = setTimeout(() => dismiss(toast, false), 3000);
        });
    }

    function dismiss(toast, permanently) {
        if (permanently) addDismissed(parseInt(toast.dataset.id, 10));
        toast.classList.remove('in');
        toast.classList.add('out');
        setTimeout(() => toast.remove(), 500);
    }

    function esc(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    /* ── Fetch & Fire ─────────────────────────────────────────── */
    function fetchAndShow(role) {
        // Already shown this session — don't repeat
        if (hasShownThisSession()) return;

        const dismissed = getDismissed();
        const path = window.location.pathname;
        const prefix = (path.includes('/patient/') || path.includes('/doctor/') || path.includes('/admin/'))
            ? '../' : '';

        fetch(`${prefix}api/get_notifications.php?limit=6&role=${encodeURIComponent(role)}`)
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success' || !Array.isArray(data.data) || !data.data.length) return;

                const available = data.data.filter(n => !dismissed.includes(n.id));
                if (!available.length) return;

                // Mark as shown BEFORE displaying (prevents double-fire on fast navigations)
                markShownThisSession();

                // Show first available notification after a brief 2-second delay
                setTimeout(() => showToast(available[0], 10000), 2000);
            })
            .catch(() => {}); // silent fail
    }

    /* ── Init ─────────────────────────────────────────────────── */
    function init() {
        injectStyles();

        // Never show on admin pages
        if (window.location.pathname.includes('/admin/')) return;

        const loggedInMeta  = document.querySelector('meta[name="care-user-logged-in"]');
        const justLoginMeta = document.querySelector('meta[name="care-just-logged-in"]');
        const roleMeta      = document.querySelector('meta[name="care-user-role"]');

        const isLoggedIn   = loggedInMeta  && loggedInMeta.content  === 'true';
        const justLoggedIn = justLoginMeta && justLoginMeta.content === 'true';
        const role         = roleMeta ? roleMeta.content : 'All';

        // If this is a fresh login, reset the session flag so notification fires
        if (justLoggedIn) {
            resetSessionFlag();
        }

        // Show if user is logged in and hasn't seen it this session
        if (isLoggedIn) {
            fetchAndShow(role);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
