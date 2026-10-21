<?php

use App\Models\OtpKod;
use Illuminate\Support\Facades\Schedule;

// Peringatan SLA harian (memerlukan cron: * * * * * php artisan schedule:run)
Schedule::command('integriti:semak-sla')->dailyAt('08:00')->withoutOverlapping();

// Buang kod OTP lama
Schedule::call(fn () => OtpKod::where('created_at', '<', now()->subDay())->delete())->daily();
