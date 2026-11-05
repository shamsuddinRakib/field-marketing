<div class="modal-header py-16 px-24" data-modal-key="courier-marketing-show">
    <h5 class="modal-title fw-semibold">Courier Dispatch #{{ $courierMarketing->id }} Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body px-24 py-16">
    <div class="card mb-16">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Representative</small>
                    <strong>{{ $courierMarketing->representative?->user?->name ?? 'N/A' }}</strong>
                    @if ($courierMarketing->representative?->employee_id)
                        <span class="text-muted text-sm">({{ $courierMarketing->representative->employee_id }})</span>
                    @endif
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Teacher & Institution</small>
                    <strong>{{ $courierMarketing->teacher?->teacher_name ?? 'N/A' }}</strong>
                    @if ($courierMarketing->institution?->institution_name)
                        <span class="d-block text-muted text-sm">{{ $courierMarketing->institution->institution_name }}</span>
                    @endif
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Courier Company</small>
                    <strong>{{ $courierMarketing->courier_company ?: '—' }}</strong>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Tracking Number</small>
                    <strong>{{ $courierMarketing->tracking_number ?: '—' }}</strong>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Dispatch Date</small>
                    <span>{{ $courierMarketing->dispatch_date?->format('d M Y') ?? '—' }}</span>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Delivery Date</small>
                    <span>{{ $courierMarketing->delivery_date?->format('d M Y') ?? '—' }}</span>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    @php
                        $statusClass = match ($courierMarketing->status) {
                            'received' => 'success',
                            'partially_received' => 'info',
                            'dispatched', 'in_transit' => 'primary',
                            'failed', 'returned', 'rejected' => 'danger',
                            default => 'warning',
                        };
                    @endphp
                    <span class="bg-{{ $statusClass }}-focus text-{{ $statusClass }}-600 border border-{{ $statusClass }}-main px-12 py-4 radius-4 fw-medium text-sm">
                        {{ $courierMarketing->statusLabel() }}
                    </span>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Created By / Received By</small>
                    <span class="text-sm">
                        Created: <strong>{{ $courierMarketing->creator?->name ?? '—' }}</strong>
                        @if ($courierMarketing->receiver)
                            | Received: <strong>{{ $courierMarketing->receiver->name }}</strong>
                            ({{ $courierMarketing->received_at?->format('d M Y') }})
                        @endif
                    </span>
                </div>

                <div class="col-12">
                    <small class="text-muted d-block">Note</small>
                    <span>{{ $courierMarketing->note ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <h6 class="mb-12 fw-semibold">Dispatched Items ({{ $courierMarketing->items->count() }})</h6>
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-end">Dispatched Qty</th>
                <th class="text-end">Received Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($courierMarketing->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product?->name ?? ('#' . $item->product_id) }}</td>
                    <td>{{ $item->product?->sku ?? '—' }}</td>
                    <td class="text-end">{{ number_format((int) $item->quantity) }}</td>
                    <td class="text-end">{{ number_format((int) $item->received_quantity) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No products found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end">{{ number_format((int) $courierMarketing->items->sum('quantity')) }}</th>
                <th class="text-end">{{ number_format((int) $courierMarketing->items->sum('received_quantity')) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>