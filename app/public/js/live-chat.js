(function () {
    'use strict';

    const configEl = document.getElementById('lc-config');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const realtime = window.__CHATBOT_REALTIME__ || {};
    const cfg = {
        bulkUrl: configEl?.dataset.bulkUrl || '',
        presenceUrl: configEl?.dataset.presenceUrl || '',
        pollUrl: configEl?.dataset.pollUrl || '',
        inboxUrl: configEl?.dataset.inboxUrl || '/inbox',
        inboxQuery: configEl?.dataset.inboxQuery || '',
        canWrite: configEl?.dataset.canWrite !== '0',
    };

    const replyForm = document.getElementById('lc-reply-form');
    const replyInput = document.getElementById('lc-reply-input');
    const messageList = document.getElementById('lc-message-list');
    const fileInput = document.getElementById('lc-file-input');
    const listScroll = document.getElementById('lc-list-scroll');

    /* ─── Utilities ─── */
    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function toast(message, type) {
        const root = document.getElementById('lc-toast-root');
        if (!root || !message) return;
        const el = document.createElement('div');
        el.className = 'lc-toast' + (type === 'error' ? ' lc-toast-error' : '');
        el.textContent = message;
        root.appendChild(el);
        requestAnimationFrame(() => el.classList.add('is-visible'));
        setTimeout(() => {
            el.classList.remove('is-visible');
            setTimeout(() => el.remove(), 300);
        }, 3200);
    }

    function validationMessage(data) {
        if (data?.message && typeof data.message === 'string') return data.message;
        const errors = data?.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors).flat().find(Boolean);
            if (first) return String(first);
        }
        return 'Request failed';
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body ?? {}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(validationMessage(data));
        return data;
    }

    async function postForm(url, formData) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(validationMessage(data));
        return data;
    }

    async function putForm(url, formData) {
        const fd = formData instanceof FormData ? formData : new FormData();
        if (!(formData instanceof FormData)) return postForm(url, fd);
        fd.append('_method', 'PUT');
        return postForm(url, fd);
    }

    /* ─── Messages ─── */
    function attachmentsHtml(attachments) {
        if (!attachments?.length) return '';
        return attachments.map((att) => {
            if (att.is_image) {
                return `<a href="${escapeHtml(att.url)}" target="_blank" class="lc-att-link"><img src="${escapeHtml(att.url)}" alt="${escapeHtml(att.original_name)}" class="lc-att-img"></a>`;
            }
            return `<a href="${escapeHtml(att.url)}" target="_blank" class="lc-att-file">📎 ${escapeHtml(att.original_name)}</a>`;
        }).join('');
    }

    function receiptHtml(status) {
        const s = status || 'sent';
        return `<span class="lc-receipt lc-receipt-${s}" title="${s}">${s === 'sent' ? '✓' : '✓✓'}</span>`;
    }

    function reactionsHtml(reactions) {
        if (!reactions?.length) return '';
        return `<div class="lc-reactions">${reactions.map((r) =>
            `<span class="lc-reaction-chip">${escapeHtml(r.emoji)}${r.count > 1 ? ' ' + r.count : ''}</span>`
        ).join('')}</div>`;
    }

    function appendMessage(msg) {
        if (!messageList || messageList.querySelector('[data-msg-id="' + msg.id + '"]')) return;

        const isVisitor = msg.sender_type === 'visitor';
        const isAgent = msg.sender_type === 'agent';
        const row = document.createElement('div');
        row.className = 'lc-msg-row' + (isVisitor ? ' lc-msg-row-visitor' : '');
        row.dataset.msgId = msg.id;
        const bubble = isVisitor ? 'visitor' : (isAgent ? 'agent' : 'bot');
        const body = (msg.source !== 'attachment' && msg.content ? escapeHtml(msg.content) : '') + attachmentsHtml(msg.attachments);
        const receipt = isAgent ? ' ' + receiptHtml(msg.receipt_status) : '';
        const reactions = !isVisitor ? reactionsHtml(msg.reactions) : '';
        row.innerHTML = `<div class="lc-msg-bubble lc-msg-bubble-${bubble}">${body}${reactions}<div class="lc-msg-time">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} · ${escapeHtml(msg.source || '')}${receipt}</div></div>`;
        messageList.appendChild(row);
        messageList.scrollTop = messageList.scrollHeight;
    }

    window.lcAppendMessage = appendMessage;
    window.lcShowToast = toast;

    function inboxItemUrl(conversationId) {
        const q = cfg.inboxQuery ? cfg.inboxQuery + '&' : '';
        return cfg.inboxUrl + '?' + q + 'conversation=' + conversationId;
    }

    function initialsFromName(name) {
        return (name || 'Visitor').split(/\s+/).filter(Boolean).slice(0, 2)
            .map((w) => w.charAt(0).toUpperCase()).join('') || 'V';
    }

    function appendInboxItem(data) {
        if (!listScroll) return;
        const id = data.conversation_id || data.id;
        if (!id || listScroll.querySelector('[data-conversation-id="' + id + '"]')) return;

        const empty = listScroll.querySelector('.lc-empty');
        empty?.remove();

        const name = data.visitor_name || 'Visitor';
        const status = data.status || 'awaiting_agent';
        const isAwaiting = status === 'awaiting_agent';
        const wrap = document.createElement('div');
        wrap.className = 'lc-item-wrap is-new';
        wrap.dataset.conversationId = id;
        wrap.innerHTML = `
            ${cfg.canWrite ? `<label class="lc-item-check-wrap"><input type="checkbox" class="lc-row-check" value="${id}"></label>` : ''}
            <a href="${escapeHtml(inboxItemUrl(id))}" class="lc-item">
                <span class="lc-item-avatar">${escapeHtml(initialsFromName(name))}</span>
                <span class="lc-item-body">
                    <span class="lc-item-top">
                        <span class="lc-item-name">${escapeHtml(name)}</span>
                        <span class="lc-item-time">now</span>
                    </span>
                    <span class="lc-item-preview">${escapeHtml(data.preview || 'New conversation')}</span>
                    <span class="lc-item-footer">
                        <span class="lc-item-site">${escapeHtml(data.website || '')}</span>
                        <span class="lc-pill ${isAwaiting ? 'is-warn' : ''}">${escapeHtml(status.replace(/_/g, ' '))}</span>
                    </span>
                </span>
                <span class="lc-unread" data-unread>1</span>
            </a>`;
        listScroll.prepend(wrap);

        wrap.querySelector('.lc-row-check')?.addEventListener('change', updateBulkBar);
        wrap.querySelector('.lc-row-check')?.addEventListener('click', (e) => e.stopPropagation());

        const countEl = document.getElementById('lc-list-count');
        if (countEl) countEl.textContent = String(parseInt(countEl.textContent, 10) + 1 || listScroll.querySelectorAll('.lc-item-wrap').length);

        setTimeout(() => wrap.classList.remove('is-new'), 4000);
    }

    window.lcAppendInboxItem = appendInboxItem;

    function updateReceipts(updates) {
        if (!messageList || !updates?.length) return;
        updates.forEach((item) => {
            const el = messageList.querySelector('[data-msg-id="' + item.id + '"] .lc-receipt');
            if (!el) return;
            const s = item.receipt_status || 'sent';
            el.className = 'lc-receipt lc-receipt-' + s;
            el.title = s;
            el.textContent = s === 'sent' ? '✓' : '✓✓';
        });
    }

    function updateReactions(reactionsByMessage) {
        if (!messageList || !reactionsByMessage) return;
        Object.entries(reactionsByMessage).forEach(([msgId, reactions]) => {
            const bubble = messageList.querySelector('[data-msg-id="' + msgId + '"] .lc-msg-bubble');
            if (!bubble) return;
            const timeEl = bubble.querySelector('.lc-msg-time');
            const html = reactionsHtml(reactions);
            const existing = bubble.querySelector('.lc-reactions');
            if (!html) { existing?.remove(); return; }
            if (existing) { existing.outerHTML = html; return; }
            const wrap = document.createElement('div');
            wrap.innerHTML = html;
            if (timeEl && wrap.firstElementChild) bubble.insertBefore(wrap.firstElementChild, timeEl);
        });
    }

    /* ─── Reply / compose ─── */
    if (replyForm && replyInput) {
        replyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = replyInput.value.trim();
            if (!content) return;
            const btn = replyForm.querySelector('[type="submit"]');
            btn.disabled = true;
            try {
                const data = await postJson(replyForm.dataset.replyUrl, { content });
                if (data.message) {
                    appendMessage(data.message);
                    replyInput.value = '';
                } else {
                    replyForm.submit();
                }
            } catch {
                replyForm.submit();
            } finally {
                btn.disabled = false;
            }
        });

        let draftTimer;
        replyInput.addEventListener('input', () => {
            clearTimeout(draftTimer);
            draftTimer = setTimeout(() => {
                postJson(replyForm.dataset.metaUrl, { agent_draft: replyInput.value }).catch(() => {});
            }, 800);
        });

        replyInput.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') replyForm.requestSubmit();
        });
    }

    fileInput?.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file || !replyForm?.dataset.uploadUrl) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', csrf);
        try {
            const data = await postForm(replyForm.dataset.uploadUrl, fd);
            if (data.message) appendMessage(data.message);
            toast('File uploaded');
        } catch {
            toast('Upload failed — check file type and size (max 10 MB)', 'error');
        } finally {
            fileInput.value = '';
        }
    });

    function initQuickRepliesPopover() {
        const trigger = document.getElementById('lc-quick-replies-btn');
        const popover = document.getElementById('lc-quick-replies-popover');
        if (!trigger || !popover) return;

        const closeBtn = document.getElementById('lc-qr-close');
        const searchInput = document.getElementById('lc-qr-search');
        const badge = document.getElementById('lc-qr-badge');
        const tabStorageKey = popover.dataset.storageTab || 'lc-qr-active-tab';
        const addForm = document.getElementById('lc-quick-reply-add');
        const addFormToggle = document.getElementById('lc-qr-toggle-form');
        const saveDraftBtn = document.getElementById('lc-qr-save-draft');
        const listMine = document.getElementById('lc-qr-list-mine');
        const tabMine = document.getElementById('lc-qr-tab-mine');
        const tabTeam = document.getElementById('lc-qr-tab-team');
        const countMine = popover.querySelector('[data-qr-count-mine]');
        const countTeam = popover.querySelector('[data-qr-count-team]');

        let open = false;

        function totalCount() {
            const mine = listMine ? listMine.querySelectorAll('[data-qr-card][data-quick-reply-id]').length : 0;
            const team = document.getElementById('lc-qr-list-team')?.querySelectorAll('[data-qr-card]').length || 0;
            return mine + team;
        }

        function syncBadge() {
            const n = totalCount();
            if (!badge) return;
            if (n <= 0) {
                badge.remove();
            } else {
                badge.textContent = String(n);
            }
        }

        function setPopoverOpen(next) {
            open = next;
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            popover.hidden = !open;
            if (open) {
                searchInput?.focus();
            } else {
                popover.querySelectorAll('.lc-qr-card.is-editing').forEach((c) => c.classList.remove('is-editing'));
                addForm?.setAttribute('hidden', '');
                addFormToggle?.setAttribute('aria-expanded', 'false');
            }
        }

        function setTab(name) {
            localStorage.setItem(tabStorageKey, name);
            popover.querySelectorAll('[data-qr-tab]').forEach((tab) => {
                const active = tab.dataset.qrTab === name;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            popover.querySelectorAll('[data-qr-panel]').forEach((panel) => {
                const active = panel.dataset.qrPanel === name;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
            filterCards();
        }

        function filterCards() {
            const q = (searchInput?.value || '').trim().toLowerCase();
            const activePanel = popover.querySelector('[data-qr-panel].is-active');
            if (!activePanel) return;
            activePanel.querySelectorAll('[data-qr-card]').forEach((card) => {
                const title = card.querySelector('.lc-qr-card-title')?.textContent?.toLowerCase() || '';
                const preview = card.querySelector('.lc-qr-card-preview')?.textContent?.toLowerCase() || '';
                const body = (card.dataset.cannedBody || '').toLowerCase();
                const match = !q || title.includes(q) || preview.includes(q) || body.includes(q);
                card.classList.toggle('is-hidden', !match);
            });
        }

        function insertReply(body, replace) {
            if (!replyInput || !body) return;
            const text = body.trim();
            if (!text) return;
            if (replace || !replyInput.value.trim()) {
                replyInput.value = text;
            } else {
                replyInput.value = `${replyInput.value.replace(/\s+$/, '')}\n\n${text}`;
            }
            replyInput.focus();
            setPopoverOpen(false);
        }

        function buildMineCard(reply) {
            const article = document.createElement('article');
            article.className = 'lc-qr-card';
            article.dataset.qrCard = '';
            article.dataset.qrScope = 'mine';
            article.dataset.quickReplyId = String(reply.id);
            article.dataset.cannedBody = reply.body;
            if (reply.update_url) article.dataset.updateUrl = reply.update_url;
            if (reply.delete_url) article.dataset.deleteUrl = reply.delete_url;

            const preview = reply.preview || reply.body;
            article.innerHTML = `
                <button type="button" class="lc-qr-card-main" data-qr-use>
                    <span class="lc-qr-card-title"></span>
                    <span class="lc-qr-card-preview"></span>
                </button>
                <div class="lc-qr-card-actions">
                    <button type="button" class="lc-qr-card-act" data-qr-edit title="Edit" aria-label="Edit">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path stroke-linecap="round" d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button type="button" class="lc-qr-card-act lc-qr-card-act--danger" data-quick-reply-delete title="Delete" aria-label="Delete">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                    </button>
                </div>
                <form class="lc-qr-card-edit" hidden data-qr-edit-form>
                    <input type="text" name="title" class="lc-qr-edit-title" maxlength="120" placeholder="Label (optional)">
                    <textarea name="body" class="lc-qr-edit-body" rows="3" maxlength="4000" required></textarea>
                    <div class="lc-qr-edit-actions">
                        <button type="submit" class="lc-btn lc-btn-primary lc-btn-sm">Save</button>
                        <button type="button" class="lc-btn lc-btn-ghost lc-btn-sm" data-qr-edit-cancel>Cancel</button>
                    </div>
                </form>`;
            article.querySelector('.lc-qr-card-title').textContent = reply.label || reply.body;
            article.querySelector('.lc-qr-card-preview').textContent = preview;
            const editForm = article.querySelector('[data-qr-edit-form]');
            editForm.querySelector('[name="title"]').value = reply.title || '';
            editForm.querySelector('[name="body"]').value = reply.body;
            const delBtn = article.querySelector('[data-quick-reply-delete]');
            if (reply.delete_url) delBtn.dataset.deleteUrl = reply.delete_url;
            return article;
        }

        function updateCardFromReply(card, reply) {
            card.dataset.cannedBody = reply.body;
            card.querySelector('.lc-qr-card-title').textContent = reply.label || reply.body;
            card.querySelector('.lc-qr-card-preview').textContent = reply.preview || reply.body;
            const editForm = card.querySelector('[data-qr-edit-form]');
            if (editForm) {
                editForm.querySelector('[name="title"]').value = reply.title || '';
                editForm.querySelector('[name="body"]').value = reply.body;
            }
        }

        function bumpTabCount(scope, delta) {
            const el = scope === 'mine' ? countMine : countTeam;
            if (!el) return;
            const n = Math.max(0, (parseInt(el.textContent, 10) || 0) + delta);
            el.textContent = String(n);
            if (scope === 'team' && tabTeam) {
                tabTeam.disabled = n === 0;
            }
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            setPopoverOpen(!open);
        });

        closeBtn?.addEventListener('click', () => setPopoverOpen(false));

        document.addEventListener('click', (e) => {
            if (!open) return;
            if (popover.contains(e.target) || trigger.contains(e.target)) return;
            setPopoverOpen(false);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && open) setPopoverOpen(false);
        });

        popover.querySelectorAll('[data-qr-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                if (tab.disabled) return;
                setTab(tab.dataset.qrTab);
            });
        });

        const savedTab = localStorage.getItem(tabStorageKey);
        if (savedTab === 'team' && tabTeam && !tabTeam.disabled) {
            setTab('team');
        } else {
            setTab('mine');
        }

        searchInput?.addEventListener('input', filterCards);

        popover.addEventListener('click', async (e) => {
            const useBtn = e.target.closest('[data-qr-use]');
            if (useBtn) {
                const card = useBtn.closest('[data-qr-card]');
                insertReply(card?.dataset.cannedBody || '', e.shiftKey);
                return;
            }

            const editBtn = e.target.closest('[data-qr-edit]');
            if (editBtn) {
                e.preventDefault();
                const card = editBtn.closest('[data-qr-card]');
                popover.querySelectorAll('.lc-qr-card.is-editing').forEach((c) => c.classList.remove('is-editing'));
                card?.classList.add('is-editing');
                return;
            }

            const cancelEdit = e.target.closest('[data-qr-edit-cancel]');
            if (cancelEdit) {
                cancelEdit.closest('[data-qr-card]')?.classList.remove('is-editing');
                return;
            }

            const delBtn = e.target.closest('[data-quick-reply-delete]');
            if (delBtn) {
                e.preventDefault();
                const card = delBtn.closest('[data-qr-card]');
                const url = delBtn.dataset.deleteUrl || card?.dataset.deleteUrl;
                if (!url || !window.confirm('Remove this quick reply?')) return;
                try {
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(validationMessage(data));
                    card?.remove();
                    if (listMine && !listMine.querySelector('[data-qr-card]')) {
                        const empty = document.createElement('p');
                        empty.className = 'lc-qr-empty';
                        empty.dataset.qrEmptyMine = '';
                        empty.textContent = 'No personal replies yet.';
                        listMine.appendChild(empty);
                    }
                    bumpTabCount('mine', -1);
                    syncBadge();
                    toast('Quick reply removed');
                } catch {
                    toast('Could not remove quick reply', 'error');
                }
            }
        });

        popover.addEventListener('submit', async (e) => {
            const editForm = e.target.closest('[data-qr-edit-form]');
            if (editForm) {
                e.preventDefault();
                const card = editForm.closest('[data-qr-card]');
                const url = card?.dataset.updateUrl;
                if (!url) return;
                try {
                    const data = await putForm(url, new FormData(editForm));
                    if (data.reply) {
                        updateCardFromReply(card, data.reply);
                        card.classList.remove('is-editing');
                        toast('Quick reply updated');
                    }
                } catch (err) {
                    toast(err.message || 'Could not update quick reply', 'error');
                }
                return;
            }

            if (e.target === addForm) {
                e.preventDefault();
                try {
                    const data = await postForm(addForm.action, new FormData(addForm));
                    const reply = data.reply;
                    if (!reply) return;
                    listMine?.querySelector('[data-qr-empty-mine]')?.remove();
                    listMine?.appendChild(buildMineCard(reply));
                    bumpTabCount('mine', 1);
                    syncBadge();
                    addForm.reset();
                    addForm.setAttribute('hidden', '');
                    addFormToggle?.setAttribute('aria-expanded', 'false');
                    setTab('mine');
                    toast('Quick reply added');
                } catch (err) {
                    toast(err.message || 'Could not save quick reply', 'error');
                }
            }
        });

        addFormToggle?.addEventListener('click', () => {
            const showing = addForm?.hasAttribute('hidden') === false;
            if (showing) {
                addForm.setAttribute('hidden', '');
                addFormToggle.setAttribute('aria-expanded', 'false');
            } else {
                addForm?.removeAttribute('hidden');
                addFormToggle.setAttribute('aria-expanded', 'true');
                document.getElementById('lc-qr-add-body')?.focus();
            }
        });

        saveDraftBtn?.addEventListener('click', () => {
            const draft = replyInput?.value?.trim() || '';
            if (!draft) {
                toast('Write a message first', 'error');
                return;
            }
            setPopoverOpen(true);
            setTab('mine');
            addForm?.removeAttribute('hidden');
            addFormToggle?.setAttribute('aria-expanded', 'true');
            const bodyEl = document.getElementById('lc-qr-add-body');
            const titleEl = document.getElementById('lc-qr-add-title');
            if (bodyEl) bodyEl.value = draft;
            if (titleEl && !titleEl.value) {
                titleEl.value = draft.length > 48 ? `${draft.slice(0, 45)}…` : draft;
            }
            bodyEl?.focus();
        });
    }

    initQuickRepliesPopover();

    /* ─── Bulk actions ─── */
    const bulkBar = document.getElementById('lc-bulk-bar');
    const rowChecks = () => [...document.querySelectorAll('.lc-row-check')];
    const selectAll = document.getElementById('lc-select-all');

    function selectedIds() {
        return rowChecks().filter((c) => c.checked).map((c) => c.value);
    }

    function updateBulkBar() {
        const n = selectedIds().length;
        bulkBar?.classList.toggle('is-visible', n > 0);
        const countEl = document.getElementById('lc-bulk-count');
        if (countEl) countEl.textContent = n + ' selected';
        if (selectAll) {
            const all = rowChecks();
            selectAll.checked = all.length > 0 && all.every((c) => c.checked);
            selectAll.indeterminate = !selectAll.checked && all.some((c) => c.checked);
        }
    }

    rowChecks().forEach((c) => {
        c.addEventListener('change', updateBulkBar);
        c.addEventListener('click', (e) => e.stopPropagation());
    });
    document.querySelectorAll('.lc-item-check-wrap').forEach((w) => w.addEventListener('click', (e) => e.stopPropagation()));
    selectAll?.addEventListener('change', () => {
        rowChecks().forEach((c) => { c.checked = selectAll.checked; });
        updateBulkBar();
    });

    document.querySelectorAll('[data-lc-bulk]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const ids = selectedIds();
            if (!ids.length || !cfg.bulkUrl) return;
            btn.disabled = true;
            try {
                await postJson(cfg.bulkUrl, { ids, action: btn.dataset.lcBulk });
                toast('Updated ' + ids.length + ' conversation(s)');
                window.location.reload();
            } catch {
                toast('Bulk action failed', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    });

    /* ─── Presence ─── */
    document.getElementById('lc-presence')?.addEventListener('change', (e) => {
        const status = e.target.value;
        if (!cfg.presenceUrl) return;
        postJson(cfg.presenceUrl, { status }).catch(() => toast('Could not update status', 'error'));
        const dot = document.querySelector('.lc-presence-dot');
        if (dot) dot.className = 'lc-presence-dot is-' + status;
    });

    /* ─── Header / drawer AJAX actions ─── */
    document.querySelectorAll('[data-lc-post]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.lcPost;
            if (!url) return;
            btn.disabled = true;
            try {
                const data = await postJson(url, {});
                toast(btn.dataset.lcSuccess || data.message || 'Done');
                if (data.lead) renderLeadSection(data.lead);
                if (btn.dataset.lcReload === '1') window.location.reload();
            } catch {
                toast('Action failed', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.querySelectorAll('[data-lc-meta-toggle]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.lcMetaUrl;
            const field = btn.dataset.lcMetaField;
            const nextVal = btn.dataset.lcMetaValue === '1';
            if (!url || !field) return;
            try {
                await postJson(url, { [field]: nextVal });
                const nowActive = nextVal;
                btn.dataset.lcMetaActive = nowActive ? '1' : '0';
                btn.dataset.lcMetaValue = nowActive ? '0' : '1';
                if (field === 'is_starred') {
                    const icon = btn.querySelector('.lc-star-icon');
                    if (icon) icon.textContent = nowActive ? '★' : '☆';
                }
                btn.classList.toggle('is-active', nowActive);
                toast(nowActive ? 'Updated' : 'Removed');
            } catch {
                toast('Could not update', 'error');
            }
        });
    });

    document.querySelectorAll('[data-lc-ajax-form]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true;
            const fd = new FormData(form);
            try {
                const data = await postForm(form.action, fd);
                toast(form.dataset.lcSuccess || data.message || 'Saved');

                if (data.contact) updateContactHeader(data.contact);

                if (data.note) {
                    const feed = document.getElementById('lc-notes-feed');
                    const empty = feed?.querySelector('.lc-notes-empty');
                    empty?.remove();
                    if (feed) {
                        const card = document.createElement('article');
                        card.className = 'lc-note-card';
                        card.innerHTML = `<p class="lc-note-author">${escapeHtml(data.note.author)}</p><div class="lc-note-body">${data.note.body_html}</div>`;
                        feed.prepend(card);
                    }
                    form.reset();
                } else if (form.dataset.lcReload === '1') {
                    window.location.reload();
                }
            } catch (err) {
                toast(err?.message || 'Save failed', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    });

    /* ─── Filters ─── */
    const filterForm = document.getElementById('lc-filter-form');
    const filterToggle = document.getElementById('lc-filter-toggle');
    const filterPanel = document.getElementById('lc-filter-panel');

    function buildFilterUrl() {
        if (!filterForm) return window.location.pathname;
        const fd = new FormData(filterForm);
        const params = new URLSearchParams();
        const q = (fd.get('q') || '').toString().trim();
        if (q) params.set('q', q);
        ['website_id', 'department_id'].forEach((k) => { if (fd.get(k)) params.set(k, fd.get(k)); });
        const sort = fd.get('sort');
        if (sort && sort !== 'newest') params.set('sort', sort);
        const view = fd.get('view');
        if (view === 'awaiting') params.set('awaiting', '1');
        if (view === 'assigned') params.set('assigned', 'me');
        if (view === 'starred') params.set('starred', '1');
        if (view === 'pinned') params.set('pinned', '1');
        const conv = new URLSearchParams(window.location.search).get('conversation');
        if (conv) params.set('conversation', conv);
        const qs = params.toString();
        return window.location.pathname + (qs ? '?' + qs : '');
    }

    function navigateFilters() {
        const url = buildFilterUrl();
        if (url !== window.location.pathname + window.location.search) window.location.href = url;
    }

    filterToggle?.addEventListener('click', () => {
        const open = filterPanel?.hasAttribute('hidden');
        filterPanel?.toggleAttribute('hidden', !open);
        filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    if (filterPanel && filterToggle?.classList.contains('is-active')) {
        filterPanel.removeAttribute('hidden');
        filterToggle.setAttribute('aria-expanded', 'true');
    }

    filterForm?.querySelectorAll('[data-auto-filter]').forEach((el) => el.addEventListener('change', navigateFilters));
    filterForm?.querySelector('.lc-filter-search')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); navigateFilters(); }
    });
    let searchTimer;
    filterForm?.querySelector('.lc-filter-search')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(navigateFilters, 450);
    });

    function currentFilterPayload() {
        if (!filterForm) return { view: 'all', sort: 'newest', q: '', website_id: null, department_id: null };
        const fd = new FormData(filterForm);
        return {
            q: (fd.get('q') || '').toString().trim(),
            website_id: fd.get('website_id') || null,
            department_id: fd.get('department_id') || null,
            sort: fd.get('sort') || 'newest',
            view: fd.get('view') || 'all',
        };
    }

    const presetSelect = document.getElementById('lc-preset-select');
    const presetSaveBtn = document.getElementById('lc-preset-save');
    const presetsListUrl = configEl?.dataset?.filterPresetsUrl;
    const presetsStoreUrl = configEl?.dataset?.filterPresetsStoreUrl;

    async function loadFilterPresets() {
        if (!presetSelect || !presetsListUrl) return;
        try {
            const res = await fetch(presetsListUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            presetSelect.querySelectorAll('option:not(:first-child)').forEach((o) => o.remove());
            (data.presets || []).forEach((p) => {
                const opt = document.createElement('option');
                opt.value = p.url;
                opt.textContent = p.name;
                opt.dataset.presetId = p.id;
                presetSelect.appendChild(opt);
            });
        } catch (_) { /* ignore */ }
    }

    presetSelect?.addEventListener('change', () => {
        const url = presetSelect.value;
        if (url) window.location.href = url;
    });

    presetSaveBtn?.addEventListener('click', async () => {
        if (!presetsStoreUrl || !csrf) return;
        const name = window.prompt('Name this saved view');
        if (!name || !name.trim()) return;
        try {
            const res = await fetch(presetsStoreUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ name: name.trim(), filters: currentFilterPayload() }),
            });
            if (res.ok) {
                await loadFilterPresets();
                toast('Saved view stored.');
            }
        } catch (_) { /* ignore */ }
    });

    loadFilterPresets();

    /* ─── Contact popup ─── */
    const contactPopup = document.getElementById('lc-contact-popup');
    const visitorToggle = document.getElementById('lc-visitor-toggle');

    function renderLeadSection(lead) {
        const section = document.getElementById('lc-lead-section');
        if (!section || !lead) return;
        section.dataset.hasLead = '1';
        section.innerHTML = `
            <div class="lc-lead-card is-saved">
                <div class="lc-lead-card-icon" aria-hidden="true">✓</div>
                <div class="lc-lead-card-body">
                    <p class="lc-lead-card-title">Saved to CRM</p>
                    <p class="lc-lead-card-name">${escapeHtml(lead.name || 'Visitor')}</p>
                    ${lead.email ? `<p class="lc-lead-card-meta">${escapeHtml(lead.email)}</p>` : ''}
                    ${lead.phone ? `<p class="lc-lead-card-meta">${escapeHtml(lead.phone)}</p>` : ''}
                    <span class="lc-lead-status">${escapeHtml(lead.status || 'New')}</span>
                </div>
                <a href="${escapeHtml(lead.url)}" class="lc-btn lc-btn-primary lc-btn-sm lc-btn-block">View in CRM</a>
            </div>`;
    }

    function updateContactHeader(contact) {
        if (!contact) return;
        const name = contact.name || 'Visitor';
        document.querySelectorAll('.lc-conversation-name, .lc-contact-popup-title').forEach((el) => {
            el.textContent = name;
        });
        const sub = document.getElementById('lc-contact-popup-sub');
        if (sub) {
            sub.textContent = contact.email || contact.phone || contact.company || 'Contact details from pre-chat';
        }
        const avatar = document.querySelector('.lc-contact-popup-avatar');
        if (avatar) {
            avatar.textContent = name.split(/\s+/).filter(Boolean).slice(0, 2)
                .map((w) => w.charAt(0).toUpperCase()).join('') || 'V';
        }
        const chatAvatar = document.querySelector('.lc-chat-avatar');
        if (chatAvatar) {
            chatAvatar.textContent = name.split(/\s+/).filter(Boolean).slice(0, 2)
                .map((w) => w.charAt(0).toUpperCase()).join('') || 'V';
        }
        const meta = document.querySelector('.lc-conversation-meta');
        if (meta) {
            const parts = [];
            const siteEl = meta.querySelector('span');
            if (siteEl?.textContent) parts.push(siteEl.textContent);
            if (contact.email) parts.push(contact.email);
            else if (contact.phone) parts.push(contact.phone);
            if (contact.company && !contact.email) parts.push(contact.company);
            meta.textContent = parts.join(' · ');
        }
    }

    const openContactPopup = () => {
        if (!contactPopup) return;
        contactPopup.hidden = false;
        contactPopup.setAttribute('aria-hidden', 'false');
        visitorToggle?.setAttribute('aria-expanded', 'true');
        document.body.classList.add('lc-contact-open');
    };

    const closeContactPopup = () => {
        if (!contactPopup) return;
        contactPopup.hidden = true;
        contactPopup.setAttribute('aria-hidden', 'true');
        visitorToggle?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('lc-contact-open');
    };

    const toggleContactPopup = () => {
        if (contactPopup?.hidden) openContactPopup();
        else closeContactPopup();
    };

    visitorToggle?.addEventListener('click', toggleContactPopup);
    document.getElementById('lc-visitor-close')?.addEventListener('click', closeContactPopup);
    document.getElementById('lc-visitor-backdrop')?.addEventListener('click', closeContactPopup);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && contactPopup && !contactPopup.hidden) closeContactPopup();
    });

    /* ─── Actions menu ─── */
    document.querySelectorAll('.lc-actions-menu').forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            document.querySelectorAll('.lc-actions-menu[open]').forEach((o) => { if (o !== menu) o.open = false; });
        });
    });
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.lc-actions-menu[open]').forEach((menu) => {
            if (!menu.contains(e.target)) menu.open = false;
        });
    });

    /* ─── Browser notifications (optional) ─── */
    const NOTIFY_KEY = 'lc-notify-enabled';
    const notifyIcon = configEl?.dataset.notifyIcon || '/agent/icon-192.svg';
    let lastAwaitingPoll = null;

    function notifyEnabled() {
        return localStorage.getItem(NOTIFY_KEY) === '1' && Notification?.permission === 'granted';
    }

    function showBrowserNotification(title, body, url) {
        if (!notifyEnabled() || document.visibilityState === 'visible') return;
        const n = new Notification(title, { body, icon: notifyIcon });
        n.onclick = () => {
            window.focus();
            if (url) window.location.href = url;
            n.close();
        };
    }

    function updateNotifyButton() {
        const btn = document.getElementById('lc-enable-notify');
        if (!btn || !('Notification' in window)) {
            btn?.remove();
            return;
        }
        const on = notifyEnabled();
        btn.textContent = on ? 'Notify on' : 'Notify';
        btn.title = on ? 'Browser alerts enabled (background tab)' : 'Enable browser alerts for new messages';
        btn.disabled = on && Notification.permission === 'granted';
    }

    document.getElementById('lc-enable-notify')?.addEventListener('click', async () => {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'granted') {
            localStorage.setItem(NOTIFY_KEY, '1');
            updateNotifyButton();
            return;
        }
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            localStorage.setItem(NOTIFY_KEY, '1');
            updateNotifyButton();
            showBrowserNotification('Inbox notifications', 'You will be alerted for new visitor messages and handoffs.');
        }
    });

    if (Notification?.permission === 'granted' && localStorage.getItem(NOTIFY_KEY) === '1') {
        updateNotifyButton();
    } else {
        updateNotifyButton();
    }

    /* ─── Message polling ─── */
    const msgPollUrl = messageList?.dataset.pollUrl;
    if (msgPollUrl && messageList) {
        let lastId = Math.max(0, ...[...messageList.querySelectorAll('[data-msg-id]')].map((el) => parseInt(el.dataset.msgId, 10) || 0));
        setInterval(async () => {
            try {
                const res = await fetch(msgPollUrl + (msgPollUrl.includes('?') ? '&' : '?') + 'after_id=' + lastId, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                (data.messages || []).forEach((m) => {
                    if (!messageList.querySelector('[data-msg-id="' + m.id + '"]')) {
                        const isVisitor = m.sender_type === 'visitor' || m.sender_type === 'user';
                        if (isVisitor && notifyEnabled()) {
                            const preview = (m.content || 'New message').slice(0, 120);
                            showBrowserNotification('New visitor message', preview, window.location.href);
                        }
                        appendMessage(m);
                        lastId = Math.max(lastId, m.id);
                    }
                });
                updateReceipts(data.receipt_updates);
                updateReactions(data.reactions);
            } catch (_) {}
        }, 3000);
        messageList.scrollTop = messageList.scrollHeight;
    }

    /* ─── List polling (unread badges) ─── */
    if (listScroll && cfg.pollUrl) {
        setInterval(async () => {
            try {
                const res = await fetch(cfg.pollUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                const awaiting = data.awaiting ?? 0;
                if (lastAwaitingPoll !== null && awaiting > lastAwaitingPoll && notifyEnabled()) {
                    showBrowserNotification(
                        'Handoff waiting',
                        `${awaiting} conversation(s) need an agent`,
                        cfg.inboxUrl || '/inbox'
                    );
                }
                lastAwaitingPoll = awaiting;
                if (data.queue_stats) {
                    Object.entries(data.queue_stats).forEach(([key, value]) => {
                        document.querySelectorAll('[data-lc-stat="' + key + '"]').forEach((el) => {
                            el.textContent = value;
                        });
                    });
                }
                (data.conversations || []).forEach((c) => {
                    if (!listScroll.querySelector('[data-conversation-id="' + c.id + '"]')) {
                        appendInboxItem(c);
                    }
                    const row = listScroll.querySelector('[data-conversation-id="' + c.id + '"]');
                    if (!row) return;
                    const badge = row.querySelector('[data-unread]');
                    if (badge) {
                        if (c.agent_unread_count > 0) {
                            badge.hidden = false;
                            badge.textContent = c.agent_unread_count;
                        } else {
                            badge.hidden = true;
                        }
                    }
                    const preview = row.querySelector('.lc-item-preview');
                    if (preview && c.preview) preview.textContent = c.preview;
                    const time = row.querySelector('.lc-item-time');
                    if (time && c.last_message_at) time.textContent = c.last_message_at;
                });
            } catch (_) {}
        }, 15000);
    }

    /* ─── Echo handled by resources/js/realtime.js ─── */

    if (configEl?.dataset.flash) toast(configEl.dataset.flash);

    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && !e.ctrlKey && !e.metaKey && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
            e.preventDefault();
            document.querySelector('.lc-filter-search')?.focus();
        }
    });

    document.querySelector('.lc-item-wrap.is-active')?.scrollIntoView({ block: 'nearest' });
})();
