<?php

namespace App\Jobs;

use App\Models\Restaurant;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        // Get all active restaurants
        $restaurants = Restaurant::where('is_active', true)->get();

        foreach ($restaurants as $restaurant) {
            try {
                $report = $reportService->generateWeeklyReport($restaurant);
                $reportService->sendReportByEmail($restaurant, $report, 'weekly');

                Log::info('Weekly report sent', [
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending weekly report', [
                    'restaurant_id' => $restaurant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
