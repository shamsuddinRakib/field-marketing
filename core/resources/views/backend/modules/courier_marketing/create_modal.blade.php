<div class="modal-header py-16 px-24 border-0" data-modal-key="courier-marketing-create">
    <h5 class="modal-title d-flex align-items-center gap-2">
        <iconify-icon icon="solar:box-bold" class="text-primary text-xl"></iconify-icon>
        Add Courier Dispatch
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<style>
    /* Desktop & Tablet Modal Width */
    @media (min-width: 992px) {
        #AjaxModal .modal-dialog {
            max-width: 960px !important;
        }
    }
    @media (min-width: 1200px) {
        #AjaxModal .modal-dialog {
            max-width: 1050px !important;
        }
    }

    /* Mobile Modal Width & Spacing */
    @media (max-width: 767.98px) {
        #AjaxModal .modal-dialog {
            margin: 0.5rem auto !important;
            max-width: calc(100% - 1rem) !important;
            width: calc(100% - 1rem) !important;
        }
        #AjaxModal .modal-header {
            padding: 14px 16px !important;
        }
        #AjaxModal .modal-body {
            padding: 16px 14px !important;
        }
    }
</style>

<div class="modal-body p-24">
    <form action="{{ route('courier-marketings.store') }}" method="post" data-ajax="true" id="courierCreateForm">
        @csrf
        
        {{-- Section: Assignment Information --}}
        <div class="mb-20">
            <h6 class="text-sm fw-bold mb-12 text-dark">Assignment Information</h6>

            {{-- Hidden location & institution IDs --}}
            <input type="hidden" name="upazila_id" id="courierHiddenUpazilaId">
            <input type="hidden" name="district_id" id="courierHiddenDistrictId">
            <input type="hidden" name="division_id" id="courierHiddenDivisionId">
            <input type="hidden" name="institution_id" id="courierHiddenInstitutionId">

            <div class="row g-3">
                {{-- Row 1: Marketing Representative & Upazila --}}
                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Marketing Representative <span class="text-danger">*</span></label>
                    @php
                        $userRep = auth()->user()->representative ?? \App\Models\backend\MarketingRepresentative::where('user_id', auth()->id())->first();
                    @endphp
                    <select name="marketing_representative_id" id="courierRepSelect" class="form-control radius-8 js-s2-ajax js-rep-select"
                        data-url="{{ route('marketing-representative.marketing-representatives.select2', ['use_rep_id' => 1]) }}"
                        data-placeholder="Select Representative" required>
                        @if ($userRep)
                            <option value="{{ $userRep->id }}" selected>{{ $userRep->user?->name ?? 'Representative' }} ({{ $userRep->employee_id }})</option>
                        @endif
                    </select>
                    <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Upazila <span class="text-danger">*</span></label>
                    <select id="courierUpazilaSelect" class="form-control radius-8"
                        data-placeholder="Search / Select Upazila" required>
                    </select>
                    <div class="invalid-feedback d-block upazila_id-error" style="display:none"></div>
                </div>

                {{-- Row 2: District, Division & Institution --}}
                <div class="col-md-3">
                    <label class="form-label text-sm mb-6 text-muted">District</label>
                    <input type="text" id="courierDistrictDisplay" class="form-control radius-8 bg-light" readonly placeholder="District">
                </div>

                <div class="col-md-3">
                    <label class="form-label text-sm mb-6 text-muted">Division</label>
                    <input type="text" id="courierDivisionDisplay" class="form-control radius-8 bg-light" readonly placeholder="Division">
                </div>

                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Institution</label>
                    <select id="courierInstitutionSelect" class="form-control radius-8"
                        data-placeholder="Select Institution">
                    </select>
                    <div class="invalid-feedback d-block institution_id-error" style="display:none"></div>
                </div>

                {{-- Row 3: Teacher Phone, Teacher Name & Location --}}
                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Select Phone Number / Teacher <span class="text-danger">*</span></label>
                    <select name="teacher_id" id="courierTeacherPhoneSelect" class="form-control radius-8"
                        data-placeholder="Search by Phone or Teacher Name" required></select>
                    <div class="invalid-feedback d-block teacher_id-error" style="display:none"></div>
                </div>

                <div class="col-md-3">
                    <label class="form-label text-sm mb-6 text-muted">Teacher Name</label>
                    <input type="text" id="autoTeacherName" class="form-control radius-8 bg-light" readonly placeholder="Teacher Name">
                </div>

                <div class="col-md-3">
                    <label class="form-label text-sm mb-6 text-muted">Location / Address</label>
                    <input type="text" id="autoLocationAddress" class="form-control radius-8 bg-light" readonly placeholder="Location & Address">
                </div>
            </div>
        </div>

        <hr class="border-neutral-200 my-20">

        {{-- Section: Products Dispatched (Assign Specimen Style) --}}
        <div class="mb-20">
            <div class="table-responsive">
                <table class="table table-sm align-middle items-table-fixed mb-0" id="courierItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-end col-qty">Qty</th>
                            <th class="col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="courier-item-row">
                            <td>
                                <select name="items[0][product_id]"
                                    class="form-control form-control-sm js-s2-ajax courier-product-select"
                                    data-url="{{ route('product.select2') }}"
                                    data-placeholder="Select Product"
                                    placeholder="Select Product"
                                    required></select>
                            </td>
                            <td>
                                <input type="number" step="1" min="1" value="1"
                                    name="items[0][quantity]"
                                    class="form-control form-control-sm text-end courier-qty"
                                    required>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="invalid-feedback d-block product_items-error mt-4" style="display:none"></div>
            <div class="d-flex justify-content-end mb-8 mt-8">
                <button type="button" id="btnAddCourierItem" class="btn btn-sm btn-outline-primary">+ Add Item</button>
            </div>

            {{-- Summary --}}
            <div class="card mb-16 bg-light border-0">
                <div class="card-body py-12 px-20 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-sm fw-semibold">Total Quantity</h6>
                    <h5 class="mb-0 fw-bold"><span id="courierTotalText">1</span></h5>
                </div>
            </div>
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
