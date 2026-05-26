@php
    $canWrite = auth()->user()->roleEnum()->canHandleLiveChat();
    $hasLead = (bool) $conversation->lead;
@endphp

<div class="lc-contact-panel">
    {{-- Contact details --}}
    <section class="lc-contact-block">
        <h4 class="lc-contact-block-title">Contact info</h4>
        <p class="lc-contact-hint">Synced from the chatbot pre-chat form. Name and phone are required.</p>
        @if($canWrite)
            <form method="POST" action="{{ route('inbox.contact', $conversation) }}" class="lc-contact-form" id="lc-contact-form" data-lc-ajax-form data-lc-success="Contact saved">
                @csrf
                <label class="lc-field">
                    <span class="lc-field-label">Name <span class="lc-field-req">*</span></span>
                    <input type="text" name="visitor_name" class="lc-field-input" value="{{ $conversation->visitor_name }}" placeholder="Full name" required>
                </label>
                <label class="lc-field">
                    <span class="lc-field-label">Phone <span class="lc-field-req">*</span></span>
                    <input type="tel" name="visitor_phone" class="lc-field-input" value="{{ $conversation->visitor_phone }}" placeholder="+1 555 000 0000" required>
                </label>
                <label class="lc-field">
                    <span class="lc-field-label">Email <span class="lc-field-opt">(optional)</span></span>
                    <input type="email" name="visitor_email" class="lc-field-input" value="{{ $conversation->visitor_email }}" placeholder="email@example.com">
                </label>
                <label class="lc-field">
                    <span class="lc-field-label">Company <span class="lc-field-opt">(optional)</span></span>
                    <input type="text" name="visitor_company" class="lc-field-input" value="{{ $conversation->visitor_company }}" placeholder="Company name">
                </label>
                <button type="submit" class="lc-btn lc-btn-secondary lc-btn-block">Save contact</button>
            </form>
        @else
            <dl class="lc-contact-readonly">
                <div><dt>Name</dt><dd>{{ $conversation->visitor_name ?: '—' }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $conversation->visitor_phone ?: '—' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $conversation->visitor_email ?: '—' }}</dd></div>
                <div><dt>Company</dt><dd>{{ $conversation->visitor_company ?: '—' }}</dd></div>
            </dl>
        @endif
    </section>

    {{-- Lead section --}}
    <section class="lc-contact-block lc-lead-block">
        <h4 class="lc-contact-block-title">Lead</h4>
        <div id="lc-lead-section" data-has-lead="{{ $hasLead ? '1' : '0' }}">
            @if($conversation->lead)
                <div class="lc-lead-card is-saved">
                    <div class="lc-lead-card-icon" aria-hidden="true">✓</div>
                    <div class="lc-lead-card-body">
                        <p class="lc-lead-card-title">Saved to CRM</p>
                        <p class="lc-lead-card-name">{{ $conversation->lead->name ?: 'Visitor' }}</p>
                        @if($conversation->lead->email)
                            <p class="lc-lead-card-meta">{{ $conversation->lead->email }}</p>
                        @endif
                        @if($conversation->lead->phone)
                            <p class="lc-lead-card-meta">{{ $conversation->lead->phone }}</p>
                        @endif
                        <span class="lc-lead-status">{{ ucfirst($conversation->lead->status) }}</span>
                    </div>
                    <a href="{{ route('leads.show', $conversation->lead) }}" class="lc-btn lc-btn-primary lc-btn-sm lc-btn-block">View in CRM</a>
                </div>
            @else
                <div class="lc-lead-empty">
                    <p class="lc-lead-empty-text">Save this visitor as a lead to track them in your CRM.</p>
                    @if($canWrite)
                        <button type="button" class="lc-btn lc-btn-primary lc-btn-block" id="lc-save-lead-btn"
                            data-lc-post="{{ route('inbox.save-lead', $conversation) }}"
                            data-lc-success="Lead saved to CRM"
                            data-lc-lead-save="1">
                            Save as lead
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- Quick session info --}}
    <section class="lc-contact-block lc-contact-block-muted">
        <h4 class="lc-contact-block-title">Session</h4>
        <dl class="lc-contact-readonly lc-contact-readonly-compact">
            <div><dt>Website</dt><dd>{{ $conversation->website?->name }}</dd></div>
            <div><dt>Channel</dt><dd>{{ $conversation->channel === 'whatsapp' ? 'WhatsApp' : 'Web widget' }}</dd></div>
            <div><dt>Status</dt><dd>{{ str_replace('_', ' ', $conversation->status) }}</dd></div>
            <div><dt>Assigned</dt><dd>{{ $conversation->assignedUser?->name ?: 'Unassigned' }}</dd></div>
            @if($conversation->rating)
                <div><dt>CSAT</dt><dd>
                    <span class="lc-rating-score">{{ $conversation->rating->score }}/5</span>
                    @if($conversation->rating->comment)
                        <p class="lc-rating-comment">{{ $conversation->rating->comment }}</p>
                    @endif
                </dd></div>
            @endif
        </dl>
    </section>

    @if($canWrite && ($agents->isNotEmpty() || ($departments ?? collect())->isNotEmpty()))
        <details class="lc-contact-more">
            <summary class="lc-contact-more-toggle">Team & notes</summary>
            <div class="lc-contact-more-body">
                @if($agents->isNotEmpty())
                    <form method="POST" action="{{ route('inbox.transfer', $conversation) }}" class="lc-drawer-form" data-lc-ajax-form data-lc-success="Transferred">
                        @csrf
                        <label class="lc-field">
                            <span class="lc-field-label">Transfer to</span>
                            <select name="user_id" class="lc-select" required>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected($conversation->assigned_user_id === $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="lc-btn lc-btn-secondary lc-btn-block">Transfer</button>
                    </form>
                @endif

                @if(($departments ?? collect())->isNotEmpty())
                    <form method="POST" action="{{ route('inbox.meta', $conversation) }}" class="lc-drawer-form" data-lc-ajax-form data-lc-success="Department updated">
                        @csrf
                        <label class="lc-field">
                            <span class="lc-field-label">Department</span>
                            <select name="department_id" class="lc-select">
                                <option value="">Unassigned</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($conversation->department_id === $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="lc-btn lc-btn-secondary lc-btn-block">Save department</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('inbox.notes.store', $conversation) }}" class="lc-drawer-form" data-lc-ajax-form data-lc-success="Note added">
                    @csrf
                    <label class="lc-field">
                        <span class="lc-field-label">Internal note</span>
                        <div class="lc-mention-wrap">
                            <textarea name="body" rows="2" class="lc-textarea lc-mention-input" placeholder="Private note…" required
                                data-mention-search-url="{{ route('inbox.mentions.search') }}"></textarea>
                        </div>
                    </label>
                    <button type="submit" class="lc-btn lc-btn-secondary lc-btn-block">Add note</button>
                </form>
                <div id="lc-notes-feed" class="lc-notes-feed lc-notes-feed-compact">
                    @forelse($conversation->internalNotes->take(3) as $note)
                        <article class="lc-note-card">
                            <p class="lc-note-author">{{ $note->user?->name }}</p>
                            <div class="lc-note-body">{!! app(\App\Services\ChatMentionService::class)->formatBodyHtml($note->body) !!}</div>
                        </article>
                    @empty
                        <p class="lc-notes-empty">No notes yet.</p>
                    @endforelse
                </div>
            </div>
        </details>
    @endif
</div>
