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
        Schema::create('inventories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedInteger('available_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('picked_quantity')->default(0);
            $table->unsignedInteger('shipped_quantity')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
            $table->index('warehouse_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
