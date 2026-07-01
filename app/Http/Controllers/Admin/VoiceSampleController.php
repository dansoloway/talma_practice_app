<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\VoiceSample;
use App\Services\VoiceSamplePlayback;
use App\Services\VoiceSampleStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VoiceSampleController extends Controller
{
    public function index(Request $request): View
    {
        $organizationId = $request->integer('organization_id') ?: null;
        $search = trim((string) $request->input('search', ''));
        $gender = $request->input('gender');
        $nativeLanguage = $request->input('native_language');
        $recordingSource = $request->input('recording_source');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $samples = VoiceSample::query()
            ->with(['organization', 'lesson', 'vocabulary'])
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->when($search !== '', fn ($query) => $query->where('target_text', 'like', '%'.$search.'%'))
            ->when($gender, fn ($query) => $query->where('gender', $gender))
            ->when($nativeLanguage, fn ($query) => $query->where('native_language', $nativeLanguage))
            ->when($recordingSource, fn ($query) => $query->where('recording_source', $recordingSource))
            ->when($dateFrom, fn ($query) => $query->whereDate('recorded_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('recorded_at', '<=', $dateTo))
            ->latest('recorded_at')
            ->paginate(25)
            ->withQueryString();

        $pronunciationEvents = ActivityEvent::query()
            ->with(['lesson', 'vocabulary'])
            ->where('activity_type', 'vocabulary')
            ->where('meta->source', 'pronunciation_check')
            ->when($organizationId, function ($query) use ($organizationId) {
                $query->whereHas('lesson.course.organizations', fn ($orgQuery) => $orgQuery->where('organizations.id', $organizationId));
            })
            ->latest()
            ->paginate(25, ['*'], 'pronunciation_page')
            ->withQueryString();

        $organizations = Organization::query()
            ->whereIn('id', VoiceSample::query()->select('organization_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $stats = [
            'total_samples' => VoiceSample::count(),
            'pronunciation_check_samples' => VoiceSample::where('recording_source', 'pronunciation_check')->count(),
            'manual_record_samples' => VoiceSample::where(function ($query) {
                $query->where('recording_source', 'manual_record')->orWhereNull('recording_source');
            })->count(),
            'samples_last_7_days' => VoiceSample::where('recorded_at', '>=', now()->subDays(7))->count(),
            'pronunciation_attempts' => ActivityEvent::where('activity_type', 'vocabulary')->where('meta->source', 'pronunciation_check')->count(),
            'pronunciation_passes' => ActivityEvent::where('activity_type', 'vocabulary')
                ->where('meta->source', 'pronunciation_check')
                ->where('meta->pronunciation_pass', true)
                ->count(),
        ];

        return view('admin.voice-samples.index', [
            'samples' => $samples,
            'pronunciationEvents' => $pronunciationEvents,
            'stats' => $stats,
            'organizations' => $organizations,
            'filters' => [
                'organization_id' => $organizationId,
                'search' => $search,
                'gender' => $gender,
                'native_language' => $nativeLanguage,
                'recording_source' => $recordingSource,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'genders' => ParentStudent::GENDERS,
            'nativeLanguages' => ParentStudent::NATIVE_LANGUAGES,
            'storageDriver' => config('filesystems.disks.'.config('filesystems.voice_training_disk').'.driver'),
            'storageBucket' => config('filesystems.disks.'.config('filesystems.voice_training_disk').'.bucket'),
        ]);
    }

    public function audio(VoiceSample $voiceSample, VoiceSamplePlayback $playback): Response
    {
        return $playback->respond($voiceSample);
    }

    public function destroy(VoiceSample $voiceSample, VoiceSampleStorage $storage): RedirectResponse
    {
        $storage->delete($voiceSample);

        return redirect()
            ->route('admin.voice-samples.index', request()->only([
                'organization_id',
                'search',
                'gender',
                'native_language',
                'recording_source',
                'date_from',
                'date_to',
                'page',
            ]))
            ->with('success', 'Recording deleted from storage.');
    }
}
