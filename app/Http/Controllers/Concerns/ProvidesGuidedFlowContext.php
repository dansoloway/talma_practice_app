<?php

namespace App\Http\Controllers\Concerns;

use App\DataTransferObjects\LessonFlowStep;
use App\Models\Lesson;
use App\Models\Organization;
use App\Services\LessonFlowService;
use Illuminate\Http\Request;

trait ProvidesGuidedFlowContext
{
    protected function guidedFlowViewData(
        Request $request,
        Lesson $lesson,
        string $stepType,
        int|string|null $activityId = null,
    ): array {
        $lesson->loadMissing('course');
        $flowService = app(LessonFlowService::class);

        if (! $flowService->isGuided($lesson)) {
            return ['guidedFlow' => null];
        }

        $org = $request->route('organization');
        if ($org && ! $org instanceof Organization) {
            $org = Organization::where('slug', $org)->first();
        }

        $currentStep = $flowService->steps($lesson)->first(
            fn (LessonFlowStep $step) => $step->type === $stepType
                && ($step->type === 'prompts' || (int) ($step->activityId ?? 0) === (int) ($activityId ?? 0))
        );

        if (! $currentStep) {
            return ['guidedFlow' => null];
        }

        return [
            'guidedFlow' => $flowService->guidedContext($lesson, $currentStep, $org),
            'org' => $org,
        ];
    }
}
