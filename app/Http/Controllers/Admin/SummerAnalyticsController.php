<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\SummerAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SummerAnalyticsController extends Controller
{
    public function __construct(
        protected SummerAnalyticsService $analytics,
    ) {}

    public function index(Request $request, Organization $organization)
    {
        $this->ensureSummerOrg($organization);

        [$start, $end] = $this->dateRange($request);

        $dailySignups = $this->analytics->dailySignups($organization, $start, $end);
        $dailyLogins = $this->analytics->dailyLogins($organization, $start, $end);
        $visits = $this->analytics->visits($organization, $start, $end);

        $formatDuration = function (?int $seconds): string {
            if (! $seconds || $seconds <= 0) {
                return '—';
            }
            $minutes = intdiv($seconds, 60);
            $remaining = $seconds % 60;
            if ($minutes === 0) {
                return "{$remaining}s";
            }
            $hours = intdiv($minutes, 60);
            $minutes = $minutes % 60;
            $parts = [];
            if ($hours > 0) {
                $parts[] = "{$hours}h";
            }
            if ($minutes > 0) {
                $parts[] = "{$minutes}m";
            }
            if ($remaining > 0 && $hours === 0) {
                $parts[] = "{$remaining}s";
            }

            return implode(' ', $parts);
        };

        return view('admin.summer-analytics', [
            'organization' => $organization,
            'dailySignups' => $dailySignups,
            'dailyLogins' => $dailyLogins,
            'visits' => $visits,
            'startDate' => $start,
            'endDate' => $end,
            'formatDuration' => $formatDuration,
            'signupParentTotal' => $dailySignups->sum('parent_count'),
            'signupChildrenTotal' => $dailySignups->sum('children_count'),
            'loginEventTotal' => $dailyLogins->sum('login_count'),
            'visitTotal' => $visits->count(),
        ]);
    }

    public function exportSignups(Request $request, Organization $organization): StreamedResponse
    {
        $this->ensureSummerOrg($organization);
        [$start, $end] = $this->dateRange($request);
        $rows = $this->analytics->signupCsvRows(
            $this->analytics->dailySignups($organization, $start, $end)
        );

        return $this->csvDownload('summer-signups.csv', $rows);
    }

    public function exportLogins(Request $request, Organization $organization): StreamedResponse
    {
        $this->ensureSummerOrg($organization);
        [$start, $end] = $this->dateRange($request);
        $rows = $this->analytics->loginCsvRows(
            $this->analytics->dailyLogins($organization, $start, $end)
        );

        return $this->csvDownload('summer-logins.csv', $rows);
    }

    public function exportVisits(Request $request, Organization $organization): StreamedResponse
    {
        $this->ensureSummerOrg($organization);
        [$start, $end] = $this->dateRange($request);
        $rows = $this->analytics->visitCsvRows(
            $this->analytics->visits($organization, $start, $end)
        );

        return $this->csvDownload('summer-sessions.csv', $rows);
    }

    protected function ensureSummerOrg(Organization $organization): void
    {
        if ($organization->slug !== Organization::SUMMER_PRACTICE_PAL_SLUG) {
            abort(404);
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(Request $request): array
    {
        $start = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::today()->subDays(30)->startOfDay();
        $end = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function csvDownload(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
