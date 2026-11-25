@extends('backend.layouts.master')

@section('meta')
    <title>Book Return Details</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Book Return Details</h6>
            <p class="m-0">View book return information</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="{{ route('book-return.book-returns.index') }}" class="hover-text-primary">Book Returns</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Details</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Return Information</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('book-return.book-returns.index') }}"
                            class="btn btn-sm btn-outline-primary">
                            <iconify-icon icon="lucide:arrow-left" class="me-1"></iconify-icon> Back to List
                        </a>
                        @if (auth()->user()->canDo('book-return.book-returns.edit'))
                            <a href="#" class="btn btn-sm btn-primary AjaxModal"
                                data-ajax-modal="{{ route('book-return.book-returns.editModal', $bookReturn->id) }}"
                                data-size="lg" data-onload="BookReturnsIndex.onLoad"
                                data-onsuccess="BookReturnsIndex.onSaved">
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
                                value="{{ $bookReturn->representative?->user?->name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Employee ID</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookReturn->representative?->employee_id ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Product Name</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookReturn->product?->name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Return Type</label>
                            <div>
                                @if ($bookReturn->return_type === 'sale')
                                    <span class="badge bg-primary-subtle text-primary-700 border border-primary-subtle text-sm px-12 py-6">Sale Copy</span>
                                @else
                                    <span class="badge bg-info-subtle text-info-700 border border-info-subtle text-sm px-12 py-6">Specimen Copy</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Returned Quantity</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookReturn->returned_quantity }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Received By</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookReturn->received_by ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Received Date</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $bookReturn->received_date?->format('d M, Y') ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Status</label>
                            <div>
                                @if ($bookReturn->status === 'received')
                                    <span
                                        class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Received</span>
                                @elseif ($bookReturn->status === 'partial')
                                    <span
                                        class="bg-info-focus text-info-600 border border-info-main px-12 py-4 radius-4 fw-medium text-sm">Partially Returned</span>
                                @elseif ($bookReturn->status === 'rejected')
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
                            <textarea class="form-control" rows="3" disabled>{{ $bookReturn->note ?? 'N/A' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Return Summary</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-16">
                        <div
                            class="w-80-px h-80-px rounded-circle bg-primary-focus text-primary-main d-inline-flex align-items-center justify-content-center fw-bold text-xl">
                            {{ strtoupper(substr($bookReturn->product?->name ?? 'BR', 0, 2)) }}
                        </div>
                    </div>
                    <h6 class="fw-semibold">{{ $bookReturn->product?->name ?? 'N/A' }}</h6>
                    <p class="text-muted text-sm mb-0">
                        {{ $bookReturn->representative?->user?->name ?? 'N/A' }}
                    </p>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-between text-sm mb-8">
                        <span class="text-muted">Returned Quantity</span>
                        <span class="fw-bold">{{ $bookReturn->returned_quantity }}</span>
                    </div>
                    <div class="d-flex justify-between text-sm mb-8">
                        <span class="text-muted">Created</span>
                        <span>{{ $bookReturn->created_at?->format('d M, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-between text-sm">
                        <span class="text-muted">Last Updated</span>
                        <span>{{ $bookReturn->updated_at?->format('d M, Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.BookReturnsIndex = window.BookReturnsIndex || {};
        window.BookReturnsIndex.onLoad = function($modal) {
            $modal.find('.js-representative-select').each(function() {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select representative',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('book-return.book-returns.representatives.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({ q: params.term || '' }),
                        processResults: data => data
                    }
                });
            });

            function initProductSelect2() {
                $modal.find('.js-product-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select product from MR stock',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('product.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: function(params) {
                                return {
                                    q: params.term || '',
                                    user_id: $modal.find('.js-representative-select').val() || '',
                                    copy_type: $modal.find('.js-return-type-select').val() || 'specimen'
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: Array.isArray(data?.results) ? data.results : []
                                };
                            }
                        }
                    });
                });
            }
            initProductSelect2();

            $modal.off('change.bookReturn').on('change.bookReturn', '.js-representative-select, .js-return-type-select', function() {
                const $prod = $modal.find('.js-product-select');
                $prod.val(null).trigger('change');
                $modal.find('.js-stock-indicator').hide();
                $modal.find('.js-return-qty-input').removeAttr('max');
            });

            $modal.off('select2:select.bookReturn').on('select2:select.bookReturn', '.js-product-select', function(e) {
                const data = e.params?.data;
                if (data && data.available_stock !== undefined && data.available_stock !== null) {
                    const stock = parseInt(data.available_stock, 10);
                    $modal.find('.js-stock-val').text(stock);
                    $modal.find('.js-stock-indicator').show();
                    $modal.find('.js-return-qty-input').attr('max', stock);
                } else {
                    $modal.find('.js-stock-indicator').hide();
                    $modal.find('.js-return-qty-input').removeAttr('max');
                }
            });

            $modal.off('select2:clear.bookReturn').on('select2:clear.bookReturn', '.js-product-select', function() {
                $modal.find('.js-stock-indicator').hide();
                $modal.find('.js-return-qty-input').removeAttr('max');
            });
        };
        window.BookReturnsIndex.onSaved = function() {
            window.location.reload();
        };
    </script>
@endsection
