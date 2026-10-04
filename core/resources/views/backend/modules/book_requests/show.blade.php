@extends('backend.layouts.master')

@section('meta')
    <title>Book Request Details</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Book Request Details</h6>
            <p class="m-0">View book request information</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
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
            <li class="fw-medium">Details</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Request Information</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('book-request.book-requests.index') }}"
                            class="btn btn-sm btn-outline-primary">
                            <iconify-icon icon="lucide:arrow-left" class="me-1"></iconify-icon> Back to List
                        </a>
                        @if (auth()->user()->canDo('book-request.book-requests.edit'))
                            <a href="#" class="btn btn-sm btn-primary AjaxModal"
                                data-ajax-modal="{{ route('book-request.book-requests.editModal', $bookRequest->id) }}"
                                data-size="lg" data-onload="BookRequestsIndex.onLoad"
                                data-onsuccess="BookRequestsIndex.onSaved">
                                <iconify-icon icon="lucide:edit" class="me-1"></iconify-icon> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Representative Name</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookRequest->representative?->user?->name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Employee ID</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookRequest->representative?->employee_id ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Product Name</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookRequest->product?->name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Product SKU</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookRequest->product?->sku ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Quantity</label>
                            <input class="form-control" type="text" disabled value="{{ $bookRequest->quantity }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Date</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookRequest->request_date?->format('d M, Y') ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Status</label>
                            <div>
                                @if ($bookRequest->status === 'approved')
                                    <span
                                        class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Approved</span>
                                @elseif ($bookRequest->status === 'rejected')
                                    <span
                                        class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Rejected</span>
                                @else
                                    <span
                                        class="bg-warning-focus text-warning-600 border border-warning-main px-12 py-4 radius-4 fw-medium text-sm">Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 mb-16">
                            <label class="form-label text-sm mb-6">Note</label>
                            <textarea class="form-control" rows="3" disabled>{{ $bookRequest->note ?? 'N/A' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Request Summary</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-16">
                        <div
                            class="w-80-px h-80-px rounded-circle bg-primary-focus text-primary-main d-inline-flex align-items-center justify-content-center fw-bold text-xl">
                            {{ strtoupper(substr($bookRequest->product?->name ?? 'BR', 0, 2)) }}
                        </div>
                    </div>
                    <h6 class="fw-semibold">{{ $bookRequest->product?->name ?? 'N/A' }}</h6>
                    <p class="text-muted text-sm mb-0">
                        {{ $bookRequest->representative?->user?->name ?? 'N/A' }}
                    </p>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between text-sm mb-8">
                        <span class="text-muted">Created</span>
                        <span>{{ $bookRequest->created_at?->format('d M, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-sm">
                        <span class="text-muted">Last Updated</span>
                        <span>{{ $bookRequest->updated_at?->format('d M, Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.BookRequestsIndex = window.BookRequestsIndex || {};
        window.BookRequestsIndex.onLoad = function($modal) {
            $modal.find('.js-representative-select').each(function() {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select representative',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('book-request.book-requests.representatives.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({ q: params.term || '' }),
                        processResults: data => data
                    }
                });
            });
            $modal.find('.js-product-select').each(function() {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select product',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('product.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({ q: params.term || '' }),
                        processResults: data => data
                    }
                });
            });
        };
        window.BookRequestsIndex.onSaved = function() {
            window.location.reload();
        };
    </script>
@endsection
