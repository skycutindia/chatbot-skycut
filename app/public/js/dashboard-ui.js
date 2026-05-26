(function () {
    'use strict';

    const ACTION_SHEET_MQ = window.matchMedia('(max-width: 1023px)');

    initSessionSecurity();
    initWebsiteActions();
    initWebsitesIndex();

    function initSessionSecurity() {
        if (!document.body.classList.contains('dash-body')) {
            return;
        }

        const sessionExpiredUrl = document.querySelector('meta[name="session-expired-url"]')?.content;
        const beaconUrl = document.querySelector('meta[name="logout-beacon-url"]')?.content;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const nav = performance.getEntriesByType('navigation')[0];

        if (nav?.type === 'reload' && sessionExpiredUrl) {
            window.location.replace(sessionExpiredUrl);
            return;
        }

        let internalNav = false;

        document.querySelectorAll('a[href]').forEach((link) => {
            try {
                const url = new URL(link.href, window.location.origin);
                if (url.origin === window.location.origin) {
                    link.addEventListener('click', () => {
                        internalNav = true;
                    });
                }
            } catch (_) {}
        });

        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', () => {
                internalNav = true;
            });
        });

        window.addEventListener('pagehide', () => {
            if (internalNav || !beaconUrl || !csrf) {
                return;
            }

            const data = new FormData();
            data.append('_token', csrf);
            navigator.sendBeacon(beaconUrl, data);
        });
    }

    function initWebsiteActions() {
        const root = document.getElementById('ws-action-sheet-root');
        const sheetBody = document.getElementById('ws-action-sheet-body');
        const sheet = root?.querySelector('.ws-action-sheet');
        const sheetTitle = document.getElementById('ws-action-sheet-title');
        const sheetClose = document.getElementById('ws-action-sheet-close');

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-actions-trigger]');
            if (trigger) {
                e.stopPropagation();
                toggleWebsiteActions(trigger);
                return;
            }

            if (
                e.target.closest('[data-actions-menu]') ||
                e.target.closest('#ws-action-sheet-root .ws-action-sheet')
            ) {
                return;
            }

            closeAllActionMenus();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllActionMenus();
        });

        window.addEventListener('resize', closeAllActionMenus);
        document.getElementById('dash-main-scroll')?.addEventListener('scroll', closeAllActionMenus, true);

        root?.addEventListener('click', (e) => {
            if (e.target === root) closeAllActionMenus();
        });

        sheetClose?.addEventListener('click', (e) => {
            e.stopPropagation();
            closeAllActionMenus();
        });

        sheetBody?.addEventListener('click', (e) => e.stopPropagation());

        ACTION_SHEET_MQ.addEventListener('change', closeAllActionMenus);
    }

    function useActionSheet() {
        return ACTION_SHEET_MQ.matches;
    }

    function toggleWebsiteActions(trigger) {
        const panelId = trigger.getAttribute('data-actions-panel-id');
        const templateId = trigger.getAttribute('data-actions-template-id');
        const panel = panelId ? document.getElementById(panelId) : null;
        const wrap = trigger.closest('[data-actions-menu]');
        const isOpen = trigger.getAttribute('aria-expanded') === 'true';

        if (isOpen) {
            closeAllActionMenus();
            return;
        }

        closeAllActionMenus();

        if (useActionSheet()) {
            openActionSheet(trigger, templateId);
            return;
        }

        if (!panel || !wrap) return;

        panel.classList.remove('hidden');
        panel.classList.add('is-positioned', 'is-open');
        positionActionsPanel(trigger, panel);
        wrap.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
    }

    function openActionSheet(trigger, templateId) {
        const root = document.getElementById('ws-action-sheet-root');
        const sheetBody = document.getElementById('ws-action-sheet-body');
        const sheet = root?.querySelector('.ws-action-sheet');
        const sheetTitle = document.getElementById('ws-action-sheet-title');
        const tpl = templateId ? document.getElementById(templateId) : null;

        if (!root || !sheetBody || !sheet || !tpl) return;

        sheetBody.innerHTML = tpl.innerHTML;
        if (sheetTitle) {
            sheetTitle.textContent = trigger.getAttribute('data-website-name') || 'Website actions';
        }

        root.classList.add('is-visible');
        sheet.classList.add('is-open');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ws-sheet-open');
        trigger.setAttribute('aria-expanded', 'true');
    }

    function positionActionsPanel(btn, panel) {
        const rect = btn.getBoundingClientRect();
        const gap = 8;
        const maxH = Math.min(window.innerHeight * 0.75, 420);
        const panelWidth = Math.min(288, window.innerWidth - 16);

        panel.style.position = 'fixed';
        panel.style.width = panelWidth + 'px';
        panel.style.maxHeight = maxH + 'px';
        panel.style.zIndex = '500';
        panel.style.overflowY = 'auto';

        let top = rect.bottom + gap;
        if (top + maxH > window.innerHeight - 8) {
            top = Math.max(8, rect.top - maxH - gap);
        }
        panel.style.top = top + 'px';
        panel.style.bottom = 'auto';

        const rightAlign = window.innerWidth - rect.right;
        if (rightAlign + panelWidth > window.innerWidth - 8) {
            panel.style.left = '8px';
            panel.style.right = 'auto';
        } else {
            panel.style.right = rightAlign + 'px';
            panel.style.left = 'auto';
        }
    }

    function closeAllActionMenus() {
        const root = document.getElementById('ws-action-sheet-root');
        const sheet = root?.querySelector('.ws-action-sheet');
        const sheetBody = document.getElementById('ws-action-sheet-body');

        if (root) {
            root.classList.remove('is-visible');
            sheet?.classList.remove('is-open');
            root.setAttribute('aria-hidden', 'true');
        }
        if (sheetBody) sheetBody.innerHTML = '';
        document.body.classList.remove('ws-sheet-open');

        document.querySelectorAll('[data-actions-trigger]').forEach((btn) => {
            btn.setAttribute('aria-expanded', 'false');
        });

        document.querySelectorAll('[data-actions-menu]').forEach((wrap) => {
            wrap.classList.remove('is-open');
            const btn = wrap.querySelector('[data-actions-trigger]');
            const panelId = btn?.getAttribute('data-actions-panel-id');
            const panel = panelId ? document.getElementById(panelId) : null;
            if (panel) {
                panel.classList.add('hidden');
                panel.classList.remove('is-positioned', 'is-open');
                panel.style.cssText = '';
            }
        });
    }

    function initWebsitesIndex() {
        const root = document.getElementById('websites-index');
        if (!root) return;

        const manageId = root.getAttribute('data-manage-website-id');
        if (manageId) {
            const card = document.getElementById('website-card-' + manageId);
            const editLink = card?.querySelector('.ws-site-card__cta, .ws-site-card__name-link');
            if (card) {
                requestAnimationFrame(() => {
                    card.classList.add('is-highlight');
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            }
            if (editLink instanceof HTMLAnchorElement) {
                setTimeout(() => editLink.focus(), 400);
            }
        }
    }

    const userBtn = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    function closeUserMenu() {
        userMenu?.classList.add('hidden');
        userBtn?.setAttribute('aria-expanded', 'false');
    }
    function openUserMenu() {
        if (!userMenu) return;
        userMenu.classList.remove('hidden');
        userBtn?.setAttribute('aria-expanded', 'true');
        syncThemeButtons();
        const first = userMenu.querySelector('.dash-user-menu__item');
        if (first instanceof HTMLElement) {
            setTimeout(() => first.focus({ preventScroll: true }), 30);
        }
    }
    function toggleUserMenu() {
        if (!userMenu) return;
        if (userMenu.classList.contains('hidden')) openUserMenu(); else closeUserMenu();
    }
    userBtn?.addEventListener('click', (e) => { e.stopPropagation(); toggleUserMenu(); });
    document.addEventListener('click', (e) => {
        if (!userMenu || userMenu.classList.contains('hidden')) return;
        const target = e.target instanceof Element ? e.target : null;
        if (target && (userMenu.contains(target) || userBtn?.contains(target))) return;
        closeUserMenu();
    });

    function readTheme() {
        try {
            const v = localStorage.getItem('dashboardTheme');
            if (v) return v;
            const legacy = localStorage.getItem('dashboard-theme');
            if (legacy === 'dark' || legacy === 'light') {
                localStorage.setItem('dashboardTheme', legacy);
                return legacy;
            }
            return 'system';
        } catch (_) { return 'system'; }
    }
    function writeTheme(value) {
        try {
            localStorage.setItem('dashboardTheme', value);
            localStorage.setItem('dashboard-theme', value === 'system' ? '' : value);
        } catch (_) {}
    }
    function applyTheme(mode) {
        const root = document.documentElement;
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const effective = mode === 'system' ? (prefersDark ? 'dark' : 'light') : mode;
        if (effective === 'dark') root.setAttribute('data-theme', 'dark');
        else root.setAttribute('data-theme', 'light');
        root.dataset.themeChoice = mode;
        const lightIcons = document.querySelectorAll('.dash-theme-icon-light');
        const darkIcons = document.querySelectorAll('.dash-theme-icon-dark');
        lightIcons.forEach((el) => el.classList.toggle('hidden', effective === 'dark'));
        darkIcons.forEach((el) => el.classList.toggle('hidden', effective !== 'dark'));
    }
    function syncThemeButtons() {
        const current = readTheme();
        document.querySelectorAll('#user-theme-options .dash-user-menu__theme-btn').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.theme === current);
            btn.setAttribute('aria-pressed', btn.dataset.theme === current ? 'true' : 'false');
        });
    }
    function setTheme(value) {
        writeTheme(value);
        applyTheme(value);
        syncThemeButtons();
    }
    applyTheme(readTheme());
    syncThemeButtons();
    document.querySelectorAll('#user-theme-options .dash-user-menu__theme-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            setTheme(btn.dataset.theme || 'system');
        });
    });
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', () => {
            if (readTheme() === 'system') applyTheme('system');
        });
    }
    const themeToggle = document.getElementById('theme-toggle');
    themeToggle?.addEventListener('click', () => {
        const current = readTheme();
        const next = current === 'dark' ? 'light' : (current === 'light' ? 'system' : 'dark');
        setTheme(next);
    });

    const shortcutsModal = document.getElementById('dash-shortcuts-modal');
    function openShortcuts() {
        shortcutsModal?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeShortcuts() {
        shortcutsModal?.classList.add('hidden');
        document.body.style.overflow = '';
    }
    document.getElementById('user-menu-shortcuts')?.addEventListener('click', () => {
        closeUserMenu();
        openShortcuts();
    });
    document.querySelectorAll('[data-shortcuts-close]').forEach((btn) => btn.addEventListener('click', closeShortcuts));
    shortcutsModal?.addEventListener('click', (e) => { if (e.target === shortcutsModal) closeShortcuts(); });

    let chordPrefix = '';
    let chordTimer = null;
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!userMenu?.classList.contains('hidden')) { closeUserMenu(); return; }
            if (!shortcutsModal?.classList.contains('hidden')) { closeShortcuts(); return; }
        }
        const target = e.target;
        const inEditable = target instanceof HTMLElement && (
            target.matches('input, textarea, select, [contenteditable="true"]') || target.isContentEditable
        );
        if (inEditable) return;

        if (e.key === '?' && (e.shiftKey || true)) {
            e.preventDefault();
            openShortcuts();
            return;
        }
        if (e.key === '/') {
            const search = document.getElementById('global-search');
            if (search instanceof HTMLInputElement) {
                e.preventDefault();
                search.focus();
                search.select();
            }
            return;
        }
        if (e.key === 'P' && e.shiftKey) { e.preventDefault(); toggleUserMenu(); return; }
        if (e.key === 't' || e.key === 'T') {
            if (!e.metaKey && !e.ctrlKey && !e.altKey) {
                e.preventDefault();
                themeToggle?.click();
                return;
            }
        }
        if (e.key.toLowerCase() === 'g' && !e.metaKey && !e.ctrlKey && !e.altKey) {
            chordPrefix = 'g';
            clearTimeout(chordTimer);
            chordTimer = setTimeout(() => { chordPrefix = ''; }, 1200);
            return;
        }
        if (chordPrefix === 'g') {
            chordPrefix = '';
            clearTimeout(chordTimer);
            const k = e.key.toLowerCase();
            const links = {
                d: '/dashboard',
                i: '/inbox',
                w: '/websites',
                s: '/settings',
            };
            if (links[k]) {
                e.preventDefault();
                window.location.href = links[k];
            }
        }
    });

    const searchInput = document.getElementById('global-search');
    const searchResults = document.getElementById('search-results');
    let searchTimer = null;

    function hideSearchResults() {
        searchResults?.classList.add('hidden');
    }

    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) {
            hideSearchResults();
            return;
        }
        searchTimer = setTimeout(async () => {
            try {
                const res = await fetch('/search?q=' + encodeURIComponent(q), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (!searchResults) return;
                if (!data.results?.length) {
                    searchResults.innerHTML = '<p class="px-4 py-3 text-sm dash-muted">No results</p>';
                } else {
                    searchResults.innerHTML = data.results
                        .map(
                            (r) =>
                                `<a href="${r.url}" class="block px-4 py-3 border-b border-[var(--dash-border)] last:border-0 hover:bg-[var(--dash-hover)]">
                                    <p class="text-sm font-medium">${escapeHtml(r.title)}</p>
                                    <p class="text-xs dash-muted">${escapeHtml(r.subtitle)} · ${escapeHtml(r.type)}</p>
                                </a>`
                        )
                        .join('');
                }
                searchResults.classList.remove('hidden');
            } catch (_) {}
        }, 250);
    });

    searchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const q = searchInput.value.trim();
            if (q) window.location.href = '/search?q=' + encodeURIComponent(q);
        }
        if (e.key === 'Escape') hideSearchResults();
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#global-search-wrap')) hideSearchResults();
    });

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    const replyForm = document.getElementById('reply-form');
    replyForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('reply-input');
        const content = input?.value.trim();
        if (!content) return;

        const btn = replyForm.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(replyForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ content }),
            });

            if (!res.ok) throw new Error('Failed');

            const data = await res.json();
            appendAgentMessage(data.message);
            if (input) input.value = '';
        } catch (_) {
            replyForm.submit();
        } finally {
            if (btn) btn.disabled = false;
        }
    });

    function appendAgentMessage(message) {
        const list = document.getElementById('message-list');
        if (!list || !message || document.querySelector(`[data-msg-id="${message.id}"]`)) return;

        const wrap = document.createElement('div');
        wrap.dataset.msgId = message.id;
        wrap.className = 'flex justify-start';
        wrap.innerHTML = `
            <div class="max-w-[80%] rounded-2xl px-4 py-3 text-sm bg-slate-800 border border-slate-700">
                ${escapeHtml(message.content)}
                <p class="text-xs opacity-60 mt-1">just now · ${escapeHtml(message.source)}</p>
            </div>`;
        list.appendChild(wrap);
        list.scrollTop = list.scrollHeight;
    }

    const realtime = window.__CHATBOT_REALTIME__;
    if (realtime?.page === 'conversation' && realtime.messagesUrl && realtime.conversationId) {
        let lastMessageId = 0;
        document.querySelectorAll('[data-msg-id]').forEach((el) => {
            const id = parseInt(el.dataset.msgId, 10);
            if (id > lastMessageId) lastMessageId = id;
        });

        setInterval(async () => {
            try {
                const res = await fetch(`${realtime.messagesUrl}?after_id=${lastMessageId}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                data.messages?.forEach((message) => {
                    if (message.sender_type === 'visitor') {
                        const list = document.getElementById('message-list');
                        if (!list || document.querySelector(`[data-msg-id="${message.id}"]`)) return;
                        const wrap = document.createElement('div');
                        wrap.dataset.msgId = message.id;
                        wrap.className = 'flex justify-end';
                        wrap.innerHTML = `
                            <div class="max-w-[80%] rounded-2xl px-4 py-3 text-sm bg-indigo-600">
                                ${escapeHtml(message.content)}
                                <p class="text-xs opacity-60 mt-1">${escapeHtml(message.source)}</p>
                            </div>`;
                        list.appendChild(wrap);
                        list.scrollTop = list.scrollHeight;
                    }
                    if (message.id > lastMessageId) lastMessageId = message.id;
                });
                const statusEl = document.getElementById('conv-status');
                if (statusEl && data.status) statusEl.textContent = data.status;
            } catch (_) {}
        }, 1500);
    }
})();
