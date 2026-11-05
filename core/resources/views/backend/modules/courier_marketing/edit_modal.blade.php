<div class="modal-header py-16 px-24 border-0" data-modal-key="courier-marketing-edit">
    <h5 class="modal-title">Edit Courier Entry #{{ $courierMarketing->id }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
    <form action="{{ route('courier-marketings.update', $courierMarketing->id) }}" method="post" data-ajax="true">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Representative</label>
                <input type="text" class="form-control radius-8 bg-light"
                    value="{{ $courierMarketing->representative?->user?->name ?? ($courierMarketing->representative?->employee_id ?? 'N/A') }}"
                    disabled>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Teacher</label>
                <input type="text" class="form-control radius-8 bg-light"
                    value="{{ $courierMarketing->teacher?->teacher_name ?? 'N/A' }}"
                    disabled>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Courier Company</label>
                <input type="text" name="courier_company" class="form-control radius-8"
                    value="{{ old('courier_company', $courierMarketing->courier_company) }}">
                <div class="invalid-feedback d-block courier_company-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Tracking Number</label>
                <input type="text" name="tracking_number" class="form-control radius-8"
                    value="{{ old('tracking_number', $courierMarketing->tracking_number) }}">
                <div class="invalid-feedback d-block tracking_number-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Dispatch Date <span class="text-danger">*</span></label>
                <input type="date" name="dispatch_date" class="form-control radius-8"
                    value="{{ old('dispatch_date', $courierMarketing->dispatch_date?->format('Y-m-d')) }}" required>
                <div class="invalid-feedback d-block dispatch_date-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Delivery Date</label>
                <input type="date" name="delivery_date" class="form-control radius-8"
                    value="{{ old('delivery_date', $courierMarketing->delivery_date?->format('Y-m-d')) }}">
                <div class="invalid-feedback d-block delivery_date-error" style="display:none"></div>
            </div>

            <div class="col-12 mb-16">
                <label class="form-label text-sm mb-6">Dispatched Items</label>
                <div class="table-responsive">
                    <table class="table table-sm bordered-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Dispatched Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courierMarketing->items as $item)
                                <tr>
                                    <td>{{ $item->product?->name ?? ('#' . $item->product_id) }}</td>
                                    <td class="text-end">{{ number_format((int) $item->quantity) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No items found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12 mb-16">
                <label class="form-label text-sm mb-6">Note</label>
                <textarea name="note" class="form-control radius-8" rows="3">{{ old('note', $courierMarketing->note) }}</textarea>
                <div class="invalid-feedback d-block note-error" style="display:none"></div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-center gap-3 mt-12">
            <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button>
        </div>
    </form>
</div>