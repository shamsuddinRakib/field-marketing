@extends('backend.layouts.master')

@section('meta')
    <title>Courier Management</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Courier Management List</h6>
            <p class="m-0">Manage Teacher Book Dispatches & Call Center Verifications</p>
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
                @if (auth()->user()->canDo('courier-marketings.store') || auth()->user()->isSuper())
                    <button type="button"
                        class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
                        data-ajax-modal="{{ route('courier-marketings.createModal') }}" data-size="lg"
                        data-onload="CourierMarketingIndex.onLoad" data-onsuccess="CourierMarketingIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add Courier Dispatch
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
                        <th>Representative</th>
                        <th>Teacher</th>
                        <th>Institution</th>
                        <th>Products Dispatched</th>
                        <th>Total Qty</th>
                        <th>Courier Company</th>
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

        function reindexProductRows($container) {
            const rows = $container.find('.courier-product-row');
            rows.each(function(index) {
                $(this).find('.js-product-select').attr('name', `product_items[${index}][product_id]`);
                $(this).find('.js-quantity-input').attr('name', `product_items[${index}][quantity]`);
            });
            rows.find('.btn-remove-row').prop('disabled', rows.length <= 1);
        }

        window.CourierMarketingIndex = {
            onLoad: function($modal) {
                $modal.find('.modal-dialog').addClass('modal-dialog-scrollable');
                $modal.off('hidden.bs.modal.courierMarketingScroll').on('hidden.bs.modal.courierMarketingScroll', function() {
                    $(this).find('.modal-dialog').removeClass('modal-dialog-scrollable');
                });

                // 1. Auto-init all .js-s2 and .js-s2-ajax dropdowns using global S2 helper
                if (window.S2 && typeof window.S2.auto === 'function') {
                    window.S2.auto($modal);
                }

                // 2. Teacher Phone Select2 Auto-fill on select / clear
                $modal.find('.js-teacher-phone-select').off('select2:select.autofill').on('select2:select.autofill', function(e) {
                    const data = e.params.data || {};
                    $modal.find('#autoTeacherName').val(data.teacher_name || '—');
                    $modal.find('#autoInstitutionName').val(data.institution_name || '—');
                    const locText = data.location ? (data.location + (data.address ? ' — ' + data.address : '')) : (data.address || '—');
                    $modal.find('#autoLocationAddress').val(locText);
                }).off('select2:clear.autofill').on('select2:clear.autofill', function() {
                    $modal.find('#autoTeacherName').val('');
                    $modal.find('#autoInstitutionName').val('');
                    $modal.find('#autoLocationAddress').val('');
                });

                // 3. Add More Product Row
                $modal.off('click.addProd', '#btnAddProductRow').on('click.addProd', '#btnAddProductRow', function(e) {
                    e.preventDefault();
                    const $container = $modal.find('#courierProductItemsContainer');
                    const templateHtml = $('#courier-product-row-template').html();
                    if (!templateHtml) return;

                    const $newRow = $(templateHtml.trim());
                    $container.append($newRow);
                    reindexProductRows($container);

                    if (window.S2 && typeof window.S2.auto === 'function') {
                        window.S2.auto($newRow);
                    }
                });

                // 4. Remove Product Row
                $modal.off('click.remProd', '.btn-remove-row').on('click.remProd', '.btn-remove-row', function(e) {
                    e.preventDefault();
                    const $container = $modal.find('#courierProductItemsContainer');
                    if ($container.find('.courier-product-row').length <= 1) return;
                    $(this).closest('.courier-product-row').remove();
                    reindexProductRows($container);
                });
            },
            onSaved: function(res) {
                $('.AjaxDataTable').DataTable().ajax.reload(null, false);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: res?.msg || 'Saved successfully',
                        timer: 1000,
                        showConfirmButton: false
                    });
                }
            }
        };

        window.CourierMarketingsIndex = window.CourierMarketingIndex;

        // Delete confirmation
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
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: res?.msg || 'Deleted',
                            timer: 1000,
                            showConfirmButton: false
                        });
                    }
                }).fail(function(xhr) {
                    const msg = xhr.responseJSON?.msg || 'Delete failed';
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: msg
                        });
                    }
                });
            };

            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete courier dispatch?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (result.isConfirmed) remove();
                });
            } else if (confirm('Delete this courier dispatch?')) {
                remove();
            }
        });
    </script>
@endsection
