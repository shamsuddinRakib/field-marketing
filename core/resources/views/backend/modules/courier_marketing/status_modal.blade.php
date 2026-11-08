<div class="modal-header py-16 px-24 border-0" data-modal-key="courier-marketing-status">
    <h5 class="modal-title d-flex align-items-center gap-2">
        <iconify-icon icon="lucide:phone-call" class="text-warning text-xl"></iconify-icon>
        Call Center & Delivery Verification #CM-{{ $courierMarketing->id }}
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
    {{-- Quick Info Card --}}
    <div class="p-16 bg-neutral-50 radius-8 border border-neutral-200 mb-20">
        <div class="row g-2">
            <div class="col-md-6">
                <span class="text-xs text-secondary d-block">Teacher</span>
                <span class="fw-semibold text-dark">{{ $courierMarketing->teacher?->teacher_name ?? '-' }}</span>
                @if ($courierMarketing->teacher?->phone)
                    <span class="badge bg-light text-primary border ms-1">{{ $courierMarketing->teacher->phone }}</span>
                @endif
            </div>
            <div class="col-md-6">
                <span class="text-xs text-secondary d-block">Institution</span>
                <span class="text-dark">{{ $courierMarketing->institution?->institution_name ?? ($courierMarketing->teacher?->institution?->institution_name ?? '-') }}</span>
            </div>
            <div class="col-md-6">
                <span class="text-xs text-secondary d-block">Courier Company</span>
                <span class="text-dark">{{ $courierMarketing->courier_company ?: '-' }}</span>
            </div>
            <div class="col-md-6">
                <span class="text-xs text-secondary d-block">Dispatch Date</span>
                <span class="text-dark">{{ $courierMarketing->dispatch_date?->format('d M, Y') ?? '-' }}</span>
            </div>
            <div class="col-12 mt-2 pt-2 border-top">
                <span class="text-xs text-secondary d-block mb-1">Products Dispatched:</span>
                <div class="d-flex flex-wrap gap-1">
                    @foreach ($courierMarketing->items as $item)
                        <span class="badge bg-white text-dark border px-8 py-4">
                            {{ $item->product?->name ?? 'Book' }} <strong class="text-primary">x {{ $item->quantity }}</strong>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('courier-marketings.updateStatus', $courierMarketing->id) }}" method="post" data-ajax="true">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Call / Delivery Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select radius-8" required>
                    <option value="received" {{ $courierMarketing->status === 'received' ? 'selected' : '' }}>
                        Confirmed / Received (টিচার বই হাতে পেয়েছেন)
                    </option>
                    <option value="in_transit" {{ $courierMarketing->status === 'in_transit' ? 'selected' : '' }}>
                        In Transit / Not Received Yet (এখনও পৌঁছায়নি)
                    </option>
                    <option value="partially_received" {{ $courierMarketing->status === 'partially_received' ? 'selected' : '' }}>
                        Partially Received (আংশিক পেয়েছেন)
                    </option>
                    <option value="failed" {{ $courierMarketing->status === 'failed' ? 'selected' : '' }}>
                        Call Attempted / No Answer (ফোন ধরেননি / ব্যস্ত ছিল)
                    </option>
                    <option value="returned" {{ $courierMarketing->status === 'returned' ? 'selected' : '' }}>
                        Returned / Rejected (পার্সেল ফেরত এসেছে)
                    </option>
                </select>
                <div class="invalid-feedback d-block status-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Delivery / Confirmation Date</label>
                <input type="date" name="delivery_date" class="form-control radius-8" value="{{ old('delivery_date', $courierMarketing->delivery_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
                <div class="invalid-feedback d-block delivery_date-error" style="display:none"></div>
            </div>

            <div class="col-12 mb-16">
                <label class="form-label text-sm mb-6">Teacher Feedback / Call Center Note</label>
                <textarea name="note" class="form-control radius-8" rows="3" placeholder="e.g. টিচারের সাথে কথা হয়েছে, তিনি বইগুলো পেয়েছেন এবং ধন্যবাদ জানিয়েছেন।"></textarea>
                <div class="invalid-feedback d-block note-error" style="display:none"></div>
            </div>

            @if (!empty($courierMarketing->note))
                <div class="col-12 mb-16">
                    <label class="form-label text-xs text-muted mb-4">Previous Call & Activity Notes:</label>
                    <div class="p-10 bg-light rounded text-xs text-secondary" style="white-space: pre-line; max-height: 120px; overflow-y: auto;">
                        {{ $courierMarketing->note }}
                    </div>
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center justify-content-center gap-3 mt-12">
            <button type="button" class="btn border border-secondary-400 text-secondary-600 px-32 py-10 radius-8" data-bs-dismiss="modal">
                Cancel
            </button>
            <button type="submit" class="btn btn-primary px-36 py-10 radius-8">
                Update Status
            </button>
        </div>
    </form>
</div>

