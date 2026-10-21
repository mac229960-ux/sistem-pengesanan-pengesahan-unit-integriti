<?php

namespace App\Providers;

use App\Services\Ai\AiProvider;
use App\Services\Ai\AiService;
use App\Services\Hr\ApiHrService;
use App\Services\Hr\HrService;
use App\Services\Hr\MockHrService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HrService::class, fn () => config('integriti.hr.driver') === 'api' ? new ApiHrService : new MockHrService);
        $this->app->bind(AiProvider::class, fn () => AiService::provider());
    }

    public function boot(): void
    {
        Carbon::setLocale('ms');
        Paginator::useTailwind();
    }
}
