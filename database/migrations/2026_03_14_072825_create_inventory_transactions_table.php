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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id');
            $table->foreignId('product_id');
            $table->string('movement_type');
            $table->decimal('quantity', 18, 3);
            $table->decimal('balance_after', 18, 3);
            $table->decimal('unit_cost', 18, 2)->nullable();
            $table->dateTime('transacted_at');
            $table->string('reference_code')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'transacted_at'], 'inventory_transactions_lookup_idx');
            $table->index('movement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
