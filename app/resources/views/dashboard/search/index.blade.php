@extends('layouts.app')

@section('title', 'Search')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Search</p>
        <h1 class="dash-page-title">Search</h1>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    <form method="GET" action="{{ route('search') }}" class="dash-filter-bar mt-4">
        <input name="q" value="{{ $query }}" placeholder="Search..." class="dash-input flex-1">
        <button type="submit" class="dash-btn-primary">Search</button>
    </form>

    <ul class="dash-list mt-8">
        @forelse($results as $result)
            <li>
                <a href="{{ $result['url'] }}" class="dash-list-item dash-card-hover">
                    <div>
                        <p class="font-medium">{{ $result['title'] }}</p>
                        <p class="text-sm dash-muted mt-1">{{ $result['subtitle'] }} · {{ $result['type'] }}</p>
                    </div>
                </a>
            </li>
        @empty
            <li class="dash-empty">
                @if(strlen($query) >= 2)
                    No results for “{{ $query }}”.
                @else
                    Type at least 2 characters to search.
                @endif
            </li>
        @endforelse
    </ul>
</div>
@endsection
