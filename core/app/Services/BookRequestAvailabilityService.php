<?php

namespace App\Services;

use App\Models\backend\BookRequestItem;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\ProductDistribution;
use Illuminate\Support\Facades\DB;

class BookRequestAvailabilityService
{
    public function forRepresentative(
        int $representativeId,
        ?string $excludeRequestGroupId = null,
        ?int $excludeRequestId = null
    ): array {
        $representative = MarketingRepresentative::find($representativeId);
        if (! $representative) {
            return [];
        }

        // 1. Query item-level distributed quantities for DELIVERED distributions
        $distributedFromItems = DB::table('product_distribution_items as pdi')
            ->join('product_distributions as pd', 'pd.id', '=', 'pdi.product_distribution_id')
            ->where('pd.user_id', (string) $representative->user_id)
            ->where('pd.status', 'delivered')
            ->selectRaw('pdi.product_id, SUM(pdi.quantity) as distributed_quantity')
            ->groupBy('pdi.product_id')
            ->pluck('distributed_quantity', 'pdi.product_id');

        // 2. Fallback: also support legacy master records where items table was not used
        $legacyDistributed = ProductDistribution::query()
            ->where('user_id', (string) $representative->user_id)
            ->where('status', 'delivered')
            ->whereNotNull('product_id')
            ->whereDoesntHave('items')
            ->selectRaw('product_id, SUM(quantity) as distributed_quantity')
            ->groupBy('product_id')
            ->pluck('distributed_quantity', 'product_id');

        $distributed = [];
        foreach ($distributedFromItems as $productId => $qty) {
            $distributed[(int) $productId] = (int) $qty;
        }
        foreach ($legacyDistributed as $productId => $qty) {
            $distributed[(int) $productId] = ($distributed[(int) $productId] ?? 0) + (int) $qty;
        }

        // 3. Subtract requested quantities from active (non-rejected) book requests
        $requestedQuery = BookRequestItem::query()
            ->join('book_requests as br', 'br.id', '=', 'book_request_items.book_request_id')
            ->where('br.marketing_representative_id', $representativeId)
            ->where('br.status', '!=', 'rejected')
            ->whereNull('br.product_distribution_id')
            ->when($excludeRequestGroupId, fn ($query) => $query->where(function ($builder) use ($excludeRequestGroupId) {
                $builder->whereNull('br.request_group_id')
                    ->orWhere('br.request_group_id', '!=', $excludeRequestGroupId);
            }))
            ->when($excludeRequestId, fn ($query) => $query->where('br.id', '!=', $excludeRequestId))
            ->selectRaw('book_request_items.product_id, SUM(book_request_items.quantity) as requested_quantity')
            ->groupBy('book_request_items.product_id');

        $requested = $requestedQuery->get()->pluck('requested_quantity', 'product_id');
        $availability = [];

        foreach ($distributed as $productId => $distributedQuantity) {
            $requestedQuantity = (int) ($requested[$productId] ?? 0);
            $distributedQuantity = (int) $distributedQuantity;

            $availability[(int) $productId] = [
                'distributed_quantity' => $distributedQuantity,
                'requested_quantity'   => $requestedQuantity,
                'available_quantity'   => max(0, $distributedQuantity - $requestedQuantity),
            ];
        }

        return $availability;
    }
}