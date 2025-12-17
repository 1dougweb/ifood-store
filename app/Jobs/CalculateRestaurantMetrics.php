<?php

namespace App\Jobs;

use App\Models\Restaurant;
use App\Services\MetricsService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateRestaurantMetrics implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Restaurant $restaurant,
        public ?Carbon $date = null,
        public string $periodType = 'daily'
    ) {
        $this->date = $date ?? Carbon::today();
    }

    /**
     * Execute the job.
     */
    public function handle(MetricsService $metricsService): void
    {
        $metricsService->calculateMetrics($this->restaurant, $this->date, $this->periodType);
    }
}
