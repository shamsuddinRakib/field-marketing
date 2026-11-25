<div class="modal-header" >
    <p class="modal-title fw-bold text-xl">Change Distribution Status</p>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form action="{{ route('product-distribution.updateStatus', $productDistribution->id) }}" method="POST" data-ajax="true">
    @csrf
    <div class="modal-body">
        <p class="mb-8 text-sm">To: <strong>{{ optional($productDistribution->user)->name ?? ('#' . $productDistribution->user_id) }}</strong>
            &nbsp;|&nbsp; Type: <span class="badge {{ ($productDistribution->distribution_type ?? 'specimen') === 'sale' ? 'bg-success-600' : 'bg-info-600' }} text-white px-8 py-2 radius-4">{{ ($productDistribution->distribution_type ?? 'specimen') === 'sale' ? 'Sale Copy' : 'Specimen Copy' }}</span>
            &nbsp;|&nbsp; Total Qty: <strong>{{ number_format((int) $productDistribution->items->sum('quantity')) }}</strong></p>
        <ul class="mb-12 text-sm">
            @foreach ($productDistribution->items as $item)
                <li>{{ optional($item->product)->name ?? ('#' . $item->product_id) }} — <strong>{{ (int) $item->quantity }}</strong></li>
            @endforeach
        </ul>
        <div class="alert alert-warning py-8 px-12 text-sm">Delivering will add these quantities to MR {{ ($productDistribution->distribution_type ?? 'specimen') === 'sale' ? 'Sale Stock' : 'Specimen Stock' }} and write stock ledger entries. This cannot be undone.</div>
        <div class="mb-3">
            <label for="status" class="form-label">Select Status</label>
            <select name="status" id="status" class="form-select">
                <option value="pending" {{ $productDistribution->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="delivered" {{ $productDistribution->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-info">Update Status</button>
    </div>
</form>