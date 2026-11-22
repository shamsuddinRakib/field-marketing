{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Edit Assignment #{{ $assignSpecimen->id }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

@php
    $firstItem = $assignSpecimen->items->first();
    $currentCategory = $firstItem?->product?->category;
    $currentSubcategory = $firstItem?->product?->subcategory;
@endphp

<style>
    @media (min-width: 992px) {
        #AjaxModal .modal-dialog.modal-lg {
            max-width: 860px;
        }
    }

    /* Specimen Items Table Clean Pixel Alignment */
    #specimenItemsTable {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }
    #specimenItemsTable thead th {
        vertical-align: middle !important;
        padding: 8px 6px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
    }
    #specimenItemsTable tbody tr {
        vertical-align: middle !important;
    }
    #specimenItemsTable tbody td {
        vertical-align: middle !important;
        padding: 6px 6px !important;
        line-height: 1 !important;
    }
    #specimenItemsTable .col-qty {
        width: 120px !important;
    }
    #specimenItemsTable .col-action {
        width: 54px !important;
    }
    @media (max-width: 575.98px) {
        #specimenItemsTable .col-qty {
            width: 88px !important;
        }
        #specimenItemsTable .col-action {
            width: 48px !important;
        }
    }
    #specimenItemsTable select.specimen-product,
    #specimenItemsTable .specimen-qty {
        display: block !important;
        width: 100% !important;
        height: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        line-height: 36px !important;
        padding: 0 10px !important;
        margin: 0 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        font-size: 0.875rem !important;
        box-sizing: border-box !important;
        background-color: #fff !important;
        vertical-align: middle !important;
    }
    #specimenItemsTable .select2-container {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
        height: auto !important;
        line-height: 1 !important;
        vertical-align: middle !important;
    }
    #specimenItemsTable .select2-container .selection {
        display: block !important;
        width: 100% !important;
        height: auto !important;
        line-height: 1 !important;
    }
    #specimenItemsTable .select2-container .dropdown-wrapper {
        display: none !important;
    }
    #specimenItemsTable .select2-container--default .select2-selection--single {
        height: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        background-color: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
        box-sizing: border-box !important;
        position: relative !important;
    }
    #specimenItemsTable .select2-container--default .select2-selection--single .select2-selection__rendered {
        display: block !important;
        width: 100% !important;
        height: 36px !important;
        line-height: 36px !important;
        padding-left: 10px !important;
        padding-right: 30px !important;
        margin: 0 !important;
        font-size: 0.875rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    #specimenItemsTable .select2-selection__placeholder {
        display: inline !important;
    }
    #specimenItemsTable .select2-container--open .select2-selection--single,
    #specimenItemsTable .specimen-qty:focus {
        border-color: var(--primary-600, #487fff) !important;
        outline: 0 !important;
    }
    #specimenItemsTable .select2-container--default .select2-selection--single .select2-selection__clear {
        height: 36px !important;
        line-height: 36px !important;
        margin: 0 4px 0 0 !important;
        padding: 0 4px !important;
        float: right !important;
        display: inline-flex !important;
        align-items: center !important;
        font-size: 16px !important;
    }
    #specimenItemsTable .select2-container--default .select2-selection--single .select2-selection__arrow {
        position: absolute !important;
        height: 36px !important;
        top: 0 !important;
        right: 6px !important;
        width: 20px !important;
    }
    #specimenItemsTable .btnRemoveRow {
        width: 38px !important;
        min-width: 38px !important;
        max-width: 38px !important;
        height: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        padding: 0 !important;
        margin: 0 auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        border-radius: 6px !important;
        box-sizing: border-box !important;
        vertical-align: middle !important;
    }
</style>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="assignSpecimenEditForm" action="{{ route('assign-specimen.update', $assignSpecimen->id) }}" method="POST" data-ajax="true">

        @csrf
        @method('PUT')

        @php
            $customerType = $assignSpecimen->teacher_id ? 'teacher' : ($assignSpecimen->library_id ? 'library' : '');
            $teacher = $assignSpecimen->teacher;
            $institution = $teacher?->institution;
            $upazila = $assignSpecimen->upazila ?? ($teacher?->upazila ?? ($institution?->upazila ?? ($assignSpecimen->library?->upazila)));
            $district = $assignSpecimen->district ?? ($teacher?->district ?? ($institution?->district ?? ($assignSpecimen->library?->district)));
            $division = $assignSpecimen->division ?? ($teacher?->division ?? ($institution?->division ?? ($assignSpecimen->library?->division)));

            $teacherPhone = $teacher?->phone ?? '';
            $teacherName = $teacher?->teacher_name ?? '';
            $institutionName = $institution?->institution_name ?? '';
            $upazilaId = $upazila?->id ?? '';
            $upazilaName = $upazila?->upazila_name ?? '';
            $districtId = $district?->district_id ?? '';
            $districtName = $district?->district_name ?? '';
            $divisionId = $division?->id ?? '';
            $divisionName = $division?->name ?? ($division?->bn_name ?? '');
            $address = $assignSpecimen->address ?: ($teacher?->address ?: ($institution?->address ?: ($assignSpecimen->library?->address ?? '')));
        @endphp

        {{-- ================= ASSIGN INFO ================= --}}
        <div class="card mb-16">
            <div class="card-body">

                <h6 class="mb-12 fw-semibold">Assignment Information</h6>

                {{-- Hidden Location IDs --}}
                <input type="hidden" name="upazila_id" id="specimenHiddenUpazilaId" value="{{ $upazilaId }}">
                <input type="hidden" name="district_id" id="specimenHiddenDistrictId" value="{{ $districtId }}">
                <input type="hidden" name="division_id" id="specimenHiddenDivisionId" value="{{ $divisionId }}">

                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="specimenRepSelect"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($assignSpecimen->user)
                                <option value="{{ $assignSpecimen->user->id }}" selected>{{ $assignSpecimen->user->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Recipient Type <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="customer_type" id="specimenCustomerType" required>
                            <option value="">Select Type</option>
                            <option value="teacher" {{ $customerType === 'teacher' ? 'selected' : '' }}>Teacher</option>
                            <option value="library" {{ $customerType === 'library' ? 'selected' : '' }}>Library</option>
                        </select>
                    </div>

                    {{-- LIBRARY ONLY --}}
                    <div class="col-md-12 mb-12" id="specimenLibraryWrap" style="display:{{ $customerType === 'library' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Select Library <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="library_id" id="specimenLibrarySelect"
                            data-url="{{ route('library.libraries.select2') }}"
                            data-placeholder="Select Library">
                            @if ($assignSpecimen->library)
                                <option value="{{ $assignSpecimen->library->id }}" selected>{{ $assignSpecimen->library->library_name }}</option>
                            @endif
                        </select>
                    </div>

                    {{-- TEACHER CASCADE FIELDS WRAP --}}
                    <div class="col-12" id="specimenTeacherFieldsWrap" style="display:{{ $customerType === 'teacher' ? 'block' : 'none' }}">
                        <div class="row g-3">
                            {{-- Row 1: Location Drilldown --}}
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Upazila</label>
                                <select class="form-control form-control-sm" id="specimenUpazilaSelect"
                                    data-placeholder="Search / Select Upazila">
                                    @if ($upazilaId)
                                        <option value="{{ $upazilaId }}" selected>{{ $upazilaName }}</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">District</label>
                                <input type="text" id="specimenDistrictDisplay"
                                    class="form-control form-control-sm radius-8 bg-light" value="{{ $districtName }}" placeholder="District" readonly>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Division</label>
                                <input type="text" id="specimenDivisionDisplay"
                                    class="form-control form-control-sm radius-8 bg-light" value="{{ $divisionName }}" placeholder="Division" readonly>
                            </div>

                            {{-- Row 2: Institution & Teacher/Phone --}}
                            <div class="col-md-6 mb-12">
                                <label class="form-label text-sm mb-8">Institution</label>
                                <select class="form-control form-control-sm" id="specimenInstitutionSelect"
                                    data-placeholder="Select Institution">
                                    @if ($institution)
                                        <option value="{{ $institution->id }}" selected>{{ $institution->institution_name }}</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-6 mb-12">
                                <label class="form-label text-sm mb-8">Search / Select Teacher <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="teacher_id" id="specimenTeacherPhoneSelect"
                                    data-placeholder="Search by Phone or Name">
                                    @if ($teacher)
                                        <option value="{{ $teacher->id }}" selected>
                                            {{ $teacherPhone ?: '—' }}{{ $teacherName ? " ({$teacherName})" : '' }}{{ $institutionName ? " - {$institutionName}" : '' }}
                                        </option>
                                    @endif
                                </select>
                            </div>

                            {{-- Row 3: Teacher Name, Institution Name, Inst Type --}}
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Teacher Name</label>
                                <input type="text" id="specimenTeacherName" class="form-control form-control-sm radius-8 bg-light"
                                    value="{{ $teacherName }}" placeholder="Teacher Name" readonly>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Institution Name</label>
                                <input type="text" id="specimenInstitutionName" class="form-control form-control-sm radius-8 bg-light"
                                    value="{{ $institutionName }}" placeholder="Institution Name" readonly>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Type <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="assign_inst_type" id="specimenAssignInstType">
                                    <option value="">Select Type</option>
                                    <option value="school" {{ $assignSpecimen->assign_inst_type === 'school' ? 'selected' : '' }}>School</option>
                                    <option value="college" {{ $assignSpecimen->assign_inst_type === 'college' ? 'selected' : '' }}>College</option>
                                    <option value="coaching" {{ $assignSpecimen->assign_inst_type === 'coaching' ? 'selected' : '' }}>Coaching</option>
                                    <option value="batch" {{ $assignSpecimen->assign_inst_type === 'batch' ? 'selected' : '' }}>Batch</option>
                                </select>
                            </div>

                            {{-- Row 4: Address --}}
                            <div class="col-12 mb-12">
                                <label class="form-label text-sm mb-8">Address</label>
                                <input type="text" name="address" id="specimenAddress" class="form-control form-control-sm radius-8"
                                    value="{{ $address }}" placeholder="Teacher / Institution Address">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-sm">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            value="{{ $assignSpecimen->note }}" placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- ================= CATEGORY & SUBCATEGORY SELECTION ================= --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Product Filter (Category & Subcategory)</h6>
                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Category <span class="text-danger">*</span></label>
                        <select id="specimenCategorySelect" class="form-control form-control-sm js-s2-ajax"
                            data-url="{{ route('category.cat.select2') }}"
                            data-placeholder="Select Category">
                            @if ($currentCategory)
                                <option value="{{ $currentCategory->id }}" selected>{{ $currentCategory->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Subcategory <span class="text-danger">*</span></label>
                        <select id="specimenSubcategorySelect" class="form-control form-control-sm"
                            data-placeholder="Select Subcategory" {{ $currentCategory ? '' : 'disabled' }}>
                            @if ($currentSubcategory)
                                <option value="{{ $currentSubcategory->id }}" selected>{{ $currentSubcategory->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SPECIMEN ITEMS ================= --}}
        <div class="card mb-16">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-12">
                    <h6 class="mb-0 fw-semibold">Products</h6>
                    <span class="text-xs {{ $currentSubcategory ? 'text-success' : 'text-muted' }}" id="specimenSelectionHint">
                        {{ $currentSubcategory ? 'Showing products for selected subcategory' : 'Please select a Category and Subcategory above to enable products' }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle items-table-fixed" id="specimenItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end col-qty" style="width:120px">Qty</th>
                                <th class="text-center col-action" style="width:54px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignSpecimen->items as $i => $item)
                                <tr class="specimen-item-row">
                                    <td>
                                        <select name="items[{{ $i }}][product_id]"
                                            class="form-control form-control-sm specimen-product"
                                            data-placeholder="Select Product" required>
                                            @if ($item->product)
                                                <option value="{{ $item->product->id }}" selected>{{ $item->product->name }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" name="items[{{ $i }}][quantity]"
                                            class="form-control form-control-sm text-end specimen-qty"
                                            value="{{ $item->quantity }}" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="specimen-item-row">
                                    <td>
                                        <select name="items[0][product_id]"
                                            class="form-control form-control-sm specimen-product"
                                            data-placeholder="Select Product" disabled required></select>
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" value="1"
                                            name="items[0][quantity]" class="form-control form-control-sm text-end specimen-qty"
                                            required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-12">
                    <button type="button" id="btnAddSpecimenItem" class="btn btn-sm btn-outline-primary" {{ $currentSubcategory ? '' : 'disabled' }}>+ Add Item</button>
                </div>
            </div>
        </div>

        {{-- ================= SUMMARY ================= --}}
        <div class="card mb-16 bg-light border-0">
            <div class="card-body py-12 px-20 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-sm fw-semibold">Total Quantity</h6>
                <h5 class="mb-0 fw-bold"><span id="specimenTotalText">{{ $assignSpecimen->items->sum('quantity') }}</span></h5>
            </div>
        </div>

        {{-- ================= ACTION ================= --}}
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-primary">Update Pending</button>
        </div>

    </form>
</div>

{{-- ================= JS ================= --}}
<script>
    window.AssignSpecimenIndex = window.AssignSpecimenIndex || { onLoad: function () {} };

    (function () {
        const $modal = $('#AjaxModal');
        const $table = $('#specimenItemsTable tbody');
        let rowIndex = {{ $assignSpecimen->items->count() }};

        function toggleRecipient() {
            const type = $('#specimenCustomerType').val();
            const isTeacher = type === 'teacher';
            const isLibrary = type === 'library';

            $('#specimenTeacherFieldsWrap').toggle(isTeacher);
            $('#specimenLibraryWrap').toggle(isLibrary);

            if (!isTeacher) {
                $('#specimenUpazilaSelect').val(null).trigger('change');
                $('#specimenDistrictDisplay').val('');
                $('#specimenDivisionDisplay').val('');
                $('#specimenInstitutionSelect').val(null).trigger('change');
                $('#specimenTeacherPhoneSelect').val(null).trigger('change');
                $('#specimenTeacherName').val('');
                $('#specimenInstitutionName').val('');
                $('#specimenAssignInstType').val('');
                $('#specimenAddress').val('');
                $('#specimenHiddenUpazilaId').val('');
                $('#specimenHiddenDistrictId').val('');
                $('#specimenHiddenDivisionId').val('');
            }
            if (!isLibrary) {
                $('#specimenLibrarySelect').val(null).trigger('change');
            }
        }

        $('#specimenCustomerType').on('change', toggleRecipient);

        // Helper to auto-detect institution type
        function autoDetectInstType(name) {
            if (!name) return;
            const lower = name.toLowerCase();
            const $type = $('#specimenAssignInstType');
            if ($type.val()) return;

            if (lower.includes('college') || lower.includes('কলেজ')) {
                $type.val('college');
            } else if (lower.includes('school') || lower.includes('স্কুল') || lower.includes('বিদ্যালয়') || lower.includes('মাদ্রাসা') || lower.includes('madrasah')) {
                $type.val('school');
            } else if (lower.includes('coaching') || lower.includes('কোচিং')) {
                $type.val('coaching');
            } else if (lower.includes('batch') || lower.includes('ব্যাচ')) {
                $type.val('batch');
            }
        }

        // 1. Upazila Select2 (filtered by selected marketing representative)
        $('#specimenUpazilaSelect').select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: 'Search / Select Upazila',
            allowClear: true,
            ajax: {
                url: "{{ route('assign-specimen.upazilas.select2') }}",
                dataType: 'json',
                delay: 200,
                cache: false,
                data: function(params) {
                    return {
                        q: params.term || '',
                        user_id: $('#specimenRepSelect').val() || ''
                    };
                },
                processResults: data => data
            }
        }).on('select2:select', function(e) {
            const data = e.params.data || {};
            const upzId = data.id || '';
            $('#specimenHiddenUpazilaId').val(upzId);
            $('#specimenHiddenDistrictId').val(data.district_id || '');
            $('#specimenHiddenDivisionId').val(data.division_id || '');
            $('#specimenDistrictDisplay').val(data.district_name || '');
            $('#specimenDivisionDisplay').val(data.division_name || '');

            // Reset downstream institution and teacher
            $('#specimenInstitutionSelect').empty().val(null).trigger('change');
            $('#specimenTeacherPhoneSelect').empty().val(null).trigger('change');
            $('#specimenTeacherName').val('');
            $('#specimenInstitutionName').val('');
        }).on('select2:clear', function() {
            $('#specimenHiddenUpazilaId').val('');
            $('#specimenHiddenDistrictId').val('');
            $('#specimenHiddenDivisionId').val('');
            $('#specimenDistrictDisplay').val('');
            $('#specimenDivisionDisplay').val('');
            $('#specimenInstitutionSelect').empty().val(null).trigger('change');
            $('#specimenTeacherPhoneSelect').empty().val(null).trigger('change');
            $('#specimenTeacherName').val('');
            $('#specimenInstitutionName').val('');
        });

        // When representative changes, reset Upazila and downstream fields
        $('#specimenRepSelect').off('change.repCascade').on('change.repCascade', function() {
            $('#specimenUpazilaSelect').empty().val(null).trigger('change');
            $('#specimenHiddenUpazilaId').val('');
            $('#specimenHiddenDistrictId').val('');
            $('#specimenHiddenDivisionId').val('');
            $('#specimenDistrictDisplay').val('');
            $('#specimenDivisionDisplay').val('');
            $('#specimenInstitutionSelect').empty().val(null).trigger('change');
            $('#specimenTeacherPhoneSelect').empty().val(null).trigger('change');
            $('#specimenTeacherName').val('');
            $('#specimenInstitutionName').val('');
            $('#specimenAddress').val('');
        });

        // 2. Institution Select2 (strictly filtered by selected upazila)
        $('#specimenInstitutionSelect').select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: 'Select Institution',
            allowClear: true,
            ajax: {
                url: "{{ route('assign-specimen.institutions.select2') }}",
                dataType: 'json',
                delay: 200,
                cache: false,
                data: function(params) {
                    return {
                        q: params.term || '',
                        upazila_id: $('#specimenHiddenUpazilaId').val() || $('#specimenUpazilaSelect').val() || ''
                    };
                },
                processResults: data => data
            }
        }).on('select2:select', function(e) {
            const data = e.params.data || {};
            $('#specimenInstitutionName').val(data.text || '');

            // If Upazila not selected, auto-fill from institution
            if (!$('#specimenUpazilaSelect').val() && data.upazila_id) {
                const opt = new Option(data.upazila_name, data.upazila_id, true, true);
                $('#specimenUpazilaSelect').append(opt).trigger('change.select2');
                $('#specimenHiddenUpazilaId').val(data.upazila_id || '');
                $('#specimenHiddenDistrictId').val(data.district_id || '');
                $('#specimenHiddenDivisionId').val(data.division_id || '');
                $('#specimenDistrictDisplay').val(data.district_name || '');
                $('#specimenDivisionDisplay').val(data.division_name || '');
            }

            if (data.address && !$('#specimenAddress').val()) {
                $('#specimenAddress').val(data.address);
            }

            autoDetectInstType(data.text || '');

            // Reset teacher selection
            $('#specimenTeacherPhoneSelect').empty().val(null).trigger('change');
            $('#specimenTeacherName').val('');
        }).on('select2:clear', function() {
            $('#specimenInstitutionName').val('');
            $('#specimenTeacherPhoneSelect').empty().val(null).trigger('change');
            $('#specimenTeacherName').val('');
        });

        // 3. Teacher / Phone Select2 (filtered by upazila_id & institution_id, or searchable directly)
        $('#specimenTeacherPhoneSelect').select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: 'Search by Phone or Name',
            allowClear: true,
            ajax: {
                url: "{{ route('assign-specimen.teachersByPhone.select2') }}",
                dataType: 'json',
                delay: 200,
                cache: false,
                data: function(params) {
                    return {
                        q: params.term || '',
                        upazila_id: $('#specimenHiddenUpazilaId').val() || $('#specimenUpazilaSelect').val() || '',
                        institution_id: $('#specimenInstitutionSelect').val() || ''
                    };
                },
                processResults: data => data
            }
        }).on('select2:select', function(e) {
            const data = e.params.data || {};
            $('#specimenTeacherName').val(data.teacher_name || '');
            if (data.institution_name) {
                $('#specimenInstitutionName').val(data.institution_name);
            }

            // Auto-fill address
            if (data.address) {
                $('#specimenAddress').val(data.address);
            }

            // If Upazila not selected, auto-fill from teacher
            if (!$('#specimenUpazilaSelect').val() && data.upazila_id) {
                const opt = new Option(data.upazila_name, data.upazila_id, true, true);
                $('#specimenUpazilaSelect').append(opt).trigger('change.select2');
                $('#specimenHiddenUpazilaId').val(data.upazila_id || '');
                $('#specimenHiddenDistrictId').val(data.district_id || '');
                $('#specimenHiddenDivisionId').val(data.division_id || '');
                $('#specimenDistrictDisplay').val(data.district_name || '');
                $('#specimenDivisionDisplay').val(data.division_name || '');
            }

            // If Institution not selected, auto-fill from teacher
            if (!$('#specimenInstitutionSelect').val() && data.institution_id) {
                const opt = new Option(data.institution_name, data.institution_id, true, true);
                $('#specimenInstitutionSelect').append(opt).trigger('change.select2');
            }

            autoDetectInstType(data.institution_name || '');
        }).on('select2:clear', function() {
            $('#specimenTeacherName').val('');
            if (!$('#specimenInstitutionSelect').val()) {
                $('#specimenInstitutionName').val('');
            }
        });

        // 4. Library select listener
        $('#specimenLibrarySelect').on('select2:select', function(e) {
            const data = e.params.data || {};
            if (data.upazila_id) $('#specimenHiddenUpazilaId').val(data.upazila_id);
            if (data.district_id) $('#specimenHiddenDistrictId').val(data.district_id);
            if (data.division_id) $('#specimenHiddenDivisionId').val(data.division_id);
            if (data.address) $('#specimenAddress').val(data.address);
        }).on('select2:clear', function() {
            $('#specimenHiddenUpazilaId').val('');
            $('#specimenHiddenDistrictId').val('');
            $('#specimenHiddenDivisionId').val('');
        });

        // ================= CASCADING CATEGORY -> SUBCATEGORY -> PRODUCT =================
        const $categorySelect = $('#specimenCategorySelect');
        const $subcategorySelect = $('#specimenSubcategorySelect');
        const $btnAddItem = $('#btnAddSpecimenItem');
        const $hint = $('#specimenSelectionHint');
        let rowIndex = {{ max($assignSpecimen->items->count(), 1) }};

        const subcategoryUrl = "{{ route('subcategory.select2') }}";
        const productUrl = "{{ route('product.select2') }}";

        function parentModal() {
            return $modal.length ? $modal : $(document.body);
        }

        function initSubcategorySelect2() {
            if ($subcategorySelect.hasClass('select2-hidden-accessible')) {
                $subcategorySelect.select2('destroy');
            }

            $subcategorySelect.prop('disabled', false).select2({
                dropdownParent: parentModal(),
                width: '100%',
                placeholder: 'Select Subcategory',
                allowClear: true,
                ajax: {
                    url: subcategoryUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            category_id: $categorySelect.val(),
                            type: $categorySelect.val()
                        };
                    },
                    processResults: function (data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });
        }

        function initProductSelect2($select) {
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            const currentSubcategory = $subcategorySelect.val();

            if (!currentSubcategory && !$select.val()) {
                $select.prop('disabled', true).empty();
                return;
            }

            $select.prop('disabled', false).select2({
                dropdownParent: parentModal(),
                width: '100%',
                placeholder: 'Select Product',
                allowClear: true,
                ajax: {
                    url: productUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            category_id: $categorySelect.val(),
                            subcategory_id: $subcategorySelect.val()
                        };
                    },
                    processResults: function (data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });
        }

        function updateAllProductSelects() {
            const hasSubcategory = !!$subcategorySelect.val();
            if (hasSubcategory) {
                $btnAddItem.prop('disabled', false);
                $hint.text('Showing products for selected subcategory').removeClass('text-muted').addClass('text-success');
                $table.find('.specimen-product').each(function () {
                    initProductSelect2($(this));
                });
            } else {
                $btnAddItem.prop('disabled', true);
                $hint.text('Please select a Category and Subcategory above to enable products').removeClass('text-success').addClass('text-muted');
                $table.find('.specimen-product').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).empty().prop('disabled', true);
                });
            }
        }

        // Initialize existing subcategory & products if loaded
        if ($categorySelect.val()) {
            initSubcategorySelect2();
        }
        $table.find('.specimen-product').each(function () {
            initProductSelect2($(this));
        });

        // On Category change -> reset Subcategory & Products
        $categorySelect.on('change', function () {
            const catId = $(this).val();

            if ($subcategorySelect.hasClass('select2-hidden-accessible')) {
                $subcategorySelect.select2('destroy');
            }
            $subcategorySelect.empty().prop('disabled', true);

            // Reset table rows
            $table.find('tr:gt(0)').remove();
            const $firstRowProduct = $table.find('tr:first .specimen-product');
            if ($firstRowProduct.hasClass('select2-hidden-accessible')) {
                $firstRowProduct.select2('destroy');
            }
            $firstRowProduct.empty().prop('disabled', true);
            $table.find('tr:first .specimen-qty').val(1);
            calculateTotal();

            if (catId) {
                initSubcategorySelect2();
            } else {
                updateAllProductSelects();
            }
        });

        // On Subcategory change -> reload product selects
        $subcategorySelect.on('change', function () {
            $table.find('tr:gt(0)').remove();
            const $firstRowProduct = $table.find('tr:first .specimen-product');
            if ($firstRowProduct.hasClass('select2-hidden-accessible')) {
                $firstRowProduct.select2('destroy');
            }
            $firstRowProduct.empty();
            $table.find('tr:first .specimen-qty').val(1);
            calculateTotal();

            updateAllProductSelects();
        });

        // Add Item row
        $('#btnAddSpecimenItem').on('click', function () {
            if (!$subcategorySelect.val()) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Select Subcategory',
                        text: 'Please select a Subcategory first to add products.'
                    });
                }
                return;
            }

            const newRowHtml = `
                <tr class="specimen-item-row">
                    <td>
                        <select name="items[${rowIndex}][product_id]" class="form-control form-control-sm specimen-product"
                            data-placeholder="Select Product" required></select>
                    </td>
                    <td>
                        <input type="number" step="1" min="1" value="1" name="items[${rowIndex}][quantity]"
                            class="form-control form-control-sm text-end specimen-qty" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                    </td>
                </tr>
            `;

            const $newRow = $(newRowHtml);
            $table.append($newRow);
            initProductSelect2($newRow.find('.specimen-product'));

            rowIndex++;
            calculateTotal();
        });

        // Remove row
        $table.on('click', '.btnRemoveRow', function () {
            if ($table.find('tr').length === 1) {
                const $firstRowProduct = $table.find('tr:first .specimen-product');
                $firstRowProduct.val(null).trigger('change');
                $table.find('tr:first .specimen-qty').val(1);
            } else {
                $(this).closest('tr').remove();
            }
            calculateTotal();
        });

        // Quantity input
        $table.on('input', '.specimen-qty', function () {
            calculateTotal();
        });

        function calculateTotal() {
            let total = 0;
            $('.specimen-qty').each(function () {
                total += parseInt($(this).val() || 0, 10);
            });
            $('#specimenTotalText').text(total);
        }

        calculateTotal();

        // Auto initialize normal S2 components
        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto($modal.length ? $modal : undefined);
        }
    })();
</script>
