/**
 * SkyCut-style embeddable chatbot widget — Laravel AI Chatbot Hub Pro
 */
(function () {
  'use strict';

  const script = document.currentScript;
  const botToken = script?.getAttribute('data-bot-token');
  if (!botToken) {
    console.error('[ChatFlow] Missing data-bot-token attribute.');
    return;
  }

  const inlineTarget = script?.getAttribute('data-inline');
  const autoOpen = script?.getAttribute('data-auto-open') === 'true';
  const instanceKey = botToken + (inlineTarget || 'float');

  if (window.__chatflowInstances?.[instanceKey]) {
    return;
  }
  window.__chatflowInstances = window.__chatflowInstances || {};
  window.__chatflowInstances[instanceKey] = true;

  const apiBase = (() => {
    const src = script.src || '';
    try {
      return new URL(src).origin;
    } catch {
      return window.location.origin;
    }
  })();

  const cssBase = (() => {
    const src = script.src || '';
    try {
      return new URL('.', src).href;
    } catch {
      return apiBase + '/widget/';
    }
  })();

  const STORAGE_KEY = 'chatflow_' + botToken;
  const SESSION_CHAT_KEY = 'chatflow_session_' + botToken;
  const PAGE_SESSION_MARKER = 'chatflow_page_' + botToken;
  const PAGE_LOAD_ID = String(Date.now()) + '_' + Math.random().toString(36).slice(2);
  const ICONS = {
    chat: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    send: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>',
    bot: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="3"/><path d="M8 15h.01M16 15h.01"/></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    minimize: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/></svg>',
    fullscreen: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>',
    fullscreenExit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>',
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  };

  const state = {
    config: null,
    open: autoOpen || !!inlineTarget,
    inline: !!inlineTarget,
    visitorId: null,
    conversationId: null,
    messages: [],
    loading: false,
    typing: false,
    pollTimer: null,
    handoff: false,
    rated: false,
    humanMode: false,
    unread: false,
    preChatDone: false,
    showRating: false,
    pendingRatingScore: 0,
    visitorProfile: { name: '', email: '', phone: '', company: '' },
    preChatSubmitting: false,
    handoffNoticeShown: false,
    configVersion: null,
    configSyncTimer: null,
    fullscreen: false,
    panelPosition: null,
    quickActionsOpen: true,
  };

  const DRAG_POS_KEY = 'chatflow_drag_' + botToken;
  const SOUND_MUTE_KEY = 'chatflow_sound_muted_' + botToken;
  let dragBound = false;
  let audioCtx = null;

  const HANDOFF_NOTICE_SOURCES = ['handoff_pending', 'handoff'];

  function storageGet() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    } catch {
      return {};
    }
  }

  function storageSet(data) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ ...storageGet(), ...data }));
  }

  function isSoundMuted() {
    return localStorage.getItem(SOUND_MUTE_KEY) === '1';
  }

  function setSoundMuted(muted) {
    if (muted) {
      localStorage.setItem(SOUND_MUTE_KEY, '1');
    } else {
      localStorage.removeItem(SOUND_MUTE_KEY);
    }
  }

  function canPlayNotificationSound() {
    return state.config?.appearance?.sound_enabled !== false && !isSoundMuted();
  }

  function playNotificationSound() {
    if (!canPlayNotificationSound()) return;
    try {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      if (!audioCtx) audioCtx = new Ctx();
      if (audioCtx.state === 'suspended') {
        audioCtx.resume().catch(() => {});
      }
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.07, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.18);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start(audioCtx.currentTime);
      osc.stop(audioCtx.currentTime + 0.18);
    } catch (_) {}
  }

  function sessionChatGet() {
    try {
      return JSON.parse(sessionStorage.getItem(SESSION_CHAT_KEY) || '{}');
    } catch {
      return {};
    }
  }

  function sessionChatSet(data) {
    sessionStorage.setItem(SESSION_CHAT_KEY, JSON.stringify({ ...sessionChatGet(), ...data }));
  }

  function initPageSession() {
    const marker = sessionStorage.getItem(PAGE_SESSION_MARKER);
    if (marker !== PAGE_LOAD_ID) {
      sessionStorage.removeItem(SESSION_CHAT_KEY);
      sessionStorage.setItem(PAGE_SESSION_MARKER, PAGE_LOAD_ID);
    }
  }

  function clearChatSession() {
    stopPolling();
    state.conversationId = null;
    state.messages = [];
    state.handoff = false;
    state.humanMode = false;
    state.preChatDone = false;
    state.rated = false;
    state.showRating = false;
    state.pendingRatingScore = 0;
    state.loading = false;
    state.typing = false;
    state.unread = false;
    state.visitorProfile = { name: '', email: '', phone: '', company: '' };
    state.handoffNoticeShown = false;
    sessionStorage.removeItem(SESSION_CHAT_KEY);
  }

  function restoreSessionChat() {
    const s = sessionChatGet();
    state.conversationId = s.conversationId || null;
    state.preChatDone = !!s.preChatDone;
    state.visitorProfile = {
      name: s.visitorName || '',
      email: s.visitorEmail || '',
      phone: s.visitorPhone || '',
      company: s.visitorCompany || '',
    };
  }

  function persistSessionChat() {
    sessionChatSet({
      conversationId: state.conversationId,
      preChatDone: state.preChatDone,
      visitorName: state.visitorProfile.name,
      visitorEmail: state.visitorProfile.email,
      visitorPhone: state.visitorProfile.phone,
      visitorCompany: state.visitorProfile.company,
    });
  }

  function isHandoffNoticeMessage(msg) {
    if (!msg) return false;
    const source = msg.source || '';
    if (HANDOFF_NOTICE_SOURCES.includes(source)) return true;
    const content = String(msg.content || '');
    return content.includes('agent will respond shortly') || content.includes('Connecting you with a live agent');
  }

  function hasHandoffNoticeInMessages() {
    return state.handoffNoticeShown || state.messages.some(isHandoffNoticeMessage);
  }

  function pushBotMessageIfNew(message) {
    if (!message?.content) return;
    if (isHandoffNoticeMessage(message) && hasHandoffNoticeInMessages()) return;
    if (message.id && state.messages.some((m) => m.id === message.id)) return;
    if (isHandoffNoticeMessage(message)) {
      state.handoffNoticeShown = true;
    }
    state.messages.push({
      id: message.id || null,
      sender_type: message.sender_type || 'bot',
      content: message.content,
      source: message.source || 'bot',
      created_at: message.created_at || new Date().toISOString(),
      attachments: message.attachments,
      receipt_status: message.receipt_status,
      reactions: message.reactions || [],
    });
    renderMessages();
  }

  function applyHandoffState(res) {
    if (!res?.handoff && res?.status !== 'awaiting_agent' && res?.mode !== 'human') return;
    state.handoff = true;
    if (res.mode === 'human' || res.status === 'awaiting_agent') {
      state.humanMode = true;
    }
    if (res.message) {
      pushBotMessageIfNew(res.message);
    }
    startPolling();
  }

  function scrollMessagesToBottom(smooth) {
    const box = document.getElementById('chatflow-messages');
    if (!box) return;
    const useSmooth = smooth !== false;
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        if (useSmooth && typeof box.scrollTo === 'function') {
          box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
        } else {
          box.scrollTop = box.scrollHeight;
        }
      });
    });
  }

  function getVisitorId() {
    const saved = storageGet().visitorId;
    if (saved) return saved;
    const id = 'v_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
    storageSet({ visitorId: id });
    return id;
  }

  async function api(path, options = {}) {
    const url = apiBase + '/api/widget/' + botToken + path;
    const res = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(options.headers || {}),
      },
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      let msg = err.message || err.error || res.statusText;
      if (res.status === 429) {
        msg = err.message || 'You\'re sending messages too quickly. Please wait a moment and try again.';
      }
      const e = new Error(typeof msg === 'string' ? msg : 'Request failed');
      e.status = res.status;
      e.errors = err.errors;
      throw e;
    }
    return res.json();
  }

  function t(key, fallback) {
    const locale = state.config?.locale || 'en';
    const tr = state.config?.translations?.[locale];
    return (tr && tr[key]) || fallback;
  }

  function injectStyles() {
    if (document.getElementById('chatflow-styles-link')) return;
    const link = document.createElement('link');
    link.id = 'chatflow-styles-link';
    link.rel = 'stylesheet';
    link.href = cssBase + 'chatbot.css';
    document.head.appendChild(link);

    if (!document.getElementById('chatflow-emoji-styles')) {
      const emojiCss = document.createElement('link');
      emojiCss.id = 'chatflow-emoji-styles';
      emojiCss.rel = 'stylesheet';
      emojiCss.href = apiBase + '/css/emoji-picker.css';
      document.head.appendChild(emojiCss);
    }

    if (!document.querySelector('link[href*="fonts.googleapis.com"][href*="Inter"]')) {
      const font = document.createElement('link');
      font.rel = 'stylesheet';
      font.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap';
      document.head.appendChild(font);
    }
  }

  function applyTheme() {
    const root = document.getElementById('chatflow-root');
    if (!root || !state.config) return;
    const a = state.config.appearance;
    const theme = a.theme_mode === 'auto'
      ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : (a.theme_mode || 'light');
    root.dataset.theme = theme;
    root.style.setProperty('--cf-primary', a.primary_color || '#0d9488');
    root.style.setProperty('--cf-primary-light', a.secondary_color || '#14b8a6');
    root.style.setProperty('--cf-user-bubble', a.primary_color || '#0d9488');
    if (a.custom_css) {
      let style = document.getElementById('chatflow-custom-css');
      if (!style) {
        style = document.createElement('style');
        style.id = 'chatflow-custom-css';
        document.head.appendChild(style);
      }
      style.textContent = a.custom_css;
    }
  }

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function formatTime(dateStr) {
    if (!dateStr) return '';
    try {
      return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
      return '';
    }
  }

  function receiptHtml(status) {
    if (!status || status === 'sent') {
      return '<span class="cf-receipt cf-receipt-sent" title="Sent">✓</span>';
    }
    if (status === 'delivered') {
      return '<span class="cf-receipt cf-receipt-delivered" title="Delivered">✓✓</span>';
    }
    return '<span class="cf-receipt cf-receipt-read" title="Read">✓✓</span>';
  }

  async function markVisitorRead(messageIds) {
    if (!state.conversationId || !messageIds.length || !state.open) return;
    try {
      await api('/read', {
        method: 'POST',
        body: JSON.stringify({
          visitor_id: state.visitorId,
          conversation_id: state.conversationId,
          message_ids: messageIds,
        }),
      });
    } catch (_) {}
  }

  function reactionEmojis() {
    return state.config?.reaction_emojis || ['👍', '❤️', '😂', '😮', '🙏'];
  }

  function reactionsHtml(m) {
    if (!m.reactions?.length) return '';
    return `<div class="cf-reactions">${m.reactions.map((r) =>
      `<button type="button" class="cf-reaction-chip${r.mine ? ' cf-reaction-mine' : ''}" data-react-msg="${m.id}" data-react-emoji="${escapeHtml(r.emoji)}">${r.emoji}${r.count > 1 ? `<span class="cf-reaction-count">${r.count}</span>` : ''}</button>`
    ).join('')}</div>`;
  }

  function reactionPickerHtml(m) {
    if (m.sender_type === 'visitor' || !m.id) return '';
    return `<div class="cf-react-picker">${reactionEmojis().map((emoji) =>
      `<button type="button" class="cf-react-btn" data-react-msg="${m.id}" data-react-emoji="${emoji}" title="React">${emoji}</button>`
    ).join('')}</div>`;
  }

  function mergeConversationReactions(reactionsByMessage) {
    if (!reactionsByMessage) return false;
    let changed = false;
    Object.entries(reactionsByMessage).forEach(([msgId, reactions]) => {
      const msg = state.messages.find((m) => String(m.id) === String(msgId));
      if (msg) {
        msg.reactions = reactions;
        changed = true;
      }
    });
    return changed;
  }

  async function reactToMessage(messageId, emoji) {
    if (!state.conversationId || !messageId) return;
    try {
      const res = await api('/reactions', {
        method: 'POST',
        body: JSON.stringify({
          visitor_id: state.visitorId,
          conversation_id: state.conversationId,
          message_id: messageId,
          emoji,
        }),
      });
      const msg = state.messages.find((m) => m.id === messageId);
      if (msg) {
        msg.reactions = res.reactions || [];
        renderMessages();
      }
    } catch (_) {}
  }

  function render() {
    let root = document.getElementById('chatflow-root');
    const host = inlineTarget ? document.querySelector(inlineTarget) : null;

    if (!root) {
      root = document.createElement('div');
      root.id = 'chatflow-root';
      if (host) {
        host.appendChild(root);
      } else {
        document.body.appendChild(root);
      }
    }

    const cfg = state.config;
    const pos = cfg?.appearance?.position || 'right';
    root.dataset.position = pos;
    root.dataset.mode = state.inline ? 'inline' : 'floating';
    root.dataset.rating = state.showRating ? 'true' : 'false';
    root.classList.toggle('open', state.open);
    applyWidgetOffsets(cfg);
    root.dataset.unread = state.unread && !state.open ? 'true' : 'false';
    root.classList.toggle('cf-fullscreen', state.fullscreen);
    const canDrag = !state.inline && cfg?.modules?.widget_draggable;
    const canFullscreen = !state.inline && cfg?.modules?.widget_fullscreen;
    const showSoundToggle = cfg?.appearance?.sound_enabled !== false;
    const soundMuted = isSoundMuted();

    const botName = cfg?.appearance?.bot_name || 'Assistant';
    const avatar = cfg?.appearance?.avatar_url;
    const isOnline = cfg?.operating_hours?.is_open !== false;
    const statusLabel = isOnline ? 'Online' : 'Offline';
    const preChatBlocking = state.open && needsPreChat();

    const avatarHtml = avatar
      ? `<img src="${escapeHtml(avatar)}" alt="">`
      : ICONS.bot;

    root.innerHTML = `
      ${state.inline ? '' : `<button id="chatflow-launcher" aria-label="Open chat" type="button">${state.open ? ICONS.close : ICONS.chat}</button>`}
      <div id="chatflow-panel" role="dialog" aria-label="${escapeHtml(botName)}" class="${preChatBlocking ? 'cf-prechat-active' : ''}">
        <div id="chatflow-header" class="${canDrag ? 'cf-draggable' : ''}">
          <div class="cf-header-avatar">${avatarHtml}</div>
          <div class="cf-header-info">
            <strong>${escapeHtml(botName)}</strong>
            <div class="cf-header-status">
              <span class="cf-status-dot ${isOnline ? '' : 'offline'}"></span>
              ${statusLabel}
            </div>
          </div>
          <div class="cf-header-actions">
            ${showSoundToggle ? `<button type="button" class="cf-icon-btn" id="chatflow-sound" aria-label="${soundMuted ? 'Unmute notifications' : 'Mute notifications'}" aria-pressed="${soundMuted ? 'true' : 'false'}" title="${soundMuted ? 'Unmute' : 'Mute'}">${soundMuted ? '🔇' : '🔔'}</button>` : ''}
            ${canFullscreen ? `<button type="button" class="cf-icon-btn" id="chatflow-fullscreen" aria-label="${state.fullscreen ? 'Exit fullscreen' : 'Fullscreen'}">${state.fullscreen ? ICONS.fullscreenExit : ICONS.fullscreen}</button>` : ''}
            ${state.inline ? '' : `<button type="button" class="cf-icon-btn" id="chatflow-minimize" aria-label="Minimize">${ICONS.minimize}</button>`}
            ${state.inline ? '' : `<button type="button" class="cf-icon-btn" id="chatflow-close" aria-label="Close">${ICONS.close}</button>`}
          </div>
        </div>
        <div class="cf-chat-body" id="chatflow-body">
          <div id="chatflow-messages" class="cf-messages-scroll"></div>
          <div id="chatflow-typing-area"></div>
          <div id="chatflow-actions"></div>
          <div id="chatflow-handoff"></div>
        </div>
        <div id="chatflow-prechat"></div>
        <div id="chatflow-rating"></div>
        <div id="chatflow-footer" ${preChatBlocking ? 'aria-hidden="true"' : ''}>
          <div id="chatflow-channels" class="cf-channels"></div>
          <div class="cf-input-wrap">
            <label class="cf-attach-btn" title="Attach file" id="chatflow-attach-label">📎
              <input type="file" id="chatflow-file" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,audio/*,video/*" hidden ${preChatBlocking ? 'disabled' : ''} />
            </label>
            <button type="button" id="chatflow-emoji-btn" class="cf-emoji-btn" title="Insert emoji" aria-label="Insert emoji" ${preChatBlocking ? 'disabled' : ''}>😊</button>
            <input id="chatflow-input" type="text" placeholder="${escapeHtml(preChatBlocking ? t('pre_chat_input_ph', 'Complete the form above to start chatting') : t('input_placeholder', 'Type a message...'))}" ${state.loading || preChatBlocking ? 'disabled' : ''} />
            <button id="chatflow-send" type="button" aria-label="Send" ${state.loading || preChatBlocking ? 'disabled' : ''}>${ICONS.send}</button>
            <div id="chatflow-emoji-panel"></div>
          </div>
          <div class="cf-powered">Powered by AI Chatbot Hub Pro</div>
        </div>
      </div>
    `;

    applyTheme();
    applyPanelPosition();
    bindEvents();
    initWidgetChrome();
    renderMessages();
    renderTyping();
    renderQuickActions();
    renderHandoffButton();
    renderChannelButtons();
    renderPreChat();
    renderRatingModal();
  }

  function renderChannelButtons() {
    const el = document.getElementById('chatflow-channels');
    if (!el) return;
    const ch = state.config?.channels || {};
    const wa = ch.whatsapp || {};
    const em = ch.email || {};
    const parts = [];
    if (wa.enabled && wa.number) {
      const num = String(wa.number).replace(/\D/g, '');
      const text = encodeURIComponent(wa.message || 'Hi, I need help');
      parts.push(`<a href="https://wa.me/${num}?text=${text}" target="_blank" rel="noopener" class="cf-channel-btn cf-channel-wa" title="WhatsApp">WhatsApp</a>`);
    }
    if (em.enabled && em.address) {
      const subj = encodeURIComponent(em.subject || 'Support request');
      parts.push(`<a href="mailto:${escapeHtml(em.address)}?subject=${subj}" class="cf-channel-btn cf-channel-email" title="Email">Email</a>`);
    }
    el.innerHTML = parts.length ? `<div class="cf-channels-row">${parts.join('')}</div>` : '';
  }

  function startConfigSync() {
    if (state.configSyncTimer) clearInterval(state.configSyncTimer);
    state.configVersion = state.config?.version ?? null;
    const tick = async () => {
      if (!state.visitorId) return;
      try {
        const cfg = await api('/config?visitor_id=' + encodeURIComponent(state.visitorId));
        if (state.configVersion !== null && cfg.version !== state.configVersion) {
          state.config = cfg;
          state.configVersion = cfg.version;
          applyTheme();
          render();
        } else if (state.configVersion === null) {
          state.configVersion = cfg.version;
        }
      } catch (_) {}
      const delay = state.open ? 15000 : 45000;
      state.configSyncTimer = setTimeout(tick, delay);
    };
    state.configSyncTimer = setTimeout(tick, 15000);
  }

  function renderMessages() {
    const box = document.getElementById('chatflow-messages');
    if (!box) return;

    const hasGreetingMsg = state.messages.some((m) => m.source === 'greeting');
    if (!state.messages.length && state.open && state.config?.messages?.welcome && !hasGreetingMsg && !needsPreChat()) {
      box.innerHTML = `
        <div class="cf-row cf-row-bot">
          <div class="cf-msg-avatar cf-msg-avatar-bot">${ICONS.bot}</div>
          <div>
            <div class="cf-bubble cf-bubble-bot">${escapeHtml(state.config.messages.welcome)}</div>
          </div>
        </div>`;
      scrollMessagesToBottom(false);
      return;
    }

    box.innerHTML = state.messages
      .map((m) => {
        const isUser = m.sender_type === 'visitor';
        const isAgent = m.sender_type === 'agent';
        const bubbleClass = isUser ? 'cf-bubble-user' : isAgent ? 'cf-bubble-agent' : 'cf-bubble-bot';
        const rowClass = isUser ? 'cf-row-user' : 'cf-row-bot';
        const avatar = isUser
          ? `<div class="cf-msg-avatar cf-msg-avatar-user">${ICONS.user}</div>`
          : `<div class="cf-msg-avatar cf-msg-avatar-bot">${ICONS.bot}</div>`;

        const attHtml = (m.attachments || []).map((att) => {
          const url = att.url + (att.url.includes('?') ? '&' : '?') + 'visitor_id=' + encodeURIComponent(state.visitorId);
          if (att.is_image) {
            return `<a href="${escapeHtml(url)}" target="_blank" rel="noopener"><img class="cf-att-img" src="${escapeHtml(url)}" alt=""></a>`;
          }
          return `<a href="${escapeHtml(url)}" target="_blank" rel="noopener" class="cf-att-file">📎 ${escapeHtml(att.original_name)}</a>`;
        }).join('');

        const textHtml = m.source !== 'attachment' && m.content ? escapeHtml(m.content) : '';

        return `
          <div class="cf-row ${rowClass}">
            ${avatar}
            <div class="cf-msg-body">
              <div class="cf-bubble ${bubbleClass}">${textHtml}${attHtml}</div>
              ${reactionsHtml(m)}
              ${reactionPickerHtml(m)}
              <div class="cf-time">${m.created_at ? formatTime(m.created_at) : ''}${isAgent ? ' ' + receiptHtml(m.receipt_status) : ''}</div>
            </div>
          </div>`;
      })
      .join('');
    scrollMessagesToBottom();

    if (state.open && state.conversationId) {
      const unreadAgentIds = state.messages
        .filter((m) => m.sender_type === 'agent' && m.id && m.receipt_status !== 'read')
        .map((m) => m.id);
      if (unreadAgentIds.length) {
        markVisitorRead(unreadAgentIds).then(() => {
          unreadAgentIds.forEach((id) => {
            const msg = state.messages.find((m) => m.id === id);
            if (msg) msg.receipt_status = 'read';
          });
        });
      }
    }
  }

  function renderTyping() {
    const el = document.getElementById('chatflow-typing-area');
    if (!el) return;
    if (!state.typing) {
      el.innerHTML = '';
      return;
    }
    el.innerHTML = `
      <div class="cf-typing-wrap">
        <div class="cf-row cf-row-bot">
          <div class="cf-msg-avatar cf-msg-avatar-bot">${ICONS.bot}</div>
          <div class="cf-bubble cf-bubble-bot cf-typing-bubble">
            <span></span><span></span><span></span>
          </div>
        </div>
      </div>`;
    scrollMessagesToBottom();
  }

  function renderQuickActions() {
    const el = document.getElementById('chatflow-actions');
    if (!el) return;

    const showSuggestions = state.config?.modules?.suggested_questions !== false;
    const suggestions = showSuggestions ? (state.config?.suggested_questions || []) : [];
    const actions = state.config?.quick_actions || [];
    const total = suggestions.length + actions.length;

    if (!total) {
      el.innerHTML = '';
      return;
    }

    const open = state.quickActionsOpen;
    const toggleLabel = t('quick_actions', 'Quick actions');
    const suggestionButtons = suggestions
      .map((q) => `<button type="button" class="cf-chip" data-q="${escapeHtml(q)}">${escapeHtml(q)}</button>`)
      .join('');
    const actionButtons = actions
      .map((a, idx) => {
        const color = (a.color || '').trim();
        const style = color ? ` style="--cf-action-color:${escapeHtml(color)}"` : '';
        const icon = a.icon ? `<span class="cf-action__icon" aria-hidden="true">${escapeHtml(a.icon)}</span>` : '';
        const desc = a.description ? `<span class="cf-action__desc">${escapeHtml(a.description)}</span>` : '';
        return `<button type="button" class="cf-action" data-idx="${idx}"${style}>
          ${icon}
          <span class="cf-action__body">
            <span class="cf-action__label">${escapeHtml(a.label)}</span>
            ${desc}
          </span>
        </button>`;
      })
      .join('');

    el.innerHTML = `
      <div class="cf-quick-actions">
        <button
          type="button"
          class="cf-quick-actions-toggle"
          id="chatflow-quick-actions-toggle"
          aria-expanded="${open ? 'true' : 'false'}"
          aria-controls="chatflow-quick-actions-panel"
        >
          <span class="cf-quick-actions-toggle__label">${escapeHtml(toggleLabel)}</span>
          <span class="cf-quick-actions-toggle__count">${total}</span>
          <span class="cf-quick-actions-toggle__chev" aria-hidden="true"></span>
        </button>
        <div
          id="chatflow-quick-actions-panel"
          class="cf-quick-actions-panel ${open ? 'is-open' : ''}"
          ${open ? '' : 'hidden'}
        >
          <div class="cf-quick-actions-grid">
            ${suggestionButtons}
            ${actionButtons}
          </div>
        </div>
      </div>`;

    document.getElementById('chatflow-quick-actions-toggle')?.addEventListener('click', () => {
      state.quickActionsOpen = !state.quickActionsOpen;
      renderQuickActions();
    });

    el.querySelectorAll('.cf-chip').forEach((btn) => {
      btn.addEventListener('click', () => sendMessage(btn.dataset.q));
    });

    el.querySelectorAll('.cf-action').forEach((btn) => {
      btn.addEventListener('click', () => {
        const idx = Number(btn.dataset.idx);
        const action = actions[idx];
        if (!action) return;
        handleQuickAction(action.action_type, action.action_value, action.label, action);
      });
    });
  }

  function renderHandoffButton() {
    const el = document.getElementById('chatflow-handoff');
    if (!el) return;
    const enabled = state.config?.modules?.live_agent !== false;
    const show = enabled && !state.humanMode && !state.handoff && state.open;
    el.innerHTML = show
      ? `<button type="button" id="chatflow-human-btn" class="cf-handoff-btn">${escapeHtml(t('request_human', 'Talk to a human agent'))}</button>`
      : '';
    document.getElementById('chatflow-human-btn')?.addEventListener('click', requestHumanAgent);
  }

  function needsPreChat() {
    if (state.config?.modules?.pre_chat_form === false) return false;
    if (state.preChatDone) return false;
    if (state.visitorProfile.name && state.visitorProfile.phone) return false;
    return true;
  }

  function preChatGreeting() {
    const welcome = state.config?.messages?.welcome;
    return welcome && String(welcome).trim()
      ? String(welcome).trim()
      : t('pre_chat_greeting', 'Hi there! Welcome — we are happy to help you.');
  }

  function renderPreChat() {
    const el = document.getElementById('chatflow-prechat');
    if (!el) return;
    const show = state.open && needsPreChat();
    const greeting = preChatGreeting();
    el.innerHTML = show
      ? `<div class="cf-prechat">
          <div class="cf-prechat-greeting">${escapeHtml(greeting)}</div>
          <p class="cf-prechat-title">${escapeHtml(t('pre_chat_title', 'Get started'))}</p>
          <p class="cf-prechat-sub">${escapeHtml(t('pre_chat_sub', 'Tell us a bit about yourself to begin the conversation.'))}</p>
          <form id="cf-prechat-form" class="cf-prechat-form" novalidate>
            <label class="cf-prechat-field">
              <span class="cf-prechat-label">${escapeHtml(t('name', 'Name'))} <span class="cf-prechat-req">*</span></span>
              <input id="cf-pre-name" type="text" class="cf-prechat-input" autocomplete="name" required placeholder="${escapeHtml(t('name_ph', 'Your full name'))}" value="${escapeHtml(state.visitorProfile.name)}" />
            </label>
            <label class="cf-prechat-field">
              <span class="cf-prechat-label">${escapeHtml(t('phone', 'Phone'))} <span class="cf-prechat-req">*</span></span>
              <input id="cf-pre-phone" type="tel" class="cf-prechat-input" autocomplete="tel" required placeholder="${escapeHtml(t('phone_ph', '+1 555 000 0000'))}" value="${escapeHtml(state.visitorProfile.phone)}" />
            </label>
            <label class="cf-prechat-field">
              <span class="cf-prechat-label">${escapeHtml(t('email', 'Email'))} <span class="cf-prechat-opt">${escapeHtml(t('optional', '(optional)'))}</span></span>
              <input id="cf-pre-email" type="email" class="cf-prechat-input" autocomplete="email" placeholder="${escapeHtml(t('email_ph', 'you@company.com'))}" value="${escapeHtml(state.visitorProfile.email)}" />
            </label>
            <label class="cf-prechat-field">
              <span class="cf-prechat-label">${escapeHtml(t('company', 'Company'))} <span class="cf-prechat-opt">${escapeHtml(t('optional', '(optional)'))}</span></span>
              <input id="cf-pre-company" type="text" class="cf-prechat-input" autocomplete="organization" placeholder="${escapeHtml(t('company_ph', 'Company name'))}" value="${escapeHtml(state.visitorProfile.company)}" />
            </label>
            <p id="cf-pre-error" class="cf-prechat-error" hidden></p>
            <button type="submit" id="cf-pre-submit" class="cf-prechat-submit" ${state.preChatSubmitting ? 'disabled' : ''}>${state.preChatSubmitting ? escapeHtml(t('starting', 'Starting…')) : escapeHtml(t('start_chat', 'Start chat'))}</button>
          </form>
        </div>`
      : '';
    const form = document.getElementById('cf-prechat-form');
    form?.addEventListener('submit', (e) => {
      e.preventDefault();
      submitPreChat();
    });
  }

  function finishPreChat(profile, apiResult) {
    state.visitorProfile = {
      name: profile.name || '',
      phone: profile.phone || '',
      email: profile.email || '',
      company: profile.company || '',
    };
    state.preChatDone = true;
    state.preChatSubmitting = false;

    if (apiResult?.conversation_id) {
      state.conversationId = apiResult.conversation_id;
      persistSessionChat();
      startPolling();
    }

    if (apiResult?.greeting) {
      const g = apiResult.greeting;
      if (!state.messages.some((m) => m.id === g.id)) {
        state.messages.push({
          id: g.id,
          sender_type: g.sender_type || 'bot',
          content: g.content,
          source: g.source || 'greeting',
          created_at: g.created_at,
        });
      }
    }

    persistSessionChat();
    renderPreChat();
    render();
    scrollMessagesToBottom();
  }

  async function submitPreChat() {
    const name = document.getElementById('cf-pre-name')?.value.trim() || '';
    const phone = document.getElementById('cf-pre-phone')?.value.trim() || '';
    const email = document.getElementById('cf-pre-email')?.value.trim() || '';
    const company = document.getElementById('cf-pre-company')?.value.trim() || '';
    const errEl = document.getElementById('cf-pre-error');

    if (!name) {
      if (errEl) {
        errEl.hidden = false;
        errEl.textContent = t('err_name', 'Please enter your name.');
      }
      document.getElementById('cf-pre-name')?.focus();
      return;
    }
    if (!phone) {
      if (errEl) {
        errEl.hidden = false;
        errEl.textContent = t('err_phone', 'Please enter your phone number.');
      }
      document.getElementById('cf-pre-phone')?.focus();
      return;
    }
    if (errEl) errEl.hidden = true;

    state.preChatSubmitting = true;
    renderPreChat();

    try {
      const res = await api('/start', {
        method: 'POST',
        body: JSON.stringify({
          visitor_id: state.visitorId,
          visitor_name: name,
          visitor_phone: phone,
          visitor_email: email || null,
          visitor_company: company || null,
          page_url: window.location.href,
        }),
      });
      finishPreChat({ name, phone, email, company }, res);
      trackEvent('pre_chat_submitted');
    } catch (e) {
      state.preChatSubmitting = false;
      renderPreChat();
      if (errEl) {
        errEl.hidden = false;
        const apiErr = e?.errors ? Object.values(e.errors).flat().find(Boolean) : null;
        errEl.textContent = apiErr || e?.message || t('err_start', 'Could not start chat. Please try again.');
      }
    }
  }

  function renderRatingModal() {
    const el = document.getElementById('chatflow-rating');
    if (!el) return;
    el.innerHTML = state.showRating
      ? `<div class="cf-rating">
          <p class="cf-rating-title">${escapeHtml(t('rate_title', 'How was your experience?'))}</p>
          <div class="cf-rating-stars">
            ${[1, 2, 3, 4, 5].map((n) => `<button type="button" class="cf-star ${state.pendingRatingScore >= n ? 'active' : ''}" data-score="${n}">★</button>`).join('')}
          </div>
          <textarea id="cf-rating-comment" class="cf-rating-comment" rows="2" placeholder="${escapeHtml(t('rate_comment', 'Optional feedback…'))}"></textarea>
          <div class="cf-rating-actions">
            <button type="button" id="cf-rating-submit" class="cf-rating-submit">${escapeHtml(t('submit', 'Submit'))}</button>
            <button type="button" id="cf-rating-skip" class="cf-rating-skip">${escapeHtml(t('skip', 'Skip'))}</button>
          </div>
        </div>`
      : '';
    el.querySelectorAll('.cf-star').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.pendingRatingScore = parseInt(btn.dataset.score, 10);
        renderRatingModal();
      });
    });
    document.getElementById('cf-rating-submit')?.addEventListener('click', submitRating);
    document.getElementById('cf-rating-skip')?.addEventListener('click', dismissRating);
  }

  async function requestHumanAgent() {
    if (needsPreChat()) {
      renderPreChat();
      return;
    }
    if (!state.conversationId) {
      await sendMessage('Talk to human agent');
      return;
    }
    try {
      const res = await api('/handoff', {
        method: 'POST',
        body: JSON.stringify({
          visitor_id: state.visitorId,
          conversation_id: state.conversationId,
          visitor_name: state.visitorProfile.name || null,
          visitor_email: state.visitorProfile.email || null,
          visitor_phone: state.visitorProfile.phone || null,
          visitor_company: state.visitorProfile.company || null,
        }),
      });
      applyHandoffState({ ...res, handoff: true, mode: 'human' });
      render();
      trackEvent('handoff_requested');
    } catch (_) {
      addMessage('bot', 'Unable to connect to an agent right now. Please try again.', 'error');
    }
  }

  async function submitRating() {
    const score = state.pendingRatingScore;
    if (score < 1) {
      await dismissRating();
      return;
    }
    const comment = document.getElementById('cf-rating-comment')?.value.trim() || null;
    state.rated = true;
    state.showRating = false;
    await trackEvent('conversation_rating', { score, comment });
    renderRatingModal();
    await endConversation();
  }

  async function dismissRating() {
    state.showRating = false;
    state.rated = true;
    renderRatingModal();
    await endConversation();
  }

  async function endConversation() {
    if (state.conversationId) {
      try {
        await api('/close', {
          method: 'POST',
          body: JSON.stringify({
            visitor_id: state.visitorId,
            conversation_id: state.conversationId,
          }),
        });
        trackEvent('conversation_closed');
      } catch (_) {}
    }
    clearChatSession();
  }

  function visitorPayload() {
    return {
      visitor_name: state.visitorProfile.name || null,
      visitor_email: state.visitorProfile.email || null,
      visitor_phone: state.visitorProfile.phone || null,
      visitor_company: state.visitorProfile.company || null,
    };
  }

  function handleQuickAction(type, value, label, action) {
    trackEvent('quick_action', { type, label });
    if (type === 'answer' && action && action.custom_answer) {
      const question = label || 'Quick question';
      const now = new Date().toISOString();
      state.messages.push({
        id: 'local-qa-q-' + Date.now(),
        sender_type: 'visitor',
        content: question,
        created_at: now,
        source: 'quick_action',
      });
      renderMessages();
      setTimeout(() => {
        state.messages.push({
          id: 'local-qa-a-' + Date.now(),
          sender_type: 'bot',
          content: action.custom_answer,
          created_at: new Date().toISOString(),
          source: 'quick_action',
        });
        renderMessages();
      }, 350);
      return;
    }
    if (type === 'url' && value) {
      window.open(value, '_blank', 'noopener');
      return;
    }
    if (type === 'email' && value) {
      window.location.href = 'mailto:' + value;
      return;
    }
    if (type === 'whatsapp' && value) {
      const num = String(value).replace(/\D/g, '');
      const text = encodeURIComponent(label || 'Hi, I need help');
      window.open('https://wa.me/' + num + '?text=' + text, '_blank', 'noopener');
      return;
    }
    if (type === 'phone' && value) {
      window.location.href = 'tel:' + value;
      return;
    }
    if (type === 'message' && value) {
      sendMessage(value);
      return;
    }
    sendMessage(label || value || 'Help');
  }

  function getPollInterval() {
    if (state.handoff || state.humanMode) return 1000;
    if (state.conversationId) return 1500;
    return 4000;
  }

  function startPolling() {
    stopPolling();
    if (!state.conversationId) return;
    const tick = async () => {
      await pollMessages();
      state.pollTimer = setTimeout(tick, getPollInterval());
    };
    state.pollTimer = setTimeout(tick, 500);
  }

  function stopPolling() {
    if (state.pollTimer) {
      clearTimeout(state.pollTimer);
      state.pollTimer = null;
    }
  }

  async function pollMessages() {
    if (!state.conversationId) return;
    try {
      const lastId = state.messages.reduce((max, m) => Math.max(max, m.id || 0), 0);
      const res = await api(
        '/poll?visitor_id=' + encodeURIComponent(state.visitorId) +
        '&conversation_id=' + state.conversationId +
        '&after_id=' + lastId
      );
      let added = false;
      (res.messages || []).forEach((m) => {
        if (isHandoffNoticeMessage(m) && hasHandoffNoticeInMessages()) {
          return;
        }
        if (!state.messages.some((x) => x.id === m.id)) {
          if (isHandoffNoticeMessage(m)) {
            state.handoffNoticeShown = true;
          }
          state.messages.push({
            id: m.id,
            sender_type: m.sender_type,
            content: m.content,
            source: m.source,
            created_at: m.created_at,
            attachments: m.attachments,
            receipt_status: m.receipt_status,
            reactions: m.reactions || [],
          });
          added = true;
        } else {
          const existing = state.messages.find((x) => x.id === m.id);
          if (existing) {
            existing.receipt_status = m.receipt_status || existing.receipt_status;
            if (m.reactions) existing.reactions = m.reactions;
          }
        }
      });
      if (mergeConversationReactions(res.reactions)) {
        added = true;
      }
      if (added) {
        const hasIncoming = (res.messages || []).some(
          (m) => (m.id || 0) > lastId && (m.sender_type === 'agent' || m.sender_type === 'bot')
        );
        if (hasIncoming) playNotificationSound();
        if (!state.open) {
          state.unread = true;
        }
        renderMessages();
        const launcher = document.getElementById('chatflow-launcher');
        if (launcher && !state.open) {
          launcher.classList.add('pulse');
        }
      }
      state.handoff = res.handoff || state.handoff;
      state.humanMode = res.mode === 'human' || state.humanMode;
    } catch (_) {}
  }

  function loadEmojiPickerScript() {
    if (window.initEmojiPicker) return Promise.resolve();
    if (document.getElementById('chatflow-emoji-script')) {
      return new Promise((resolve) => {
        const wait = () => (window.initEmojiPicker ? resolve() : setTimeout(wait, 50));
        wait();
      });
    }
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.id = 'chatflow-emoji-script';
      script.src = apiBase + '/js/emoji-picker.js';
      script.onload = () => resolve();
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  function initWidgetEmojiPicker() {
    loadEmojiPickerScript().then(() => {
      window.initEmojiPicker?.({
        trigger: document.getElementById('chatflow-emoji-btn'),
        input: document.getElementById('chatflow-input'),
        panel: document.getElementById('chatflow-emoji-panel'),
      });
    }).catch(() => {});
  }

  function loadPanelPosition() {
    if (state.inline || state.config?.modules?.widget_draggable === false) return;
    try {
      const saved = JSON.parse(sessionStorage.getItem(DRAG_POS_KEY) || 'null');
      if (saved && typeof saved.left === 'number' && typeof saved.top === 'number') {
        state.panelPosition = saved;
      }
    } catch (_) {}
  }

  function savePanelPosition() {
    if (!state.panelPosition) {
      sessionStorage.removeItem(DRAG_POS_KEY);
      return;
    }
    sessionStorage.setItem(DRAG_POS_KEY, JSON.stringify(state.panelPosition));
  }

  function applyWidgetOffsets(cfg) {
    const root = document.getElementById('chatflow-root');
    if (!root || !cfg?.appearance) return;
    const bottom = Number(cfg.appearance.offset_bottom);
    const side = Number(cfg.appearance.offset_side);
    root.style.setProperty('--cf-offset-bottom', `${Number.isFinite(bottom) ? bottom : 24}px`);
    root.style.setProperty('--cf-offset-side', `${Number.isFinite(side) ? side : 24}px`);
  }

  function applyPanelPosition() {
    const panel = document.getElementById('chatflow-panel');
    if (!panel || state.inline || state.fullscreen) {
      if (panel && state.fullscreen) {
        panel.style.left = '';
        panel.style.top = '';
        panel.style.right = '';
        panel.style.bottom = '';
      }
      return;
    }
    if (state.panelPosition) {
      panel.style.left = state.panelPosition.left + 'px';
      panel.style.top = state.panelPosition.top + 'px';
      panel.style.right = 'auto';
      panel.style.bottom = 'auto';
    }
  }

  function initWidgetChrome() {
    if (dragBound) return;
    dragBound = true;

    document.addEventListener('pointerdown', (e) => {
      const header = e.target.closest('#chatflow-header.cf-draggable');
      if (!header || e.target.closest('.cf-icon-btn') || state.inline || state.fullscreen) return;
      const panel = document.getElementById('chatflow-panel');
      if (!panel) return;

      const rect = panel.getBoundingClientRect();
      const offsetX = e.clientX - rect.left;
      const offsetY = e.clientY - rect.top;

      const onMove = (ev) => {
        const left = Math.max(8, Math.min(window.innerWidth - rect.width - 8, ev.clientX - offsetX));
        const top = Math.max(8, Math.min(window.innerHeight - rect.height - 8, ev.clientY - offsetY));
        state.panelPosition = { left, top };
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
        panel.style.right = 'auto';
        panel.style.bottom = 'auto';
      };

      const onUp = () => {
        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);
        savePanelPosition();
      };

      document.addEventListener('pointermove', onMove);
      document.addEventListener('pointerup', onUp);
    });
  }

  function toggleFullscreen() {
    state.fullscreen = !state.fullscreen;
    render();
  }

  function bindEvents() {
    document.getElementById('chatflow-launcher')?.addEventListener('click', toggle);
    document.getElementById('chatflow-close')?.addEventListener('click', () => closePanel());
    document.getElementById('chatflow-minimize')?.addEventListener('click', () => minimizePanel());
    document.getElementById('chatflow-fullscreen')?.addEventListener('click', () => toggleFullscreen());
    document.getElementById('chatflow-sound')?.addEventListener('click', () => {
      setSoundMuted(!isSoundMuted());
      render();
    });
    document.getElementById('chatflow-send')?.addEventListener('click', () => {
      const input = document.getElementById('chatflow-input');
      if (input?.value.trim()) sendMessage(input.value.trim());
    });
    document.getElementById('chatflow-input')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const input = e.target;
        if (input.value.trim()) sendMessage(input.value.trim());
      }
    });
    document.getElementById('chatflow-file')?.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (file) uploadAttachment(file);
      e.target.value = '';
    });
    initWidgetEmojiPicker();

    document.getElementById('chatflow-messages')?.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-react-msg]');
      if (!btn) return;
      e.preventDefault();
      reactToMessage(parseInt(btn.dataset.reactMsg, 10), btn.dataset.reactEmoji);
    });
  }

  async function uploadAttachment(file) {
    if (state.loading) return;
    if (needsPreChat()) {
      renderPreChat();
      return;
    }
    if (!state.conversationId) {
      await sendMessage('Hello');
      if (!state.conversationId) return;
    }

    state.loading = true;
    render();

    const formData = new FormData();
    formData.append('file', file);
    formData.append('visitor_id', state.visitorId);
    formData.append('conversation_id', state.conversationId);

    try {
      const url = apiBase + '/api/widget/' + botToken + '/attachments';
      const res = await fetch(url, { method: 'POST', body: formData });
      if (!res.ok) throw new Error('upload failed');
      const data = await res.json();
      if (data.message) {
        state.messages.push({
          id: data.message.id,
          sender_type: data.message.sender_type,
          content: data.message.content,
          source: data.message.source,
          created_at: data.message.created_at,
          attachments: data.message.attachments || [],
        });
        renderMessages();
      }
    } catch (_) {
      addMessage('bot', 'File upload failed. Max size 10 MB.', 'error');
    } finally {
      state.loading = false;
      render();
    }
  }

  function openPanel() {
    state.open = true;
    state.unread = false;
    trackEvent('widget_open');
    render();
    scrollMessagesToBottom();
    startConfigSync();
  }

  function minimizePanel() {
    state.open = false;
    state.showRating = false;
    render();
  }

  async function closePanel() {
    const shouldRate = state.open
      && state.messages.length > 0
      && state.conversationId
      && !state.rated
      && state.config?.modules?.csat_survey !== false;

    if (shouldRate) {
      state.showRating = true;
      state.pendingRatingScore = 0;
      state.open = false;
      renderRatingModal();
      render();
      return;
    }

    await endConversation();
    state.open = false;
    state.showRating = false;
    render();
  }

  function toggle() {
    if (state.open) closePanel();
    else openPanel();
  }

  function addMessage(senderType, content, source, id, createdAt) {
    if (senderType === 'bot' && isHandoffNoticeMessage({ content, source }) && hasHandoffNoticeInMessages()) {
      return;
    }
    if (senderType === 'bot' && isHandoffNoticeMessage({ content, source })) {
      state.handoffNoticeShown = true;
    }
    state.messages.push({
      id: id || null,
      sender_type: senderType,
      content,
      source,
      created_at: createdAt || new Date().toISOString(),
    });
    renderMessages();
  }

  async function promptRating() {
    if (!state.conversationId || state.rated) return;
    state.showRating = true;
    state.pendingRatingScore = 0;
    renderRatingModal();
  }

  async function trackEvent(type, payload = {}) {
    if (!state.config?.modules?.analytics) return;
    try {
      await api('/events', {
        method: 'POST',
        body: JSON.stringify({
          event_type: type,
          visitor_id: state.visitorId,
          conversation_id: state.conversationId,
          payload,
        }),
      });
    } catch (_) {}
  }

  async function sendMessage(text) {
    if (state.loading) return;
    if (needsPreChat()) {
      renderPreChat();
      return;
    }
    const input = document.getElementById('chatflow-input');
    if (input) input.value = '';

    if (!state.config?.operating_hours?.is_open) {
      addMessage('bot', state.config?.messages?.outside_hours || state.config?.messages?.offline || 'We are offline.', 'offline');
      return;
    }

    if (!state.open) openPanel();

    addMessage('visitor', text, 'user');
    state.loading = true;
    state.typing = state.config?.modules?.typing_indicator !== false;
    renderTyping();
    const sendBtn = document.getElementById('chatflow-send');
    const inputEl = document.getElementById('chatflow-input');
    if (sendBtn) sendBtn.disabled = true;
    if (inputEl) inputEl.disabled = true;

    try {
      const res = await api('/chat', {
        method: 'POST',
        body: JSON.stringify({
          message: text,
          visitor_id: state.visitorId,
          conversation_id: state.conversationId,
          page_url: window.location.href,
          ...visitorPayload(),
        }),
      });

      if (res.conversation_id) {
        state.conversationId = res.conversation_id;
        persistSessionChat();
        startPolling();
      }

      if (res.offline && res.message) {
        pushBotMessageIfNew(res.message);
      } else if (res.message && !res.handoff && res.status !== 'awaiting_agent') {
        pushBotMessageIfNew(res.message);
      }

      if (res.handoff || res.status === 'awaiting_agent') {
        applyHandoffState(res);
      } else if (res.mode === 'human') {
        state.humanMode = true;
        startPolling();
      }

      trackEvent('message_sent');
    } catch (err) {
      const text = err?.status === 429
        ? (err.message || 'You\'re sending messages too quickly. Please wait a moment and try again.')
        : 'Sorry, something went wrong. Please try again.';
      addMessage('bot', text, 'error');
    } finally {
      state.loading = false;
      state.typing = false;
      render();
    }
  }

  async function loadConfig() {
    initPageSession();
    state.visitorId = getVisitorId();
    state.messages = [];
    restoreSessionChat();

    state.config = await api('/config?visitor_id=' + encodeURIComponent(state.visitorId));
    state.configVersion = state.config?.version ?? null;
    startConfigSync();

    if (state.config?.appearance?.auto_open && !state.inline) {
      state.open = true;
    }

    if (state.config?.appearance?.custom_js) {
      try {
        new Function(state.config.appearance.custom_js)();
      } catch (e) {
        console.warn('[ChatFlow] custom_js error', e);
      }
    }
  }

  async function init() {
    injectStyles();
    state.visitorId = getVisitorId();
    try {
      await loadConfig();
      loadPanelPosition();
      render();
      trackEvent('widget_loaded');
    } catch (err) {
      console.error('[ChatFlow] Failed to load config:', err.message);
    }
  }

  window.ChatFlow = window.ChatFlow || {};
  window.ChatFlow.open = () => openPanel();
  window.ChatFlow.close = () => closePanel();
  window.ChatFlow.toggle = () => toggle();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
