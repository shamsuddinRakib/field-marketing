{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Spot Sale #{{ $spotSale->id }} Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    @php
        $customerType = $spotSale->customerType() ?? '—';
        $customerName = $spotSale->customerName();
        $customerPhone = $spotSale->customerPhone();
        $customer = $spotSale->customer;

        $contextInfo = null;
        if ($customerType === 'teacher') {
            $inst = $customer?->institution?->institution_name ?? $spotSale->teacher?->institution?->institution_name;
            if ($inst) $contextInfo = ['label' => 'Institution', 'value' => $inst];
        } elseif ($customerType === 'library') {
            $addr = $customer?->address ?? $spotSale->library?->address;
            if ($addr) $contextInfo = ['label' => 'Address / Market', 'value' => $addr];
        } elseif ($customerType === 'student') {
            $cls = $customer?->courseClass?->name ?? $spotSale->student?->courseClass?->name;
            $inst = $customer?->institution?->institution_name ?? $spotSale->student?->institution?->institution_name;
            $parts = array_filter([$cls ? "Class: {$cls}" : null, $inst ? "Institution: {$inst}" : null]);
            if (!empty($parts)) $contextInfo = ['label' => 'Student Info', 'value' => implode(' | ', $parts)];
        }
    @endphp

    {{-- ================= SUMMARY ================= --}}
    <div class="card mb-16">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">User</small>
                    <strong>{{ optional($spotSale->user)->name ?? '#' . $spotSale->user_id }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Status</small>
                    @if ($spotSale->status === 'approved')
                        <span
                            class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Approved</span>
                    @else
                        <span
                            class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Pending</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Customer Type</small>
                    <strong class="text-capitalize">{{ $customerType }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Customer</small>
                    <strong>{{ $customerName }}</strong>
                    @if ($customerPhone && $customerPhone !== '—')
                        <div class="text-xs text-muted"><i class="ri-phone-line"></i> {{ $customerPhone }}</div>
                    @endif
                </div>
                @if ($contextInfo)
                    <div class="col-md-6">
                        <small class="text-muted d-block">{{ $contextInfo['label'] }}</small>
                        <span>{{ $contextInfo['value'] }}</span>
                    </div>
                @endif
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
                    <td>{{ optional($item->product)->name ?? '#' . $item->product_id }}</td>
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
            @php
                $rawSubtotal = $spotSale->subtotal !== null ? (float) $spotSale->subtotal : (float) $spotSale->items->sum('total');
                $discount = (float) ($spotSale->discount ?? 0);
            @endphp
            <tr>
                <th colspan="3" class="text-end">Subtotal:</th>
                <th class="text-end">{{ number_format((int) $spotSale->items->sum('quantity')) }}</th>
                <th class="text-end">৳ {{ number_format((float) $rawSubtotal, 2) }}</th>
            </tr>
            @if ($discount > 0)
                <tr>
                    <th colspan="4" class="text-end text-danger">Discount:</th>
                    <th class="text-end text-danger">(-) ৳ {{ number_format($discount, 2) }}</th>
                </tr>
            @endif
            <tr class="table-active">
                <th colspan="4" class="text-end fw-bold">Grand Total:</th>
                <th class="text-end fw-bold text-primary-600">৳ {{ number_format((float) $spotSale->total, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
            Close
        </button>
    </div>

</div>
