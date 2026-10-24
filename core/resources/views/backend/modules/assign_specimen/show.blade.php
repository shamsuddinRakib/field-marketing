{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Assignment #{{ $assignSpecimen->id }} Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    @php
        $customerType = $assignSpecimen->teacher_id ? 'teacher' : ($assignSpecimen->library_id ? 'library' : '—');
        $recipientName = $assignSpecimen->teacher_id
            ? (optional($assignSpecimen->teacher)->teacher_name ?? ('#' . $assignSpecimen->teacher_id))
            : ($assignSpecimen->library_id
                ? (optional($assignSpecimen->library)->library_name ?? ('#' . $assignSpecimen->library_id))
                : '—');
    @endphp

    {{-- ================= SUMMARY ================= --}}
    <div class="card mb-16">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Marketing Representative</small>
                    <strong>{{ optional($assignSpecimen->user)->name ?? ('#' . $assignSpecimen->user_id) }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    @if ($assignSpecimen->status === 'approved')
                        <span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Approved</span>
                    @else
                        <span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Pending</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Recipient Type</small>
                    <strong class="text-capitalize">{{ $customerType }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Recipient</small>
                    <strong>{{ $recipientName }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Note</small>
                    <span>{{ $assignSpecimen->note ?? '—' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Date</small>
                    <span>{{ $assignSpecimen->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= ITEMS ================= --}}
    <h6 class="mb-12 fw-semibold">Specimen Products ({{ $assignSpecimen->items->count() }})</h6>
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-end">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignSpecimen->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ optional($item->product)->name ?? ('#' . $item->product_id) }}</td>
                    <td class="text-end">{{ number_format((int) $item->quantity) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">No items found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-end">Total Quantity</th>
                <th class="text-end">{{ number_format((int) $assignSpecimen->items->sum('quantity')) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </div>

</div>
