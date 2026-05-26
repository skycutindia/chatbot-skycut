<x-mail::message>
# Join {{ $organization->name }}

@if($inviter)
**{{ $inviter->name }}** invited you to join **{{ $organization->name }}** as **{{ $roleLabel }}**.
@else
You have been invited to join **{{ $organization->name }}** as **{{ $roleLabel }}**.
@endif

Sign in with **{{ $invite->email }}** using the link below. This invite expires {{ $expiresAt->diffForHumans() }}.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

If you did not expect this email, you can ignore it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
