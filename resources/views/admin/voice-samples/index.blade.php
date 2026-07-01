@extends('layouts.admin')

@section('title', 'Voice Samples')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Voice Training Samples</h1>
        <p class="text-gray-600 mt-2">Browse anonymized learner recordings stored for voice training. Audio is served through authenticated, short-lived links.</p>
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

    <div class="card">
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
                                        <source src="{{ route('admin.voice-samples.audio', $sample) }}" type="audio/mpeg">
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
</div>
@endsection
