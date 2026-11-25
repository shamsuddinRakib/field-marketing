 @extends('backend.layouts.master')

@section('meta')
    <title>Add Spot Sale</title>
@endsection

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Spot Sales</h6>
            <p class="fw-semibold mb-0">Add Spot Sale</p>
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
            <li class="fw-medium">Add Spot Sale</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('spot-sales.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Spot Sales
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="spotSaleCreateForm" action="{{ route('spot-sales.store') }}" method="POST">
        @csrf

        {{-- 1. SPOT SALE INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Spot Sale Information</h6>

                <div class="row g-3">
                    {{-- Marketing Representative --}}
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        @php
                            $userRep = auth()->user()->representative ?? \App\Models\backend\MarketingRepresentative::where('user_id', auth()->id())->first();
                        @endphp
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="spotSaleUserSelect"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($userRep)
                                <option value="{{ $userRep->user_id ?? auth()->id() }}" selected>
                                    {{ $userRep->user?->name ?? auth()->user()->name }} ({{ $userRep->employee_id }})
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
                            <option value="teacher">Teacher</option>
                            <option value="library">Library</option>
                            <option value="student">Student</option>
                        </select>
                        <div class="invalid-feedback d-block customer_type-error" style="display:none"></div>
                    </div>

                    {{-- Teacher Wrap --}}
                    <div class="col-12" id="teacherWrap" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Teacher Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm radius-8" name="teacher_name" id="teacherName" placeholder="Enter teacher name">
                                <div class="invalid-feedback d-block teacher_name-error name-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Phone Number</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="teacher_phone" id="teacherPhone" placeholder="Enter phone number">
                                <div class="invalid-feedback d-block teacher_phone-error phone-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Institution</label>
                                <select class="form-control form-control-sm js-s2-ajax" name="teacher_institution_id" id="teacherInstitutionSelect"
                                    data-url="{{ route('institution.institutions.select2') }}" data-placeholder="Select Institution">
                                </select>
                                <div class="invalid-feedback d-block teacher_institution_id-error institution_id-error" style="display:none"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Library Wrap --}}
                    <div class="col-12" id="libraryWrap" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Library Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm radius-8" name="library_name" id="libraryName" placeholder="Enter library name">
                                <div class="invalid-feedback d-block library_name-error name-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Phone Number</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="library_phone" id="libraryPhone" placeholder="Enter phone number">
                                <div class="invalid-feedback d-block library_phone-error phone-error" style="display:none"></div>
                            </div>
                            <div class="col-md-4 mb-12">
                                <label class="form-label text-sm mb-8">Address / Market</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="library_address" id="libraryAddress" placeholder="Enter library address / market">
                                <div class="invalid-feedback d-block library_address-error address-error" style="display:none"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Student Wrap --}}
                    <div class="col-12" id="studentWrap" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Student Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm radius-8" name="student_name" id="studentName" placeholder="Enter student name">
                                <div class="invalid-feedback d-block student_name-error name-error" style="display:none"></div>
                            </div>
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Phone Number</label>
                                <input type="text" class="form-control form-control-sm radius-8" name="student_phone" id="studentPhone" placeholder="Enter phone number">
                                <div class="invalid-feedback d-block student_phone-error phone-error" style="display:none"></div>
                            </div>
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Course Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm radius-8" name="student_course_class_id" id="courseClassSelect">
                                    <option value="">Select Class</option>
                                    @foreach ($classes ?? [] as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback d-block student_course_class_id-error course_class_id-error" style="display:none"></div>
                            </div>
                            <div class="col-md-3 mb-12">
                                <label class="form-label text-sm mb-8">Institution <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm js-s2-ajax" name="student_institution_id" id="studentInstitutionSelect"
                                    data-url="{{ route('institution.institutions.select2') }}" data-placeholder="Select Institution">
                                </select>
                                <div class="invalid-feedback d-block student_institution_id-error institution_id-error" style="display:none"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Note --}}
                    <div class="col-12 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <textarea name="note" class="form-control form-control-sm radius-8" rows="2" placeholder="Write any note about this spot sale...">{{ old('note') }}</textarea>
                        <div class="invalid-feedback d-block note-error" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER (With Price & Grand Total) --}}
        @include('backend.modules.products.partial.product_item_picker', [
            'show_price'    => true,
            'copy_type'     => 'sale',
            'user_selector' => '#spotSaleUserSelect',
        ])

        {{-- Hidden Status Input --}}
        <input type="hidden" name="status" id="spotSaleStatus" value="pending">

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

            // Auto-init Select2 for dynamically added fields
            if (window.S2 && typeof window.S2.auto === 'function') {
                window.S2.auto($('#spotSaleCreateForm'));
            }

            // Save as Pending Click
            $('#btnSavePending').on('click', function() {
                if (!$('#customerType').val()) {
                    if (window.Swal) Swal.fire({ icon: 'warning', title: 'Customer Type Required', text: 'Please select customer type.' });
                    else alert('Please select customer type.');
                    return;
                }
                $('#spotSaleStatus').val('pending');
                $('#spotSaleCreateForm').submit();
            });

            // Approve Sale Click
            $('#btnApproveSale').on('click', function() {
                if (!$('#customerType').val()) {
                    if (window.Swal) Swal.fire({ icon: 'warning', title: 'Customer Type Required', text: 'Please select customer type.' });
                    else alert('Please select customer type.');
                    return;
                }
                $('#spotSaleStatus').val('approved');
                $('#spotSaleCreateForm').submit();
            });

            // AJAX Form Submit
            $('#spotSaleCreateForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btnPending = $('#btnSavePending');
                const $btnApprove = $('#btnApproveSale');

                $form.find('.invalid-feedback').hide().text('');
                $btnPending.prop('disabled', true);
                $btnApprove.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing...');

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
                                    text: res.msg || 'Spot sale saved successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('spot-sales.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Spot sale saved successfully');
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
                            const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to save spot sale.';
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
