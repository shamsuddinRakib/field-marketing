<div class="modal-header py-16 px-24 border-0" data-modal-key="courier-marketing-create">
    <h5 class="modal-title d-flex align-items-center gap-2">
        <iconify-icon icon="solar:box-bold" class="text-primary text-xl"></iconify-icon>
        Add Courier Dispatch
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
    <form action="{{ route('courier-marketings.store') }}" method="post" data-ajax="true" id="courierCreateForm">
        @csrf
        
        {{-- Section: Assignment Information --}}
        <div class="mb-20">
            <h6 class="text-sm fw-bold mb-12 text-dark">Assignment Information</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Marketing Representative <span class="text-danger">*</span></label>
                    @php
                        $userRep = auth()->user()->representative ?? \App\Models\backend\MarketingRepresentative::where('user_id', auth()->id())->first();
                    @endphp
                    <select name="marketing_representative_id" class="form-control radius-8 js-s2-ajax js-rep-select"
                        data-url="{{ route('marketing-representative.marketing-representatives.select2', ['use_rep_id' => 1]) }}"
                        data-placeholder="Select Representative" required>
                        @if ($userRep)
                            <option value="{{ $userRep->id }}" selected>{{ $userRep->user?->name ?? 'Representative' }} ({{ $userRep->employee_id }})</option>
                        @endif
                    </select>
                    <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Select Phone Number <span class="text-danger">*</span></label>
                    <select name="teacher_id" class="form-control radius-8 js-s2-ajax js-teacher-phone-select"
                        data-url="{{ route('teacher.teachers.select2') }}"
                        data-placeholder="Search by Phone Number" required></select>
                    <div class="invalid-feedback d-block teacher_id-error" style="display:none"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-sm mb-6 text-muted">Teacher Name</label>
                    <input type="text" id="autoTeacherName" class="form-control radius-8 bg-light" readonly placeholder="Teacher Name">
                </div>

                <div class="col-md-4">
                    <label class="form-label text-sm mb-6 text-muted">Institution Name</label>
                    <input type="text" id="autoInstitutionName" class="form-control radius-8 bg-light" readonly placeholder="Institution Name">
                </div>

                <div class="col-md-4">
                    <label class="form-label text-sm mb-6 text-muted">Location / Address</label>
                    <input type="text" id="autoLocationAddress" class="form-control radius-8 bg-light" readonly placeholder="Location & Address">
                </div>
            </div>
        </div>

        <hr class="border-neutral-200 my-20">

        {{-- Section: Products Dispatched --}}
        <div class="mb-20">
            <div class="d-flex align-items-center justify-content-between mb-12">
                <h6 class="text-sm fw-bold mb-0 text-dark">
                    Products Dispatched <span class="text-danger">*</span>
                </h6>
                <button type="button" class="btn btn-sm btn-outline-primary radius-8 d-flex align-items-center gap-1 py-4 px-12" id="btnAddProductRow">
                    <iconify-icon icon="ic:baseline-plus" class="text-base"></iconify-icon>
                    Add Another Product
                </button>
            </div>

            <div id="courierProductItemsContainer" class="d-flex flex-column gap-2">
                {{-- Initial Row --}}
                <div class="row g-2 align-items-center courier-product-row">
                    <div class="col-md-8">
                        <select name="product_items[0][product_id]" class="form-control radius-8 js-s2-ajax js-product-select"
                            data-url="{{ route('product.select2') }}"
                            data-placeholder="Select Book / Product..." required></select>
                        <div class="invalid-feedback d-block product_items.0.product_id-error" style="display:none"></div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-xs text-muted">Qty</span>
                            <input type="number" name="product_items[0][quantity]" class="form-control radius-8 text-center" value="1" min="1" required>
                        </div>
                        <div class="invalid-feedback d-block product_items.0.quantity-error" style="display:none"></div>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger radius-8 w-100 btn-remove-row" disabled title="Remove">
                            &times;
                        </button>
                    </div>
                </div>
            </div>
            <div class="invalid-feedback d-block product_items-error" style="display:none"></div>
        </div>

        <hr class="border-neutral-200 my-20">

        {{-- Section: Courier & Dispatch Information (Tracking omitted as requested) --}}
        <div class="mb-16">
            <h6 class="text-sm fw-bold mb-12 text-dark">Courier & Dispatch Details</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-sm mb-6">Courier Company</label>
                    <input type="text" name="courier_company" class="form-control radius-8" list="courierCompanyList" placeholder="e.g. Sundarban, Steadfast">
                    <datalist id="courierCompanyList">
                        <option value="Sundarban Courier Service">
                        <option value="Steadfast Courier">
                        <option value="SA Paribahan">
                        <option value="Pathao Courier">
                        <option value="RedX Logistics">
                        <option value="Karatoa Courier">
                        <option value="eCourier">
                    </datalist>
                    <div class="invalid-feedback d-block courier_company-error" style="display:none"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-sm mb-6">Dispatch Date <span class="text-danger">*</span></label>
                    <input type="date" name="dispatch_date" class="form-control radius-8" value="{{ old('dispatch_date', date('Y-m-d')) }}" required>
                    <div class="invalid-feedback d-block dispatch_date-error" style="display:none"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-sm mb-6">Expected Delivery Date</label>
                    <input type="date" name="delivery_date" class="form-control radius-8" value="{{ old('delivery_date') }}">
                    <div class="invalid-feedback d-block delivery_date-error" style="display:none"></div>
                </div>

                <div class="col-12">
                    <label class="form-label text-sm mb-6">Note / Remarks</label>
                    <textarea name="note" class="form-control radius-8" rows="2" placeholder="Write any dispatch note...">{{ old('note') }}</textarea>
                    <div class="invalid-feedback d-block note-error" style="display:none"></div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-center gap-3 mt-16 pt-12 border-top">
            <button type="button" class="btn border border-secondary-400 text-secondary-600 px-40 py-11 radius-8" data-bs-dismiss="modal">
                Cancel
            </button>
            <button type="submit" class="btn btn-primary px-48 py-12 radius-8">
                Save Dispatch
            </button>
        </div>
    </form>
</div>

{{-- Template for dynamic product row --}}
<template id="courier-product-row-template">
    <div class="row g-2 align-items-center courier-product-row">
        <div class="col-md-8">
            <select class="form-control radius-8 js-s2-ajax js-product-select"
                data-url="{{ route('product.select2') }}"
                data-placeholder="Select Book / Product..." required></select>
            <div class="invalid-feedback d-block js-error-product" style="display:none"></div>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text bg-light text-xs text-muted">Qty</span>
                <input type="number" class="form-control radius-8 text-center js-quantity-input" value="1" min="1" required>
            </div>
            <div class="invalid-feedback d-block js-error-qty" style="display:none"></div>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-outline-danger radius-8 w-100 btn-remove-row" title="Remove">
                &times;
            </button>
        </div>
    </div>
</template>
