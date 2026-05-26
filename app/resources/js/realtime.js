import { onConversationEvent, onOrganizationEvent } from './echo';

const config = window.__CHATBOT_REALTIME__ ?? {};

function notify(message) {
    if (typeof window.lcShowToast === 'function') {
        window.lcShowToast(message);
        return;
    }

    let toast = document.getElementById('reverb-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'reverb-toast';
        toast.className = 'hidden fixed bottom-6 right-6 px-4 py-3 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-lg z-50';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 5000);
}

function appendInboxItem(data) {
    if (typeof window.lcAppendInboxItem === 'function') {
        window.lcAppendInboxItem(data);
        return;
    }

    const list = document.getElementById('lc-list-scroll');
    const id = data.conversation_id || data.id;
    if (!list || !id || list.querySelector(`[data-conversation-id="${id}"]`)) {
        return;
    }

    const cfg = document.getElementById('lc-config');
    const base = cfg?.dataset.inboxUrl || '/inbox';
    const el = document.createElement('div');
    el.dataset.conversationId = id;
    el.className = 'lc-item-wrap is-new';
    el.innerHTML = `<a href="${base}?conversation=${id}" class="lc-item"><span class="lc-item-name">${data.visitor_name || 'Visitor'}</span></a>`;
    list.prepend(el);
}

function appendChatMessage(message) {
    if (typeof window.lcAppendMessage === 'function') {
        window.lcAppendMessage(message);
        return;
    }

    const list = document.getElementById('lc-message-list') || document.getElementById('message-list');
    if (!list || list.querySelector(`[data-msg-id="${message.id}"]`)) {
        return;
    }

    const isVisitor = message.sender_type === 'visitor';
    const wrap = document.createElement('div');
    wrap.dataset.msgId = message.id;
    wrap.className = 'lc-msg-row' + (isVisitor ? ' lc-msg-row-visitor' : '');
    wrap.innerHTML = `<div class="lc-msg-bubble lc-msg-bubble-${isVisitor ? 'visitor' : 'agent'}">${escapeHtml(message.content || '')}</div>`;
    list.appendChild(wrap);
    list.scrollTop = list.scrollHeight;
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

if (config.orgId && window.Echo) {
    onOrganizationEvent(config.orgId, 'conversation.awaiting_agent', (data) => {
        notify('New chat awaiting agent');
        if (config.page === 'inbox') {
            appendInboxItem({ ...data, action: 'handoff', status: 'awaiting_agent' });
        } else if (config.page !== 'conversation') {
            notify('Open Live Inbox to respond');
        }
    });

    onOrganizationEvent(config.orgId, 'conversation.updated', (data) => {
        if (config.page === 'inbox' && (data.action === 'handoff' || data.status === 'awaiting_agent')) {
            appendInboxItem(data);
        }
        const statusEl = document.getElementById('conv-status');
        if (statusEl && config.conversationId === data.conversation_id) {
            statusEl.textContent = data.status;
        }
    });

    onOrganizationEvent(config.orgId, 'message.sent', (message) => {
        if (config.page === 'inbox' && config.conversationId !== message.conversation_id) {
            notify('New message in live chat');
        }
        if (config.conversationId === message.conversation_id) {
            appendChatMessage(message);
        }
    });

    if (config.conversationId) {
        onConversationEvent(config.conversationId, 'message.sent', appendChatMessage);
        onConversationEvent(config.conversationId, 'conversation.updated', (data) => {
            const statusEl = document.getElementById('conv-status');
            if (statusEl) {
                statusEl.textContent = data.status;
            }
        });
    }
} else if (config.orgId) {
    setInterval(async () => {
        if (config.page !== 'inbox') return;
        try {
            const cfg = document.getElementById('lc-config');
            const pollUrl = cfg?.dataset.pollUrl || '/inbox/poll';
            const res = await fetch(pollUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data.queue_stats) {
                Object.entries(data.queue_stats).forEach(([key, value]) => {
                    document.querySelectorAll(`[data-lc-stat="${key}"]`).forEach((el) => {
                        el.textContent = value;
                    });
                });
            }
            if (data.awaiting > 0) notify(`${data.awaiting} chat(s) in queue`);
        } catch (_) {}
    }, 15000);
}

if (!window.Echo && config.page === 'conversation' && config.messagesUrl && config.conversationId) {
    let lastMessageId = 0;
    document.querySelectorAll('[data-msg-id]').forEach((el) => {
        const id = parseInt(el.dataset.msgId, 10);
        if (id > lastMessageId) lastMessageId = id;
    });

    setInterval(async () => {
        try {
            const url = `${config.messagesUrl}?after_id=${lastMessageId}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            data.messages?.forEach((message) => {
                appendChatMessage(message);
                if (message.id > lastMessageId) lastMessageId = message.id;
            });
            const statusEl = document.getElementById('conv-status');
            if (statusEl && data.status) {
                statusEl.textContent = data.status;
            }
        } catch (_) {}
    }, 1500);
}
