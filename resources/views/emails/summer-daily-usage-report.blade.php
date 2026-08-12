<x-mail::message>
# TALMA Summer — daily usage

Report for **{{ $date }}** ({{ $timezone }}).

| Metric | Count |
|:--|--:|
| Logins | {{ $logins }} |
| Lessons completed | {{ $lessonsCompleted }} |
| Voice recordings | {{ $voiceRecordings }} |

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
