<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\ShippingProviderInterface;
use App\Services\ShipmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessShipmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public array|int $backoff = [10, 30, 60, 120, 300];

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $shipmentId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(ShipmentService $shipmentService, ShippingProviderInterface $shippingProvider): void
    {
        $shipmentService->processShipment($this->shipmentId, $shippingProvider);
    }

    /**
     * Stop retrying once the retry window expires.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(30);
    }

    /**
     * Handle a failed job after all retries are exhausted.
     */
    public function failed(Throwable $exception): void
    {
        try {
            $reason = $this->buildFailureReason($exception);

            app(ShipmentService::class)->failShipment($this->shipmentId, $reason);
        } catch (Throwable $secondaryException) {
            Log::error('Shipment processing job failed to mark shipment as failed.', [
                'shipment_id' => $this->shipmentId,
                'exception' => $secondaryException->getMessage(),
            ]);
        }

        Log::error('Shipment processing job failed.', [
            'shipment_id' => $this->shipmentId,
            'exception' => $exception->getMessage(),
        ]);
    }

    /**
     * Build a readable failure reason for the shipment record.
     */
    private function buildFailureReason(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return $message !== '' ? sprintf('Shipment processing failed: %s', $message) : 'Shipment processing failed after all retries were exhausted.';
    }
}
