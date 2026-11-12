@extends('backend.layouts.master')

@section('meta')
    <title>Daily Visits</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Daily Visit List</h6>
            <p class="m-0">Manage Daily Visits</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Daily Visits</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Datatables</h5>
            <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
                <div class="search-set me-2">
                    <div id="tableSearch" class="search-input"></div>
                </div>

                <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
                    @include('backend.include.buttons')
                </ul>

                @perm('daily-visit.report.index')
                    <a href="{{ route('daily-visit.report.index') }}"
                        class="d-flex align-items-center gap-1 btn btn-outline-primary btn-sm px-12 py-8 radius-8">
                        <iconify-icon icon="solar:chart-square-outline" class="text-xl"></iconify-icon>Visit Report
                    </a>
                @endperm

                @perm('daily-visit.store')
                    <button class="d-flex btn btn-primary btn-sm px-12 py-8 radius-8 AjaxModal"
                        data-ajax-modal="{{ route('daily-visit.create') }}" data-size="lg"
                        data-onsuccess="BranchesIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add Visit
                    </button>
                @endperm

            </div>
        </div>

        <div class="card-body">
            <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="branchesTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" id="select-all">
                                <label class="form-check-label">S.L</label>
                            </div>
                        </th>
                        <th>MR Name</th>
                        <th>Visitable Type</th>
                        <th>Visited</th>
                        <th>Note</th>
                        <th>Status</th>
                        <th style="width:120px">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>


@endsection

@section('script')
    <script>
        var DATATABLE_URL = "{{ route('daily-visit.listAjax') }}";

        window.BranchesIndex = {
            onSaved: function(res) {
                // DataTable current page reload
                $('.AjaxDataTable').DataTable().ajax.reload(null, false);
                // Optional: extra toast (আপনার গ্লোবালে আগেই toast দেওয়া আছে, এটা না দিলেও হবে)
                if (window.Swal) Swal.fire({
                    icon: 'success',
                    title: 'Created',
                    text: res?.msg || 'Saved',
                    timer: 1000,
                    showConfirmButton: false
                });
            }
        };


        $(document).on('click', '.btn-branch-delete', function(e) {
            e.preventDefault();
            const url = $(this).data('url');

            const doDelete = () => {
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        // DataTable রিলোড (পজিশন ধরে)
                        $('.AjaxDataTable').DataTable().ajax.reload(null, false);
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: res?.msg || 'Deleted',
                                timer: 1000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const msg = xhr.responseJSON?.msg || 'Cannot delete this visit.';
                            Swal && Swal.fire({
                                icon: 'warning',
                                title: 'Blocked',
                                text: msg
                            });
                        } else if (xhr.status === 403) {
                            Swal && Swal.fire({
                                icon: 'warning',
                                title: 'Forbidden',
                                text: xhr.responseJSON?.message || 'Permission denied'
                            });
                        } else {
                            Swal && Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: 'Delete failed'
                            });
                        }
                    }
                });
            };

            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete this visit?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(r => {
                    if (r.isConfirmed) doDelete();
                });
            } else {
                if (confirm('Delete this visit?')) doDelete();
            }
        });
    </script>
    <script>
        window.BranchesIndex = {
            onSaved: function(res) {
                $('.AjaxDataTable').DataTable().ajax.reload(null, false);

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Created',
                        text: res?.msg || 'Saved',
                        timer: 1000,
                        showConfirmButton: false
                    });
                }
            }
        };

        window.DailyVisitIndex = {

            // Initialize existing Teacher Create Modal
            onTeacherModalLoad: function($modal) {

                // Institution
                $modal.find('.js-institution-select').each(function() {

                    const $select = $(this);

                    if ($select.hasClass('select2-hidden-accessible')) return;

                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select institution',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('teacher.teachers.institutions.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    });
                });

                // Department
                $modal.find('.js-department-select').each(function() {

                    const $select = $(this);

                    if ($select.hasClass('select2-hidden-accessible')) return;

                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select Department',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('department.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    });
                });

                // Subject
                $modal.find('.js-subject-select').each(function() {

                    const $select = $(this);

                    if ($select.hasClass('select2-hidden-accessible')) return;

                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select Subject',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('subject.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    });
                });

                // Class
                $modal.find('.js-class-select').each(function() {

                    const $select = $(this);

                    if ($select.hasClass('select2-hidden-accessible')) return;

                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select Class',
                        allowClear: true,
                        ajax: {
                            url: "{{ route('class.select2') }}",
                            dataType: 'json',
                            delay: 200,
                            data: params => ({
                                q: params.term || ''
                            }),
                            processResults: data => data
                        }
                    });
                });
            },

            // Teacher successfully created
            onTeacherCreated: function(res) {

                if (!res || !res.id || !res.teacher_name) {
                    console.error('Teacher response is incomplete:', res);
                    return;
                }

                const $teacher = $('#teacher');

                const option = new Option(
                    res.teacher_name,
                    res.id,
                    true,
                    true
                );

                $teacher.append(option).trigger('change');

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Teacher Added',
                        text: res.msg || 'Teacher created successfully.',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
            },

            // Library successfully created
            onLibraryModalLoad: function($modal) {

                const $form = $modal.find('form');
                const $upazila = $form.find('.js-upazila-select');

                if (!$upazila.length || $upazila.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $upazila.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select upazila',
                    allowClear: true,
                    ajax: {
                        url: "{{ Route::has('library.libraries.upazilas.select2') ? route('library.libraries.upazilas.select2') : (Route::has('institution.institutions.upazilas.select2') ? route('institution.institutions.upazilas.select2') : url('library/libraries/upazilas-select2')) }}",
                        dataType: 'json',
                        delay: 200,
                        data: function(params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return data;
                        }
                    }
                });

                // Automatically set district and division
                $upazila.on('select2:select', function(e) {

                    const item = e.params.data || {};

                    $form.find('[name="district_id"]').val(item.district_id || '');
                    $form.find('[name="district_name"]').val(item.district_name || '');

                    $form.find('[name="division_id"]').val(item.division_id || '');
                    $form.find('[name="division_name"]').val(item.division_name || '');

                });

                // Clear district and division when upazila is cleared
                $upazila.on('select2:clear select2:unselect', function() {

                    $form.find('[name="district_id"]').val('');
                    $form.find('[name="district_name"]').val('');

                    $form.find('[name="division_id"]').val('');
                    $form.find('[name="division_name"]').val('');

                });
            },

            onLibraryCreated: function(res) {

                if (!res || !res.id || !res.library_name) {
                    console.error('Library response is incomplete:', res);
                    return;
                }

                const $library = $('#library');

                const option = new Option(
                    res.library_name,
                    res.id,
                    true,
                    true
                );

                $library.append(option).trigger('change');

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Library Added',
                        text: res.msg || 'Library created successfully.',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
            }
        };
    </script>
@endsection
