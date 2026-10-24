<div class="modal-header">
    <p class="modal-title fw-bold text-xl">Approve Specimen Assignment (#{{ $assignSpecimen->id }})</p>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form action="{{ route('assign-specimen.updateStatus', $assignSpecimen->id) }}" method="POST" data-ajax="true">
    @csrf
    <div class="modal-body">
        @php
            $recipientName = $assignSpecimen->teacher_id
                ? (optional($assignSpecimen->teacher)->teacher_name ?? ('#' . $assignSpecimen->teacher_id))
                : (optional($assignSpecimen->library)->library_name ?? ('#' . $assignSpecimen->library_id));
        @endphp
        <p class="mb-8 text-sm">MR: <strong>{{ optional($assignSpecimen->user)->name ?? ('#' . $assignSpecimen->user_id) }}</strong>
            &nbsp;|&nbsp; To: <strong>{{ $recipientName }}</strong>
            &nbsp;|&nbsp; Total Qty: <strong>{{ number_format((int) $assignSpecimen->items->sum('quantity')) }}</strong></p>
        <ul class="mb-12 text-sm">
            @foreach ($assignSpecimen->items as $item)
                <li>{{ optional($item->product)->name ?? ('#' . $item->product_id) }} — <strong>{{ (int) $item->quantity }}</strong></li>
            @endforeach
        </ul>
        <div class="alert alert-warning py-8 px-12 text-sm">Approving will deduct these quantities from MR current stock and write stock ledger (OUT) entries. Fails if stock is insufficient. This cannot be undone.</div>
        <div class="mb-3">
            <label for="status" class="form-label">Select Status</label>
            <select name="status" id="status" class="form-select">
                <option value="pending" {{ $assignSpecimen->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ $assignSpecimen->status == 'approved' ? 'selected' : '' }}>Approved</option>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-info">Update Status</button>
    </div>
</form>
