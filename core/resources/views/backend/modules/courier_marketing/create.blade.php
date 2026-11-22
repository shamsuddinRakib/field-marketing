@extends('backend.layouts.master')

@section('meta')
    <title>Add Courier Dispatch</title>
@endsection

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Courier Management</h6>
            <p class="fw-semibold mb-0">Add Courier Dispatch</p>
        </div>

        <ul class="d-flex align-items-center gap-2 mb-0">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="{{ route('courier-marketings.index') }}" class="hover-text-primary">Courier Management</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Add Courier Dispatch</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('courier-marketings.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Courier List
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="courierCreateForm" action="{{ route('courier-marketings.store') }}" method="POST">
        @csrf

        {{-- Hidden location & institution IDs --}}
        <input type="hidden" name="upazila_id" id="courierHiddenUpazilaId">
        <input type="hidden" name="district_id" id="courierHiddenDistrictId">
        <input type="hidden" name="division_id" id="courierHiddenDivisionId">
        <input type="hidden" name="institution_id" id="courierHiddenInstitutionId">

        {{-- 1. ASSIGNMENT INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Assignment Information</h6>

                <div class="row g-3">
                    {{-- Row 1: Marketing Representative & Upazila --}}
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        @php
                            $userRep = auth()->user()->representative ?? \App\Models\backend\MarketingRepresentative::where('user_id', auth()->id())->first();
                        @endphp
                        <select name="marketing_representative_id" id="courierRepSelect" class="form-control form-control-sm js-s2-ajax"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2', ['use_rep_id' => 1]) }}"
                            data-placeholder="Select Representative" required>
                            @if ($userRep)
                                <option value="{{ $userRep->id }}" selected>{{ $userRep->user?->name ?? 'Representative' }} ({{ $userRep->employee_id }})</option>
                            @endif
                        </select>
                        <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Upazila <span class="text-danger">*</span></label>
                        <select id="courierUpazilaSelect" class="form-control form-control-sm"
                            data-placeholder="Search / Select Upazila" required>
                        </select>
                        <div class="invalid-feedback d-block upazila_id-error" style="display:none"></div>
                    </div>

                    {{-- Row 2: District, Division & Institution --}}
                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8 text-muted">District</label>
                        <input type="text" id="courierDistrictDisplay" class="form-control form-control-sm radius-8 bg-light" readonly placeholder="District">
                    </div>

                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8 text-muted">Division</label>
                        <input type="text" id="courierDivisionDisplay" class="form-control form-control-sm radius-8 bg-light" readonly placeholder="Division">
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Institution</label>
                        <select id="courierInstitutionSelect" class="form-control form-control-sm"
                            data-placeholder="Select Institution">
                        </select>
                        <div class="invalid-feedback d-block institution_id-error" style="display:none"></div>
                    </div>

                    {{-- Row 3: Teacher Phone, Teacher Name & Location --}}
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Select Phone Number / Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="courierTeacherPhoneSelect" class="form-control form-control-sm"
                            data-placeholder="Search by Phone or Teacher Name" required></select>
                        <div class="invalid-feedback d-block teacher_id-error" style="display:none"></div>
                    </div>

                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8 text-muted">Teacher Name</label>
                        <input type="text" id="autoTeacherName" class="form-control form-control-sm radius-8 bg-light" readonly placeholder="Teacher Name">
                    </div>

                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8 text-muted">Location / Address</label>
                        <input type="text" id="autoLocationAddress" class="form-control form-control-sm radius-8 bg-light" readonly placeholder="Location & Address">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER COMPONENT --}}
        @include('backend.modules.products.partial.product_item_picker')

        {{-- 3. COURIER & DISPATCH DETAILS --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Courier & Dispatch Details</h6>

                <div class="row g-3">
                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Courier Company</label>
                        <input type="text" name="courier_company" class="form-control form-control-sm radius-8" list="courierCompanyList" placeholder="e.g. Sundarban, Steadfast">
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

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Dispatch Date <span class="text-danger">*</span></label>
                        <input type="date" name="dispatch_date" class="form-control form-control-sm radius-8" value="{{ old('dispatch_date', date('Y-m-d')) }}" required>
                        <div class="invalid-feedback d-block dispatch_date-error" style="display:none"></div>
                    </div>

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Expected Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control form-control-sm radius-8" value="{{ old('delivery_date') }}">
                        <div class="invalid-feedback d-block delivery_date-error" style="display:none"></div>
                    </div>

                    <div class="col-12 mb-12">
                        <label class="form-label text-sm mb-8">Note / Remarks</label>
                        <textarea name="note" class="form-control form-control-sm radius-8" rows="2" placeholder="Write any dispatch note...">{{ old('note') }}</textarea>
                        <div class="invalid-feedback d-block note-error" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('courier-marketings.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitCourier">Save Dispatch</button>
        </div>
    </form>
@endsection

@section('script')
<script>
    (function checkDeps() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            setTimeout(checkDeps, 30);
            return;
        }

        jQuery(function($) {
            const $repSelect = $('#courierRepSelect');
            const $upazilaSelect = $('#courierUpazilaSelect');
            const $instSelect = $('#courierInstitutionSelect');
            const $teacherSelect = $('#courierTeacherPhoneSelect');

            // 1. Marketing Representative Select2
            $repSelect.select2({
                width: '100%',
                placeholder: 'Select Representative',
                allowClear: true,
                ajax: {
                    url: "{{ route('marketing-representative.marketing-representatives.select2', ['use_rep_id' => 1]) }}",
                    dataType: 'json',
                    delay: 200,
                    data: function(params) {
                        return { q: params.term || '' };
                    },
                    processResults: function(data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });

            // 2. Upazila Select2 (filtered by selected marketing_representative_id)
            $upazilaSelect.select2({
                width: '100%',
                placeholder: 'Search / Select Upazila',
                allowClear: true,
                ajax: {
                    url: "{{ route('courier-marketings.upazilas.select2') }}",
                    dataType: 'json',
                    delay: 200,
                    cache: false,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            marketing_representative_id: $repSelect.val() || ''
                        };
                    },
                    processResults: function(data) {
                        return data;
                    }
                }
            }).on('select2:select', function(e) {
                const data = e.params.data || {};
                $('#courierHiddenUpazilaId').val(data.id || '');
                $('#courierHiddenDistrictId').val(data.district_id || '');
                $('#courierHiddenDivisionId').val(data.division_id || '');
                $('#courierDistrictDisplay').val(data.district_name || '');
                $('#courierDivisionDisplay').val(data.division_name || '');

                // Reset downstream
                $instSelect.empty().val(null).trigger('change');
                $teacherSelect.empty().val(null).trigger('change');
                $('#courierHiddenInstitutionId').val('');
                $('#autoTeacherName').val('');
                $('#autoLocationAddress').val('');
            }).on('select2:clear', function() {
                $('#courierHiddenUpazilaId').val('');
                $('#courierHiddenDistrictId').val('');
                $('#courierHiddenDivisionId').val('');
                $('#courierDistrictDisplay').val('');
                $('#courierDivisionDisplay').val('');

                $instSelect.empty().val(null).trigger('change');
                $teacherSelect.empty().val(null).trigger('change');
                $('#courierHiddenInstitutionId').val('');
                $('#autoTeacherName').val('');
                $('#autoLocationAddress').val('');
            });

            // Reset upazila & downstream when representative changes
            $repSelect.on('change', function() {
                $upazilaSelect.empty().val(null).trigger('change');
                $('#courierHiddenUpazilaId').val('');
                $('#courierHiddenDistrictId').val('');
                $('#courierHiddenDivisionId').val('');
                $('#courierDistrictDisplay').val('');
                $('#courierDivisionDisplay').val('');
                $instSelect.empty().val(null).trigger('change');
                $teacherSelect.empty().val(null).trigger('change');
                $('#courierHiddenInstitutionId').val('');
                $('#autoTeacherName').val('');
                $('#autoLocationAddress').val('');
            });

            // 3. Institution Select2 (filtered by selected upazila_id)
            $instSelect.select2({
                width: '100%',
                placeholder: 'Select Institution',
                allowClear: true,
                ajax: {
                    url: "{{ route('courier-marketings.institutions.select2') }}",
                    dataType: 'json',
                    delay: 200,
                    cache: false,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            upazila_id: $('#courierHiddenUpazilaId').val() || $upazilaSelect.val() || ''
                        };
                    },
                    processResults: function(data) {
                        return data;
                    }
                }
            }).on('select2:select', function(e) {
                const data = e.params.data || {};
                $('#courierHiddenInstitutionId').val(data.id || '');
                if (data.address && !$('#autoLocationAddress').val()) {
                    $('#autoLocationAddress').val(data.address);
                }

                $teacherSelect.empty().val(null).trigger('change');
                $('#autoTeacherName').val('');
            }).on('select2:clear', function() {
                $('#courierHiddenInstitutionId').val('');
                $teacherSelect.empty().val(null).trigger('change');
                $('#autoTeacherName').val('');
            });

            // 4. Teacher / Phone Select2 (filtered by upazila_id & institution_id)
            $teacherSelect.select2({
                width: '100%',
                placeholder: 'Search by Phone or Teacher Name',
                allowClear: true,
                ajax: {
                    url: "{{ route('courier-marketings.teachersByPhone.select2') }}",
                    dataType: 'json',
                    delay: 200,
                    cache: false,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            upazila_id: $('#courierHiddenUpazilaId').val() || $upazilaSelect.val() || '',
                            institution_id: $('#courierHiddenInstitutionId').val() || $instSelect.val() || ''
                        };
                    },
                    processResults: function(data) {
                        return data;
                    }
                }
            }).on('select2:select', function(e) {
                const data = e.params.data || {};
                $('#autoTeacherName').val(data.teacher_name || '—');
                $('#autoLocationAddress').val(data.address || '—');

                if (!$instSelect.val() && data.institution_id) {
                    $('#courierHiddenInstitutionId').val(data.institution_id);
                    const opt = new Option(data.institution_name, data.institution_id, true, true);
                    $instSelect.append(opt).trigger('change.select2');
                }
            }).on('select2:clear', function() {
                $('#autoTeacherName').val('');
                if (!$instSelect.val()) {
                    $('#autoLocationAddress').val('');
                }
            });

            // AJAX Form Submit
            $('#courierCreateForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#btnSubmitCourier');

                // Clear previous validation errors
                $form.find('.invalid-feedback').hide().text('');

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res?.ok) {
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: res.msg || 'Courier dispatch saved successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('courier-marketings.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Courier dispatch saved successfully');
                                window.location.href = res.redirect || "{{ route('courier-marketings.index') }}";
                            }
                        } else {
                            $btn.prop('disabled', false).text('Save Dispatch');
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: res?.msg || 'Something went wrong' });
                            } else {
                                alert(res?.msg || 'Something went wrong');
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Save Dispatch');
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const errors = xhr.responseJSON.errors;
                            let firstError = '';
                            Object.keys(errors).forEach(key => {
                                const msg = errors[key][0];
                                if (!firstError) firstError = msg;
                                const safeKey = key.replace(/\./g, '_');
                                const $err = $form.find('.' + safeKey + '-error');
                                if ($err.length) {
                                    $err.text(msg).show();
                                }
                            });
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Validation Error', text: firstError || 'Please correct the highlighted errors.' });
                            }
                        } else {
                            const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to save courier dispatch.';
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }
                });
            });
        });
    })();
</script>
@endsection
