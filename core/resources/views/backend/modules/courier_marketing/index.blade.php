@extends('backend.layouts.master')

@section('meta')
    <title>Courier Management</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Courier Management List</h6>
            <p class="m-0">Manage Courier</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Courier Management</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Courier List</h5>
            <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
                <div class="search-set me-2">
                    <div id="tableSearch" class="search-input"></div>
                </div>
                <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
                    @include('backend.include.buttons')
                </ul>
                @if (auth()->user()->canDo('courier-marketing.store'))
                    <button type="button"
                        class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
                        data-ajax-modal="{{ route('courier-marketings.createModal') }}"
                        data-size="lg" data-onload="CourierMarketingIndex.onLoad"
                        data-onsuccess="CourierMarketingIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add Courier
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="courierMarketingTable"
                style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">S.L</th>
                        <th>Representative Name</th>
                        <th>Teacher Name</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Courier Company</th>
                        <th>Tracking Number</th>
                        <th>Dispatch Date</th>
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
        var DATATABLE_URL = "{{ route('courier-marketings.list.ajax') }}";

        function renderRequestItems($modal, items) {
            const $container = $modal.find('.courier-marketing-product-items');
            $container.empty();

            const availableItems = (items || []).filter(item => parseInt(item.available_quantity, 10) > 0);

            if (!availableItems.length) {
                $container.html('<div class="col-12"><div class="alert alert-warning py-8 px-12 mb-0 text-sm">No available quantity remaining for this approved request.</div></div>');
                return;
            }

            availableItems.forEach((item, index) => {
                const $row = $($('#courier-marketing-product-template').html().trim());
                $row.find('.js-product-name').val(item.product_name);
                $row.find('.js-book-request-item-id')
                    .attr('name', `product_items[${index}][book_request_item_id]`)
                    .val(item.book_request_item_id);

                $row.find('.js-item-id-error').addClass(`product_items.${index}.book_request_item_id-error`);

                const $qty = $row.find('.js-quantity-input');
                $qty.attr('name', `product_items[${index}][quantity]`)
                    .attr('max', item.available_quantity)
                    .val(item.available_quantity);

                $row.find('.js-qty-error').addClass(`product_items.${index}.quantity-error`);
                $row.find('.js-dist-hint').text(
                    `Approved: ${item.approved_quantity} | Dispatched: ${item.previously_dispatched} | Available: ${item.available_quantity}`
                );

                $container.append($row);
            });

            if ($container.find('.courier-marketing-product-row').length <= 1) {
                $container.find('.btn-remove-product').prop('disabled', true);
            }
        }

        function reindexProductRows($container) {
            $container.find('.courier-marketing-product-row').each(function(index) {
                $(this).find('.js-book-request-item-id').attr('name', `product_items[${index}][book_request_item_id]`);
                $(this).find('.js-quantity-input').attr('name', `product_items[${index}][quantity]`);
            });
            $container.find('.btn-remove-product').prop(
                'disabled',
                $container.find('.courier-marketing-product-row').length <= 1
            );
        }

        window.CourierMarketingIndex = {
            onLoad: function($modal) {
                $modal.find('.modal-dialog').addClass('modal-dialog-scrollable');
                $modal.off('hidden.bs.modal.courierMarketingScroll').on('hidden.bs.modal.courierMarketingScroll', function() {
                    $(this).find('.modal-dialog').removeClass('modal-dialog-scrollable');
                });

                // Approved Book Request Select2
                $modal.find('.js-book-request-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select approved book request',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('courier-marketings.approvedRequests.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({ q: params.term || '' }),
                            processResults: data => data
                        }
                    }).off('select2:select.loadItems').on('select2:select.loadItems', function(e) {
                        const bookRequestId = e.params.data.id;
                        $.get("{{ route('courier-marketings.requestItems') }}", { book_request_id: bookRequestId })
                            .done(function(res) {
                                if (res.ok) renderRequestItems($modal, res.items);
                            });
                    }).off('select2:clear.loadItems').on('select2:clear.loadItems', function() {
                        $modal.find('.courier-marketing-product-items').html(
                            '<div class="col-12 text-muted text-sm py-8">Select an approved Book Request to load products.</div>'
                        );
                    });
                });

                // Teacher Select2
                $modal.find('.js-teacher-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select teacher',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('courier-marketings.teachers.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({ q: params.term || '' }),
                            processResults: data => data
                        }
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

        // Keep alias in case any button calls CourierMarketingsIndex
        window.CourierMarketingsIndex = window.CourierMarketingIndex;

        $(document).on('click', '.btn-remove-product', function() {
            const $items = $(this).closest('.courier-marketing-product-items');
            if ($items.find('.courier-marketing-product-row').length <= 1) return;
            $(this).closest('.courier-marketing-product-row').remove();
            reindexProductRows($items);
        });

        // Block quantity over Available
        $(document).on('input', '.courier-marketing-product-row .js-quantity-input', function() {
            const $row = $(this).closest('.courier-marketing-product-row');
            const max = parseInt($(this).attr('max'), 10);
            if (isNaN(max)) return;
            const val = parseInt($(this).val(), 10);
            if (!isNaN(val) && val > max) {
                $(this).val(max);
                const $err = $row.find('.js-qty-error');
                if ($err.length) {
                    $err.text('Quantity cannot exceed available quantity (' + max + ').').show();
                }
            }
        });

        $(document).on('click', '.btn-courier-marketing-delete', function(e) {
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
                }).done(function(res) {
                    $('.AjaxDataTable').DataTable().ajax.reload(null, false);
                    if (window.Swal) Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: res?.msg || 'Deleted',
                        timer: 1000,
                        showConfirmButton: false
                    });
                }).fail(function(xhr) {
                    const msg = xhr.responseJSON?.msg || 'Delete failed';
                    if (window.Swal) Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: msg
                    });
                });
            };
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete courier entry?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (result.isConfirmed) remove();
                });
            } else if (confirm('Delete this courier entry?')) remove();
        });
    </script>
@endsection