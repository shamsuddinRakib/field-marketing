@extends('backend.layouts.master')

@section('meta')
    <title>Edit Spot Sale #{{ $spotSale->id }}</title>
@endsection

@php
    $customer = $spotSale->customer;
    $customerType = $spotSale->customerType() ?? '';

    $teacherName = $customer && $customer->customer_type === 'teacher' ? $customer->name : ($spotSale->teacher?->teacher_name ?? '');
    $teacherPhone = $customer && $customer->customer_type === 'teacher' ? $customer->phone : ($spotSale->teacher?->phone ?? '');
    $teacherInstitution = $customer && $customer->customer_type === 'teacher' ? $customer->institution : ($spotSale->teacher?->institution ?? null);

    $libraryName = $customer && $customer->customer_type === 'library' ? $customer->name : ($spotSale->library?->library_name ?? ($spotSale->library?->name ?? ''));
    $libraryPhone = $customer && $customer->customer_type === 'library' ? $customer->phone : ($spotSale->library?->phone ?? '');
    $libraryAddress = $customer && $customer->customer_type === 'library' ? $customer->address : ($spotSale->library?->address ?? '');

    $studentName = $customer && $customer->customer_type === 'student' ? $customer->name : ($spotSale->student?->student_name ?? '');
    $studentPhone = $customer && $customer->customer_type === 'student' ? $customer->phone : '';
    $studentCourseClassId = $customer && $customer->customer_type === 'student' ? $customer->course_class_id : ($spotSale->student?->course_class_id ?? null);
    $studentInstitution = $customer && $customer->customer_type === 'student' ? $customer->institution : ($spotSale->student?->institution ?? null);
@endphp

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Spot Sales</h6>
            <p class="fw-semibold mb-0">Edit Spot Sale #{{ $spotSale->id }}</p>
        </div>

        <ul class="d-flex align-items-center gap-2 mb-0">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="{{ route('spot-sales.index') }}" class="hover-text-primary">Spot Sales</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Edit #{{ $spotSale->id }}</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('spot-sales.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Spot Sales
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="spotSaleEditForm" action="{{ route('spot-sales.update', $spotSale->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- 1. SPOT SALE INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Spot Sale Information</h6>

                <div class="row g-3">
                    {{-- Marketing Representative --}}
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="editSpotSaleUserSelect"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($spotSale->user)
                                <option value="{{ $spotSale->user_id }}" selected>
                                    {{ $spotSale->user->name }}
                                </option>
                            @endif
                        </select>
                        <div class="invalid-feedback d-block user_id-error" style="display:none"></div>
                    </div>

                    {{-- Customer Type --}}
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Customer Type <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm radius-8" name="customer_type" id="customerType" required>
                            <option value="">Select Type</option>
                            <option value="teacher" @selected($customerType === 'teacher')>Teacher</option>
                            <option value="library" @selected($customerType === 'library')>Library</option>
                            <option value="student" @selected($customerType === 'student')>Student</option>
                        </select>
                        <div class="invalid-feedback d-block customer_type-error" style="display:none"></div>
                    </div>

                    {{-- Teacher Wrap --}}
                    <div class="col-12" id="teacherWrap" style="{{ $customerType === 'teacher' ? '' : 'display:none;' }}">
                        <div class="row g-3">
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Teacher Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm radius-8" name="teacher_name" id="teacherName"
                                    value="{{ old('teacher_name', $teacherName) }}" placeholder="Enter teacher name">
                                <div class="invalid-feedback d-block teacher_name-error name-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Phone Number</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="teacher_phone" id="teacherPhone"
                                    value="{{ old('teacher_phone', $teacherPhone) }}" placeholder="Enter phone number">
                                <div class="invalid-feedback d-block teacher_phone-error phone-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Institution</label>
                                <select class="form-control form-control-sm js-s2-ajax" name="teacher_institution_id" id="teacherInstitutionSelect"
                                    data-url="{{ route('institution.institutions.select2') }}" data-placeholder="Select Institution">
                                    @if ($teacherInstitution)
                                        <option value="{{ $teacherInstitution->id }}" selected>{{ $teacherInstitution->institution_name }}</option>
                                    @endif
                                </select>
                                <div class="invalid-feedback d-block teacher_institution_id-error institution_id-error" style="display:none"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Library Wrap --}}
                    <div class="col-12" id="libraryWrap" style="{{ $customerType === 'library' ? '' : 'display:none;' }}">
                        <div class="row g-3">
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Library Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm radius-8" name="library_name" id="libraryName"
                                    value="{{ old('library_name', $libraryName) }}" placeholder="Enter library name">
                                <div class="invalid-feedback d-block library_name-error name-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Phone Number</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="library_phone" id="libraryPhone"
                                    value="{{ old('library_phone', $libraryPhone) }}" placeholder="Enter phone number">
                                <div class="invalid-feedback d-block library_phone-error phone-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Address / Market</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="library_address" id="libraryAddress"
                                    value="{{ old('library_address', $libraryAddress) }}" placeholder="Enter library address / market">
                                <div class="invalid-feedback d-block library_address-error address-error" style="display:none"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Student Wrap --}}
                    <div class="col-12" id="studentWrap" style="{{ $customerType === 'student' ? '' : 'display:none;' }}">
                        <div class="row g-3">
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Student Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm radius-8" name="student_name" id="studentName"
                                    value="{{ old('student_name', $studentName) }}" placeholder="Enter student name">
                                <div class="invalid-feedback d-block student_name-error name-error" style="display:none"></div>
                            </div>
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Phone Number</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="student_phone" id="studentPhone"
                                    value="{{ old('student_phone', $studentPhone) }}" placeholder="Enter phone number">
                                <div class="invalid-feedback d-block student_phone-error phone-error" style="display:none"></div>
                            </div>
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Course Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm radius-8" name="student_course_class_id" id="courseClassSelect">
                                    <option value="">Select Class</option>
                                    @foreach ($classes ?? [] as $class)
                                        <option value="{{ $class->id }}" @selected(old('student_course_class_id', $studentCourseClassId) == $class->id)>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback d-block student_course_class_id-error course_class_id-error" style="display:none"></div>
                            </div>
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Institution <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm js-s2-ajax" name="student_institution_id" id="studentInstitutionSelect"
                                    data-url="{{ route('institution.institutions.select2') }}" data-placeholder="Select Institution">
                                    @if ($studentInstitution)
                                        <option value="{{ $studentInstitution->id }}" selected>{{ $studentInstitution->institution_name }}</option>
                                    @endif
                                </select>
                                <div class="invalid-feedback d-block student_institution_id-error institution_id-error" style="display:none"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Note --}}
                    <div class="col-12 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <textarea name="note" class="form-control form-control-sm radius-8" rows="2" placeholder="Write any note about this spot sale...">{{ old('note', $spotSale->note) }}</textarea>
                        <div class="invalid-feedback d-block note-error" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER (With Price, Preloaded Items & Grand Total) --}}
        @include('backend.modules.products.partial.product_item_picker', [
            'show_price'    => true,
            'items'         => $spotSale->items,
            'copy_type'     => 'sale',
            'user_selector' => '#spotSaleUserSelect',
        ])

        {{-- Hidden Status Input --}}
        <input type="hidden" name="status" id="spotSaleStatus" value="{{ $spotSale->status }}">

        {{-- 3. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('spot-sales.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="button" class="btn btn-sm btn-outline-warning" id="btnSavePending">Save as Pending</button>
            <button type="button" class="btn btn-sm btn-success" id="btnApproveSale">Approve Sale</button>
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
            // Customer Type Toggle
            function toggleCustomer() {
                const type = $('#customerType').val();
                $('#teacherWrap').toggle(type === 'teacher');
                $('#libraryWrap').toggle(type === 'library');
                $('#studentWrap').toggle(type === 'student');

                // Toggle disabled attribute so inactive inputs are not submitted
                $('#teacherWrap :input').prop('disabled', type !== 'teacher');
                $('#libraryWrap :input').prop('disabled', type !== 'library');
                $('#studentWrap :input').prop('disabled', type !== 'student');

                $('#teacherName').prop('required', type === 'teacher');
                $('#libraryName').prop('required', type === 'library');
                $('#studentName').prop('required', type === 'student');
                $('#courseClassSelect').prop('required', type === 'student');
                $('#studentInstitutionSelect').prop('required', type === 'student');
            }

            $('#customerType').on('change', toggleCustomer);
            toggleCustomer();

            // Auto-init Select2 helpers
            if (window.S2 && typeof window.S2.auto === 'function') {
                window.S2.auto($('#spotSaleEditForm'));
            }

            // Save as Pending Click
            $('#btnSavePending').on('click', function() {
                if (!$('#customerType').val()) {
                    if (window.Swal) Swal.fire({ icon: 'warning', title: 'Customer Type Required', text: 'Please select customer type.' });
                    else alert('Please select customer type.');
                    return;
                }
                $('#spotSaleStatus').val('pending');
                $('#spotSaleEditForm').submit();
            });

            // Approve Sale Click
            $('#btnApproveSale').on('click', function() {
                if (!$('#customerType').val()) {
                    if (window.Swal) Swal.fire({ icon: 'warning', title: 'Customer Type Required', text: 'Please select customer type.' });
                    else alert('Please select customer type.');
                    return;
                }
                $('#spotSaleStatus').val('approved');
                $('#spotSaleEditForm').submit();
            });

            // AJAX Form Submit
            $('#spotSaleEditForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btnPending = $('#btnSavePending');
                const $btnApprove = $('#btnApproveSale');

                $form.find('.invalid-feedback').hide().text('');
                $btnPending.prop('disabled', true);
                $btnApprove.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

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
                                    text: res.msg || 'Spot sale updated successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('spot-sales.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Spot sale updated successfully');
                                window.location.href = res.redirect || "{{ route('spot-sales.index') }}";
                            }
                        } else {
                            $btnPending.prop('disabled', false);
                            $btnApprove.prop('disabled', false).text('Approve Sale');
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: res?.msg || 'Something went wrong' });
                            } else {
                                alert(res?.msg || 'Something went wrong');
                            }
                        }
                    },
                    error: function(xhr) {
                        $btnPending.prop('disabled', false);
                        $btnApprove.prop('disabled', false).text('Approve Sale');
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
                            const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to update spot sale.';
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
