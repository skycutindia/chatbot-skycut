<section class="dash-form-section">
    <h2 class="dash-form-section-title">Widget layout</h2>
    <div class="grid md:grid-cols-2 gap-4 mt-4">
        <div class="dash-field">
            <label class="dash-label" for="theme_mode">Theme</label>
            <select id="theme_mode" name="theme_mode" class="dash-select w-full">
                @foreach(['light','dark','auto'] as $t)
                    <option value="{{ $t }}" @selected($c->theme_mode === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div class="dash-field">
            <label class="dash-label" for="position">Widget position</label>
            <select id="position" name="position" class="dash-select w-full">
                <option value="right" @selected($c->position === 'right')>Bottom right</option>
                <option value="left" @selected($c->position === 'left')>Bottom left</option>
            </select>
        </div>
        <div class="dash-field">
            <label class="dash-label" for="widget_offset_bottom">Offset from bottom (px)</label>
            <input id="widget_offset_bottom" name="widget_offset_bottom" type="number" min="0" max="200"
                   value="{{ old('widget_offset_bottom', $security['widget_offset_bottom'] ?? 24) }}" class="dash-input w-full">
        </div>
        <div class="dash-field">
            <label class="dash-label" for="widget_offset_side">Offset from side (px)</label>
            <input id="widget_offset_side" name="widget_offset_side" type="number" min="0" max="200"
                   value="{{ old('widget_offset_side', $security['widget_offset_side'] ?? 24) }}" class="dash-input w-full">
        </div>
    </div>
    <div class="dash-field mt-4">
        <label class="dash-label" for="typing_indicator_text">Typing indicator text</label>
        <input id="typing_indicator_text" name="typing_indicator_text" value="{{ old('typing_indicator_text', $c->typing_indicator_text) }}" class="dash-input w-full">
    </div>
    <div class="dash-field mt-4">
        <label class="dash-label" for="offline_message">Offline message</label>
        <textarea id="offline_message" name="offline_message" rows="2" class="dash-textarea w-full">{{ old('offline_message', $c->offline_message) }}</textarea>
    </div>
    <div class="dash-field mt-4">
        <label class="dash-label" for="outside_hours_message">Outside hours message</label>
        <textarea id="outside_hours_message" name="outside_hours_message" rows="2" class="dash-textarea w-full">{{ old('outside_hours_message', $c->outside_hours_message) }}</textarea>
    </div>
</section>

<section class="dash-form-section">
    <h2 class="dash-form-section-title">Business hours</h2>
    <div class="dash-field mt-4 max-w-xs">
        <label class="dash-label" for="hours_timezone">Timezone</label>
        <select id="hours_timezone" name="hours_timezone" class="dash-select w-full">
            @foreach(['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Australia/Sydney'] as $tz)
                <option value="{{ $tz }}" @selected(old('hours_timezone', $hoursByDay->first()?->timezone ?? config('app.timezone')) === $tz)>{{ $tz }}</option>
            @endforeach
        </select>
    </div>
    <div class="dash-table-wrap mt-4">
        <table class="dash-table text-sm">
            <thead>
                <tr><th>Day</th><th>Closed</th><th>Opens</th><th>Closes</th></tr>
            </thead>
            <tbody>
                @foreach(range(0, 6) as $day)
                    @php
                        $hour = $hoursByDay->get($day);
                        $opens = $hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '09:00';
                        $closes = $hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '17:00';
                    @endphp
                    <tr>
                        <td>{{ $dayNames[$day] }}</td>
                        <td>
                            <input type="hidden" name="hours[{{ $day }}][is_closed]" value="0">
                            <input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1" class="dash-checkbox" @checked(old("hours.{$day}.is_closed", $hour?->is_closed))>
                        </td>
                        <td><input type="time" name="hours[{{ $day }}][opens_at]" value="{{ old("hours.{$day}.opens_at", $opens) }}" class="dash-input"></td>
                        <td><input type="time" name="hours[{{ $day }}][closes_at]" value="{{ old("hours.{$day}.closes_at", $closes) }}" class="dash-input"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="dash-form-section">
    <h2 class="dash-form-section-title">WhatsApp &amp; email buttons</h2>
    <div class="grid md:grid-cols-2 gap-4 mt-4">
        <div class="space-y-3">
            <label class="dash-checkbox-row">
                <input type="checkbox" name="whatsapp_enabled" value="1" @checked($channels['whatsapp']['enabled'] ?? false)>
                Enable WhatsApp
            </label>
            <input id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $channels['whatsapp']['number'] ?? '') }}" class="dash-input w-full" placeholder="WhatsApp number">
            <input id="whatsapp_message" name="whatsapp_message" value="{{ old('whatsapp_message', $channels['whatsapp']['message'] ?? '') }}" class="dash-input w-full">
        </div>
        <div class="space-y-3">
            <label class="dash-checkbox-row">
                <input type="checkbox" name="email_channel_enabled" value="1" @checked($channels['email']['enabled'] ?? false)>
                Enable email
            </label>
            <input id="support_email" name="support_email" type="email" value="{{ old('support_email', $channels['email']['address'] ?? '') }}" class="dash-input w-full">
            <input id="email_subject" name="email_subject" value="{{ old('email_subject', $channels['email']['subject'] ?? '') }}" class="dash-input w-full">
        </div>
    </div>
</section>

<section class="dash-form-section">
    <h2 class="dash-form-section-title">AI configuration</h2>
    <label class="dash-checkbox-row mt-4">
        <input type="checkbox" name="ai_enabled" value="1" @checked($c->ai_enabled)>
        Enable AI
    </label>
    <div class="grid md:grid-cols-3 gap-4 mt-4">
        <div class="dash-field">
            <label class="dash-label" for="ai_model">Model</label>
            <input id="ai_model" name="ai_model" value="{{ old('ai_model', $c->ai_model) }}" class="dash-input w-full">
        </div>
        <div class="dash-field">
            <label class="dash-label" for="ai_temperature">Temperature</label>
            <input id="ai_temperature" name="ai_temperature" type="number" step="0.01" min="0" max="2" value="{{ old('ai_temperature', $c->ai_temperature) }}" class="dash-input w-full">
        </div>
        <div class="dash-field">
            <label class="dash-label" for="confidence_threshold">Confidence</label>
            <input id="confidence_threshold" name="confidence_threshold" type="number" step="0.01" min="0" max="1" value="{{ old('confidence_threshold', $c->confidence_threshold) }}" class="dash-input w-full">
        </div>
    </div>
    <div class="dash-field mt-4">
        <label class="dash-label" for="system_prompt">System prompt</label>
        <textarea id="system_prompt" name="system_prompt" rows="4" class="dash-textarea w-full">{{ old('system_prompt', $c->system_prompt) }}</textarea>
    </div>
    <input type="hidden" name="ai_tone" value="{{ $c->ai_tone }}">
</section>

<section class="dash-form-section">
    <h2 class="dash-form-section-title">Modules &amp; security</h2>
    <label class="dash-checkbox-row mt-4">
        <input type="checkbox" name="sound_enabled" value="1" @checked(old('sound_enabled', $c->sound_enabled ?? true))>
        Notification sounds
    </label>
    <div class="space-y-2 mt-4">
        @foreach($modules as $key => $enabled)
            <label class="dash-checkbox-row">
                <input type="checkbox" name="modules[{{ $key }}]" value="1" @checked($enabled)>
                {{ str_replace('_', ' ', ucfirst($key)) }}
            </label>
        @endforeach
    </div>
    <div class="grid md:grid-cols-2 gap-4 mt-4">
        <input id="rate_limit_per_minute" name="rate_limit_per_minute" type="number" value="{{ $c->rate_limit_per_minute }}" class="dash-input w-full">
        <input id="rate_limit_per_hour" name="rate_limit_per_hour" type="number" value="{{ $c->rate_limit_per_hour }}" class="dash-input w-full">
    </div>
    <label class="dash-checkbox-row mt-4">
        <input type="checkbox" name="require_domain_validation" value="1" @checked($c->require_domain_validation)>
        Require allowed domains
    </label>
</section>

<section class="dash-form-section">
    <h2 class="dash-form-section-title">Custom code</h2>
    <div class="grid lg:grid-cols-2 gap-4 mt-4">
        <textarea id="custom_css" name="custom_css" rows="6" class="dash-textarea w-full font-mono text-sm">{{ old('custom_css', $c->custom_css) }}</textarea>
        <textarea id="custom_js" name="custom_js" rows="6" class="dash-textarea w-full font-mono text-sm">{{ old('custom_js', $c->custom_js) }}</textarea>
    </div>
</section>
