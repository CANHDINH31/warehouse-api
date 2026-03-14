<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function increaseStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        string $movementType,
        ?string $referenceCode = null,
        ?float $unitCost = null,
        ?int $createdBy = null,
        ?string $note = null,
        string|Carbon|null $transactedAt = null
    ): Inventory {
        return $this->changeStock(
            warehouseId: $warehouseId,
            productId: $productId,
            quantityDelta: abs($quantity),
            movementType: $movementType,
            referenceCode: $referenceCode,
            unitCost: $unitCost,
            createdBy: $createdBy,
            note: $note,
            transactedAt: $transactedAt
        );
    }

    public function decreaseStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        string $movementType,
        ?string $referenceCode = null,
        ?float $unitCost = null,
        ?int $createdBy = null,
        ?string $note = null,
        string|Carbon|null $transactedAt = null
    ): Inventory {
        return $this->changeStock(
            warehouseId: $warehouseId,
            productId: $productId,
            quantityDelta: -abs($quantity),
            movementType: $movementType,
            referenceCode: $referenceCode,
            unitCost: $unitCost,
            createdBy: $createdBy,
            note: $note,
            transactedAt: $transactedAt
        );
    }

    public function adjustStockToActual(
        int $warehouseId,
        int $productId,
        float $actualQuantity,
        ?string $referenceCode = null,
        ?int $createdBy = null,
        ?string $note = null,
        string|Carbon|null $transactedAt = null
    ): Inventory {
        $inventory = $this->getLockedInventory($warehouseId, $productId);
        $currentQuantity = (float) $inventory->quantity;
        $delta = round($actualQuantity - $currentQuantity, 3);

        if ($delta === 0.0) {
            return $inventory;
        }

        return $this->changeStock(
            warehouseId: $warehouseId,
            productId: $productId,
            quantityDelta: $delta,
            movementType: 'stocktake_adjustment',
            referenceCode: $referenceCode,
            unitCost: null,
            createdBy: $createdBy,
            note: $note,
            transactedAt: $transactedAt
        );
    }

    public function ensureSufficientStock(int $warehouseId, int $productId, float $requiredQuantity): void
    {
        $inventory = $this->getLockedInventory($warehouseId, $productId);
        $availableQuantity = (float) $inventory->quantity;

        if ($availableQuantity < $requiredQuantity) {
            throw ValidationException::withMessages([
                'items' => [
                    "Không đủ tồn kho cho sản phẩm #{$productId} tại kho #{$warehouseId}. Tồn hiện tại: {$availableQuantity}",
                ],
            ]);
        }
    }

    private function changeStock(
        int $warehouseId,
        int $productId,
        float $quantityDelta,
        string $movementType,
        ?string $referenceCode,
        ?float $unitCost,
        ?int $createdBy,
        ?string $note,
        string|Carbon|null $transactedAt
    ): Inventory {
        if ($quantityDelta === 0.0) {
            return $this->getLockedInventory($warehouseId, $productId);
        }

        $inventory = $this->getLockedInventory($warehouseId, $productId);
        $currentQuantity = (float) $inventory->quantity;
        $newQuantity = round($currentQuantity + $quantityDelta, 3);

        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'items' => [
                    "Không đủ tồn kho cho sản phẩm #{$productId} tại kho #{$warehouseId}. Tồn hiện tại: {$currentQuantity}",
                ],
            ]);
        }

        $movementTime = $transactedAt ? Carbon::parse($transactedAt) : now();

        $inventory->update([
            'quantity' => $newQuantity,
            'last_movement_at' => $movementTime,
        ]);

        InventoryTransaction::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'quantity' => $quantityDelta,
            'balance_after' => $newQuantity,
            'unit_cost' => $unitCost,
            'transacted_at' => $movementTime,
            'reference_code' => $referenceCode,
            'note' => $note,
            'created_by' => $createdBy,
        ]);

        return $inventory->fresh();
    }

    private function getLockedInventory(int $warehouseId, int $productId): Inventory
    {
        $inventory = Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            $inventory = Inventory::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => 0,
                'last_movement_at' => now(),
            ]);

            $inventory = Inventory::query()
                ->where('id', $inventory->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return $inventory;
    }
}
