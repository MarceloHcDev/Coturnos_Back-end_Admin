<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductStockResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * GET /api/v1/admin/stock
     * Visão geral do estoque (produto + tamanho), com alertas via ?status=low|out|ok.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ok', 'low', 'out'])],
            'active' => ['nullable', 'in:0,1,true,false'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $stocks = ProductStock::query()
            ->with('product:id,name,slug,brand,active')
            ->whereHas('product', function (Builder $q) use ($validated, $request) {
                if (! empty($validated['search'])) {
                    $search = $validated['search'];
                    $q->where(function ($w) use ($search) {
                        $w->where('name', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    });
                }

                if (isset($validated['active'])) {
                    $q->where('active', $request->boolean('active'));
                }
            })
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->status($status))
            ->orderBy('product_id')
            ->orderBy('size')
            ->paginate($validated['per_page'] ?? 30);

        return ProductStockResource::collection($stocks);
    }

    /**
     * GET /api/v1/admin/products/{product}/stock
     */
    public function show(Product $product): JsonResponse
    {
        $stocks = $product->stocks()->orderBy('size')->get();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'active' => $product->active,
            ],
            'total_stock' => $stocks->sum('quantity'),
            'data' => ProductStockResource::collection($stocks),
        ]);
    }

    /**
     * POST /api/v1/admin/products/{product}/stock/movements
     *
     * type = in         -> entrada de `quantity` unidades (>= 1)
     * type = out        -> saída de `quantity` unidades (>= 1), exige reason
     * type = adjustment -> define o saldo como `quantity` (>= 0), exige reason
     *
     * Se o tamanho ainda não existir para o produto, ele é criado.
     */
    public function move(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'string', 'max:10'],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['required_if:type,out,adjustment', 'nullable', 'string', 'max:255'],
        ]);

        $type = StockMovementType::from($validated['type']);

        if ($type !== StockMovementType::ADJUSTMENT && $validated['quantity'] < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'A quantidade deve ser de pelo menos 1.',
            ]);
        }

        $movement = $this->stockService->move(
            $product,
            $validated['size'],
            $type,
            $validated['quantity'],
            $request->user(),
            $validated['reason'] ?? null,
        );

        $movement->load('user:id,first_name,last_name');

        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }

    /**
     * PATCH /api/v1/admin/products/{product}/stock/{stock}
     * Atualiza o estoque mínimo (alerta de estoque baixo). A quantidade só muda via movimentação.
     */
    public function update(Request $request, Product $product, ProductStock $stock): ProductStockResource
    {
        abort_unless($stock->product_id === $product->id, 404);

        $validated = $request->validate([
            'min_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $stock->update($validated);

        return new ProductStockResource($stock);
    }

    /**
     * GET /api/v1/admin/products/{product}/stock/movements
     * Histórico de movimentações, com filtros ?size= e ?type=.
     */
    public function movements(Request $request, Product $product): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'size' => ['nullable', 'string', 'max:10'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $movements = StockMovement::query()
            ->whereIn('product_stock_id', function ($q) use ($product, $validated) {
                $q->select('id')->from('product_stocks')->where('product_id', $product->id);

                if (! empty($validated['size'])) {
                    $q->where('size', ProductStock::normalizeSize($validated['size']));
                }
            })
            ->when($validated['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->with(['stock:id,product_id,size', 'user:id,first_name,last_name'])
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 30);

        return StockMovementResource::collection($movements);
    }
}
