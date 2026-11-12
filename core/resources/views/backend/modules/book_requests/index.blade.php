@extends('backend.layouts.master')

@section('meta')
    <title>Book Requests</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Book Request List</h6>
            <p class="m-0">Manage book requests</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Book Requests</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Book Request List</h5>
            <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
                <div class="search-set me-2">
                    <div id="tableSearch" class="search-input"></div>
                </div>
                <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
                    @include('backend.include.buttons')
                </ul>
                @if (auth()->user()->canDo('book-request.book-requests.store'))
                    <button type="button"
                        class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
                        data-ajax-modal="{{ route('book-request.book-requests.createModal') }}"
                        data-size="lg" data-onload="BookRequestsIndex.onLoad"
                        data-onsuccess="BookRequestsIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add book request
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="bookRequestsTable"
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
                        <th>Quantity</th>
                        <th>Note</th>
                        <th>Date</th>
                        <th>Status</th>
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
        var DATATABLE_URL = "{{ route('book-request.book-requests.list.ajax') }}";

        function setDistForRow($row, availableQty) {
            const $qty = $row.find('.js-quantity-input, input[type="number"]');
            const $hint = $row.find('.js-dist-hint');
            if (availableQty !== null && availableQty !== undefined && availableQty !== '') {
                availableQty = parseInt(availableQty, 10);
                $qty.attr('max', availableQty);
                if ($hint.length) $hint.text('Available: ' + availableQty);
                const cur = parseInt($qty.val(), 10);
                if (!isNaN(cur) && cur > availableQty) {
                    $qty.val(availableQty);
                    const $err = $row.find('.invalid-feedback');
                    if ($err.length) {
                        $err.text('Quantity cannot exceed available quantity (' + availableQty + ').').show();
                        setTimeout(() => { if (!$qty.is(':focus')) $err.hide(); }, 3000);
                    }
                }
            } else {
                $qty.removeAttr('max');
                if ($hint.length) $hint.text('Available: —');
            }
        }

        function initBookRequestProductSelect($select, $modal) {
            if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
            $select.select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: 'Select product from Distribution List',
                allowClear: true,
                ajax: {
                    url: "{{ route('product-distribution.products.select2') }}",
                    dataType: 'json',
                    delay: 200,
                    data: params => ({
                        q: params.term || '',
                        marketing_representative_id: $modal.find('[name="marketing_representative_id"]').val(),
                        exclude_request_group_id: $modal.find('[name="request_group_id"]').val(),
                        exclude_request_id: $modal.find('[name="exclude_request_id"]').val()
                    }),
                    processResults: data => ({
                        results: (data.results || []).filter(item => !$modal.find('.js-product-select').not($select)
                            .toArray().some(other => String($(other).val()) === String(item.id)))
                    })
                }
            }).off('select2:select.distCheck').on('select2:select.distCheck', function(e) {
                const availableQty = e.params && e.params.data ? e.params.data.available_quantity : null;
                setDistForRow($(this).closest('.book-request-product-row'), availableQty);
            }).off('select2:clear.distCheck').on('select2:clear.distCheck', function() {
                setDistForRow($(this).closest('.book-request-product-row'), null);
            });
        }

        window.BookRequestsIndex = {
            onLoad: function($modal) {
                $modal.find('.modal-dialog').addClass('modal-dialog-scrollable');
                $modal.off('hidden.bs.modal.bookRequestsScroll').on('hidden.bs.modal.bookRequestsScroll', function() {
                    $(this).find('.modal-dialog').removeClass('modal-dialog-scrollable');
                });
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
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    });
                });
                $modal.find('.js-product-select').each(function() {
                    initBookRequestProductSelect($(this), $modal);
                });
                $modal.find('.book-request-product-items').each(function() {
                    $(this).data('next-index', $(this).find('.book-request-product-row').length);
                });
                // Refresh distribution products when representative changes
                $modal.find('.js-representative-select').on('change', function() {
                    $modal.find('.js-product-select').val(null).trigger('change');
                    $modal.find('.js-product-select').each(function() {
                        initBookRequestProductSelect($(this), $modal);
                    });
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

        $(document).on('click', '.btn-add-product', function() {
            const $modal = $(this).closest('.modal');
            const $items = $(this).siblings('.book-request-product-items');
            const $row = $($('#book-request-product-template').html().trim());
            const index = Number($items.data('next-index') || 0);
            $items.data('next-index', index + 1);
            $row.find('select')
                .attr('name', `product_items[${index}][product_id]`)
                .siblings('.invalid-feedback').addClass(`product_items.${index}.product_id-error`);
            $row.find('input')
                .attr('name', `product_items[${index}][quantity]`)
                .siblings('.invalid-feedback').addClass(`product_items.${index}.quantity-error`);
            $items.append($row);
            initBookRequestProductSelect($row.find('.js-product-select'), $modal);
            $items.find('.btn-remove-product').prop('disabled', false);
        });

        $(document).on('click', '.btn-remove-product', function() {
            const $items = $(this).closest('.book-request-product-items');
            if ($items.find('.book-request-product-row').length <= 1) return;
            $(this).closest('.book-request-product-row').remove();
            if ($items.find('.book-request-product-row').length === 1) {
                $items.find('.btn-remove-product').prop('disabled', true);
            }
        });

        // Block quantity over Dist (must be equal or less)
        $(document).on('input', '.book-request-product-row .js-quantity-input, .book-request-product-row input[type="number"]', function() {
            const $row = $(this).closest('.book-request-product-row');
            const max = parseInt($(this).attr('max'), 10);
            if (isNaN(max)) return;
            const val = parseInt($(this).val(), 10);
            if (!isNaN(val) && val > max) {
                $(this).val(max);
                const $err = $row.find('.invalid-feedback');
                if ($err.length) {
                    $err.text('Quantity cannot exceed available quantity (' + max + ').').show();
                }
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Exceeds Available Quantity', text: 'Quantity cannot exceed available (' + max + ').', timer: 1500, showConfirmButton: false });
                }
            }
        });

        // Final guard on submit: do not allow any row over Dist
        $(document).on('submit', 'form[action*="book-requests"]', function(e) {
            let blocked = false;
            $(this).find('.book-request-product-row').each(function() {
                const $qty = $(this).find('.js-quantity-input, input[type="number"]');
                const max = parseInt($qty.attr('max'), 10);
                const val = parseInt($qty.val(), 10);
                if (!isNaN(max) && !isNaN(val) && val > max) {
                    blocked = true;
                    const $err = $(this).find('.invalid-feedback');
                    if ($err.length) $err.text('Quantity cannot exceed available quantity (' + max + ').').show();
                }
            });
            if (blocked) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Blocked', text: 'One or more quantities exceed the available quantity.' });
                return false;
            }
        });

        $(document).on('click', '.btn-book-request-delete', function(e) {
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
                    title: 'Delete book request?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (result.isConfirmed) remove();
                });
            } else if (confirm('Delete this book request?')) remove();
        });
    </script>
@endsection
