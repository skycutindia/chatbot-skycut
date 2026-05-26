(function () {
    'use strict';

    const btn = document.getElementById('mention-menu-button');
    const panel = document.getElementById('mention-menu-panel');
    const list = document.getElementById('mention-menu-list');
    const indexUrl = btn?.dataset.mentionsUrl;
    const readUrl = btn?.dataset.mentionsReadUrl;

    if (!btn || !panel || !list || !indexUrl) return;

    async function loadMentions() {
        try {
            const res = await fetch(indexUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            const mentions = data.mentions || [];

            if (!mentions.length) {
                list.innerHTML = '<p class="dash-muted text-sm px-3 py-2">No unread mentions.</p>';
                return;
            }

            list.innerHTML = mentions.map((item) =>
                `<a href="${item.url}" class="lc-mention-feed-item">
                    <p class="font-medium text-sm">${escapeHtml(item.author || 'Teammate')} mentioned you</p>
                    <p class="text-xs dash-muted">${escapeHtml(item.visitor)} · ${escapeHtml(item.website || '')}</p>
                    <p class="text-xs mt-1">${escapeHtml(item.excerpt || '')}</p>
                </a>`
            ).join('');
        } catch (_) {
            list.innerHTML = '<p class="dash-muted text-sm px-3 py-2">Could not load mentions.</p>';
        }
    }

    function escapeHtml(value) {
        const el = document.createElement('span');
        el.textContent = value || '';
        return el.innerHTML;
    }

    btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const open = panel.classList.toggle('hidden');
        if (!open) {
            await loadMentions();
            if (readUrl) {
                fetch(readUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).catch(() => {});
            }
            const dot = btn.querySelector('.dash-notify-dot');
            if (dot) dot.remove();
        }
    });

    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && !btn.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
})();
