@extends('backend.layouts.master')

@section('meta')
    <title>Courier Management</title>
@endsection

@section('content')
    <style>
        /* Courier Items Table (Assign Specimen layout with pixel-locked vertical alignment) */
        #courierItemsTable {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        #courierItemsTable thead th {
            vertical-align: middle !important;
            padding: 8px 6px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }
        #courierItemsTable tbody td {
            vertical-align: middle !important;
            padding: 6px 6px !important;
            line-height: 1 !important;
        }
        #courierItemsTable .col-qty {
            width: 120px;
        }
        #courierItemsTable .col-action {
            width: 54px;
        }
        @media (max-width: 575.98px) {
            #courierItemsTable .col-qty {
                width: 88px;
            }
            #courierItemsTable .col-action {
                width: 48px;
            }
        }
        #courierItemsTable .select2-container {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
            height: auto !important;
            line-height: 1 !important;
        }
        #courierItemsTable .select2-container .selection {
            display: block !important;
            width: 100% !important;
            height: auto !important;
            line-height: 1 !important;
        }
        #courierItemsTable .select2-container .dropdown-wrapper {
            display: none !important;
        }
        #courierItemsTable .select2-container--default .select2-selection--single {
            height: 40px !important;
            min-height: 40px !important;
            max-height: 40px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            background-color: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            display: flex !important;
            align-items: center !important;
            box-sizing: border-box !important;
            position: relative !important;
        }
        #courierItemsTable .select2-container--default .select2-selection--single .select2-selection__rendered {
            display: block !important;
            width: 100% !important;
            height: 38px !important;
            line-height: 38px !important;
            padding-left: 12px !important;
            padding-right: 30px !important;
            margin: 0 !important;
            font-size: 0.875rem !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        #courierItemsTable .select2-selection__placeholder {
            display: inline !important;
        }
        #courierItemsTable .select2-container--open .select2-selection--single,
        #courierItemsTable .courier-qty:focus {
            border-color: var(--primary-600, #487fff) !important;
            outline: 0 !important;
        }
        #courierItemsTable .select2-container--default .select2-selection--single .select2-selection__clear {
            height: 38px !important;
            line-height: 38px !important;
            margin: 0 4px 0 0 !important;
            padding: 0 4px !important;
            float: right !important;
            display: inline-flex !important;
            align-items: center !important;
            font-size: 16px !important;
        }
        #courierItemsTable .select2-container--default .select2-selection--single .select2-selection__arrow {
            position: absolute !important;
            height: 38px !important;
            top: 0 !important;
            right: 6px !important;
            width: 20px !important;
        }
        #courierItemsTable .courier-qty {
            display: block !important;
            width: 100% !important;
            height: 40px !important;
            min-height: 40px !important;
            max-height: 40px !important;
            line-height: 38px !important;
            padding: 0 10px !important;
            margin: 0 !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            font-size: 0.875rem !important;
            box-sizing: border-box !important;
            background-color: #fff !important;
        }
        #courierItemsTable .btnRemoveRow {
            width: 40px !important;
            min-width: 40px !important;
            max-width: 40px !important;
            height: 40px !important;
            min-height: 40px !important;
            max-height: 40px !important;
            padding: 0 !important;
            margin: 0 auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            line-height: 1 !important;
            border-radius: 6px !important;
            box-sizing: border-box !important;
        }
    </style>
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
                        data-ajax-modal="{{ route('courier-marketings.createModal') }}" data-size="xl"
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

                const cleanRowHtml = $modal.find('#courierItemsTable tbody tr:first').prop('outerHTML') || '';

                // 1. Auto-init .js-s2 and .js-s2-ajax dropdowns (for Rep select and product items)
                if (window.S2 && typeof window.S2.auto === 'function') {
                    window.S2.auto($modal);
                }

                const $repSelect = $modal.find('#courierRepSelect');
                const $upazilaSelect = $modal.find('#courierUpazilaSelect');
                const $instSelect = $modal.find('#courierInstitutionSelect');
                const $teacherSelect = $modal.find('#courierTeacherPhoneSelect');

                // 2. Upazila Select2 (filtered by selected marketing_representative_id)
                if ($upazilaSelect.length) {
                    $upazilaSelect.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Search / Select Upazila',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('courier-marketings.upazilas.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            cache: false,
                            data: function(params) {
                                return {
                                    q: params.term || '',
                                    marketing_representative_id: $repSelect.val() || ''
                                };
                            },
                            processResults: function(data) {
                                return data;
                            }
                        }
                    }).on('select2:select', function(e) {
                        const data = e.params.data || {};
                        $modal.find('#courierHiddenUpazilaId').val(data.id || '');
                        $modal.find('#courierHiddenDistrictId').val(data.district_id || '');
                        $modal.find('#courierHiddenDivisionId').val(data.division_id || '');
                        $modal.find('#courierDistrictDisplay').val(data.district_name || '');
                        $modal.find('#courierDivisionDisplay').val(data.division_name || '');

                        // Clear downstream
                        $instSelect.empty().val(null).trigger('change');
                        $teacherSelect.empty().val(null).trigger('change');
                        $modal.find('#courierHiddenInstitutionId').val('');
                        $modal.find('#autoTeacherName').val('');
                        $modal.find('#autoLocationAddress').val('');
                    }).on('select2:clear', function() {
                        $modal.find('#courierHiddenUpazilaId').val('');
                        $modal.find('#courierHiddenDistrictId').val('');
                        $modal.find('#courierHiddenDivisionId').val('');
                        $modal.find('#courierDistrictDisplay').val('');
                        $modal.find('#courierDivisionDisplay').val('');

                        $instSelect.empty().val(null).trigger('change');
                        $teacherSelect.empty().val(null).trigger('change');
                        $modal.find('#courierHiddenInstitutionId').val('');
                        $modal.find('#autoTeacherName').val('');
                        $modal.find('#autoLocationAddress').val('');
                    });
                }

                // When Representative changes, reset Upazila and downstream
                $repSelect.off('change.repCascade').on('change.repCascade', function() {
                    $upazilaSelect.empty().val(null).trigger('change');
                    $modal.find('#courierHiddenUpazilaId').val('');
                    $modal.find('#courierHiddenDistrictId').val('');
                    $modal.find('#courierHiddenDivisionId').val('');
                    $modal.find('#courierDistrictDisplay').val('');
                    $modal.find('#courierDivisionDisplay').val('');
                    $instSelect.empty().val(null).trigger('change');
                    $teacherSelect.empty().val(null).trigger('change');
                    $modal.find('#courierHiddenInstitutionId').val('');
                    $modal.find('#autoTeacherName').val('');
                    $modal.find('#autoLocationAddress').val('');
                });

                // 3. Institution Select2 (filtered by selected upazila_id)
                if ($instSelect.length) {
                    $instSelect.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select Institution',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('courier-marketings.institutions.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            cache: false,
                            data: function(params) {
                                return {
                                    q: params.term || '',
                                    upazila_id: $modal.find('#courierHiddenUpazilaId').val() || $upazilaSelect.val() || ''
                                };
                            },
                            processResults: function(data) {
                                return data;
                            }
                        }
                    }).on('select2:select', function(e) {
                        const data = e.params.data || {};
                        $modal.find('#courierHiddenInstitutionId').val(data.id || '');
                        if (data.address && !$modal.find('#autoLocationAddress').val()) {
                            $modal.find('#autoLocationAddress').val(data.address);
                        }

                        // Reset teacher select to filter by this institution
                        $teacherSelect.empty().val(null).trigger('change');
                        $modal.find('#autoTeacherName').val('');
                    }).on('select2:clear', function() {
                        $modal.find('#courierHiddenInstitutionId').val('');
                        $teacherSelect.empty().val(null).trigger('change');
                        $modal.find('#autoTeacherName').val('');
                    });
                }

                // 4. Teacher / Phone Select2 (filtered by upazila_id & institution_id)
                if ($teacherSelect.length) {
                    $teacherSelect.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Search by Phone or Teacher Name',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('courier-marketings.teachersByPhone.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            cache: false,
                            data: function(params) {
                                return {
                                    q: params.term || '',
                                    upazila_id: $modal.find('#courierHiddenUpazilaId').val() || $upazilaSelect.val() || '',
                                    institution_id: $modal.find('#courierHiddenInstitutionId').val() || $instSelect.val() || ''
                                };
                            },
                            processResults: function(data) {
                                return data;
                            }
                        }
                    }).on('select2:select', function(e) {
                        const data = e.params.data || {};
                        $modal.find('#autoTeacherName').val(data.teacher_name || '—');
                        $modal.find('#autoLocationAddress').val(data.address || '—');

                        // If institution wasn't selected, auto-select it from teacher's institution
                        if (!$instSelect.val() && data.institution_id) {
                            $modal.find('#courierHiddenInstitutionId').val(data.institution_id);
                            const opt = new Option(data.institution_name, data.institution_id, true, true);
                            $instSelect.append(opt).trigger('change.select2');
                        }
                    }).on('select2:clear', function() {
                        $modal.find('#autoTeacherName').val('');
                        if (!$instSelect.val()) {
                            $modal.find('#autoLocationAddress').val('');
                        }
                    });
                }

                // 5. Products Table (Assign Specimen style)
                const $courierTable = $modal.find('#courierItemsTable tbody');
                let courierRowIndex = 1;

                function calculateCourierTotal() {
                    let total = 0;
                    $modal.find('.courier-qty').each(function() {
                        total += parseInt($(this).val() || 0, 10);
                    });
                    $modal.find('#courierTotalText').text(total);
                }

                $modal.off('click.addCourierItem', '#btnAddCourierItem').on('click.addCourierItem', '#btnAddCourierItem', function(e) {
                    e.preventDefault();
                    if (!cleanRowHtml) return;

                    const $row = $(cleanRowHtml);
                    $row.find('input, select').each(function() {
                        const name = $(this).attr('name');
                        if (!name) return;
                        $(this).attr('name', name.replace(/\[\d+]/, `[${courierRowIndex}]`));
                        if ($(this).hasClass('courier-qty')) {
                            $(this).val(1);
                        } else {
                            $(this).val('').html('');
                        }
                    });

                    $courierTable.append($row);

                    if (window.S2 && typeof window.S2.auto === 'function') {
                        window.S2.auto($row);
                    }

                    courierRowIndex++;
                    calculateCourierTotal();
                });

                $modal.off('click.remCourierRow', '.btnRemoveRow').on('click.remCourierRow', '.btnRemoveRow', function(e) {
                    e.preventDefault();
                    if ($courierTable.find('tr').length <= 1) return;
                    $(this).closest('tr').remove();
                    calculateCourierTotal();
                });

                $modal.off('input.courierQty change.courierQty', '.courier-qty').on('input.courierQty change.courierQty', '.courier-qty', function() {
                    calculateCourierTotal();
                });

                calculateCourierTotal();
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
        window.ModalHooks = window.ModalHooks || {};
        window.ModalHooks['courier-marketing-create'] = {
            onLoad: window.CourierMarketingIndex.onLoad,
            onSuccess: window.CourierMarketingIndex.onSaved
        };

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
