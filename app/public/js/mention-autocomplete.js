(function () {
    'use strict';

    function initMentionAutocomplete(textarea) {
        const searchUrl = textarea.dataset.mentionSearchUrl;
        if (!searchUrl) return;

        const wrap = textarea.closest('.lc-mention-wrap') || textarea.parentElement;
        let menu = wrap.querySelector('.lc-mention-menu');
        if (!menu) {
            menu = document.createElement('div');
            menu.className = 'lc-mention-menu hidden';
            wrap.appendChild(menu);
        }

        let activeIndex = 0;
        let items = [];
        let mentionStart = null;

        function closeMenu() {
            menu.classList.add('hidden');
            menu.innerHTML = '';
            items = [];
            mentionStart = null;
        }

        function insertMention(agent) {
            const value = textarea.value;
            const before = value.slice(0, mentionStart);
            const after = value.slice(textarea.selectionStart);
            const mention = '@' + agent.name + ' ';
            textarea.value = before + mention + after;
            const pos = before.length + mention.length;
            textarea.setSelectionRange(pos, pos);
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            closeMenu();
        }

        function renderMenu() {
            if (!items.length) {
                closeMenu();
                return;
            }

            menu.innerHTML = items.map((agent, index) =>
                `<button type="button" class="lc-mention-option${index === activeIndex ? ' lc-mention-option-active' : ''}" data-index="${index}">
                    <span class="lc-mention-option-name">${escapeHtml(agent.name)}</span>
                    <span class="lc-mention-option-email">${escapeHtml(agent.email || '')}</span>
                </button>`
            ).join('');
            menu.classList.remove('hidden');

            menu.querySelectorAll('.lc-mention-option').forEach((btn) => {
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    insertMention(items[parseInt(btn.dataset.index, 10)]);
                });
            });
        }

        function escapeHtml(value) {
            const el = document.createElement('span');
            el.textContent = value || '';
            return el.innerHTML;
        }

        async function search(query) {
            const res = await fetch(searchUrl + '?q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            items = data.agents || [];
            activeIndex = 0;
            renderMenu();
        }

        textarea.addEventListener('input', () => {
            const pos = textarea.selectionStart;
            const before = textarea.value.slice(0, pos);
            const match = before.match(/@([^\s@]*)$/);
            if (!match) {
                closeMenu();
                return;
            }

            mentionStart = pos - match[0].length;
            search(match[1] || '');
        });

        textarea.addEventListener('keydown', (e) => {
            if (menu.classList.contains('hidden') || !items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                renderMenu();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                renderMenu();
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                e.preventDefault();
                insertMention(items[activeIndex]);
            } else if (e.key === 'Escape') {
                closeMenu();
            }
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) closeMenu();
        });
    }

    function autoInit() {
        document.querySelectorAll('[data-mention-search-url]').forEach(initMentionAutocomplete);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    window.initMentionAutocomplete = initMentionAutocomplete;
})();
