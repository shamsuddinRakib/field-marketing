<div class="modal-header py-16 px-24 border-0" data-modal-key="book-return-edit">
    <h5 class="modal-title">Edit Book Return</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
    <form action="{{ route('book-return.book-returns.update', $bookReturn->id) }}" method="post"
        data-ajax="true">
        @csrf
        @method('PUT')
        @if ($bookReturn->return_group_id)
            <input type="hidden" name="return_group_id" value="{{ $bookReturn->return_group_id }}">
        @endif
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
            @if ($bookReturn->return_group_id)
                <div class="col-md-6 mb-16">
                    <label class="form-label text-sm mb-6">Book Request Date <span class="text-danger">*</span></label>
                    <input type="date" name="request_date" class="form-control radius-8"
                        value="{{ old('request_date', $bookReturns->first()?->bookRequest?->request_date?->format('Y-m-d')) }}" required>
                    <div class="invalid-feedback d-block request_date-error" style="display:none"></div>
                </div>
                <div class="col-12 mb-16">
                    <label class="form-label text-sm mb-6">Products <span class="text-danger">*</span> <small class="text-muted">(from Distribution List)</small></label>
                    <div class="row g-2 book-return-items">
                        @foreach ($bookReturns as $index => $returnItem)
                            <div class="col-12 row g-2 align-items-start book-return-item-row">
                                <div class="col-md-6">
                                    <select name="items[{{ $index }}][product_distribution_id]" class="form-control radius-8 js-distribution-select" required>
                                        @if ($returnItem->product_distribution_id)
                                            <option value="{{ $returnItem->product_distribution_id }}" data-issued-quantity="{{ $returnItem->issued_quantity }}" selected>
                                                {{ $returnItem->productDistribution?->product?->name ?? $returnItem->bookRequest?->product?->name ?? $returnItem->product?->name ?? '#' . $returnItem->product_id }} (Requested: {{ $returnItem->issued_quantity }}, Remaining: {{ max(0, $returnItem->issued_quantity) }}) [Dist#{{ $returnItem->product_distribution_id }}]
                                            </option>
                                        @elseif ($returnItem->book_request_id)
                                            <option value="" data-book-request-id="{{ $returnItem->book_request_id }}" data-issued-quantity="{{ $returnItem->issued_quantity }}" selected>
                                                {{ $returnItem->bookRequest?->product?->name ?? $returnItem->product?->name ?? '#' . $returnItem->product_id }} (Requested: {{ $returnItem->issued_quantity }}) [Req#{{ $returnItem->book_request_id }}]
                                            </option>
                                        @endif
                                    </select>
                                    <div class="invalid-feedback d-block items.{{ $index }}.product_distribution_id-error" style="display:none"></div>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control radius-8 js-issued-quantity" value="{{ $returnItem->issued_quantity }}" placeholder="Issued" readonly>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="items[{{ $index }}][returned_quantity]" class="form-control radius-8 js-returned-quantity"
                                        value="{{ $returnItem->returned_quantity }}" min="1" required>
                                    <div class="invalid-feedback d-block items.{{ $index }}.returned_quantity-error" style="display:none"></div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger radius-8 w-100 btn-remove-return-item" aria-label="Remove product" @disabled($loop->first && $loop->last)>&times;</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="invalid-feedback d-block items-error" style="display:none"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-8 btn-add-return-item">Add product</button>
                </div>
            @else
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
            @endif
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Status</label>
                <select name="status" class="form-select radius-8">
                    @foreach (\App\Models\backend\BookReturn::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $bookReturn->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @unless ($bookReturn->return_group_id)
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
            @endunless
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
@if ($bookReturn->return_group_id)
    <template id="book-return-item-template">
        <div class="col-12 row g-2 align-items-start book-return-item-row">
            <div class="col-md-6">
                <select class="form-control radius-8 js-distribution-select" required></select>
                <div class="invalid-feedback d-block" style="display:none"></div>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control radius-8 js-issued-quantity" placeholder="Issued" readonly>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control radius-8 js-returned-quantity" value="1" min="1" required>
                <div class="invalid-feedback d-block" style="display:none"></div>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger radius-8 w-100 btn-remove-return-item" aria-label="Remove product">&times;</button>
            </div>
        </div>
    </template>
@endif
