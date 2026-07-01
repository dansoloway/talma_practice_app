@extends('layouts.admin')

@section('title', 'Voice Dashboard')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Voice &amp; Pronunciation Dashboard</h1>
        <p class="text-gray-600 mt-2">
            Browse anonymized learner recordings and pronunciation-check progress.
            Audio files are stored in {{ strtoupper($storageDriver) }}@if($storageBucket) (bucket: <code class="text-sm bg-gray-100 px-1 rounded">{{ $storageBucket }}</code>)@endif;
            this page is the admin UI — the AWS S3 console only shows raw files.
        </p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="card">
            <div class="card-body py-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Total recordings</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_samples']) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Last 7 days</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['samples_last_7_days']) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Pronunciation check audio</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['pronunciation_check_samples']) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Manual record audio</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['manual_record_samples']) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Pronunciation attempts</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['pronunciation_attempts']) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Words passed</p>
                <p class="text-2xl font-bold text-green-700 mt-1">{{ number_format($stats['pronunciation_passes']) }}</p>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.voice-samples.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                    <select id="organization_id" name="organization_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All organizations</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" {{ (string) ($filters['organization_id'] ?? '') === (string) $organization->id ? 'selected' : '' }}>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Target text</label>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search prompt text..." class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label for="recording_source" class="block text-sm font-medium text-gray-700 mb-1">Recording source</label>
                    <select id="recording_source" name="recording_source" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All sources</option>
                        <option value="pronunciation_check" {{ ($filters['recording_source'] ?? '') === 'pronunciation_check' ? 'selected' : '' }}>Pronunciation check</option>
                        <option value="manual_record" {{ ($filters['recording_source'] ?? '') === 'manual_record' ? 'selected' : '' }}>Manual record</option>
                    </select>
                </div>

                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select id="gender" name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All</option>
                        @foreach($genders as $value => $labels)
                            <option value="{{ $value }}" {{ ($filters['gender'] ?? '') === $value ? 'selected' : '' }}>
                                {{ \App\Models\ParentStudent::optionLabel($labels) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="native_language" class="block text-sm font-medium text-gray-700 mb-1">Native language</label>
                    <select id="native_language" name="native_language" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All</option>
                        @foreach($nativeLanguages as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['native_language'] ?? '') === $value ? 'selected' : '' }}>
                                {{ \App\Models\ParentStudent::optionLabel($label) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From date</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To date</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>

                <div class="md:col-span-2 lg:col-span-3 flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Apply filters</button>
                    <a href="{{ route('admin.voice-samples.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Audio recordings</h2>
            <p class="text-sm text-gray-500 mt-1">Anonymized voice samples saved when learners record or complete a pronunciation check and tap Next word.</p>
        </div>
        <div class="card-body p-0 overflow-x-auto">
            @if($samples->isEmpty())
                <p class="p-6 text-gray-600">No voice samples match your filters.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Recorded</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Organization</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Lesson</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Target text</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Source</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Profile</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Duration</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Audio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($samples as $sample)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ $sample->recorded_at?->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $sample->organization?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $sample->lesson?->title ?? '—' }}
                                    @if($sample->vocabulary)
                                        <div class="text-xs text-gray-500 mt-0.5">Vocab: {{ $sample->vocabulary->english_word }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900 font-medium max-w-xs">
                                    {{ $sample->target_text }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    @if($sample->recording_source === 'pronunciation_check')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Pronunciation check</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Manual record</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    Age {{ $sample->age }},
                                    {{ \App\Models\ParentStudent::optionLabel($genders[$sample->gender] ?? $sample->gender) }},
                                    {{ \App\Models\ParentStudent::optionLabel($nativeLanguages[$sample->native_language] ?? $sample->native_language) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    @if($sample->duration_ms)
                                        {{ number_format($sample->duration_ms / 1000, 1) }}s
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 min-w-[220px]">
                                    <audio controls preload="none" class="w-full max-w-xs h-8">
                                        <source src="{{ route('admin.voice-samples.audio', $sample) }}" type="{{ app(\App\Services\VoiceSamplePlayback::class)->contentType($sample) }}">
                                    </audio>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4 border-t border-gray-200">
                    {{ $samples->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Pronunciation progress</h2>
            <p class="text-sm text-gray-500 mt-1">Logged when learners tap Next word after a guided vocabulary pronunciation check (pass/fail, no student name).</p>
        </div>
        <div class="card-body p-0 overflow-x-auto">
            @if($pronunciationEvents->isEmpty())
                <p class="p-6 text-gray-600">No pronunciation-check progress events yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">When</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Lesson</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Word</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Result</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Heard</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Audio saved</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Skipped</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($pronunciationEvents as $event)
                            @php
                                $meta = $event->meta ?? [];
                                $passed = ($meta['pronunciation_pass'] ?? false) === true;
                            @endphp
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ $event->created_at?->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $event->lesson?->title ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-900 font-medium">
                                    {{ $event->vocabulary?->english_word ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($passed)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Passed</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Not passed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs">
                                    {{ $meta['heard'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if(! empty($meta['audio_uploaded']))
                                        <span class="text-green-700">Uploaded</span>
                                    @elseif(! empty($meta['audio_captured']))
                                        <span class="text-amber-700">Captured, not uploaded</span>
                                    @elseif(! empty($meta['skipped']))
                                        —
                                    @else
                                        <span class="text-amber-700" title="Browser did not capture uploadable audio during the pronunciation check">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ ! empty($meta['skipped']) ? 'Yes' : 'No' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4 border-t border-gray-200">
                    {{ $pronunciationEvents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
