{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Spot Sale #{{ $spotSale->id }} Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    @php
        $customerType = $spotSale->teacher_id ? 'teacher' : ($spotSale->library_id ? 'library' : '—');
        $customerName = $spotSale->teacher_id
            ? (optional($spotSale->teacher)->teacher_name ?? ('#' . $spotSale->teacher_id))
            : ($spotSale->library_id
                ? (optional($spotSale->library)->library_name ?: optional($spotSale->library)->name ?? ('#' . $spotSale->library_id))
                : '—');
    @endphp

    {{-- ================= SUMMARY ================= --}}
    <div class="card mb-16">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">User</small>
                    <strong>{{ optional($spotSale->user)->name ?? ('#' . $spotSale->user_id) }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    @if ($spotSale->status === 'approved')
                        <span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Approved</span>
                    @else
                        <span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Pending</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Customer Type</small>
                    <strong class="text-capitalize">{{ $customerType }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Customer</small>
                    <strong>{{ $customerName }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Note</small>
                    <span>{{ $spotSale->note ?? '—' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Date</small>
                    <span>{{ $spotSale->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= ITEMS ================= --}}
    <h6 class="mb-12 fw-semibold">Ordered Items ({{ $spotSale->items->count() }})</h6>
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-end">Price</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($spotSale->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ optional($item->product)->name ?? ('#' . $item->product_id) }}</td>
                    <td class="text-end">{{ number_format((float) $item->price, 2) }}</td>
                    <td class="text-end">{{ number_format((int) $item->quantity) }}</td>
                    <td class="text-end">{{ number_format((float) $item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No items found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Grand Total</th>
                <th class="text-end">{{ number_format((int) $spotSale->items->sum('quantity')) }}</th>
                <th class="text-end">{{ number_format((float) $spotSale->total, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
            Close
        </button>
    </div>

</div>
