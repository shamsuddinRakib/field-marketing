@extends('backend.layouts.master')

@section('meta')
    <title>Department</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0"> Department List</h6>
            <p class="text-muted m-0">Manage Department List</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium"> Department</li>
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

                @perm('department.store')
                    <button class="d-flex btn btn-primary btn-sm px-12 py-8 radius-8 AjaxModal"
                        data-ajax-modal="{{ route('department.createModal') }}" data-size="lg"
                        data-onsuccess="departmentIndex.onSaved">
                        <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add Department
                    </button>
                @endperm
            </div>
        </div>

        <div class="card-body">
            <table class="table bordered-table table-scroll mb-0 AjaxDataTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" id="select-all">
                                <label class="form-check-label">S.L</label>
                            </div>
                        </th>
                        <th>Department Name</th>

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
        var DATATABLE_URL = "{{ route('department.list.ajax') }}";

        window.departmentIndex = {
            onSaved: function(res) {
                $('.AjaxDataTable').DataTable().ajax.reload(null, false);
                if (window.Swal) Swal.fire({
                    icon: 'success',
                    title: 'Created',
                    text: res?.msg || 'Saved successfully!',
                    timer: 1000,
                    showConfirmButton: false
                });
            }
        };


        $(document).on('click', '.btn-department-delete', function(e) {
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
                            const msg = xhr.responseJSON?.msg || 'Can not delete this department.';
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
                    title: 'Delete Department?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#d33'
                }).then(r => {
                    if (r.isConfirmed) doDelete();
                });
            } else {
                if (confirm('Delete this Department?')) doDelete();
            }
        });

        // Follow Subject Name rule: Department Name must be distinct (not same can be added)
        // Mirrors subjects logic: backend Rule::unique + LOWER(TRIM()) check, frontend live validation
        // Subjects: core/app/Http/Controllers/backend/SubjectController.php:97, core/resources/views/backend/modules/subjects/create_modal.blade.php:13
        (function() {
            const SELECT2_URL = "{{ route('department.select2') }}";
            let debounceTimer = null;
            let isDuplicate = false;
            let lastNorm = '';

            function norm(v) { return (v || '').trim().toLowerCase(); }

            function setError($input, msg) {
                const $form = $input.closest('form');
                const $err = $form.find('.department_name-error').first();
                if (msg) {
                    $input.addClass('is-invalid');
                    $err.text(msg).show();
                    $form.find('[type="submit"]').prop('disabled', true);
                    isDuplicate = true;
                } else {
                    $input.removeClass('is-invalid');
                    if ($err.text().includes('already exists') || $err.text().includes('distinct')) $err.text('').hide();
                    else $err.hide();
                    $form.find('[type="submit"]').prop('disabled', false);
                    isDuplicate = false;
                }
            }

            function checkDuplicate($input) {
                const val = $input.val() || '';
                const n = norm(val);
                const original = norm($input.data('original') || '');
                if (!n) { setError($input, ''); lastNorm = ''; return; }
                if (original && n === original) { setError($input, ''); lastNorm = n; return; }
                if (n === lastNorm && isDuplicate) return;
                $.getJSON(SELECT2_URL, { q: val.trim() }).done(function(res) {
                    const items = res?.results || [];
                    const exists = items.some(function(it) { return norm(it.text) === n; });
                    if (exists) setError($input, 'This department name already exists. Please use a different name.');
                    else setError($input, '');
                    lastNorm = n;
                }).fail(function() { setError($input, ''); });
            }

            function attach($modal) {
                const $input = $modal.find('input[name="department_name"]');
                if (!$input.length) return;
                isDuplicate = false; lastNorm = '';
                $input.off('input.deptUnique blur.deptUnique');
                $input.on('input.deptUnique', function() {
                    const $self = $(this);
                    $self.closest('form').find('.department_name-error').hide();
                    $self.removeClass('is-invalid');
                    $self.closest('form').find('[type="submit"]').prop('disabled', false);
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function(){ checkDuplicate($self); }, 450);
                });
                $input.on('blur.deptUnique', function(){ clearTimeout(debounceTimer); checkDuplicate($(this)); });
                $modal.find('form[data-ajax="true"]').off('submit.deptUnique').on('submit.deptUnique', function(e){
                    if (isDuplicate) {
                        e.preventDefault(); e.stopImmediatePropagation();
                        if (window.Swal) Swal.fire({ icon:'warning', title:'Duplicate', text:'Each department name must be distinct. Please use a different name.' });
                        return false;
                    }
                });
            }

            window.ModalHooks = window.ModalHooks || {};
            window.ModalHooks['department-create'] = { onLoad: function($m){ attach($m); } };
            window.ModalHooks['department-edit'] = { onLoad: function($m){ attach($m); } };
            $(document).on('shown.bs.modal', '#AjaxModal', function(){
                const $m = $(this);
                const key = ($m.data('modal-key')||'').toString();
                if (key === 'department-create' || key === 'department-edit' || $m.find('input[name="department_name"]').length) attach($m);
            });
        })();
    </script>
@endsection
