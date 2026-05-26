(function (global) {
    'use strict';

    const CATEGORIES = {
        Smileys: ['😀', '😃', '😄', '😁', '😅', '😂', '🙂', '😉', '😊', '😍', '🥰', '😘', '😎', '🤔', '😮', '😢', '😭', '😡', '🙄', '😴'],
        Gestures: ['👍', '👎', '👏', '🙌', '🙏', '🤝', '💪', '✌️', '🤞', '👋', '🫶', '👌', '✅', '❌', '⭐', '🔥', '💯', '🎉', '✨', '💡'],
        Hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '💔', '💕', '💖', '💗', '💘', '💝', '💞', '💓'],
        Chat: ['💬', '📞', '📧', '📎', '📁', '📅', '🕐', '🏠', '🛒', '💳', '🚚', '📦', '🔔', '❓', '❗', '⚠️', '🆘', '🔒', '🔑', '📝'],
    };

    function insertAtCursor(input, text) {
        if (!input) return;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        input.value = input.value.slice(0, start) + text + input.value.slice(end);
        const pos = start + text.length;
        input.setSelectionRange(pos, pos);
        input.focus();
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function buildPanel(panel, input, onPick) {
        panel.classList.add('emoji-picker-panel');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Emoji picker');
        panel.hidden = true;

        const tabs = document.createElement('div');
        tabs.className = 'emoji-picker-tabs';

        const grid = document.createElement('div');
        grid.className = 'emoji-picker-grid';

        const names = Object.keys(CATEGORIES);
        let active = names[0];

        function renderGrid(category) {
            grid.innerHTML = '';
            CATEGORIES[category].forEach((emoji) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'emoji-picker-item';
                btn.textContent = emoji;
                btn.setAttribute('aria-label', emoji);
                btn.addEventListener('click', () => {
                    insertAtCursor(input, emoji);
                    if (onPick) onPick(emoji);
                });
                grid.appendChild(btn);
            });
        }

        names.forEach((name) => {
            const tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'emoji-picker-tab' + (name === active ? ' emoji-picker-tab-active' : '');
            tab.textContent = name;
            tab.addEventListener('click', () => {
                active = name;
                tabs.querySelectorAll('.emoji-picker-tab').forEach((el) => {
                    el.classList.toggle('emoji-picker-tab-active', el.textContent === name);
                });
                renderGrid(name);
            });
            tabs.appendChild(tab);
        });

        panel.appendChild(tabs);
        panel.appendChild(grid);
        renderGrid(active);
    }

    function initEmojiPicker(options) {
        const trigger = typeof options.trigger === 'string'
            ? document.querySelector(options.trigger)
            : options.trigger;
        const input = typeof options.input === 'string'
            ? document.querySelector(options.input)
            : options.input;
        const panel = typeof options.panel === 'string'
            ? document.querySelector(options.panel)
            : options.panel;

        if (!trigger || !input || !panel) return null;

        buildPanel(panel, input, options.onPick);

        function close() {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }

        function toggle() {
            const open = panel.hidden;
            panel.hidden = !open;
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        trigger.type = trigger.type || 'button';
        trigger.setAttribute('aria-expanded', 'false');
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggle();
        });

        document.addEventListener('click', (e) => {
            if (panel.hidden) return;
            if (panel.contains(e.target) || trigger.contains(e.target)) return;
            close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !panel.hidden) close();
        });

        return { close, toggle };
    }

    global.initEmojiPicker = initEmojiPicker;

    function autoInit() {
        document.querySelectorAll('[data-emoji-trigger]').forEach((trigger) => {
            const input = document.querySelector(trigger.dataset.emojiInput || '');
            const panel = document.querySelector(trigger.dataset.emojiPanel || '');
            if (input && panel) {
                initEmojiPicker({ trigger, input, panel });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }
})(window);
