<?php

declare(strict_types=1);

use App\Enums\ProviderResponse;
use App\Enums\ReservationStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ShipmentWebhook;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ReservationService;
use App\Services\ShipmentService;
use App\Services\Providers\MockShippingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Helper: build a customer/order/warehouse/product/inventory fixture with a
 * given available quantity, and return the pieces needed by the tests.
 */
function makeFixture(int $availableQuantity = 5): array
{
    $customer = Customer::query()->create([
        'full_name' => 'Test Customer',
        'email' => fake()->unique()->safeEmail(),
    ]);

    $order = Order::query()->create([
        'customer_id' => $customer->getKey(),
        'status' => 'pending',
        'total' => 100.00,
    ]);

    $warehouse = Warehouse::query()->create([
        'name' => 'Main WH',
        'code' => 'WH-' . fake()->unique()->numerify('###'),
        'location' => 'Cairo',
    ]);

    $product = Product::query()->create([
        'sku' => 'SKU-' . fake()->unique()->numerify('####'),
        'name' => 'Test Product',
    ]);

    $inventory = Inventory::query()->create([
        'warehouse_id' => $warehouse->getKey(),
        'product_id' => $product->getKey(),
        'total_quantity' => $availableQuantity,
        'available_quantity' => $availableQuantity,
        'reserved_quantity' => 0,
        'picked_quantity' => 0,
        'shipped_quantity' => 0,
        'version' => 1,
    ]);

    return compact('customer', 'order', 'warehouse', 'product', 'inventory');
}

it('prevents overselling when two reservations race for the last unit', function () {
    ['order' => $order1, 'inventory' => $inventory] = makeFixture(availableQuantity: 1);
    $order2 = Order::query()->create([
        'customer_id' => $order1->customer_id,
        'status' => 'pending',
        'total' => 50.00,
    ]);

    $service = app(ReservationService::class);

    // First reservation succeeds and takes the only unit.
    $service->createReservation($order1, [
        ['inventory_id' => $inventory->getKey(), 'quantity' => 1],
    ]);

    // Second reservation for a *different* order must fail — no unit left.
    expect(fn () => $service->createReservation($order2, [
        ['inventory_id' => $inventory->getKey(), 'quantity' => 1],
    ]))->toThrow(InsufficientInventoryException::class);

    $inventory->refresh();
    expect($inventory->available_quantity)->toBe(0)
        ->and($inventory->reserved_quantity)->toBe(1);
});

it('is idempotent when the same reservation command runs twice for the same order', function () {
    ['order' => $order, 'inventory' => $inventory] = makeFixture(availableQuantity: 5);
    $service = app(ReservationService::class);

    $first = $service->createReservation($order, [
        ['inventory_id' => $inventory->getKey(), 'quantity' => 2],
    ]);

    $second = $service->createReservation($order, [
        ['inventory_id' => $inventory->getKey(), 'quantity' => 2],
    ]);

    // Same reservation returned, not a new one created.
    expect($second->getKey())->toBe($first->getKey())
        ->and(Reservation::query()->where('order_id', $order->getKey())->count())->toBe(1);

    $inventory->refresh();
    // Only reserved once — not double-reserved.
    expect($inventory->reserved_quantity)->toBe(2)
        ->and($inventory->available_quantity)->toBe(3);
});

it('allows release and pick to succeed when both available and reserved buckets are non-zero', function () {
    // Regression test for the resolveState() bug: a row with BOTH
    // available_quantity > 0 AND reserved_quantity > 0 must still allow
    // valid transitions on the reserved bucket.
    ['order' => $order, 'inventory' => $inventory] = makeFixture(availableQuantity: 10);
    $inventoryService = app(InventoryService::class);

    $inventoryService->reserve(
        inventoryId: $inventory->getKey(),
        quantity: 3,
        referenceType: 'test',
        referenceId: '1',
    );

    $inventory->refresh();
    expect($inventory->available_quantity)->toBe(7)
        ->and($inventory->reserved_quantity)->toBe(3);

    // This must NOT throw, even though available_quantity (7) is also > 0.
    $inventoryService->pick(
        inventoryId: $inventory->getKey(),
        quantity: 3,
        referenceType: 'test',
        referenceId: '1',
    );

    $inventory->refresh();
    expect($inventory->reserved_quantity)->toBe(0)
        ->and($inventory->picked_quantity)->toBe(3)
        ->and($inventory->available_quantity)->toBe(7);
});

it('ignores a duplicate shipment webhook for the same event id', function () {
    ['order' => $order, 'inventory' => $inventory] = makeFixture(availableQuantity: 5);
    $reservationService = app(ReservationService::class);
    $shipmentService = app(ShipmentService::class);

    $reservation = $reservationService->createReservation($order, [
        ['inventory_id' => $inventory->getKey(), 'quantity' => 2],
    ]);
    $reservationService->consumeReservation($reservation->getKey());
    $shipment = $shipmentService->createShipment($reservation->fresh());

    $payload = ['event' => 'shipment.in_transit', 'status' => 'in_transit'];

    $first = $shipmentService->handleWebhook($shipment->getKey(), 'evt-123', $payload);
    $second = $shipmentService->handleWebhook($shipment->getKey(), 'evt-123', $payload);

    expect($second->getKey())->toBe($first->getKey())
        ->and(ShipmentWebhook::query()->where('event_id', 'evt-123')->count())->toBe(1);
});

it('ships only the remaining quantity on a partial shipment, not the full amount again', function () {
    ['order' => $order, 'inventory' => $inventory] = makeFixture(availableQuantity: 10);
    $reservationService = app(ReservationService::class);
    $shipmentService = app(ShipmentService::class);
    $inventoryService = app(InventoryService::class);

    $reservation = $reservationService->createReservation($order, [
        ['inventory_id' => $inventory->getKey(), 'quantity' => 10],
    ]);
    $reservationService->consumeReservation($reservation->getKey());
    $shipment = $shipmentService->createShipment($reservation->fresh());

    // Move all reserved stock to picked so partialShipment can ship from it.
    $inventoryService->pick(
        inventoryId: $inventory->getKey(),
        quantity: 10,
        referenceType: 'test',
        referenceId: '1',
    );

    // Simulate a provider reporting partial success for 4 of 10 units.
    $fakeProvider = new class implements \App\Contracts\ShippingProviderInterface {
        public function name(): string { return 'FakeProvider'; }
        public function ship(\App\Models\Shipment $shipment): ProviderResponse { return ProviderResponse::PARTIAL_SUCCESS; }
        public function getTrackingNumber(\App\Models\Shipment $shipment): ?string { return 'TRACK-1'; }
        public function getProviderReference(\App\Models\Shipment $shipment): ?string { return 'REF-1'; }
        public function getPartialQuantity(\App\Models\Shipment $shipment): int { return 4; }
    };

    $shipmentService->partialShipment($shipment->fresh(), 'partial test', $fakeProvider);

    $inventory->refresh();
    expect($inventory->shipped_quantity)->toBe(4)
        ->and($inventory->picked_quantity)->toBe(6);
});

it('rejects a reservation when requested quantity exceeds available stock', function () {
    ['order' => $order, 'inventory' => $inventory] = makeFixture(availableQuantity: 2);
    $service = app(ReservationService::class);

    expect(fn () => $service->createReservation($order, [
        ['inventory_id' => $inventory->getKey(), 'quantity' => 3],
    ]))->toThrow(InsufficientInventoryException::class);

    $inventory->refresh();
    expect($inventory->available_quantity)->toBe(2)
        ->and($inventory->reserved_quantity)->toBe(0);
});