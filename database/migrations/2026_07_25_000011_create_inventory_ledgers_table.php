<?php

use App\Enums\TransactionType;
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
        Schema::create('inventory_ledgers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type')->default(TransactionType::INITIAL_INTAKE->value);
            $table->string('from_state')->nullable();
            $table->string('to_state')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('inventory_id');
            $table->index('transaction_type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_ledgers');
    }
};
