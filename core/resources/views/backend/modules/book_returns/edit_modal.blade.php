<div class="modal-header py-16 px-24 border-0" data-modal-key="book-return-edit">
    <h5 class="modal-title">Edit Book Return</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
    <form action="{{ route('book-return.book-returns.update', $bookReturn->id) }}" method="post"
        data-ajax="true">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Representative Name <span class="text-danger">*</span></label>
                <select name="marketing_representative_id" class="form-control radius-8 js-representative-select"
                    required>
                    @if ($bookReturn->marketing_representative_id)
                        <option value="{{ $bookReturn->marketing_representative_id }}" selected>
                            @if ($bookReturn->representative?->user?->name)
                                {{ $bookReturn->representative->user->name }}
                            @else
                                {{ $bookReturn->representative?->employee_id ?: '#' . $bookReturn->marketing_representative_id }}
                            @endif
                        </option>
                    @endif
                </select>
                <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Return Type <span class="text-danger">*</span></label>
                <select name="return_type" class="form-select radius-8 js-return-type-select" required>
                    <option value="specimen" @selected(old('return_type', $bookReturn->return_type ?? 'specimen') === 'specimen')>Specimen Copy</option>
                    <option value="sale" @selected(old('return_type', $bookReturn->return_type) === 'sale')>Sale Copy</option>
                </select>
                <div class="invalid-feedback d-block return_type-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Product Name (MR In-Hand Stock) <span class="text-danger">*</span></label>
                <select name="product_id" class="form-control radius-8 js-product-select" required>
                    @if ($bookReturn->product_id)
                        <option value="{{ $bookReturn->product_id }}" selected>
                            {{ $bookReturn->product?->name ?? '#' . $bookReturn->product_id }}</option>
                    @endif
                </select>
                <div class="invalid-feedback d-block product_id-error" style="display:none"></div>
                <div class="js-stock-indicator mt-1" style="display:none;">
                    <span class="badge bg-info-subtle text-info-700 border border-info-subtle text-xs px-8 py-3">
                        In-Hand Available: <strong class="js-stock-val">0</strong>
                    </span>
                </div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Return Quantity <span class="text-danger">*</span></label>
                <input type="number" name="returned_quantity" class="form-control radius-8 js-return-qty-input"
                    value="{{ old('returned_quantity', $bookReturn->returned_quantity) }}" min="1" required>
                <div class="invalid-feedback d-block returned_quantity-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Status</label>
                <select name="status" class="form-select radius-8">
                    @foreach (\App\Models\backend\BookReturn::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $bookReturn->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Received By</label>
                <input type="text" name="received_by" class="form-control radius-8"
                    value="{{ old('received_by', $bookReturn->received_by) }}" placeholder="Who received the books">
                <div class="invalid-feedback d-block received_by-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Received Date</label>
                <input type="date" name="received_date" class="form-control radius-8"
                    value="{{ old('received_date', $bookReturn->received_date?->format('Y-m-d')) }}">
                <div class="invalid-feedback d-block received_date-error" style="display:none"></div>
            </div>
            <div class="col-12 mb-16">
                <label class="form-label text-sm mb-6">Note</label>
                <textarea name="note" class="form-control radius-8" rows="3">{{ old('note', $bookReturn->note) }}</textarea>
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
