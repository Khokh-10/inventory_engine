<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ShipmentStatus;
use App\Jobs\ProcessShipmentJob;
use App\Models\Shipment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPendingShipmentsCommand extends Command
{
    protected $signature = 'shipments:process';

    protected $description = 'Process all pending shipments by dispatching shipment processing jobs.';

    public function handle(): int
    {
        Log::info('Shipment processing command started.');

        $pendingCount = Shipment::query()
            ->where('status', ShipmentStatus::PENDING->value)
            ->count();

        $this->info(sprintf('Found %d pending shipments.', $pendingCount));

        $processed = 0;

        Shipment::query()
            ->where('status', ShipmentStatus::PENDING->value)
            ->chunkById(100, function (mixed $shipments) use (&$processed): void {
                foreach ($shipments as $shipment) {
                    $this->dispatchShipment($shipment);
                    $processed++;
                }
            });

        $this->info('Completed.');
        Log::info('Shipment processing command completed.', [
            'processed' => $processed,
        ]);

        return self::SUCCESS;
    }

    private function dispatchShipment(Shipment $shipment): void
    {
        try {
            $this->line(sprintf('Dispatching Shipment #%d', $shipment->getKey()));
            ProcessShipmentJob::dispatch($shipment->getKey());

            Log::info('Shipment dispatched.', [
                'shipment_id' => $shipment->getKey(),
            ]);
        } catch (Throwable $exception) {
            $this->error(sprintf('Failed to dispatch Shipment #%d', $shipment->getKey()));

            Log::error('Shipment dispatch failed.', [
                'shipment_id' => $shipment->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
