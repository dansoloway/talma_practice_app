<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ViewTtsLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tts:logs {--lines=100 : Number of lines to show}';

    /**
     * The console command description.
     */
    protected $description = 'View TTS generation logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lines = (int) $this->option('lines');
        $lines = min($lines, 1000); // Cap at 1000 lines
        
        $logFile = storage_path('logs/tts_generation.log');
        
        if (!file_exists($logFile)) {
            $this->error('Log file not found: ' . $logFile);
            return 1;
        }
        
        // Read last N lines from log file
        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key() + 1;
        
        $startLine = max(0, $totalLines - $lines);
        
        $this->info("Showing last {$lines} lines of {$totalLines} total lines:");
        $this->line('');
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->current();
            if ($line !== false) {
                // Color code errors
                if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) {
                    $this->error(trim($line));
                } elseif (stripos($line, 'success') !== false) {
                    $this->info(trim($line));
                } else {
                    $this->line(trim($line));
                }
            }
            $file->next();
        }
        
        return 0;
    }
}

