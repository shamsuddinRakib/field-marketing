{{-- MODAL HEADER --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Expense #{{ $expense->id }} Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- MODAL BODY --}}
<div class="modal-body px-24 py-16">

    {{-- SUMMARY --}}
    <div class="card mb-16">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Marketing Representative</small>
                    <div>
                        <strong>Name:</strong>
                        {{ optional($expense->user)->name ?? ('#' . $expense->user_id) }}
                    </div>
                    <div class="text-muted small">
                        <strong>Phone:</strong>
                        {{ optional($expense->user)->phone ?? '—' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    @if ($expense->status === 'posted')
                        <span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Posted</span>
                    @else
                        <span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Draft</span>
                    @endif
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Expense Name</small>
                    <strong>{{ $expense->name ?? '—' }}</strong>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Expense Date</small>
                    <span>{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') : '—' }}</span>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Route / Location</small>
                    <div>
                        <span class="text-muted small">From:</span> <strong>{{ $expense->from_location ?: '—' }}</strong>
                    </div>
                    <div>
                        <span class="text-muted small">To:</span> <strong>{{ $expense->to_location ?: '—' }}</strong>
                    </div>
                </div>

                <div class="col-md-6">
                    <small class="text-muted d-block">Attachment</small>
                    @if ($expense->attachment)
                        <a href="{{ asset($expense->attachment) }}" target="_blank"
                            class="d-inline-flex align-items-center gap-1 text-primary text-decoration-none">
                            <iconify-icon icon="mdi:file-download-outline"></iconify-icon>
                            <span>View Attachment</span>
                        </a>
                    @else
                        <span>—</span>
                    @endif
                </div>

                @if ($expense->reference)
                    <div class="col-md-6">
                        <small class="text-muted d-block">Reference</small>
                        <span>{{ $expense->reference }}</span>
                    </div>
                @endif

                @if ($expense->description)
                    <div class="col-md-6">
                        <small class="text-muted d-block">Description</small>
                        <span>{{ $expense->description }}</span>
                    </div>
                @endif

                @if ($expense->posted_at)
                    <div class="col-md-6">
                        <small class="text-muted d-block">Posted At</small>
                        <span>{{ \Carbon\Carbon::parse($expense->posted_at)->format('d M Y, h:i A') }}</span>
                    </div>
                @endif

                <div class="col-md-6">
                    <small class="text-muted d-block">Created At</small>
                    <span>{{ $expense->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ITEMS --}}
    <h6 class="mb-12 fw-semibold">Expense Items ({{ $expense->items->count() }})</h6>
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 50px">#</th>
                <th style="width: 30%">Category</th>
                <th>Description</th>
                <th class="text-end" style="width: 140px">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($expense->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ optional($item->category)->name ?? '—' }}</td>
                    <td>{{ $item->description ?? '—' }}</td>
                    <td class="text-end">{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">No items found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total Amount</th>
                <th class="text-end">{{ number_format((float) $expense->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    {{-- ACTION --}}
    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <a href="{{ route('expenses.invoice', $expense->id) }}" target="_blank"
            class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
            <iconify-icon icon="mdi:printer"></iconify-icon> Print Invoice
        </a>
    </div>

</div>

