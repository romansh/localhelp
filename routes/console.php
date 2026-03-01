<?php

use App\Models\HelpRequest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('help-requests:expire', function () {
    $count = HelpRequest::where('expires_at', '<', now())
        ->where('status', '!=', 'fulfilled')
        ->update(['status' => 'fulfilled']);

    $this->info("Marked {$count} expired help requests as fulfilled.");
})->purpose('Mark expired help requests as fulfilled');

// Run expiration sweep hourly
Schedule::command('help-requests:expire')->hourly();
