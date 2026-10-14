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
                        <th>Institution Name</th>
                        <th>Product Name</th>
                        <th>Issued Quantity</th>
                        <th>Returned Quantity</th>
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

        function initBookReturnProductSelect($select, $modal) {
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
                        marketing_representative_id: $modal.find('[name="marketing_representative_id"]').val()
                    }),
                    processResults: data => data
                }
            });
        }

        function initBookReturnDistributionSelect($select, $modal) {
            if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
            $select.select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: 'Select product from Distribution List',
                allowClear: true,
                ajax: {
                    // Same source as Book Requests: product_distribution list
                    url: "{{ route('product-distribution.distributions.select2') }}",
                    dataType: 'json',
                    delay: 200,
                    data: params => ({
                        q: params.term || '',
                        marketing_representative_id: $modal.find('[name="marketing_representative_id"]').val(),
                        return_group_id: $modal.find('[name="return_group_id"]').val()
                    }),
                    processResults: data => ({
                        results: (data.results || []).filter(item => !$modal.find('.js-distribution-select').not($select)
                            .toArray().some(other => String($(other).val()) === String(item.id)))
                    })
                }
            }).on('select2:select', function(event) {
                const item = event.params.data;
                const $row = $(this).closest('.book-return-item-row');
                $row.find('.js-issued-quantity').val(item.issued_quantity || item.quantity || '');
                const maxQty = item.remaining_quantity || item.quantity || 0;
                const $retQty = $row.find('.js-returned-quantity, [name$="[returned_quantity]"]');
                $retQty.attr('max', maxQty);
                // Clamp over-Dist immediately (must be equal or less)
                const cur = parseInt($retQty.val(), 10);
                if (!isNaN(cur) && cur > maxQty) $retQty.val(maxQty);
            }).on('select2:clear', function() {
                const $row = $(this).closest('.book-return-item-row');
                $row.find('.js-issued-quantity').val('');
                $row.find('.js-returned-quantity, [name$="[returned_quantity]"]').removeAttr('max');
            });
        }

        // Backward-compat alias (old class name used in cached HTML)
        function initBookReturnRequestSelect($select, $modal) {
            return initBookReturnDistributionSelect($select, $modal);
        }

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
                $modal.find('.js-institution-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select institution',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('institution.institutions.select2') }}",
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
                    initBookReturnProductSelect($(this), $modal);
                });
                $modal.find('.js-distribution-select, .js-book-request-select').each(function() {
                    initBookReturnDistributionSelect($(this), $modal);
                });
                $modal.find('.book-return-items').each(function() {
                    $(this).data('next-index', $(this).find('.book-return-item-row').length);
                });
                // Products come from Distribution List (same as Book Requests):
                // filter by MR only, no request_date needed
                $modal.find('.js-representative-select').on('change', function() {
                    $modal.find('.js-distribution-select, .js-book-request-select').val(null).trigger('change');
                    $modal.find('.js-issued-quantity').val('');
                    $modal.find('.js-distribution-select, .js-book-request-select').each(function() {
                        initBookReturnDistributionSelect($(this), $modal);
                    });
                    // Products also come from Distribution List -> refresh on rep change
                    $modal.find('.js-product-select').val(null).trigger('change');
                    $modal.find('.js-product-select').each(function() {
                        initBookReturnProductSelect($(this), $modal);
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

        $(document).on('click', '.btn-add-return-item', function() {
            const $modal = $(this).closest('.modal');
            const $items = $(this).siblings('.book-return-items');
            const $row = $($('#book-return-item-template').html().trim());
            const index = Number($items.data('next-index') || 0);
            $items.data('next-index', index + 1);
            $row.find('select')
                .attr('name', `items[${index}][product_distribution_id]`)
                .siblings('.invalid-feedback').addClass(`items.${index}.product_distribution_id-error`);
            $row.find('input:not(.js-issued-quantity)')
                .attr('name', `items[${index}][returned_quantity]`)
                .siblings('.invalid-feedback').addClass(`items.${index}.returned_quantity-error`);
            $items.append($row);
            initBookReturnDistributionSelect($row.find('.js-distribution-select, .js-book-request-select'), $modal);
            $items.find('.btn-remove-return-item').prop('disabled', false);
        });

        // Block returned quantity over Dist/Issued (must be equal or less)
        $(document).on('input', '.book-return-item-row .js-returned-quantity, .book-return-item-row input[name$="[returned_quantity]"]', function() {
            const max = parseInt($(this).attr('max'), 10);
            if (isNaN(max)) return;
            const val = parseInt($(this).val(), 10);
            if (!isNaN(val) && val > max) {
                $(this).val(max);
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Exceeds Distribution', text: 'Returned quantity must be equal or less than Dist/Remaining (' + max + ').', timer: 1500, showConfirmButton: false });
            }
        });

        $(document).on('submit', 'form[action*="book-returns"]', function(e) {
            let blocked = false;
            $(this).find('.book-return-item-row').each(function() {
                const $qty = $(this).find('.js-returned-quantity, input[name$="[returned_quantity]"]');
                const max = parseInt($qty.attr('max'), 10);
                const val = parseInt($qty.val(), 10);
                if (!isNaN(max) && !isNaN(val) && val > max) { blocked = true; }
            });
            if (blocked) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Blocked', text: 'Returned quantity exceeds Dist. Must be equal or less.' });
                return false;
            }
        });

        $(document).on('click', '.btn-remove-return-item', function() {
            const $items = $(this).closest('.book-return-items');
            if ($items.find('.book-return-item-row').length <= 1) return;
            $(this).closest('.book-return-item-row').remove();
            if ($items.find('.book-return-item-row').length === 1) {
                $items.find('.btn-remove-return-item').prop('disabled', true);
            }
        });

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
