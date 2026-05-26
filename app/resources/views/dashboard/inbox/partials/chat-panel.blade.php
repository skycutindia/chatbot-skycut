@php
    $canWrite = auth()->user()->roleEnum()->canHandleLiveChat();
    $agentQuickReplies = $agentQuickReplies ?? collect();
    $quickReplyCount = $agentQuickReplies->count() + ($canned ?? collect())->count();
    $visitorInitials = collect(explode(' ', trim($conversation->visitor_name ?: 'Visitor')))
        ->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->join('') ?: 'V';
    $isClosed = in_array($conversation->status, ['closed', 'resolved'], true);
    $statusLabel = str_replace('_', ' ', $conversation->status);
    $contactSubtitle = $conversation->visitor_email
        ?: ($conversation->visitor_phone ?: ($conversation->visitor_company ?: 'Contact details from pre-chat'));
@endphp

<div class="lc-chat-panel" data-conversation-id="{{ $conversation->id }}">
    <header class="lc-conversation-head">
        <div class="lc-conversation-head-left">
            <a href="{{ route('inbox.index', request()->except('conversation')) }}" class="lc-chat-back" aria-label="Back to conversations">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div class="lc-conversation-identity">
                <span class="lc-chat-avatar">{{ $visitorInitials }}</span>
                <div class="lc-conversation-info">
                    <div class="lc-conversation-title-row">
                        <h2 class="lc-conversation-name">{{ $conversation->visitor_name ?: 'Visitor' }}</h2>
                        <span class="lc-head-pill {{ $conversation->status === 'awaiting_agent' ? 'is-warn' : ($isClosed ? 'is-muted' : 'is-success') }}">{{ ucfirst($statusLabel) }}</span>
                        @if($conversation->channel === 'whatsapp')<span class="lc-head-pill is-accent">WhatsApp</span>@endif
                        <span class="lc-head-pill">{{ ucfirst($conversation->mode) }}</span>
                    </div>
                    <p class="lc-conversation-meta">
                        <span>{{ $website->name }}</span>
                        @if($conversation->visitor_email)
                            <span class="lc-meta-sep">·</span>
                            <span>{{ $conversation->visitor_email }}</span>
                        @elseif($conversation->visitor_phone)
                            <span class="lc-meta-sep">·</span>
                            <span>{{ $conversation->visitor_phone }}</span>
                        @endif
                        @if($conversation->visitor_company && !$conversation->visitor_email)
                            <span class="lc-meta-sep">·</span>
                            <span>{{ $conversation->visitor_company }}</span>
                        @endif
                        @if($conversation->assignedUser)
                            <span class="lc-meta-sep">·</span>
                            <span>Assigned to {{ $conversation->assignedUser->name }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="lc-conversation-head-actions">
            <button type="button" id="lc-visitor-toggle" class="lc-btn lc-btn-ghost" title="Contact & lead" aria-expanded="false" aria-controls="lc-contact-popup">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="lc-btn-label">Contact</span>
            </button>
            @if($canWrite)
                <button type="button" class="lc-btn lc-btn-ghost lc-icon-btn" title="Star conversation"
                    data-lc-meta-toggle data-lc-meta-url="{{ route('inbox.meta', $conversation) }}"
                    data-lc-meta-field="is_starred" data-lc-meta-value="{{ $conversation->is_starred ? '0' : '1' }}"
                    data-lc-meta-active="{{ $conversation->is_starred ? '1' : '0' }}">
                    <span class="lc-star-icon">{{ $conversation->is_starred ? '★' : '☆' }}</span>
                </button>
                <button type="button" class="lc-btn lc-btn-ghost lc-icon-btn" title="Pin conversation"
                    data-lc-meta-toggle data-lc-meta-url="{{ route('inbox.meta', $conversation) }}"
                    data-lc-meta-field="is_pinned" data-lc-meta-value="{{ $conversation->is_pinned ? '0' : '1' }}"
                    data-lc-meta-active="{{ $conversation->is_pinned ? '1' : '0' }}">
                    📌
                </button>
                @if(!$conversation->assigned_user_id)
                    <button type="button" class="lc-btn lc-btn-primary"
                        data-lc-post="{{ route('inbox.assign', $conversation) }}"
                        data-lc-success="Assigned to you">
                        Assign me
                    </button>
                @endif
                <details class="lc-actions-menu">
                    <summary class="lc-btn lc-btn-ghost lc-icon-btn" title="More actions">⋯</summary>
                    <div class="lc-actions-dropdown">
                        @if(!$isClosed)
                            <button type="button" data-lc-post="{{ route('inbox.close', $conversation) }}" data-lc-success="Chat closed" data-lc-reload="1">Close chat</button>
                            <button type="button" data-lc-post="{{ route('inbox.resolve', $conversation) }}" data-lc-success="Marked resolved" data-lc-reload="1">Mark resolved</button>
                        @else
                            <button type="button" data-lc-post="{{ route('inbox.reopen', $conversation) }}" data-lc-success="Chat reopened" data-lc-reload="1">Reopen chat</button>
                        @endif
                        @if($conversation->mode === 'human')
                            <button type="button" data-lc-post="{{ route('inbox.return-to-ai', $conversation) }}" data-lc-success="Returned to AI" data-lc-reload="1">Return to AI</button>
                        @endif
                        <a href="{{ route('inbox.transcript.csv', $conversation) }}">Export CSV</a>
                        <a href="{{ route('inbox.transcript.pdf', $conversation) }}">Export PDF</a>
                        <a href="{{ route('inbox.transcript.txt', $conversation) }}">Export TXT</a>
                    </div>
                </details>
            @else
                <details class="lc-actions-menu">
                    <summary class="lc-btn lc-btn-ghost" title="Export transcript">Export</summary>
                    <div class="lc-actions-dropdown">
                        <a href="{{ route('inbox.transcript.csv', $conversation) }}">Export CSV</a>
                        <a href="{{ route('inbox.transcript.pdf', $conversation) }}">Export PDF</a>
                        <a href="{{ route('inbox.transcript.txt', $conversation) }}">Export TXT</a>
                    </div>
                </details>
            @endif
        </div>
    </header>

    <div class="lc-chat-workspace">
        <div class="lc-chat-main">
            <div class="lc-chat-body">
                <div id="lc-message-list" class="lc-messages" data-poll-url="{{ route('websites.conversations.messages', [$website, $conversation]) }}">
                @php $lastMsgDate = null; @endphp
                @forelse($conversation->messages as $msg)
                    @php
                        $msgDate = $msg->created_at->format('Y-m-d');
                        $dateLabel = $msg->created_at->isToday() ? 'Today' : ($msg->created_at->isYesterday() ? 'Yesterday' : $msg->created_at->format('M j, Y'));
                    @endphp
                    @if($msgDate !== $lastMsgDate)
                        <div class="lc-msg-date"><span>{{ $dateLabel }}</span></div>
                        @php $lastMsgDate = $msgDate; @endphp
                    @endif
                    <div class="lc-msg-row {{ $msg->sender_type === 'visitor' ? 'lc-msg-row-visitor' : '' }}" data-msg-id="{{ $msg->id }}">
                        <div class="lc-msg-bubble lc-msg-bubble-{{ $msg->sender_type === 'visitor' ? 'visitor' : ($msg->sender_type === 'agent' ? 'agent' : 'bot') }}">
                            @if($msg->content && $msg->source !== 'attachment'){{ $msg->content }}@endif
                            @foreach($msg->attachments as $att)
                                @if(str_starts_with((string) $att->mime_type, 'image/'))
                                    <a href="{{ route('chat.attachments.download', $att) }}" target="_blank" class="lc-att-link">
                                        <img src="{{ route('chat.attachments.download', $att) }}" alt="{{ $att->original_name }}" class="lc-att-img">
                                    </a>
                                @else
                                    <a href="{{ route('chat.attachments.download', $att) }}" class="lc-att-file" target="_blank">📎 {{ $att->original_name }}</a>
                                @endif
                            @endforeach
                            @if(in_array($msg->sender_type, ['agent', 'bot'], true) && $msg->reactions->isNotEmpty())
                                <div class="lc-reactions">
                                    @foreach($msg->reactions->groupBy('emoji') as $emoji => $group)
                                        <span class="lc-reaction-chip">{{ $emoji }} {{ $group->count() }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="lc-msg-time">
                                {{ $msg->created_at->format('H:i') }} · {{ $msg->source }}
                                @if($msg->sender_type === 'agent')
                                    @php $receipt = $msg->read_at ? 'read' : ($msg->delivered_at ? 'delivered' : 'sent'); @endphp
                                    <span class="lc-receipt lc-receipt-{{ $receipt }}" title="{{ ucfirst($receipt) }}">{{ $receipt === 'sent' ? '✓' : '✓✓' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="lc-msg-empty">No messages yet — say hello to start the conversation.</div>
                @endforelse
                </div>
            </div>

            <div class="lc-compose-dock">
            @if($isClosed)
                <footer class="lc-closed-banner">
                    <div class="lc-closed-banner-inner">
                        <span class="lc-closed-banner-icon" aria-hidden="true">✓</span>
                        <div class="lc-closed-banner-text">
                            <p class="lc-closed-banner-title">Conversation {{ $statusLabel }}</p>
                            <p class="lc-closed-banner-sub">
                                @if($canWrite)
                                    Reopen to reply, or export the transcript from the menu.
                                @else
                                    Read-only — export the transcript using Export in the header.
                                @endif
                            </p>
                        </div>
                        @if($canWrite)
                        <button type="button" class="lc-btn lc-btn-primary"
                            data-lc-post="{{ route('inbox.reopen', $conversation) }}"
                            data-lc-success="Chat reopened" data-lc-reload="1">Reopen</button>
                        @endif
                    </div>
                </footer>
            @elseif($canWrite)
                <footer class="lc-compose">
                    <form id="lc-reply-form" method="POST"
                        action="{{ route('inbox.reply', $conversation) }}"
                        data-reply-url="{{ route('inbox.reply', $conversation) }}"
                        data-meta-url="{{ route('inbox.meta', $conversation) }}"
                        data-upload-url="{{ route('inbox.attachments.upload', $conversation) }}">
                        @csrf
                        <div class="lc-compose-picker-wrap">
                            <div class="lc-compose-row">
                                <label class="lc-compose-tool" title="Attach file">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                                    <input type="file" id="lc-file-input" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,audio/*,video/*">
                                </label>
                                <button type="button" id="lc-emoji-btn" class="lc-compose-tool lc-emoji-btn" title="Emoji"
                                    data-emoji-trigger data-emoji-input="#lc-reply-input" data-emoji-panel="#lc-emoji-panel">😊</button>
                                @if($quickReplyCount > 0 || $canWrite)
                                <button type="button" id="lc-quick-replies-btn" class="lc-compose-tool lc-qr-trigger"
                                    title="Quick replies" aria-expanded="false" aria-controls="lc-quick-replies-popover">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    @if($quickReplyCount > 0)
                                        <span class="lc-qr-badge" id="lc-qr-badge">{{ $quickReplyCount }}</span>
                                    @endif
                                </button>
                                @endif
                                <input type="text" name="content" id="lc-reply-input" class="lc-compose-input"
                                    placeholder="Write a reply…  Ctrl+Enter to send" value="{{ $conversation->agent_draft }}">
                                <button type="submit" class="lc-btn lc-btn-send">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                                    Send
                                </button>
                            </div>
                            <div id="lc-emoji-panel"></div>
                            @if($quickReplyCount > 0 || $canWrite)
                            <div id="lc-quick-replies-popover" class="lc-qr-popover" hidden role="dialog" aria-label="Quick replies"
                                data-storage-tab="lc-qr-active-tab">
                                <header class="lc-qr-popover-head">
                                    <h4 class="lc-qr-popover-title">Quick replies</h4>
                                    <button type="button" class="lc-qr-popover-close" id="lc-qr-close" aria-label="Close">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/></svg>
                                    </button>
                                </header>
                                <div class="lc-qr-tabs" role="tablist">
                                    <button type="button" class="lc-qr-tab is-active" role="tab" aria-selected="true"
                                        data-qr-tab="mine" id="lc-qr-tab-mine">My <span class="lc-qr-tab-count" data-qr-count-mine>{{ $agentQuickReplies->count() }}</span></button>
                                    <button type="button" class="lc-qr-tab" role="tab" aria-selected="false"
                                        data-qr-tab="team" id="lc-qr-tab-team" @if(($canned ?? collect())->isEmpty()) disabled @endif>
                                        Team <span class="lc-qr-tab-count" data-qr-count-team>{{ ($canned ?? collect())->count() }}</span>
                                    </button>
                                </div>
                                <div class="lc-qr-search-wrap">
                                    <svg class="lc-qr-search-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                                    <input type="search" id="lc-qr-search" class="lc-qr-search" placeholder="Search replies…" autocomplete="off">
                                </div>
                                <div class="lc-qr-panels">
                                    <div class="lc-qr-panel is-active" data-qr-panel="mine" id="lc-qr-panel-mine" role="tabpanel">
                                        <div class="lc-qr-list" id="lc-qr-list-mine">
                                            @forelse($agentQuickReplies as $qr)
                                                <article class="lc-qr-card" data-qr-card data-qr-scope="mine"
                                                    data-quick-reply-id="{{ $qr->id }}"
                                                    data-canned-body="{{ e($qr->body) }}"
                                                    data-update-url="{{ route('inbox.quick-replies.update', $qr) }}"
                                                    data-delete-url="{{ route('inbox.quick-replies.destroy', $qr) }}">
                                                    <button type="button" class="lc-qr-card-main" data-qr-use>
                                                        <span class="lc-qr-card-title">{{ $qr->displayLabel() }}</span>
                                                        <span class="lc-qr-card-preview">{{ \Illuminate\Support\Str::limit($qr->body, 72) }}</span>
                                                    </button>
                                                    @if($canWrite)
                                                    <div class="lc-qr-card-actions">
                                                        <button type="button" class="lc-qr-card-act" data-qr-edit title="Edit" aria-label="Edit">
                                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path stroke-linecap="round" d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                        </button>
                                                        <button type="button" class="lc-qr-card-act lc-qr-card-act--danger" data-quick-reply-delete title="Delete" aria-label="Delete"
                                                            data-delete-url="{{ route('inbox.quick-replies.destroy', $qr) }}">
                                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                                                        </button>
                                                    </div>
                                                    <form class="lc-qr-card-edit" hidden data-qr-edit-form>
                                                        <input type="text" name="title" class="lc-qr-edit-title" maxlength="120"
                                                            value="{{ $qr->title }}" placeholder="Label (optional)">
                                                        <textarea name="body" class="lc-qr-edit-body" rows="3" maxlength="4000" required>{{ $qr->body }}</textarea>
                                                        <div class="lc-qr-edit-actions">
                                                            <button type="submit" class="lc-btn lc-btn-primary lc-btn-sm">Save</button>
                                                            <button type="button" class="lc-btn lc-btn-ghost lc-btn-sm" data-qr-edit-cancel>Cancel</button>
                                                        </div>
                                                    </form>
                                                    @endif
                                                </article>
                                            @empty
                                                <p class="lc-qr-empty" data-qr-empty-mine>No personal replies yet.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="lc-qr-panel" data-qr-panel="team" id="lc-qr-panel-team" role="tabpanel" hidden>
                                        <div class="lc-qr-list" id="lc-qr-list-team">
                                            @forelse(($canned ?? collect()) as $c)
                                                <article class="lc-qr-card lc-qr-card--team" data-qr-card data-qr-scope="team" data-canned-body="{{ e($c->body) }}">
                                                    <button type="button" class="lc-qr-card-main" data-qr-use>
                                                        <span class="lc-qr-card-title">{{ $c->title }}</span>
                                                        <span class="lc-qr-card-preview">{{ \Illuminate\Support\Str::limit($c->body, 72) }}</span>
                                                    </button>
                                                </article>
                                            @empty
                                                <p class="lc-qr-empty">No team canned responses for this site.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                @if($canWrite)
                                <footer class="lc-qr-foot">
                                    <button type="button" class="lc-qr-foot-btn" id="lc-qr-save-draft">Save current message</button>
                                    <button type="button" class="lc-qr-foot-btn lc-qr-foot-btn--primary" id="lc-qr-toggle-form" aria-expanded="false">New reply</button>
                                    <form id="lc-quick-reply-add" class="lc-qr-form" action="{{ route('inbox.quick-replies.store') }}" method="post" hidden>
                                        @csrf
                                        <input type="text" name="title" id="lc-qr-add-title" class="lc-qr-form-input" maxlength="120" placeholder="Label (optional)" autocomplete="off">
                                        <textarea name="body" id="lc-qr-add-body" class="lc-qr-form-input lc-qr-form-textarea" rows="3" maxlength="4000" placeholder="Reply text…" required></textarea>
                                        <button type="submit" class="lc-btn lc-btn-primary lc-btn-sm lc-qr-form-submit">Add to my replies</button>
                                    </form>
                                </footer>
                                @endif
                                <p class="lc-qr-hint">Click to append · Shift+click to replace · Esc to close</p>
                            </div>
                            @endif
                        </div>
                    </form>
                </footer>
            @else
                <footer class="lc-readonly-banner">
                    <p>Read-only access — you can view conversations but not reply.</p>
                </footer>
            @endif
            </div>
        </div>
    </div>

    {{-- Contact popup (hidden until opened) --}}
    <div id="lc-contact-popup" class="lc-contact-popup" hidden aria-hidden="true">
        <button type="button" id="lc-visitor-backdrop" class="lc-contact-popup-backdrop" aria-label="Close"></button>
        <div id="lc-visitor-sidebar" class="lc-contact-popup-panel" role="dialog" aria-labelledby="lc-contact-popup-title">
            <header class="lc-contact-popup-head">
                <div class="lc-contact-popup-head-main">
                    <span class="lc-contact-popup-avatar">{{ $visitorInitials }}</span>
                    <div>
                        <h3 id="lc-contact-popup-title" class="lc-contact-popup-title">{{ $conversation->visitor_name ?: 'Visitor' }}</h3>
                        <p class="lc-contact-popup-sub" id="lc-contact-popup-sub">{{ $contactSubtitle }}</p>
                    </div>
                </div>
                <button type="button" id="lc-visitor-close" class="lc-contact-popup-close" aria-label="Close">×</button>
            </header>
            <div class="lc-contact-popup-body">
                @include('dashboard.inbox.partials.visitor-panel', [
                    'conversation' => $conversation,
                    'agents' => $agents,
                    'departments' => $departments,
                ])
            </div>
        </div>
    </div>
</div>
