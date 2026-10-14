<div class="modal-header py-16 px-24" data-modal-key="book-return-show">
    <h5 class="modal-title fw-semibold">Book Return #{{ $bookReturn->id }} Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body px-24 py-16">
    <div class="card mb-16">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Representative</small>
                    <strong>{{ $bookReturn->representative?->user?->name ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Employee ID</small>
                    <strong>{{ $bookReturn->representative?->employee_id ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Institution</small>
                    <strong>{{ $bookReturn->institution?->institution_name ?: ($bookReturn->institution?->name ?? '—') }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    @if ($bookReturn->status === 'received')
                        <span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Received</span>
                    @elseif ($bookReturn->status === 'partial')
                        <span class="badge text-sm fw-semibold bg-dark-info-gradient px-20 py-9 radius-4 text-white">Partially Returned</span>
                    @elseif ($bookReturn->status === 'rejected')
                        <span class="badge text-sm fw-semibold bg-dark-danger-gradient px-20 py-9 radius-4 text-white">Rejected</span>
                    @else
                        <span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Pending</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Received By</small>
                    <span>{{ $bookReturn->received_by ?: '—' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Received Date</small>
                    <span>{{ $bookReturn->received_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="col-12">
                    <small class="text-muted d-block">Note</small>
                    <span>{{ $bookReturn->note ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <h6 class="mb-12 fw-semibold">Returned Items ({{ $bookReturns->count() }})</h6>
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-end">Issued Qty</th>
                <th class="text-end">Returned Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookReturns as $index => $returnItem)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $returnItem->product?->name ?? ('#' . $returnItem->product_id) }}</td>
                    <td class="text-end">{{ number_format((int) $returnItem->issued_quantity) }}</td>
                    <td class="text-end">{{ number_format((int) $returnItem->returned_quantity) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">No return items found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-end">Total</th>
                <th class="text-end">{{ number_format((int) $bookReturns->sum('issued_quantity')) }}</th>
                <th class="text-end">{{ number_format((int) $bookReturns->sum('returned_quantity')) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>
