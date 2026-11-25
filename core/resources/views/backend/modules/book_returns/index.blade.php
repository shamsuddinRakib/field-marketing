@extends('backend.layouts.master')

@section('meta')
    <title>Book Returns</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Book Return List</h6>
            <p class="m-0">Manage book returns</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Book Returns</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Book Return List</h5>
            <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
                <div class="search-set me-2">
                    <div id="tableSearch" class="search-input"></div>
                </div>
                <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
                    @include('backend.include.buttons')
                </ul>
                @if (auth()->user()->canDo('book-return.book-returns.store'))
                    <button type="button"
                        class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
                        data-ajax-modal="{{ route('book-return.book-returns.createModal') }}"
                        data-size="lg" data-onload="BookReturnsIndex.onLoad"
                        data-onsuccess="BookReturnsIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add book return
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="bookReturnsTable"
                style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" id="select-all">
                                <label class="form-check-label">S.L</label>
                            </div>
                        </th>
                        <th>Representative Name</th>
                        <th>Product Name</th>
                        <th>Return Type</th>
                        <th>Return Quantity</th>
                        <th>Note</th>
                        <th>Status</th>
                        <th>Received By</th>
                        <th>Received Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var DATATABLE_URL = "{{ route('book-return.book-returns.list.ajax') }}";

        window.BookReturnsIndex = {
            onLoad: function($modal) {
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
                            data: params => ({
                                q: params.term || ''
                            }),
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

                // When Representative or Return Type changes, reset selected product & stock info
                $modal.off('change.bookReturn').on('change.bookReturn', '.js-representative-select, .js-return-type-select', function() {
                    const $prod = $modal.find('.js-product-select');
                    $prod.val(null).trigger('change');
                    $modal.find('.js-stock-indicator').hide();
                    $modal.find('.js-return-qty-input').removeAttr('max');
                });

                // When product is selected, display available stock and cap max return quantity
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
            },
            onSaved: function(res) {
                $('.AjaxDataTable').DataTable().ajax.reload(null, false);
                if (window.Swal) Swal.fire({
                    icon: 'success',
                    title: 'Saved',
                    text: res?.msg || 'Saved',
                    timer: 1000,
                    showConfirmButton: false
                });
            }
        };

        $(document).on('click', '.btn-book-return-delete', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const remove = function() {
                $.ajax({
                        url: url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            _method: 'DELETE',
                            _token: '{{ csrf_token() }}'
                        }
                    })
                    .done(function(res) {
                        $('.AjaxDataTable').DataTable().ajax.reload(null, false);
                        if (window.Swal) Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: res?.msg || 'Deleted',
                            timer: 1000,
                            showConfirmButton: false
                        });
                    }).fail(function() {
                        if (window.Swal) Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: 'Delete failed'
                        });
                    });
            };
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete book return?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (result.isConfirmed) remove();
                });
            } else if (confirm('Delete this book return?')) remove();
        });
    </script>
@endsection
