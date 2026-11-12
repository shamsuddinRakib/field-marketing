<div class="modal-header py-16 px-24" data-modal-key="book-request-show">
    <h5 class="modal-title fw-semibold">Book Request #{{ $bookRequest->id }} Details</h5>
   
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body px-24 py-16">
    <div class="card mb-16">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Representative</small>
                    <strong>{{ $bookRequest->representative?->user?->name ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Employee ID</small>
                    <strong>{{ $bookRequest->representative?->employee_id ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    @if ($bookRequest->status === 'approved')
                        <span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Approved</span>
                    @elseif ($bookRequest->status === 'rejected')
                        <span class="badge text-sm fw-semibold bg-dark-danger-gradient px-20 py-9 radius-4 text-white">Rejected</span>
                    @else
                        <span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Pending</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Date</small>
                    <span>{{ $bookRequest->request_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="col-12">
                    <small class="text-muted d-block">Note</small>
                    <span>{{ $bookRequest->note ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <h6 class="mb-12 fw-semibold">Requested Items ({{ $bookRequests->count() }})</h6>
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-end">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookRequests as $index => $requestItem)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $requestItem->product?->name ?? ('#' . $requestItem->product_id) }}</td>
                    <td>{{ $requestItem->product?->sku ?? '—' }}</td>
                    <td class="text-end">{{ number_format((int) $requestItem->quantity) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">No products found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total Quantity</th>
                <th class="text-end">{{ number_format((int) $bookRequests->sum('quantity')) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>
