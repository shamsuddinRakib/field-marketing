@extends('backend.layouts.master')

@section('meta')
    <title>Add Book Request</title>
@endsection

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Book Requests</h6>
            <p class="fw-semibold mb-0">Add Book Request</p>
        </div>

        <ul class="d-flex align-items-center gap-2 mb-0">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="{{ route('book-request.book-requests.index') }}" class="hover-text-primary">Book Requests</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Add Book Request</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('book-request.book-requests.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Book Requests
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="bookRequestCreateForm" action="{{ route('book-request.book-requests.store') }}" method="POST">
        @csrf

        {{-- 1. REQUEST INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Request Information</h6>

                <div class="row g-3">
                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Representative <span class="text-danger">*</span></label>
                        @php
                            $userRep = auth()->user()->representative ?? \App\Models\backend\MarketingRepresentative::where('user_id', auth()->id())->first();
                        @endphp
                        <select name="marketing_representative_id" id="bookRequestRepSelect" class="form-control form-control-sm js-s2-ajax"
                            data-url="{{ route('book-request.book-requests.representatives.select2') }}"
                            data-placeholder="Select Representative" required>
                            @if ($userRep)
                                <option value="{{ $userRep->id }}" selected>
                                    {{ $userRep->user?->name ?? 'Representative' }} ({{ $userRep->employee_id }})
                                </option>
                            @endif
                        </select>
                        <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
                    </div>

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Date <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" class="form-control form-control-sm radius-8"
                            value="{{ old('request_date', date('Y-m-d')) }}" required>
                        <div class="invalid-feedback d-block request_date-error" style="display:none"></div>
                    </div>

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm radius-8" required>
                            @foreach (\App\Models\backend\BookRequest::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback d-block status-error" style="display:none"></div>
                    </div>

                    <div class="col-12 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <textarea name="note" class="form-control form-control-sm radius-8" rows="2"
                            placeholder="Write anything about this request">{{ old('note') }}</textarea>
                        <div class="invalid-feedback d-block note-error" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER COMPONENT --}}
        @include('backend.modules.products.partial.product_item_picker')

        {{-- 3. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('book-request.book-requests.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitBookRequest">Save Request</button>
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
            $('#bookRequestRepSelect').select2({
                width: '100%',
                placeholder: 'Select Representative',
                allowClear: true,
                ajax: {
                    url: "{{ route('book-request.book-requests.representatives.select2') }}",
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

            // AJAX Form Submit with redirect
            $('#bookRequestCreateForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#btnSubmitBookRequest');

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
                                    text: res.msg || 'Book request created successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('book-request.book-requests.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Book request created successfully');
                                window.location.href = res.redirect || "{{ route('book-request.book-requests.index') }}";
                            }
                        } else {
                            $btn.prop('disabled', false).text('Save Request');
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: res?.msg || 'Something went wrong' });
                            } else {
                                alert(res?.msg || 'Something went wrong');
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Save Request');
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
                            const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to create book request.';
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
