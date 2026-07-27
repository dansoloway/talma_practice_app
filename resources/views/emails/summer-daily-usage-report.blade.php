<x-mail::message>
# Summer Practice Pal — daily usage

Report for **{{ $date }}** ({{ $timezone }}).

| Metric | Count |
|:--|--:|
| Logins | {{ $logins }} |
| Lessons completed | {{ $lessonsCompleted }} |
| Voice recordings | {{ $voiceRecordings }} |

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
