{{-- Usage: @include('layouts.partials.page-header', ['eyebrow' => 'Live chat', 'title' => 'Inbox']) --}}
<div class="dash-page-header">
    <div>
        @if(!empty($eyebrow))
            <p class="dash-page-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="dash-page-title">{{ $title }}</h1>
    </div>
</div>
