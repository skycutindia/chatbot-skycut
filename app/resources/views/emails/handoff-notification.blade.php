<x-mail::message>
# Live chat awaiting agent

A visitor on **{{ $website->name }}** needs help from a live agent.

**Visitor:** {{ $conversation->visitor_name ?: 'Anonymous' }}  
**Reason:** {{ str_replace('_', ' ', $reason) }}  
**Status:** {{ $conversation->status }}

@if($conversation->messages->isNotEmpty())
**Recent messages:**
@foreach($conversation->messages->reverse() as $message)
- **{{ ucfirst($message->sender_type) }}:** {{ \Illuminate\Support\Str::limit($message->content, 120) }}
@endforeach
@endif

<x-mail::button :url="$chatUrl">
Open conversation
</x-mail::button>

<x-mail::button :url="$inboxUrl">
Live inbox
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
