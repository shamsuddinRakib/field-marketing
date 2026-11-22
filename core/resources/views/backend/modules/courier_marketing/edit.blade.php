@extends('backend.layouts.master')

@section('meta')
    <title>Edit Courier Dispatch #CM-{{ $courierMarketing->id }}</title>
@endsection

@php
    $rep = $courierMarketing->representative;
    $teacher = $courierMarketing->teacher;
    $institution = $courierMarketing->institution ?? $teacher?->institution;
@endphp

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Courier Management</h6>
            <p class="fw-semibold mb-0">Edit Courier Dispatch #CM-{{ $courierMarketing->id }}</p>
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
            <li class="fw-medium">Edit #CM-{{ $courierMarketing->id }}</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('courier-marketings.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Courier List
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="courierEditForm" action="{{ route('courier-marketings.update', $courierMarketing->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- 1. ASSIGNMENT DETAILS --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Assignment Details</h6>

                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative</label>
                        <select name="marketing_representative_id" id="editCourierRepSelect" class="form-control form-control-sm js-s2-ajax"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2', ['use_rep_id' => 1]) }}"
                            data-placeholder="Select Representative">
                            @if ($rep)
                                <option value="{{ $courierMarketing->marketing_representative_id }}" selected>
                                    {{ $rep->user?->name ?? 'Representative' }} ({{ $rep->employee_id }})
                                </option>
                            @endif
                        </select>
                        <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Teacher</label>
                        <input type="text" class="form-control form-control-sm radius-8 bg-light"
                            value="{{ $teacher?->teacher_name ?? '-' }} ({{ $teacher?->phone ?? 'No Phone' }})"
                            disabled>
                    </div>

                    <div class="col-12 mb-12">
                        <label class="form-label text-sm mb-8 text-muted">Institution & Location</label>
                        <input type="text" class="form-control form-control-sm radius-8 bg-light"
                            value="{{ $institution?->institution_name ?? '-' }} {{ $institution?->address ? '— ' . $institution->address : '' }}"
                            disabled>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER COMPONENT (pre-loaded with items) --}}
        @include('backend.modules.products.partial.product_item_picker', ['items' => $courierMarketing->items])

        {{-- 3. COURIER & DISPATCH DETAILS --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Courier & Dispatch Details</h6>

                <div class="row g-3">
                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Courier Company</label>
                        <input type="text" name="courier_company" class="form-control form-control-sm radius-8"
                            value="{{ old('courier_company', $courierMarketing->courier_company) }}" list="editCourierCompanyList" placeholder="e.g. Sundarban, Steadfast">
                        <datalist id="editCourierCompanyList">
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
                        <input type="date" name="dispatch_date" class="form-control form-control-sm radius-8"
                            value="{{ old('dispatch_date', $courierMarketing->dispatch_date?->format('Y-m-d')) }}" required>
                        <div class="invalid-feedback d-block dispatch_date-error" style="display:none"></div>
                    </div>

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Expected Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control form-control-sm radius-8"
                            value="{{ old('delivery_date', $courierMarketing->delivery_date?->format('Y-m-d')) }}">
                        <div class="invalid-feedback d-block delivery_date-error" style="display:none"></div>
                    </div>

                    <div class="col-12 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <textarea name="note" class="form-control form-control-sm radius-8" rows="2" placeholder="Write any dispatch note...">{{ old('note', $courierMarketing->note) }}</textarea>
                        <div class="invalid-feedback d-block note-error" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('courier-marketings.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-primary" id="btnUpdateCourier">Update Dispatch</button>
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
            // Representative Select2
            $('#editCourierRepSelect').select2({
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

            // AJAX Form Submit
            $('#courierEditForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#btnUpdateCourier');

                $form.find('.invalid-feedback').hide().text('');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

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
                                    text: res.msg || 'Courier entry updated successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('courier-marketings.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Courier entry updated successfully');
                                window.location.href = res.redirect || "{{ route('courier-marketings.index') }}";
                            }
                        } else {
                            $btn.prop('disabled', false).text('Update Dispatch');
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: res?.msg || 'Something went wrong' });
                            } else {
                                alert(res?.msg || 'Something went wrong');
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Update Dispatch');
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
                            const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to update courier entry.';
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
