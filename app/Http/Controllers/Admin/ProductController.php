<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovementType;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Gestão administrativa de produtos.
 * Não existe método destroy: produtos só podem ser inativados/reativados.
 */
class ProductController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * GET /api/v1/admin/products
     * Lista TODOS os produtos (ativos e inativos), com filtros.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'in:0,1,true,false'],
            'product_type' => ['nullable', 'string', 'max:60'],
            'brand' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->withSum('stocks as total_stock', 'quantity')
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['active']), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when($validated['product_type'] ?? null, fn ($q, $type) => $q->where('product_type', $type))
            ->when($validated['brand'] ?? null, fn ($q, $brand) => $q->where('brand', $brand))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15);

        return ProductResource::collection($products);
    }

    /**
     * POST /api/v1/admin/products
     * Cria o produto e, opcionalmente, a grade inicial de estoque.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:191', 'alpha_dash', 'unique:products,slug'],
            'product_type' => ['required', 'string', 'max:60'],
            'brand' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'promotional_price' => ['nullable', 'numeric', 'min:0', 'lt:base_price'],
            'active' => ['sometimes', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],

            'stocks' => ['sometimes', 'array'],
            'stocks.*.size' => ['required', 'string', 'max:10', 'distinct:ignore_case'],
            'stocks.*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'stocks.*.min_quantity' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ]);

        $stocks = $validated['stocks'] ?? [];
        unset($validated['stocks']);

        $product = DB::transaction(function () use ($validated, $stocks, $request) {
            $product = Product::create($validated);

            foreach ($stocks as $row) {
                $size = ProductStock::normalizeSize($row['size']);

                $product->stocks()->create([
                    'size' => $size,
                    'quantity' => 0,
                    'min_quantity' => $row['min_quantity'] ?? 0,
                ]);

                if ($row['quantity'] > 0) {
                    $this->stockService->move(
                        $product,
                        $size,
                        StockMovementType::INBOUND,
                        $row['quantity'],
                        $request->user(),
                        'Estoque inicial',
                    );
                }
            }

            return $product;
        });

        $product->load('stocks')->loadSum('stocks as total_stock', 'quantity');

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /**
     * GET /api/v1/admin/products/{product}
     */
    public function show(Product $product): ProductResource
    {
        $product->load('stocks')->loadSum('stocks as total_stock', 'quantity');

        return new ProductResource($product);
    }

    /**
     * PUT|PATCH /api/v1/admin/products/{product}
     * Não altera `active` (use activate/deactivate) nem estoque (use /stock).
     */
    public function update(Request $request, Product $product): ProductResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'required', 'string', 'max:191', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product->id)],
            'product_type' => ['sometimes', 'required', 'string', 'max:60'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:80'],
            'description' => ['sometimes', 'nullable', 'string'],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99'],
            'promotional_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99'],
            'width' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99'],
            'length' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        // Preço promocional precisa ser menor que o preço base efetivo (novo ou atual)
        $basePrice = (float) ($validated['base_price'] ?? $product->base_price);
        $promoPrice = array_key_exists('promotional_price', $validated)
            ? $validated['promotional_price']
            : $product->promotional_price;

        if ($promoPrice !== null && (float) $promoPrice >= $basePrice) {
            throw ValidationException::withMessages([
                'promotional_price' => 'O preço promocional deve ser menor que o preço base.',
            ]);
        }

        $product->update($validated);

        $product->load('stocks')->loadSum('stocks as total_stock', 'quantity');

        return new ProductResource($product);
    }

    /**
     * PATCH /api/v1/admin/products/{product}/activate
     */
    public function activate(Product $product): ProductResource
    {
        $product->update(['active' => true]);

        return new ProductResource($product->fresh());
    }

    /**
     * PATCH /api/v1/admin/products/{product}/deactivate
     * Substitui a exclusão: o produto sai da loja pública, mas o histórico (pedidos, estoque) é preservado.
     */
    public function deactivate(Product $product): ProductResource
    {
        $product->update(['active' => false]);

        return new ProductResource($product->fresh());
    }
}
