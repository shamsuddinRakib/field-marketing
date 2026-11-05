<div class="modal-header py-16 px-24 border-0" data-modal-key="courier-marketing-create">
    <h5 class="modal-title">Add Courier Dispatch</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
    <form action="{{ route('courier-marketings.store') }}" method="post" data-ajax="true">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Approved Book Request <span class="text-danger">*</span></label>
                <select name="book_request_id" class="form-control radius-8 js-book-request-select" required></select>
                <div class="invalid-feedback d-block book_request_id-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Teacher <span class="text-danger">*</span></label>
                <select name="teacher_id" class="form-control radius-8 js-teacher-select" required></select>
                <div class="invalid-feedback d-block teacher_id-error" style="display:none"></div>
            </div>

            <div class="col-12 mb-16">
                <label class="form-label text-sm mb-6">Products <span class="text-danger">*</span></label>
                <div class="row g-2 courier-marketing-product-items">
                    <div class="col-12 text-muted text-sm py-8">
                        Select an approved Book Request to load products.
                    </div>
                </div>
                <div class="invalid-feedback d-block product_items-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Courier Company</label>
                <input type="text" name="courier_company" class="form-control radius-8" placeholder="e.g. Sundarban, Steadfast">
                <div class="invalid-feedback d-block courier_company-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Tracking Number</label>
                <input type="text" name="tracking_number" class="form-control radius-8" placeholder="Enter tracking number">
                <div class="invalid-feedback d-block tracking_number-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Dispatch Date <span class="text-danger">*</span></label>
                <input type="date" name="dispatch_date" class="form-control radius-8" value="{{ old('dispatch_date', date('Y-m-d')) }}" required>
                <div class="invalid-feedback d-block dispatch_date-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Expected Delivery Date</label>
                <input type="date" name="delivery_date" class="form-control radius-8" value="{{ old('delivery_date') }}">
                <div class="invalid-feedback d-block delivery_date-error" style="display:none"></div>
            </div>

            <div class="col-12 mb-16">
                <label class="form-label text-sm mb-6">Note</label>
                <textarea name="note" class="form-control radius-8" rows="3" placeholder="Write anything about this dispatch">{{ old('note') }}</textarea>
                <div class="invalid-feedback d-block note-error" style="display:none"></div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-center gap-3 mt-12">
            <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Save</button>
        </div>
    </form>
</div>

<template id="courier-marketing-product-template">
    <div class="col-12 row g-2 align-items-start courier-marketing-product-row mb-8">
        <div class="col-md-8">
            <input type="text" class="form-control radius-8 js-product-name bg-light" readonly>
            <input type="hidden" class="js-book-request-item-id">
            <div class="invalid-feedback d-block js-item-id-error" style="display:none"></div>
        </div>
        <div class="col-md-3">
            <input type="number" class="form-control radius-8 js-quantity-input" value="1" min="1" required>
            <small class="text-muted js-dist-hint d-block mt-4" style="font-size:11px;">Available: —</small>
            <div class="invalid-feedback d-block js-qty-error" style="display:none"></div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger radius-8 w-100 btn-remove-product" aria-label="Remove product">&times;</button>
        </div>
    </div>
</template>