@extends('layouts.app')

@section('title', 'Organization settings')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Settings</p>
        <h1 class="dash-page-title">Organization settings</h1>
        <p class="dash-page-sub">Branding and defaults for your workspace</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    <form method="POST" action="{{ route('settings.organization.update') }}" class="dash-card mt-8">
        <div class="dash-card-body space-y-6">
            @csrf
            @method('PUT')

            <div class="dash-field">
                <label class="dash-label" for="name">Organization name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $organization->name) }}" required class="dash-input w-full">
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-field">
                <label class="dash-label" for="logo_url">Logo URL</label>
                <input type="url" id="logo_url" name="logo_url" value="{{ old('logo_url', $organization->logo_url) }}" placeholder="https://example.com/logo.png" class="dash-input w-full">
                @error('logo_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-field">
                <label class="dash-label" for="timezone">Timezone</label>
                <select id="timezone" name="timezone" class="dash-select w-full">
                    @foreach(['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Australia/Sydney'] as $tz)
                        <option value="{{ $tz }}" @selected(old('timezone', $organization->timezone) === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-field">
                <label class="dash-label" for="notification_email">Notification email</label>
                <input type="email" id="notification_email" name="notification_email" value="{{ old('notification_email', $organization->settings['notification_email'] ?? '') }}"
                       placeholder="alerts@yourcompany.com" class="dash-input w-full">
                <p class="text-xs dash-muted mt-1">Used for handoff alerts and weekly report emails.</p>
                @error('notification_email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-form-section-title mt-4">Slack & Microsoft Teams</div>
            <p class="text-sm dash-muted">Incoming webhook URLs for live chat alerts. Create a webhook in Slack or Teams, then paste the URL below.</p>

            <div class="dash-field">
                <label class="dash-label" for="slack_webhook_url">Slack incoming webhook URL</label>
                <input type="url" id="slack_webhook_url" name="slack_webhook_url"
                       value="{{ old('slack_webhook_url', $organization->settings['slack_webhook_url'] ?? '') }}"
                       placeholder="https://hooks.slack.com/services/..." class="dash-input w-full">
                @error('slack_webhook_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                <div class="flex flex-wrap gap-4 mt-2 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_slack_handoff" value="1" @checked(old('notify_slack_handoff', $organization->settings['notify_slack_handoff'] ?? true))>
                        Handoff to human
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_slack_new_message" value="1" @checked(old('notify_slack_new_message', $organization->settings['notify_slack_new_message'] ?? false))>
                        New visitor message in queue
                    </label>
                </div>
            </div>

            <div class="dash-field">
                <label class="dash-label" for="teams_webhook_url">Microsoft Teams webhook URL</label>
                <input type="url" id="teams_webhook_url" name="teams_webhook_url"
                       value="{{ old('teams_webhook_url', $organization->settings['teams_webhook_url'] ?? '') }}"
                       placeholder="https://outlook.office.com/webhook/..." class="dash-input w-full">
                @error('teams_webhook_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                <div class="flex flex-wrap gap-4 mt-2 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_teams_handoff" value="1" @checked(old('notify_teams_handoff', $organization->settings['notify_teams_handoff'] ?? true))>
                        Handoff to human
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_teams_new_message" value="1" @checked(old('notify_teams_new_message', $organization->settings['notify_teams_new_message'] ?? false))>
                        New visitor message in queue
                    </label>
                </div>
            </div>

            <button type="submit" class="dash-btn-primary">Save settings</button>
        </div>
    </form>

    <section class="dash-card mt-8">
        <div class="dash-card-body space-y-6">
            <div>
                <h2 class="dash-form-section-title">Team invites</h2>
                <p class="text-sm dash-muted mt-2">Invite teammates by email. They accept via a secure link (valid 7 days). You can also <a href="{{ route('team.create') }}" class="dash-link">add a member manually</a> with a password.</p>
            </div>

            <form method="POST" action="{{ route('settings.organization.invites.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="dash-field flex-1 min-w-[200px]">
                    <label class="dash-label" for="invite_email">Email</label>
                    <input type="email" id="invite_email" name="email" value="{{ old('email') }}" required class="dash-input w-full" placeholder="agent@company.com">
                    @error('invite_email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="dash-field min-w-[140px]">
                    <label class="dash-label" for="invite_role">Role</label>
                    <select id="invite_role" name="role" class="dash-select w-full" required>
                        @foreach($inviteRoles as $role)
                            <option value="{{ $role }}" @selected(old('role') === $role)>{{ \App\Enums\UserRole::from($role)->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="dash-btn-secondary">Send invite</button>
            </form>

            @if($pendingInvites->isNotEmpty())
                <ul class="dash-list">
                    @foreach($pendingInvites as $invite)
                        <li class="dash-list-item text-sm flex flex-wrap items-center justify-between gap-2">
                            <span>
                                <strong>{{ $invite->email }}</strong>
                                <span class="dash-muted">· {{ \App\Enums\UserRole::from($invite->role)->label() }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <input type="text" readonly value="{{ route('team.invite.show', $invite->token) }}" class="dash-input text-xs max-w-[280px]" onclick="this.select()">
                                <form method="POST" action="{{ route('settings.organization.invites.destroy', $invite) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dash-btn-danger dash-btn-sm">Cancel</button>
                                </form>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="dash-card mt-8">
        <div class="dash-card-body">
            <h2 class="dash-form-section-title">Role permissions</h2>
            <p class="text-sm dash-muted mt-2">What each role can do in this workspace (enforced by route middleware). Permissions are fixed per role; contact your platform admin for custom overrides.</p>
            <div class="dash-table-wrap mt-4">
                <table class="dash-table text-sm">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Websites</th>
                            <th>Inbox</th>
                            <th>Settings</th>
                            <th>Analytics</th>
                            <th>Read-only</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roleMatrix as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['websites'] ? 'Yes' : '—' }}</td>
                                <td>{{ $row['inbox'] ? 'Yes' : '—' }}</td>
                                <td>{{ $row['settings'] ? 'Yes' : '—' }}</td>
                                <td>{{ $row['analytics'] ? 'Yes' : '—' }}</td>
                                <td>{{ $row['read_only'] ? 'Yes' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
