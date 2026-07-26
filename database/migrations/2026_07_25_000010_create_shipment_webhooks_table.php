<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipment_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('event_id')->unique();
            $table->string('provider')->nullable();
            $table->string('event_type')->nullable();
            $table->string('status')->nullable();
            $table->longText('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_webhooks');
    }
};
