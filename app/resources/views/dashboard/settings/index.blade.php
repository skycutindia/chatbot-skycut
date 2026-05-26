@extends('layouts.app')

@section('title', 'Settings')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Workspace</p>
        <h1 class="dash-page-title">Settings</h1>
        <p class="dash-page-sub">ChatGPT API, dashboard modules, and workspace preferences</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow ws-settings-hub" data-settings-hub>
    <nav class="ws-training-tabs mb-6" role="tablist">
        <a href="{{ route('settings.index', ['tab' => 'ai']) }}" class="ws-training-tab {{ $tab === 'ai' ? 'is-active' : '' }}">AI &amp; ChatGPT</a>
        <a href="{{ route('settings.index', ['tab' => 'dashboard']) }}" class="ws-training-tab {{ $tab === 'dashboard' ? 'is-active' : '' }}">Dashboard</a>
        <a href="{{ route('settings.index', ['tab' => 'links']) }}" class="ws-training-tab {{ $tab === 'links' ? 'is-active' : '' }}">More</a>
    </nav>

    @if($tab === 'ai')
        <div class="dash-card mb-6">
            <div class="dash-card-body">
                <div class="cf-split flex-wrap gap-3 items-start">
                    <div>
                        <h2 class="dash-form-section-title">Connection status</h2>
                        <p class="text-sm dash-muted mt-1">
                            @if($aiConfigured)
                                <span class="dash-badge is-success">Configured</span>
                                Active key: <code>{{ $aiKeyMask }}</code> ({{ $aiKeySource }})
                            @else
                                <span class="dash-badge is-warn">Not configured</span>
                                Add an API key below to enable AI replies and URL training.
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('settings.ai.test') }}" class="cf-cluster">
                        @csrf
                        <button type="submit" class="dash-btn-secondary dash-btn-sm">Test connection</button>
                        @if($isSuperAdmin)
                            <button type="submit" name="platform" value="1" class="dash-btn-ghost dash-btn-sm">Test platform key</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        @unless($aiConfigured)
        <div class="dash-alert dash-alert-warning mb-6" role="status">
            <p class="text-sm"><strong>OpenAI is not configured.</strong> Paste your API key below and save, or set <code>OPENAI_API_KEY</code> in <code>.env</code> and refresh.</p>
        </div>
        @endunless

        <form method="POST" action="{{ route('settings.ai.update') }}" class="dash-card mb-6">
            <div class="dash-card-header"><h3 class="font-semibold">Workspace OpenAI API key</h3></div>
            <div class="dash-card-body space-y-4">
                @csrf @method('PUT')
                <p class="text-sm dash-muted">Required for AI replies, URL training, and embeddings. Saving a key enables it for this workspace automatically.</p>
                @if($platformKeySet && ! $useOrgKey)
                <label class="dash-checkbox-row">
                    <input type="checkbox" name="use_org_openai_key" value="1" @checked($useOrgKey)>
                    Use this workspace key instead of the platform default
                </label>
                @else
                <input type="hidden" name="use_org_openai_key" value="1">
                @endif
                <div class="dash-field">
                    <label class="dash-label" for="org_openai_api_key">OpenAI API key</label>
                    <input id="org_openai_api_key" type="password" name="openai_api_key" autocomplete="off"
                        placeholder="{{ $hasOrgKey ? 'Leave blank to keep current key' : 'sk-…' }}" class="dash-input w-full font-mono text-sm">
                </div>
                <div class="dash-field">
                    <label class="dash-label" for="org_openai_model">Default model (this org)</label>
                    <input id="org_openai_model" name="openai_default_model" value="{{ old('openai_default_model', $openaiModel) }}"
                        class="dash-input w-full" placeholder="{{ $platformModel }}">
                </div>
                @if($hasOrgKey)
                    <label class="dash-checkbox-row">
                        <input type="checkbox" name="clear_org_key" value="1">
                        Remove stored organization key
                    </label>
                @endif
                <button type="submit" class="dash-btn-primary">Save organization AI settings</button>
            </div>
        </form>

        @if($isSuperAdmin)
        <form method="POST" action="{{ route('settings.platform-ai.update') }}" class="dash-card border-2 border-[var(--dash-accent)]">
            <div class="dash-card-header">
                <h3 class="font-semibold">Platform AI (super admin)</h3>
                <span class="dash-badge">Global default</span>
            </div>
            <div class="dash-card-body space-y-4">
                @csrf @method('PUT')
                <div class="dash-field">
                    <label class="dash-label" for="platform_openai_api_key">Platform OpenAI API key</label>
                    <input id="platform_openai_api_key" type="password" name="openai_api_key" autocomplete="off"
                        placeholder="{{ $platformKeySet ? 'Leave blank to keep current' : 'sk-…' }}" class="dash-input w-full font-mono text-sm">
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="dash-field">
                        <label class="dash-label" for="platform_model">Default model</label>
                        <input id="platform_model" name="openai_default_model" value="{{ old('openai_default_model', $platformModel) }}" class="dash-input w-full">
                    </div>
                    <div class="dash-field">
                        <label class="dash-label" for="platform_base_url">API base URL</label>
                        <input id="platform_base_url" type="url" name="openai_base_url" value="{{ old('openai_base_url', $platformBaseUrl) }}" class="dash-input w-full">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="dash-field">
                        <label class="dash-label" for="training_max_qa_pairs">Max Q&amp;A pairs per URL</label>
                        <input id="training_max_qa_pairs" type="number" name="training_max_qa_pairs" min="3" max="15"
                            value="{{ old('training_max_qa_pairs', $maxPairs) }}" class="dash-input w-full">
                    </div>
                    <label class="dash-checkbox-row sm:col-span-2 sm:mt-8">
                        <input type="checkbox" name="semantic_search_enabled" value="1" @checked($semanticEnabled)>
                        Enable semantic knowledge search (embeddings)
                    </label>
                </div>
                <label class="dash-checkbox-row">
                    <input type="checkbox" name="clear_platform_key" value="1">
                    Clear platform API key
                </label>
                <button type="submit" class="dash-btn-primary">Save platform AI settings</button>
            </div>
        </form>
        @endif
    @endif

    @if($tab === 'dashboard')
        <form method="POST" action="{{ route('settings.dashboard-modules.update') }}" class="dash-card">
            <div class="dash-card-header"><h3 class="font-semibold">Dashboard modules</h3></div>
            <div class="dash-card-body space-y-3">
                @csrf @method('PUT')
                <p class="text-sm dash-muted mb-4">Show or hide main sections in the sidebar for everyone in this organization.</p>
                @foreach([
                    'websites' => 'Websites & bots',
                    'inbox' => 'Inbox (live chat)',
                    'leads' => 'Leads CRM',
                    'reports' => 'Reports',
                    'team' => 'Team management',
                    'training' => 'Bot training hub',
                ] as $key => $label)
                    <label class="dash-checkbox-row">
                        <input type="checkbox" name="modules[{{ $key }}]" value="1" @checked($dashboardModules[$key] ?? true)>
                        {{ $label }}
                    </label>
                @endforeach
                <button type="submit" class="dash-btn-primary mt-4">Save dashboard layout</button>
            </div>
        </form>
    @endif

    @if($tab === 'links')
        <div class="grid sm:grid-cols-2 gap-4">
            <a href="{{ route('settings.profile.edit') }}" class="dash-card hover:border-[var(--dash-accent)] transition-colors">
                <div class="dash-card-body">
                    <h3 class="font-semibold">My profile</h3>
                    <p class="text-sm dash-muted mt-1">Account, password, 2FA</p>
                </div>
            </a>
            @if($isSuperAdmin)
            <a href="{{ route('admin.settings.edit') }}" class="dash-card hover:border-[var(--dash-accent)] transition-colors">
                <div class="dash-card-body">
                    <h3 class="font-semibold">Legacy platform admin</h3>
                    <p class="text-sm dash-muted mt-1">Maintenance mode &amp; platform name</p>
                </div>
            </a>
            @endif
        </div>
    @endif
</div>
@endsection
