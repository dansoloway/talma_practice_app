<?php

namespace App\Services;

use App\Models\LearnerLoginEvent;
use App\Models\LearnerVisit;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SummerAnalyticsService
{
    public function dailySignups(Organization $organization, Carbon $start, Carbon $end): Collection
    {
        $parents = User::query()
            ->where('role', User::ROLE_PARENT)
            ->whereHas('organizations', function ($q) use ($organization, $start, $end) {
                $q->where('organizations.id', $organization->id)
                    ->where('organization_user.role', 'parent')
                    ->whereBetween('organization_user.created_at', [$start, $end]);
            })
            ->withCount('parentStudents')
            ->with(['organizations' => function ($q) use ($organization) {
                $q->where('organizations.id', $organization->id);
            }])
            ->orderBy('created_at')
            ->get();

        $byDay = [];

        foreach ($parents as $parent) {
            $joinedAt = $parent->organizations->first()?->pivot?->created_at
                ?? $parent->created_at;
            $day = Carbon::parse($joinedAt)->toDateString();

            if (! isset($byDay[$day])) {
                $byDay[$day] = [
                    'date' => $day,
                    'parents' => [],
                    'parent_count' => 0,
                    'children_count' => 0,
                ];
            }

            $childCount = (int) $parent->parent_students_count;
            $byDay[$day]['parents'][] = [
                'id' => $parent->id,
                'name' => $parent->name,
                'email' => $parent->email,
                'children_count' => $childCount,
                'signed_up_at' => Carbon::parse($joinedAt)->toDateTimeString(),
            ];
            $byDay[$day]['parent_count']++;
            $byDay[$day]['children_count'] += $childCount;
        }

        krsort($byDay);

        return collect(array_values($byDay));
    }

    public function dailyLogins(Organization $organization, Carbon $start, Carbon $end): Collection
    {
        $events = LearnerLoginEvent::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['user', 'parentStudent.parent'])
            ->orderByDesc('created_at')
            ->get();

        $byDay = [];

        foreach ($events as $event) {
            $day = $event->created_at->toDateString();
            if (! isset($byDay[$day])) {
                $byDay[$day] = [
                    'date' => $day,
                    'logins' => [],
                    'login_count' => 0,
                    'unique_user_ids' => [],
                ];
            }

            $user = $event->user;
            $child = $event->parentStudent;
            $label = $user?->name ?? 'Unknown';
            $email = $user?->email;
            $detail = null;

            if ($user?->isStudent() && $child) {
                $label = $child->display_name;
                $detail = 'Child of '.($child->parent?->name ?? 'parent');
                $email = $user->email;
            } elseif ($user?->isParent() && $child) {
                $detail = 'Practicing as '.$child->display_name;
            }

            $byDay[$day]['logins'][] = [
                'user_id' => $event->user_id,
                'name' => $label,
                'email' => $email,
                'detail' => $detail,
                'logged_in_at' => $event->created_at->toDateTimeString(),
            ];
            $byDay[$day]['login_count']++;
            $byDay[$day]['unique_user_ids'][$event->user_id] = true;
        }

        foreach ($byDay as &$day) {
            $day['unique_users'] = count($day['unique_user_ids']);
            unset($day['unique_user_ids']);
        }
        unset($day);

        krsort($byDay);

        return collect(array_values($byDay));
    }

    public function visits(Organization $organization, Carbon $start, Carbon $end): Collection
    {
        $visits = LearnerVisit::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('started_at', [$start, $end])
            ->with([
                'user',
                'parentStudent.parent',
                'lessons.lesson',
            ])
            ->orderByDesc('started_at')
            ->get();

        return $visits->map(function (LearnerVisit $visit) {
            $user = $visit->user;
            $child = $visit->parentStudent;
            $who = $user?->name ?? 'Unknown';
            $email = $user?->email;
            $detail = null;

            if ($child) {
                $who = $child->display_name;
                if ($user?->isParent()) {
                    $detail = 'via parent '.$user->name;
                    $email = $user->email;
                } elseif ($child->parent) {
                    $detail = 'child of '.$child->parent->name;
                }
            }

            $lessons = $visit->lessons
                ->sortBy('first_seen_at')
                ->values()
                ->map(fn ($row) => [
                    'id' => $row->lesson_id,
                    'title' => $row->lesson?->title ?? ('Lesson #'.$row->lesson_id),
                    'first_seen_at' => $row->first_seen_at?->toDateTimeString(),
                ])
                ->all();

            return [
                'id' => $visit->id,
                'who' => $who,
                'email' => $email,
                'detail' => $detail,
                'started_at' => $visit->started_at->toDateTimeString(),
                'ended_at' => $visit->ended_at?->toDateTimeString(),
                'duration_seconds' => $visit->effectiveDurationSeconds(),
                'end_reason' => $visit->end_reason ?? ($visit->isOpen() ? LearnerVisit::END_REASON_STILL_OPEN : null),
                'lesson_count' => count($lessons),
                'lessons' => $lessons,
            ];
        });
    }

    public function signupCsvRows(Collection $dailySignups): array
    {
        $rows = [['date', 'parent_name', 'parent_email', 'children_count']];
        foreach ($dailySignups as $day) {
            foreach ($day['parents'] as $parent) {
                $rows[] = [
                    $day['date'],
                    $parent['name'],
                    $parent['email'],
                    $parent['children_count'],
                ];
            }
        }

        return $rows;
    }

    public function loginCsvRows(Collection $dailyLogins): array
    {
        $rows = [['date', 'name', 'email', 'detail', 'logged_in_at']];
        foreach ($dailyLogins as $day) {
            foreach ($day['logins'] as $login) {
                $rows[] = [
                    $day['date'],
                    $login['name'],
                    $login['email'] ?? '',
                    $login['detail'] ?? '',
                    $login['logged_in_at'],
                ];
            }
        }

        return $rows;
    }

    public function visitCsvRows(Collection $visits): array
    {
        $rows = [['started_at', 'who', 'email', 'detail', 'duration_seconds', 'lesson_count', 'lessons', 'end_reason']];
        foreach ($visits as $visit) {
            $lessonTitles = collect($visit['lessons'])->pluck('title')->implode('; ');
            $rows[] = [
                $visit['started_at'],
                $visit['who'],
                $visit['email'] ?? '',
                $visit['detail'] ?? '',
                $visit['duration_seconds'],
                $visit['lesson_count'],
                $lessonTitles,
                $visit['end_reason'] ?? '',
            ];
        }

        return $rows;
    }
}
