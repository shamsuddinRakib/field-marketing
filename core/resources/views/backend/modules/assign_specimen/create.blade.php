@extends('backend.layouts.master')

@section('meta')
    <title>Assign Specimen</title>
@endsection

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Specimen Assign</h6>
            <p class="fw-semibold mb-0">Assign Specimen</p>
        </div>

        <ul class="d-flex align-items-center gap-2 mb-0">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="{{ route('assign-specimen.index') }}" class="hover-text-primary">Specimen Assign</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Create</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('assign-specimen.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Specimen
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="assignSpecimenCreateForm" action="{{ route('assign-specimen.store') }}" method="POST">
        @csrf

        {{-- 1. ASSIGNMENT INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Assignment Information</h6>

                {{-- Hidden Location IDs --}}
                <input type="hidden" name="upazila_id" id="specimenHiddenUpazilaId">
                <input type="hidden" name="district_id" id="specimenHiddenDistrictId">
                <input type="hidden" name="division_id" id="specimenHiddenDivisionId">

                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        @php
                            $userRep = auth()->user()->representative ?? \App\Models\backend\MarketingRepresentative::where('user_id', auth()->id())->first();
                        @endphp
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="specimenRepSelect"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Representative" required>
                            @if ($userRep)
                                <option value="{{ $userRep->user_id ?? auth()->id() }}" selected>{{ $userRep->user?->name ?? auth()->user()->name }} ({{ $userRep->employee_id }})</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Recipient Type <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="customer_type" id="specimenCustomerType" required>
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
                                    class="form-control form-control-sm radius-8 bg-light" placeholder="Institution Name" readonly>
                            </div>

                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Type <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="assign_inst_type" id="specimenAssignInstType">
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
                        <label class="form-label text-sm mb-8">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8" placeholder="Optional note">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER COMPONENT --}}
        @include('backend.modules.products.partial.product_item_picker', [
            'copy_type'     => 'specimen', 'user_selector' => '#specimenRepSelect',
        ])

        {{-- 3. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('assign-specimen.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitSpecimen">Save as Pending</button>
        </div>
    </form>
@endsection

@section('script')
<script>
    (function() {
        // Recipient Selection Cascade Logic
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

        // Upazila Select2
        $('#specimenUpazilaSelect').select2({
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
            $('#specimenHiddenUpazilaId').val(data.id || '');
            $('#specimenHiddenDistrictId').val(data.district_id || '');
            $('#specimenHiddenDivisionId').val(data.division_id || '');
            $('#specimenDistrictDisplay').val(data.district_name || '');
            $('#specimenDivisionDisplay').val(data.division_name || '');

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

        $('#specimenRepSelect').on('change.repCascade', function() {
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

        // Institution Select2
        $('#specimenInstitutionSelect').select2({
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

            $('#specimenTeacherPhoneSelect').empty().val(null).trigger('change');
            $('#specimenTeacherName').val('');
        }).on('select2:clear', function() {
            $('#specimenInstitutionName').val('');
            $('#specimenTeacherPhoneSelect').empty().val(null).trigger('change');
            $('#specimenTeacherName').val('');
        });

        // Teacher / Phone Select2
        $('#specimenTeacherPhoneSelect').select2({
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

            if (data.address) {
                $('#specimenAddress').val(data.address);
            }

            if (!$('#specimenUpazilaSelect').val() && data.upazila_id) {
                const opt = new Option(data.upazila_name, data.upazila_id, true, true);
                $('#specimenUpazilaSelect').append(opt).trigger('change.select2');
                $('#specimenHiddenUpazilaId').val(data.upazila_id || '');
                $('#specimenHiddenDistrictId').val(data.district_id || '');
                $('#specimenHiddenDivisionId').val(data.division_id || '');
                $('#specimenDistrictDisplay').val(data.district_name || '');
                $('#specimenDivisionDisplay').val(data.division_name || '');
            }

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

        // Library Select2
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

        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto();
        }
    })();
</script>
@endsection
