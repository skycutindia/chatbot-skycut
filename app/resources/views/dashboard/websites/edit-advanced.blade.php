@extends('layouts.websites-workspace')

@section('title', 'Advanced — '.$website->name)

@section('ws-tab', 'advanced')
@section('ws-title', 'Advanced settings')
@section('ws-subtitle', 'Widget layout, hours, channels, AI model, and custom code.')

@section('workspace')
@php
    $c = $website->configuration;
    $modules = $c->modules();
    $channels = $c->widgetChannels();
    $security = $c->security_settings ?? [];
    $hoursByDay = $website->operatingHours->keyBy('day_of_week');
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
@endphp
    <form method="POST" action="{{ route('websites.update', $website) }}" class="ws-settings-form ws-stack">
        @csrf @method('PUT')
        <input type="hidden" name="name" value="{{ $website->name }}">
        <input type="hidden" name="bot_name" value="{{ $c->bot_name }}">
        <input type="hidden" name="primary_color" value="{{ $c->primary_color }}">
        <input type="hidden" name="secondary_color" value="{{ $c->secondary_color }}">
        <input type="hidden" name="locale" value="{{ $c->locale }}">
        <input type="hidden" name="is_active" value="{{ $website->is_active ? '1' : '0' }}">

        @include('dashboard.websites.partials.edit-advanced-fields')

        <div class="ws-settings-savebar">
            <a href="{{ route('websites.edit.bot', $website) }}" class="ws-btn-ghost">← Bot settings</a>
            <button type="submit" class="ws-btn-primary">Save advanced settings</button>
        </div>
    </form>
@endsection
