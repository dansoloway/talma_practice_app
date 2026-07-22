<?php

namespace App\Services;

use App\Models\LearnerLoginEvent;
use App\Models\LearnerVisit;
use App\Models\LearnerVisitLesson;
use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearnerVisitTracker
{
    public const SESSION_VISIT_KEY = 'learner_visit_id';

    public function recordLogin(User $user, Organization $organization, ?Request $request = null): LearnerLoginEvent
    {
        $parentStudentId = $this->resolveParentStudentId($user);

        $event = LearnerLoginEvent::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'parent_student_id' => $parentStudentId,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);

        $this->startOrResumeVisit($user, $organization, $parentStudentId);

        return $event;
    }

    public function touch(Organization $organization, ?int $lessonId = null): ?LearnerVisit
    {
        $user = Auth::guard('admin')->user();
        if (! $user instanceof User) {
            return null;
        }

        if (! $user->isParent() && ! $user->isStudent()) {
            return null;
        }

        $parentStudentId = $this->resolveParentStudentId($user);
        $visit = $this->startOrResumeVisit($user, $organization, $parentStudentId);

        if ($lessonId) {
            $this->recordLesson($visit, $lessonId);
        }

        return $visit;
    }

    public function recordLesson(LearnerVisit $visit, int $lessonId): LearnerVisitLesson
    {
        $now = now();

        $row = LearnerVisitLesson::query()
            ->where('learner_visit_id', $visit->id)
            ->where('lesson_id', $lessonId)
            ->first();

        if ($row) {
            $row->update(['last_seen_at' => $now]);

            return $row->fresh();
        }

        return LearnerVisitLesson::create([
            'learner_visit_id' => $visit->id,
            'lesson_id' => $lessonId,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);
    }

    public function endCurrentVisit(User $user, Organization $organization, string $reason = LearnerVisit::END_REASON_LOGOUT): void
    {
        $visitId = session(self::SESSION_VISIT_KEY);
        $visit = null;

        if ($visitId) {
            $visit = LearnerVisit::query()
                ->where('id', $visitId)
                ->where('user_id', $user->id)
                ->where('organization_id', $organization->id)
                ->whereNull('ended_at')
                ->first();
        }

        if (! $visit) {
            $visit = LearnerVisit::query()
                ->where('user_id', $user->id)
                ->where('organization_id', $organization->id)
                ->whereNull('ended_at')
                ->orderByDesc('last_seen_at')
                ->first();
        }

        if ($visit) {
            $this->closeVisit($visit, $reason);
        }

        session()->forget(self::SESSION_VISIT_KEY);
    }

    public function startOrResumeVisit(User $user, Organization $organization, ?int $parentStudentId = null): LearnerVisit
    {
        $parentStudentId ??= $this->resolveParentStudentId($user);
        $now = now();

        $visit = $this->findOpenVisit($user, $organization, $parentStudentId);

        if ($visit && $this->isIdle($visit)) {
            $this->closeVisit($visit, LearnerVisit::END_REASON_IDLE);
            $visit = null;
        }

        if ($visit) {
            $updates = ['last_seen_at' => $now];
            if ($parentStudentId && $visit->parent_student_id !== $parentStudentId) {
                $updates['parent_student_id'] = $parentStudentId;
            }
            $visit->update($updates);
            session([self::SESSION_VISIT_KEY => $visit->id]);

            return $visit->fresh();
        }

        $visit = LearnerVisit::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'parent_student_id' => $parentStudentId,
            'started_at' => $now,
            'last_seen_at' => $now,
        ]);

        session([self::SESSION_VISIT_KEY => $visit->id]);

        return $visit;
    }

    protected function findOpenVisit(User $user, Organization $organization, ?int $parentStudentId): ?LearnerVisit
    {
        $visitId = session(self::SESSION_VISIT_KEY);
        if ($visitId) {
            $fromSession = LearnerVisit::query()
                ->where('id', $visitId)
                ->where('user_id', $user->id)
                ->where('organization_id', $organization->id)
                ->whereNull('ended_at')
                ->first();

            if ($fromSession) {
                return $fromSession;
            }
        }

        $query = LearnerVisit::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->whereNull('ended_at')
            ->orderByDesc('last_seen_at');

        if ($parentStudentId) {
            $query->where('parent_student_id', $parentStudentId);
        }

        return $query->first();
    }

    protected function isIdle(LearnerVisit $visit): bool
    {
        return $visit->last_seen_at->lte(now()->subMinutes(LearnerVisit::IDLE_MINUTES));
    }

    protected function closeVisit(LearnerVisit $visit, string $reason): void
    {
        $endedAt = now();
        if ($reason === LearnerVisit::END_REASON_IDLE) {
            $endedAt = $visit->last_seen_at->copy();
        }

        $visit->update([
            'ended_at' => $endedAt,
            'duration_seconds' => max(0, $visit->started_at->diffInSeconds($endedAt)),
            'end_reason' => $reason,
        ]);
    }

    protected function resolveParentStudentId(User $user): ?int
    {
        if ($user->isParent()) {
            $childId = session('selected_student_id');
            if (! $childId) {
                return null;
            }

            $child = ParentStudent::query()
                ->where('parent_id', $user->id)
                ->find($childId);

            return $child?->id;
        }

        if ($user->isStudent()) {
            return $user->linkedParentStudent?->id;
        }

        return null;
    }
}
