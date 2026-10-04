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
                <label class="form-label text-sm mb-6">Institution Name</label>
                <select name="institution_id" class="form-control radius-8 js-institution-select">
                    @if ($bookReturn->institution_id)
                        <option value="{{ $bookReturn->institution_id }}" selected>
                            {{ ($bookReturn->institution?->institution_name ?: $bookReturn->institution?->name) ?? 'Institution' }}
                            @if ($bookReturn->institution?->code) ({{ $bookReturn->institution->code }}) @endif
                        </option>
                    @endif
                </select>
                <div class="invalid-feedback d-block institution_id-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Product Name <span class="text-danger">*</span></label>
                <select name="product_id" class="form-control radius-8 js-product-select" required>
                    @if ($bookReturn->product_id)
                        <option value="{{ $bookReturn->product_id }}" selected>
                            {{ $bookReturn->product?->name ?? '#' . $bookReturn->product_id }}</option>
                    @endif
                </select>
                <div class="invalid-feedback d-block product_id-error" style="display:none"></div>
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
                <label class="form-label text-sm mb-6">Issued Quantity <span class="text-danger">*</span></label>
                <input type="number" name="issued_quantity" class="form-control radius-8"
                    value="{{ old('issued_quantity', $bookReturn->issued_quantity) }}" min="1" required>
                <div class="invalid-feedback d-block issued_quantity-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Returned Quantity <span class="text-danger">*</span></label>
                <input type="number" name="returned_quantity" class="form-control radius-8"
                    value="{{ old('returned_quantity', $bookReturn->returned_quantity) }}" min="0" required>
                <div class="invalid-feedback d-block returned_quantity-error" style="display:none"></div>
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
