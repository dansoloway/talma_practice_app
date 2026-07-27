<?php

/**
 * Cloudways cron entrypoint for Laravel's scheduler.
 *
 * In Cloudways Cron Job Management (PHP), set the command filename to:
 *   scheduler-cron.php
 * Schedule: every minute (* * * * *)
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->call('schedule:run');

echo $kernel->output();

exit($status);
