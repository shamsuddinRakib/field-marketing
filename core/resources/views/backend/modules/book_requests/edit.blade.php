@extends('backend.layouts.master')

@section('meta')
    <title>Edit Book Request #{{ $bookRequest->id }}</title>
@endsection

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Book Requests</h6>
            <p class="fw-semibold mb-0">Edit Book Request #{{ $bookRequest->id }}</p>
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
            <li class="fw-medium">Edit #{{ $bookRequest->id }}</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('book-request.book-requests.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Book Requests
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="bookRequestEditForm" action="{{ route('book-request.book-requests.update', $bookRequest->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="request_group_id" value="{{ $bookRequest->request_group_id }}">
        <input type="hidden" name="exclude_request_id" value="{{ $bookRequest->id }}">

        {{-- 1. REQUEST INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Request Information</h6>

                <div class="row g-3">
                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8">Representative <span class="text-danger">*</span></label>
                        <select name="marketing_representative_id" id="editBookRequestRepSelect" class="form-control form-control-sm js-s2-ajax"
                            data-url="{{ route('book-request.book-requests.representatives.select2') }}"
                            data-placeholder="Select Representative" required>
                            @if ($bookRequest->marketing_representative_id)
                                <option value="{{ $bookRequest->marketing_representative_id }}" selected>
                                    @if ($bookRequest->representative?->user?->name)
                                        {{ $bookRequest->representative->user->name }} ({{ $bookRequest->representative->employee_id }})
                                    @else
                                        {{ $bookRequest->representative?->employee_id ?: '#' . $bookRequest->marketing_representative_id }}
                                    @endif
                                </option>
                            @endif
                        </select>
                        <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
                    </div>

                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8">Request Type <span class="text-danger">*</span></label>
                        <select name="request_type" class="form-select form-select-sm radius-8" required>
                            @foreach (\App\Models\backend\BookRequest::REQUEST_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('request_type', $bookRequest->request_type ?? 'specimen') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback d-block request_type-error" style="display:none"></div>
                    </div>

                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8">Date <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" class="form-control form-control-sm radius-8"
                            value="{{ old('request_date', $bookRequest->request_date?->format('Y-m-d')) }}" required>
                        <div class="invalid-feedback d-block request_date-error" style="display:none"></div>
                    </div>

                    <div class="col-md-3 mb-12">
                        <label class="form-label text-sm mb-8">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm radius-8" required>
                            @foreach (\App\Models\backend\BookRequest::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $bookRequest->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback d-block status-error" style="display:none"></div>
                    </div>

                    <div class="col-12 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <textarea name="note" class="form-control form-control-sm radius-8" rows="2"
                            placeholder="Write anything about this request">{{ old('note', $bookRequest->note) }}</textarea>
                        <div class="invalid-feedback d-block note-error" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER COMPONENT (preloaded with items) --}}
        @include('backend.modules.products.partial.product_item_picker', ['items' => $bookRequests])

        {{-- 3. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('book-request.book-requests.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-primary" id="btnUpdateBookRequest">Update Request</button>
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
            $('#editBookRequestRepSelect').select2({
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
            $('#bookRequestEditForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#btnUpdateBookRequest');

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
                                    text: res.msg || 'Book request updated successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('book-request.book-requests.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Book request updated successfully');
                                window.location.href = res.redirect || "{{ route('book-request.book-requests.index') }}";
                            }
                        } else {
                            $btn.prop('disabled', false).text('Update Request');
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: res?.msg || 'Something went wrong' });
                            } else {
                                alert(res?.msg || 'Something went wrong');
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Update Request');
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
                            const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to update book request.';
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
