<x-mail::message>
# Weekly chatbot report

Here's your **{{ $days }}-day** summary for **{{ $organization->name }}**.

Attached files:
- **CSV** — import into spreadsheets
- **Excel** — open directly in Microsoft Excel
@if(isset($hasPdf) && $hasPdf)
- **PDF** — printable summary
@endif

<x-mail::button :url="url('/reports')">
View full reports
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
