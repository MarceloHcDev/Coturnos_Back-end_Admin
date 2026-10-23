<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único ponto de escrita de quantidade de estoque.
 * Será reutilizado pelo fluxo de pedidos (baixa na confirmação de pagamento, devolução em cancelamento).
 */
class StockService
{
    /**
     * Registra uma movimentação e atualiza o saldo de forma atômica.
     *
     * - INBOUND:    soma $quantity ao saldo
     * - OUTBOUND:   subtrai $quantity (falha se o saldo ficar negativo)
     * - ADJUSTMENT: define o saldo como $quantity
     *
     * @throws ValidationException
     */
    public function move(
        Product $product,
        string $size,
        StockMovementType $type,
        int $quantity,
        ?User $user = null,
        ?string $reason = null,
    ): StockMovement {
        $size = ProductStock::normalizeSize($size);

        return DB::transaction(function () use ($product, $size, $type, $quantity, $user, $reason) {
            $stock = ProductStock::firstOrCreate(
                ['product_id' => $product->id, 'size' => $size],
                ['quantity' => 0, 'min_quantity' => 0],
            );

            // Relê com lock para evitar condição de corrida entre requisições simultâneas
            $stock = ProductStock::query()->lockForUpdate()->findOrFail($stock->id);

            $before = $stock->quantity;

            $after = match ($type) {
                StockMovementType::INBOUND => $before + $quantity,
                StockMovementType::OUTBOUND => $before - $quantity,
                StockMovementType::ADJUSTMENT => $quantity,
            };

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Estoque insuficiente para o tamanho {$size}. Disponível: {$before}.",
                ]);
            }

            $stock->update(['quantity' => $after]);

            $movement = $stock->movements()->create([
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $after - $before,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $reason,
            ]);

            $movement->setRelation('stock', $stock);

            return $movement;
        });
    }
}
