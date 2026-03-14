<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('product_group_id')->references('id')->on('product_groups')->nullOnDelete();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('stock_receipt_items', function (Blueprint $table) {
            $table->foreign('stock_receipt_id')->references('id')->on('stock_receipts')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::table('stock_issues', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('stock_issue_items', function (Blueprint $table) {
            $table->foreign('stock_issue_id')->references('id')->on('stock_issues')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::table('stocktakes', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('stocktake_items', function (Blueprint $table) {
            $table->foreign('stocktake_id')->references('id')->on('stocktakes')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('stocktake_items', function (Blueprint $table) {
            $table->dropForeign(['stocktake_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('stocktakes', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['stock_transfer_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['from_warehouse_id']);
            $table->dropForeign(['to_warehouse_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('stock_issue_items', function (Blueprint $table) {
            $table->dropForeign(['stock_issue_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('stock_issues', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('stock_receipt_items', function (Blueprint $table) {
            $table->dropForeign(['stock_receipt_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_group_id']);
        });
    }
};
