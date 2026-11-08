{{-- MODAL HEADER --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Assign Specimen</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<style>
    @media (min-width: 992px) {
        #AjaxModal .modal-dialog.modal-lg {
            max-width: 860px;
        }
    }
</style>

{{-- MODAL BODY --}}
<div class="modal-body px-24 py-16">

    <form id="assignSpecimenCreateForm" action="{{ route('assign-specimen.store') }}" method="POST" data-ajax="true">

        @csrf

        {{-- ASSIGN INFO --}}
        <div class="card mb-16 ">
            <div class="card-body ">

                <h6 class="mb-12 fw-semibold">Assignment Information</h6>

                {{-- Hidden Location IDs --}}
                <input type="hidden" name="upazila_id" id="specimenHiddenUpazilaId">
                <input type="hidden" name="district_id" id="specimenHiddenDistrictId">
                <input type="hidden" name="division_id" id="specimenHiddenDivisionId">

                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span
                                class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Representative" required>
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Recipient Type <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="customer_type" id="specimenCustomerType"
                            required>
                            <option value="">Select Type</option>
                            <option value="teacher">Teacher</option>
                            <option value="library">Library</option>
                        </select>
                    </div>

                    {{-- LIBRARY ONLY --}}
                    <div class="col-md-12 mb-12" id="specimenLibraryWrap" style="display:none">
                        <label class="form-label text-sm mb-8">Select Library <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="library_id"
                            id="specimenLibrarySelect" data-url="{{ route('library.libraries.select2') }}"
                            data-placeholder="Select Library">
                        </select>
                    </div>

                    {{-- TEACHER CASCADE FIELDS WRAP --}}
                    <div class="col-12" id="specimenTeacherFieldsWrap" style="display:none">
                        <div class="row g-3">
                            {{-- Row 1: Location Drilldown --}}
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Upazila</label>
                                <select class="form-control form-control-sm" id="specimenUpazilaSelect"
                                    data-placeholder="Search / Select Upazila">
                                </select>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">District</label>
                                <input type="text" id="specimenDistrictDisplay"
                                    class="form-control form-control-sm radius-8 bg-light" placeholder="District" readonly>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Division</label>
                                <input type="text" id="specimenDivisionDisplay"
                                    class="form-control form-control-sm radius-8 bg-light" placeholder="Division" readonly>
                            </div>

                            {{-- Row 2: Institution & Teacher/Phone --}}
                            <div class="col-md-6 mb-12">
                                <label class="form-label text-sm mb-8">Institution</label>
                                <select class="form-control form-control-sm" id="specimenInstitutionSelect"
                                    data-placeholder="Select Institution">
                                </select>
                            </div>

                            <div class="col-md-6 mb-12">
                                <label class="form-label text-sm mb-8">Search / Select Teacher <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="teacher_id"
                                    id="specimenTeacherPhoneSelect"
                                    data-placeholder="Search by Phone or Name">
                                </select>
                            </div>

                            {{-- Row 3: Teacher Name, Institution Name, Inst Type --}}
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Teacher Name</label>
                                <input type="text" id="specimenTeacherName"
                                    class="form-control form-control-sm radius-8 bg-light" placeholder="Teacher Name" readonly>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Institution Name</label>
                                <input type="text" id="specimenInstitutionName"
                                    class="form-control form-control-sm radius-8 bg-light" placeholder="Institution Name"
                                    readonly>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Type <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="assign_inst_type"
                                    id="specimenAssignInstType">
                                    <option value="">Select Type</option>
                                    <option value="school">School</option>
                                    <option value="college">College</option>
                                    <option value="coaching">Coaching</option>
                                    <option value="batch">Batch</option>
                                </select>
                            </div>

                            {{-- Row 4: Address --}}
                            <div class="col-12 mb-12">
                                <label class="form-label text-sm mb-8">Address</label>
                                <input type="text" name="address" id="specimenAddress" class="form-control form-control-sm radius-8"
                                    placeholder="Address">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-sm">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- SPECIMEN ITEMS --}}
        <div class="table-responsive">
            <table class="table table-sm align-middle items-table-fixed" id="specimenItemsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:75%">Product</th>
                        <th class="text-end" style="width:140px">Qty</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="specimen-item-row">
                        <td>
                            <select name="items[0][product_id]"
                                class="form-control form-control-sm js-s2-ajax specimen-product"
                                data-url="{{ route('product.select2') }}" data-placeholder="Select Product"
                                required></select>
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
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mb-8">
            <button type="button" id="btnAddSpecimenItem" class="btn btn-sm btn-outline-primary">+ Add Item</button>
        </div>

        {{-- SUMMARY --}}
        <div class="card mb-16 bg-light border-0">
            <div class="card-body py-12 px-20 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-sm fw-semibold">Total Quantity</h6>
                <h5 class="mb-0 fw-bold"><span id="specimenTotalText">1</span></h5>
            </div>
        </div>

        {{-- ACTION --}}
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-primary">Save as Pending</button>
        </div>

    </form>
</div>

{{-- JS HOOKS --}}
<script>
    window.AssignSpecimenIndex = window.AssignSpecimenIndex || {
        onLoad: function() {}
    };

    (function() {
        const $modal = $('#AjaxModal');
        const $table = $('#specimenItemsTable tbody');
        let rowIndex = 1;

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

        // 1. Upazila Select2
        $('#specimenUpazilaSelect').select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: 'Search / Select Upazila',
            allowClear: true,
            ajax: {
                url: "{{ route('institution.institutions.upazilas.select2') }}",
                dataType: 'json',
                delay: 200,
                cache: false,
                data: params => ({ q: params.term || '' }),
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

        $('#btnAddSpecimenItem').on('click', function() {
            const $firstRow = $table.find('tr:first');

            $firstRow.find('.select2-hidden-accessible').each(function() {
                $(this).select2('destroy');
            });

            const $row = $firstRow.clone();

            $row.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[\d+]/, `[${rowIndex}]`));
                if ($(this).hasClass('specimen-qty')) {
                    $(this).val(1);
                } else {
                    $(this).val('').html('');
                }
            });

            $table.append($row);

            if (window.S2 && typeof window.S2.auto === 'function') {
                window.S2.auto();
            }

            rowIndex++;
            calculateTotal();
        });

        $table.on('click', '.btnRemoveRow', function() {
            if ($table.find('tr').length === 1) return;
            $(this).closest('tr').remove();
            calculateTotal();
        });

        $table.on('input', '.specimen-qty', function() {
            calculateTotal();
        });

        function calculateTotal() {
            let total = 0;
            $('.specimen-qty').each(function() {
                total += parseInt($(this).val() || 0, 10);
            });
            $('#specimenTotalText').text(total);
        }

        calculateTotal();

        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto();
        }
    })();
</script>
