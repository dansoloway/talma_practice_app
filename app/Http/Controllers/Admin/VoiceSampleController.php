<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\VoiceSample;
use App\Services\VoiceSamplePlayback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoiceSampleController extends Controller
{
    public function index(Request $request): View
    {
        $organizationId = $request->integer('organization_id') ?: null;
        $search = trim((string) $request->input('search', ''));
        $gender = $request->input('gender');
        $nativeLanguage = $request->input('native_language');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $samples = VoiceSample::query()
            ->with(['organization', 'lesson', 'vocabulary'])
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->when($search !== '', fn ($query) => $query->where('target_text', 'like', '%'.$search.'%'))
            ->when($gender, fn ($query) => $query->where('gender', $gender))
            ->when($nativeLanguage, fn ($query) => $query->where('native_language', $nativeLanguage))
            ->when($dateFrom, fn ($query) => $query->whereDate('recorded_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('recorded_at', '<=', $dateTo))
            ->latest('recorded_at')
            ->paginate(25)
            ->withQueryString();

        $organizations = Organization::query()
            ->whereIn('id', VoiceSample::query()->select('organization_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('admin.voice-samples.index', [
            'samples' => $samples,
            'organizations' => $organizations,
            'filters' => [
                'organization_id' => $organizationId,
                'search' => $search,
                'gender' => $gender,
                'native_language' => $nativeLanguage,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'genders' => ParentStudent::GENDERS,
            'nativeLanguages' => ParentStudent::NATIVE_LANGUAGES,
        ]);
    }

    public function audio(VoiceSample $voiceSample, VoiceSamplePlayback $playback): RedirectResponse|StreamedResponse
    {
        return $playback->respond($voiceSample);
    }
}
