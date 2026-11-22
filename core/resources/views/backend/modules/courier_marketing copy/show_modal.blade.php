<div class="modal-header py-16 px-24 border-0" data-modal-key="courier-marketing-show">
    <h5 class="modal-title fw-semibold d-flex align-items-center gap-2">
        <iconify-icon icon="solar:box-bold" class="text-primary text-xl"></iconify-icon>
        Courier Dispatch #CM-{{ $courierMarketing->id }} Details
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body px-24 py-16">
    <div class="card border border-neutral-200 mb-16 radius-8">
        <div class="card-body p-20">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Representative</small>
                    <strong class="text-dark">{{ $courierMarketing->representative?->user?->name ?? 'N/A' }}</strong>
                    @if ($courierMarketing->representative?->employee_id)
                        <span class="text-muted text-sm">({{ $courierMarketing->representative->employee_id }})</span>
                    @endif
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Teacher & Institution</small>
                    <strong class="text-dark">{{ $courierMarketing->teacher?->teacher_name ?? 'N/A' }}</strong>
                    @if ($courierMarketing->teacher?->phone)
                        <span class="badge bg-light text-primary border ms-1">{{ $courierMarketing->teacher->phone }}</span>
                    @endif
                    @if ($courierMarketing->institution?->institution_name)
                        <span class="d-block text-muted text-sm mt-1">{{ $courierMarketing->institution->institution_name }}</span>
                    @endif
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Courier Company</small>
                    <strong class="text-dark">{{ $courierMarketing->courier_company ?: '—' }}</strong>
                </div>



                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Dispatch Date</small>
                    <span>{{ $courierMarketing->dispatch_date?->format('d M, Y') ?? '—' }}</span>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Delivery / Confirmed Date</small>
                    <span>{{ $courierMarketing->delivery_date?->format('d M, Y') ?? '—' }}</span>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Status</small>
                    @php
                        $statusClass = match ($courierMarketing->status) {
                            'received'           => 'success',
                            'partially_received' => 'info',
                            'dispatched', 'in_transit' => 'primary',
                            'failed', 'returned' => 'danger',
                            default              => 'warning',
                        };
                    @endphp
                    <span class="bg-{{ $statusClass }}-focus text-{{ $statusClass }}-600 border border-{{ $statusClass }}-main px-12 py-4 radius-4 fw-medium text-sm">
                        {{ $courierMarketing->statusLabel() }}
                    </span>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Audit Log</small>
                    <span class="text-xs text-secondary">
                        Created by: <strong>{{ $courierMarketing->creator?->name ?? '—' }}</strong>
                        @if ($courierMarketing->receiver)
                            <br>Verified by: <strong>{{ $courierMarketing->receiver->name }}</strong>
                            ({{ $courierMarketing->received_at?->format('d M, Y h:i A') }})
                        @endif
                    </span>
                </div>

                @if (trim((string) $courierMarketing->note) !== '')
                    <div class="col-12 mt-2 pt-2 border-top">
                        <small class="text-muted d-block mb-1">Call Center & Activity Notes:</small>
                        <div class="p-10 bg-light rounded text-xs text-dark" style="white-space: pre-line;">{{ trim((string) $courierMarketing->note) }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <h6 class="mb-12 fw-semibold">Dispatched Products ({{ $courierMarketing->items->count() }})</h6>
    <div class="table-responsive">
        <table class="table table-sm bordered-table align-middle text-sm">
            <thead class="table-light">
                <tr>
                    <th style="width: 45px;">#</th>
                    <th>Product Name</th>
                    <th>Product Code</th>
                    <th class="text-end">Dispatched Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courierMarketing->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="fw-medium">{{ $item->product?->name ?? ('#' . $item->product_id) }}</td>
                        <td>{{ $item->product?->product_code ?: '—' }}</td>
                        <td class="text-end fw-semibold text-primary">{{ number_format((int) $item->quantity) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-12">No products found for this dispatch.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal-footer border-0 py-12 px-24">
    <button type="button" class="btn btn-secondary px-32 py-10 radius-8" data-bs-dismiss="modal">Close</button>
</div>
