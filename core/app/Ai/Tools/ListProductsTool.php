<?php

namespace App\Ai\Tools;

use Throwable;
use App\Ai\Concerns\FormatsAgentOutput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListProductsTool implements Tool
{
    use FormatsAgentOutput;

    public function description(): Stringable|string
    {
        return 'Lists our products with product id, name, sku, price, mrp and brand. Use it to resolve product names for other tools and to quote product prices.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $search = trim((string) ($request['search'] ?? ''));
            $limit = $this->agentLimit($request, 20, 50);

            $rows = DB::table('products as p')
                ->leftJoin('brands as br', 'br.id', '=', 'p.brand_id')
                ->leftJoin('categories as cat', 'cat.id', '=', 'p.category_id')
                ->whereNull('p.deleted_at')
                ->where('p.is_active', 1)
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($w) use ($search) {
                        $w->where('p.name', 'like', "%{$search}%")
                            ->orWhere('p.sku', 'like', "%{$search}%");
                    });
                })
                ->orderBy('p.name')
                ->select('p.id', 'p.name', 'p.sku', 'p.price', 'p.mrp', 'br.name as brand', 'cat.name as category')
                ->limit($limit)
                ->get();

            if ($rows->isEmpty()) {
                return $this->agentEmpty($search === ''
                    ? 'No active products found.'
                    : "No product matched search '{$search}'.");
            }

            return $this->agentJson([
                'ok' => true,
                'count' => $rows->count(),
                'price_note' => 'Prices are in BDT (Tk).',
                'products' => $rows->map(fn ($row) => [
                    'product_id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'sku' => (string) $row->sku,
                    'price' => $this->agentNumber($row->price),
                    'mrp' => $this->agentNumber($row->mrp),
                    'brand' => $row->brand ? (string) $row->brand : null,
                    'category' => $row->category ? (string) $row->category : null,
                ])->all(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while listing products.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Optional filter: part of a product name or sku.'),
        ];
    }
}
