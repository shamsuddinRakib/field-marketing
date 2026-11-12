@extends('backend.layouts.master')

@section('meta')
    <title>Marketing Representatives</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Marketing Representative List</h6>
            <p class="m-0">Manage Marketing Representatives</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Marketing Representatives</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Representative List</h5>
            <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
                <div class="search-set me-2">
                    <div id="tableSearch" class="search-input"></div>
                </div>
                <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
                    @include('backend.include.buttons')
                </ul>
                @if (auth()->user()->canDo('marketing-representative.marketing-representatives.store') ||
                        auth()->user()->canDo('usermanage.marketing-representatives.store') ||
                        auth()->user()->isSuper())
                    <button type="button"
                        class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
                        data-ajax-modal="{{ route('marketing-representative.marketing-representatives.createModal') }}"
                        data-size="xl" data-onload="MarketingRepresentativesIndex.onLoad"
                        data-onsuccess="MarketingRepresentativesIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add representative
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="marketingRepresentativesTable"
                style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" id="select-all">
                                <label class="form-check-label">S.L</label>
                            </div>
                        </th>
                        <th>Mr. Details</th>
                        {{-- <th>Mobile</th> --}}
                        {{-- <th>Email</th> --}}
                        <th>Designation</th>
                        <th>Current Status</th>
                        <th>Branch</th>
                        <th>Upazila</th>
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
        var DATATABLE_URL = "{{ route('marketing-representative.marketing-representatives.list.ajax') }}";

        window.initMarketingRepresentativeTerritory = function($modal) {
            if (!$modal || !$modal.length) $modal = $('#AjaxModal');

            // 1. Branch Select2
            $modal.find('.js-branch-select').each(function() {
                const $select = $(this);
                if ($select.data('select2')) $select.select2('destroy');
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select branch',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('org.branches.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({ q: params.term || '' }),
                        processResults: data => data
                    }
                });
            });

            // 2. Designation Select2
            $modal.find('.js-designation-select').each(function() {
                const $select = $(this);
                if ($select.data('select2')) $select.select2('destroy');
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select designation',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('mr-designation.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({ q: params.term || '' }),
                        processResults: data => data
                    }
                });
            });

            // 3. District & Upazila Cascading Select2
            $modal.find('form').each(function() {
                const $form = $(this);
                const $districtSelect = $form.find('.js-district-select');
                const $upazilaSelect = $form.find('.js-upazila-select');
                const $divisionNameInput = $form.find('input[name="division_name"]');
                const $divisionIdInput = $form.find('input[name="division_id"]');

                if (!$districtSelect.length || !$upazilaSelect.length) return;

                if ($districtSelect.data('select2')) $districtSelect.select2('destroy');
                if ($upazilaSelect.data('select2')) $upazilaSelect.select2('destroy');

                // District Select2 (Multiple)
                $districtSelect.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select District(s)',
                    allowClear: true,
                    multiple: true,
                    ajax: {
                        url: "{{ route('marketing-representative.marketing-representatives.districts.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({ q: params.term || '' }),
                        processResults: data => data
                    }
                });

                // Upazila Select2 (Multiple)
                $upazilaSelect.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select Upazila(s)',
                    allowClear: true,
                    multiple: true,
                    ajax: {
                        url: "{{ route('marketing-representative.marketing-representatives.upazilas.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: function(params) {
                            const districtIds = $districtSelect.val() || [];
                            return {
                                q: params.term || '',
                                district_ids: districtIds
                            };
                        },
                        processResults: data => data
                    }
                });

                // Tag selected upazila option with its district_id
                $upazilaSelect.off('select2:select.territory').on('select2:select.territory', function(e) {
                    const data = e.params?.data;
                    if (data && data.district_id) {
                        $upazilaSelect.find('option[value="' + data.id + '"]').attr('data-district-id', data.district_id);
                    }
                });

                function syncTerritoryState() {
                    const selectedDistData = $districtSelect.select2('data') || [];
                    const selectedDistIds = ($districtSelect.val() || []).map(String);
                    const hasDistricts = selectedDistIds.length > 0;

                    // Calculate Divisions
                    const divisionNames = [];
                    let firstDivisionId = '';
                    selectedDistData.forEach(function(item) {
                        const divName = item.division_name || $(item.element).data('division-name');
                        const divId = item.division_id || $(item.element).data('division-id');
                        if (divName && !divisionNames.includes(divName)) {
                            divisionNames.push(divName);
                        }
                        if (divId && !firstDivisionId) {
                            firstDivisionId = divId;
                        }
                    });

                    $divisionNameInput.val(divisionNames.join(', '));
                    $divisionIdInput.val(firstDivisionId);

                    const $uContainer = $upazilaSelect.next('.select2-container');
                    if (!hasDistricts) {
                        // No districts selected -> lock Upazila & clear selections
                        $upazilaSelect.val(null).trigger('change.select2');
                        $upazilaSelect.prop('disabled', true);
                        $uContainer.addClass('select2-container--disabled');
                        $uContainer.find('.select2-selection').attr('aria-disabled', 'true').attr('tabindex', '-1');
                        $uContainer.find('.select2-search__field').prop('disabled', true).attr('placeholder', 'Select District first');
                    } else {
                        // Districts selected -> unlock Upazila
                        $upazilaSelect.prop('disabled', false);
                        $uContainer.removeClass('select2-container--disabled');
                        $uContainer.find('.select2-selection').attr('aria-disabled', 'false').attr('tabindex', '0');
                        $uContainer.find('.select2-search__field').prop('disabled', false).attr('placeholder', 'Select Upazila(s)');

                        // Clean up any upazila not in selected districts
                        let removedAny = false;
                        $upazilaSelect.find('option:selected').each(function() {
                            const optDist = String($(this).data('district-id') || $(this).attr('data-district-id') || '');
                            if (optDist && !selectedDistIds.includes(optDist)) {
                                $(this).prop('selected', false);
                                removedAny = true;
                            }
                        });
                        if (removedAny) {
                            $upazilaSelect.trigger('change.select2');
                        }
                    }
                }

                $districtSelect.off('select2:select.territory select2:unselect.territory select2:clear.territory change.territory')
                    .on('select2:select.territory select2:unselect.territory select2:clear.territory change.territory', syncTerritoryState);

                // Initial sync
                syncTerritoryState();
            });
        };

        window.MarketingRepresentativesIndex = {
            onLoad: function($modal) {
                window.initMarketingRepresentativeTerritory($modal);
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

        window.ModalHooks = window.ModalHooks || {};
        window.ModalHooks['marketing-representative-create'] = { onLoad: window.MarketingRepresentativesIndex.onLoad };
        window.ModalHooks['marketing-representative-edit'] = { onLoad: window.MarketingRepresentativesIndex.onLoad };

        $(document).on('click', '.btn-representative-delete', function(e) {
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
                    title: 'Delete representative?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (result.isConfirmed) remove();
                });
            } else if (confirm('Delete this representative?')) remove();
        });
    </script>
@endsection
