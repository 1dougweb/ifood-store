<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\EvolutionApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Notification $notification
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(EvolutionApiService $evolutionApi): void
    {
        try {
            $this->notification->update(['status' => 'sending']);

            $phoneNumber = $evolutionApi->formatPhoneNumber($this->notification->recipient);
            $result = $evolutionApi->sendTextMessage($phoneNumber, $this->notification->message);

            if ($result) {
                $this->notification->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'metadata' => $result,
                ]);

                Log::info('WhatsApp notification sent successfully', [
                    'notification_id' => $this->notification->id,
                ]);
            } else {
                $this->notification->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to send message via Evolution API',
                ]);

                Log::error('Failed to send WhatsApp notification', [
                    'notification_id' => $this->notification->id,
                ]);
            }
        } catch (\Exception $e) {
            $this->notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Exception sending WhatsApp notification', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
