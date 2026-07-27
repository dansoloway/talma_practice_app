<?php

namespace App\Console\Commands;

use App\Mail\SummerDailyUsageReportMail;
use App\Services\SummerDailyUsageReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SummerDailyUsageReport extends Command
{
    protected $signature = 'talma:summer-daily-usage-report
                            {--date= : Report date in Asia/Jerusalem (YYYY-MM-DD); defaults to yesterday}
                            {--dry-run : Print counts without sending email}';

    protected $description = 'Email yesterday\'s Summer Practice Pal usage (logins, lessons completed, voice recordings)';

    public function handle(SummerDailyUsageReportService $reports): int
    {
        $dateOption = $this->option('date');

        if (is_string($dateOption) && trim($dateOption) !== '') {
            try {
                $reports->windowForDate($dateOption);
            } catch (Throwable) {
                $this->error('Invalid --date. Use YYYY-MM-DD.');

                return self::FAILURE;
            }
        } else {
            $dateOption = null;
        }

        $report = $reports->forDate($dateOption);

        if (! $report['organization']) {
            $this->warn('Summer Practice Pal organization not found; skipping report.');

            return self::SUCCESS;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Date', $report['date'].' ('.$report['timezone'].')'],
                ['Logins', (string) $report['logins']],
                ['Lessons completed', (string) $report['lessons_completed']],
                ['Voice recordings', (string) $report['voice_recordings']],
            ]
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run: email not sent.');

            return self::SUCCESS;
        }

        $recipients = config('app.summer_daily_report_emails', []);

        if ($recipients === []) {
            $this->warn('No summer daily report recipients configured; skipping send.');

            return self::SUCCESS;
        }

        Mail::to($recipients)->send(new SummerDailyUsageReportMail([
            'date' => $report['date'],
            'timezone' => $report['timezone'],
            'logins' => $report['logins'],
            'lessons_completed' => $report['lessons_completed'],
            'voice_recordings' => $report['voice_recordings'],
        ]));

        $this->info('Report emailed to: '.implode(', ', $recipients));

        return self::SUCCESS;
    }
}
