@extends('backend.layouts.master')

@section('meta')
    <title>Teachers</title>
@endsection

@section('content')
    <style>
        .teacher-filters-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease-in-out;
        }
        .teacher-col-filter {
            height: 38px !important;
            font-size: 13px !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
            width: 100% !important;
            transition: all 0.15s ease-in-out;
        }
        .teacher-col-filter:focus {
            border-color: #487fff !important;
            outline: 0 !important;
            box-shadow: 0 0 0 3px rgba(72, 127, 255, 0.15) !important;
            background-color: #ffffff !important;
        }
        .teacher-col-filter::placeholder {
            color: #94a3b8 !important;
            font-size: 12px !important;
        }
        .teacher-col-filter:not(:placeholder-shown) {
            border-color: #487fff !important;
            background-color: #f0f7ff !important;
            font-weight: 500;
        }
        .teacher-reset-btn {
            height: 38px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            border: 1px solid #fca5a5 !important;
            color: #ef4444 !important;
            background-color: #fff5f5 !important;
            transition: all 0.2s ease-in-out !important;
        }
        .teacher-reset-btn:hover {
            background-color: #ef4444 !important;
            border-color: #ef4444 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25) !important;
            transform: translateY(-1px);
        }
        .teacher-reset-btn:active {
            transform: translateY(0);
        }
    </style>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Teacher List</h6>
            <p class="m-0">Manage Teachers</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Teachers</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Teacher List</h5>
            <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
                <div class="search-set me-2">
                    <div id="tableSearch" class="search-input"></div>
                </div>
                <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
                    @include('backend.include.buttons')
                </ul>
                @if (auth()->user()->canDo('teacher.teachers.store') || auth()->user()->isSuper())
                    <button type="button"
                        class="btn btn-outline-success btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
                        data-ajax-modal="{{ route('teacher.teachers.importModal') }}" data-size="lg">
                        <iconify-icon icon="lucide:file-spreadsheet" class="text-xl"></iconify-icon>Import Excel
                    </button>
                    <button type="button"
                        class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
                        data-ajax-modal="{{ Route::has('teacher.teachers.createModal') ? route('teacher.teachers.createModal') : '#' }}"
                        data-size="lg" data-onload="TeachersIndex.onLoad" data-onsuccess="TeachersIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add Teacher
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="teachersTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" id="select-all">
                                <label class="form-check-label">S.L</label>
                            </div>
                        </th>
                        <th>Teacher Name</th>
                        <th>Phone</th>
                        <th>Institution</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Upazila</th>
                        <th>District</th>
                        <th>Division</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <!-- Column Quick Filters Row (Positioned below Table, above Pagination) -->
            <div id="teacherFiltersBox" class="teacher-filters-card p-16 radius-8 bg-neutral-50 border border-neutral-200 mt-16 mb-12">
                <div class="d-flex align-items-center justify-content-between mb-12">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center w-28-px h-28-px bg-warning-500 text-primary-600 radius-6">
                            <iconify-icon icon="solar:filter-bold-duotone" class="text-base"></iconify-icon>
                        </span>
                        <span class="fw-semibold text-sm text-neutral-800">Quick Column Filters</span>
                        <span class="badge bg-primary-50 text-primary-600 border border-primary-200 text-xs px-8 py-2 radius-4 d-none" id="activeFilterBadge">
                            <span id="activeFilterCount">0</span> Filter Active
                        </span>
                    </div>
                    <span class="text-secondary-light text-xs d-none d-sm-inline-block">Filter directly by table columns</span>
                </div>
                <div class="row g-2 g-md-3 align-items-end">
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label text-xs fw-semibold text-neutral-700 mb-1 d-flex align-items-center gap-1">
                            <iconify-icon icon="solar:book-bookmark-bold-duotone" class="text-primary-600 text-sm"></iconify-icon>
                            Subject
                        </label>
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm teacher-col-filter radius-6"
                                data-col="subject" placeholder="Filter Subject..." title="Filter by Subject">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label text-xs fw-semibold text-neutral-700 mb-1 d-flex align-items-center gap-1">
                            <iconify-icon icon="solar:diploma-bold-duotone" class="text-primary-600 text-sm"></iconify-icon>
                            Class
                        </label>
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm teacher-col-filter radius-6"
                                data-col="class" placeholder="Filter Class..." title="Filter by Class">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label text-xs fw-semibold text-neutral-700 mb-1 d-flex align-items-center gap-1">
                            <iconify-icon icon="solar:map-point-wave-bold-duotone" class="text-primary-600 text-sm"></iconify-icon>
                            Upazila
                        </label>
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm teacher-col-filter radius-6"
                                data-col="upazila" placeholder="Filter Upazila..." title="Filter by Upazila">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label text-xs fw-semibold text-neutral-700 mb-1 d-flex align-items-center gap-1">
                            <iconify-icon icon="solar:city-bold-duotone" class="text-primary-600 text-sm"></iconify-icon>
                            District
                        </label>
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm teacher-col-filter radius-6"
                                data-col="district" placeholder="Filter District..." title="Filter by District">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label text-xs fw-semibold text-neutral-700 mb-1 d-flex align-items-center gap-1">
                            <iconify-icon icon="solar:global-bold-duotone" class="text-primary-600 text-sm"></iconify-icon>
                            Division
                        </label>
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm teacher-col-filter radius-6"
                                data-col="division" placeholder="Filter Division..." title="Filter by Division">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label d-none d-md-block text-xs text-transparent mb-1">Action</label>
                        <button type="button" class="btn btn-outline-danger btn-sm radius-6 w-100 d-inline-flex align-items-center justify-content-center gap-2 teacher-reset-btn" id="btnResetTableFilters" title="Clear all column filters">
                            <iconify-icon icon="solar:restart-bold" class="text-base"></iconify-icon>
                            <span class="fw-semibold text-xs">Reset Filters</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var DATATABLE_URL =
            "{{ Route::has('teacher.teachers.list.ajax') ? route('teacher.teachers.list.ajax') : url('teacher/teachers/list') }}";

        window.TeachersIndex = {
            onLoad: function($modal) {
                $modal.find('.js-institution-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) return;
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select institution',
                        allowClear: true,
                        ajax: {
                            url: "{{ Route::has('teacher.teachers.institutions.select2') ? route('teacher.teachers.institutions.select2') : (Route::has('institution.institutions.select2') ? route('institution.institutions.select2') : url('institution/institutions/select2')) }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    }).on('select2:select', function(e) {
                        const data = e.params.data;
                        if (data) {
                            $modal.find('#teacher_upazila_display').val(data.upazila_name || '');
                            $modal.find('#teacher_district_display').val(data.district_name || '');
                            $modal.find('#teacher_division_display').val(data.division_name || '');
                            $modal.find('#teacher_upazila_id').val(data.upazila_id || '');
                            $modal.find('#teacher_district_id').val(data.district_id || '');
                            $modal.find('#teacher_division_id').val(data.division_id || '');

                            const $addr = $modal.find('textarea[name="address"]');
                            if (!$addr.val() && data.address) {
                                $addr.val(data.address);
                            }
                        }
                    }).on('select2:clear', function() {
                        $modal.find('#teacher_upazila_display').val('');
                        $modal.find('#teacher_district_display').val('');
                        $modal.find('#teacher_division_display').val('');
                        $modal.find('#teacher_upazila_id').val('');
                        $modal.find('#teacher_district_id').val('');
                        $modal.find('#teacher_division_id').val('');
                    });
                });
                $modal.find('.js-department-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) return;
                    const placeholder = $select.data('placeholder') || 'Select Department';
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: placeholder,
                        allowClear: true,
                        ajax: {
                            url: "{{ Route::has('department.select2') ? route('department.select2') : url('department/department/select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    });
                });
                $modal.find('.js-subject-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) return;
                    const placeholder = $select.data('placeholder') || 'Select Subject(s)';
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: placeholder,
                        allowClear: true,
                        tags: true,
                        ajax: {
                            url: "{{ Route::has('subject.select2') ? route('subject.select2') : url('subject/subject/select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: function(data) {
                                return {
                                    results: (data.results || []).map(function(item) {
                                        return {
                                            id: item.text || item.id,
                                            text: item.text
                                        };
                                    })
                                };
                            }
                        }
                    });
                });
                $modal.find('.js-class-select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) return;
                    const placeholder = $select.data('placeholder') || 'Select Class(es)';
                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: placeholder,
                        allowClear: true,
                        tags: true,
                        ajax: {
                            url: "{{ Route::has('class.select2') ? route('class.select2') : url('class/classes/select2/type') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: function(data) {
                                return {
                                    results: (data.results || []).map(function(item) {
                                        return {
                                            id: item.text || item.id,
                                            text: item.text
                                        };
                                    })
                                };
                            }
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

        // Quick Add Institution Modal from inside Teacher Modals
        $(document).on('click', '.js-quick-add-institution', function(e) {
            e.preventDefault();
            const $triggerBtn = $(this);
            const origBtnHtml = $triggerBtn.html();
            const $parentContainer = $triggerBtn.closest('.modal, #AjaxModal, #teacherImportContainer');
            const $targetSelect = $parentContainer.find('.js-institution-select').first();

            const $secModal = $('#secondaryAjaxModal');
            const $content = $secModal.find('.modal-content');

            // Visual indicator on trigger button while fetching form
            $triggerBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
                );

            $.ajax({
                url: "{{ route('institution.institutions.createModal') }}",
                type: 'GET'
            }).done(function(html) {
                $triggerBtn.prop('disabled', false).html(origBtnHtml);
                $content.html(html);
                $secModal.modal('show');

                // Bind Upazila select2 in secondary modal
                const $upazilaSelect = $secModal.find('.js-upazila-select');
                if ($upazilaSelect.length) {
                    $upazilaSelect.select2({
                        dropdownParent: $secModal,
                        width: '100%',
                        placeholder: 'Select Upazila',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('institution.institutions.upazilas.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    }).on('select2:select', function(evt) {
                        const d = evt.params.data;
                        $secModal.find('#district_name').val(d.district_name || '');
                        $secModal.find('#district_id').val(d.district_id || '');
                        $secModal.find('#division_name').val(d.division_name || '');
                        $secModal.find('#division_id').val(d.division_id || '');
                    }).on('select2:clear', function() {
                        $secModal.find('#district_name, #district_id, #division_name, #division_id')
                            .val('');
                    });
                }

                // Intercept form submit inside secondary modal
                $secModal.find('form').off('submit.quickAdd').on('submit.quickAdd', function(subEvt) {
                    subEvt.preventDefault();
                    const $form = $(this);
                    const $submitBtn = $form.find('[type="submit"]').prop('disabled', true);
                    $form.find('.invalid-feedback.d-block').hide().text('');
                    $form.find('.is-invalid').removeClass('is-invalid');

                    $.ajax({
                        url: $form.attr('action'),
                        type: 'POST',
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        dataType: 'json'
                    }).done(function(res) {
                        if (res && res.ok) {
                            $secModal.modal('hide');
                            const instText = (res.institution_name || $form.find(
                                '#institution_name').val() || 'Institution') + (res
                                .code ? ` (${res.code})` : '');
                            const newOption = new Option(instText, res.id, true, true);
                            $targetSelect.append(newOption).trigger('change');

                            // Automatically populate location into teacher modal
                            const upzName = $secModal.find('#upazila_id option:selected')
                                .text() || $secModal.find(
                                    '.js-upazila-select option:selected').text() || '';
                            const distName = $secModal.find('#district_name').val() || '';
                            const divName = $secModal.find('#division_name').val() || '';
                            const upzId = $secModal.find('#upazila_id').val() || $secModal
                                .find('.js-upazila-select').val() || '';
                            const distId = $secModal.find('#district_id').val() || '';
                            const divId = $secModal.find('#division_id').val() || '';
                            const addr = $secModal.find('[name="address"]').val() || '';

                            $parentContainer.find('#teacher_upazila_display').val(upzName);
                            $parentContainer.find('#teacher_district_display').val(
                            distName);
                            $parentContainer.find('#teacher_division_display').val(divName);
                            $parentContainer.find('#teacher_upazila_id').val(upzId);
                            $parentContainer.find('#teacher_district_id').val(distId);
                            $parentContainer.find('#teacher_division_id').val(divId);
                            const $addrField = $parentContainer.find(
                                'textarea[name="address"]');
                            if (!$addrField.val() && addr) {
                                $addrField.val(addr);
                            }

                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Institution Added!',
                                    text: `${instText} has been created and selected.`,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }
                        } else {
                            alert(res?.msg || 'Could not save institution.');
                            $submitBtn.prop('disabled', false);
                        }
                    }).fail(function(xhr) {
                        $submitBtn.prop('disabled', false);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON
                            .errors) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                const $errDiv = $form.find(`.${key}-error`);
                                if ($errDiv.length) {
                                    $errDiv.text(errors[key][0]).show();
                                }
                                $form.find(`[name="${key}"]`).addClass(
                                'is-invalid');
                            });
                        } else {
                            alert('Save failed: ' + (xhr.responseJSON?.message || xhr
                                .statusText));
                        }
                    });
                });
            }).fail(function() {
                $triggerBtn.prop('disabled', false).html(origBtnHtml);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load institution form.'
                    });
                } else {
                    alert('Failed to load institution form.');
                }
            });
        });

        // Fix nested Bootstrap 5 modal stacking & scrolling
        $('#secondaryAjaxModal').off('shown.bs.modal.stack').on('shown.bs.modal.stack', function() {
            $('.modal-backdrop').last().css('z-index', 1058);
        });

        $('#secondaryAjaxModal').off('hidden.bs.modal.stack').on('hidden.bs.modal.stack', function() {
            if ($('#AjaxModal').is(':visible') || $('.modal.show').length) {
                $('body').addClass('modal-open');
            }
        });

        $(document).on('click', '.btn-teacher-delete', function(e) {
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
                    title: 'Delete teacher?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (result.isConfirmed) remove();
                });
            } else if (confirm('Delete this teacher?')) remove();
        });

        // =========================================================================
        // COLUMN-WISE LIVE SEARCH FILTERS (Positioned below table, above pagination)
        // =========================================================================
        function positionTeacherFilters() {
            const $box = $('#teacherFiltersBox');
            const $dtBottom = $('#teachersTable_wrapper .dt-bottom');
            if ($dtBottom.length && $box.length) {
                if (!$box.next().is($dtBottom)) {
                    $box.insertBefore($dtBottom);
                }
            }
        }

        function updateFilterBadge() {
            let activeCount = 0;
            $('.teacher-col-filter').each(function() {
                if (($(this).val() || '').trim() !== '') {
                    activeCount++;
                }
            });
            const $badge = $('#activeFilterBadge');
            const $count = $('#activeFilterCount');
            if (activeCount > 0) {
                $count.text(activeCount);
                $badge.removeClass('d-none');
            } else {
                $badge.addClass('d-none');
            }
        }

        // Position filters above pagination on DataTables init / draw / ready
        $(document).on('init.dt draw.dt', function() {
            positionTeacherFilters();
        });
        $(document).ready(function() {
            positionTeacherFilters();
            setTimeout(positionTeacherFilters, 150);
        });

        // Pass filter parameters on every DataTables AJAX call
        $('.AjaxDataTable').on('preXhr.dt', function(e, settings, data) {
            data.filter_subject  = ($('.teacher-col-filter[data-col="subject"]').val() || '').trim();
            data.filter_class    = ($('.teacher-col-filter[data-col="class"]').val() || '').trim();
            data.filter_upazila  = ($('.teacher-col-filter[data-col="upazila"]').val() || '').trim();
            data.filter_district = ($('.teacher-col-filter[data-col="district"]').val() || '').trim();
            data.filter_division = ($('.teacher-col-filter[data-col="division"]').val() || '').trim();
        });

        // Debounced live typing search (350ms)
        let teacherFilterTimer;
        $(document).on('input keyup change search', '.teacher-col-filter', function(e) {
            updateFilterBadge();
            clearTimeout(teacherFilterTimer);
            teacherFilterTimer = setTimeout(function() {
                $('.AjaxDataTable').DataTable().ajax.reload();
            }, 350);
        });

        // Clear all column filters
        $(document).on('click', '#btnResetTableFilters', function(e) {
            e.preventDefault();
            $('.teacher-col-filter').val('');
            updateFilterBadge();
            $('.AjaxDataTable').DataTable().ajax.reload();
        });
    </script>
@endsection
