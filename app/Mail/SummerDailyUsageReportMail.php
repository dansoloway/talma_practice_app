<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SummerDailyUsageReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     date: string,
     *     timezone: string,
     *     logins: int,
     *     lessons_completed: int,
     *     voice_recordings: int,
     * }  $report
     */
    public function __construct(
        public array $report,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Summer Practice Pal — daily usage ('.$this->report['date'].')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.summer-daily-usage-report',
            with: [
                'date' => $this->report['date'],
                'timezone' => $this->report['timezone'],
                'logins' => $this->report['logins'],
                'lessonsCompleted' => $this->report['lessons_completed'],
                'voiceRecordings' => $this->report['voice_recordings'],
            ],
        );
    }
}
