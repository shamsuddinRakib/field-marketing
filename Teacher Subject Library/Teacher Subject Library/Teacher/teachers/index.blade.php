@extends('backend.layouts.master')

@section('meta')
  <title>Teachers</title>
@endsection

@section('content')
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
        <div class="search-set me-2"><div id="tableSearch" class="search-input"></div></div>
        <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
          @include('backend.include.buttons')
        </ul>
        @if(auth()->user()->canDo('teacher.teachers.store') || auth()->user()->isSuper())
          <button type="button" class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
              data-ajax-modal="{{ Route::has('teacher.teachers.createModal') ? route('teacher.teachers.createModal') : '#' }}" data-size="lg"
              data-onload="TeachersIndex.onLoad" data-onsuccess="TeachersIndex.onSaved">
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
            <th>Institution</th>
            <th>Designation</th>
            <th>Department</th>
            <th>Subject</th>
            <th>Class</th>
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
    var DATATABLE_URL = "{{ Route::has('teacher.teachers.list.ajax') ? route('teacher.teachers.list.ajax') : url('teacher/teachers/list') }}";

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
              data: params => ({ q: params.term || '' }),
              processResults: data => data
            }
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
              data: params => ({ q: params.term || '' }),
              processResults: data => data
            }
          });
        });
        $modal.find('.js-subject-select').each(function() {
          const $select = $(this);
          if ($select.hasClass('select2-hidden-accessible')) return;
          const placeholder = $select.data('placeholder') || 'Select Subject';
          $select.select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            ajax: {
              url: "{{ Route::has('subject.select2') ? route('subject.select2') : url('subject/subject/select2') }}",
              dataType: 'json',
              delay: 200,
              data: params => ({ q: params.term || '' }),
              processResults: data => data
            }
          });
        });
        $modal.find('.js-class-select').each(function() {
          const $select = $(this);
          if ($select.hasClass('select2-hidden-accessible')) return;
          const placeholder = $select.data('placeholder') || 'Select Class';
          $select.select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            ajax: {
              url: "{{ Route::has('class.select2') ? route('class.select2') : url('class/classes/select2/type') }}",
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
        if (window.Swal) Swal.fire({ icon: 'success', title: 'Saved', text: res?.msg || 'Saved', timer: 1000, showConfirmButton: false });
      }
    };

    $(document).on('click', '.btn-teacher-delete', function(e) {
      e.preventDefault();
      const url = $(this).data('url');
      const remove = function() {
        $.ajax({ url: url, type: 'POST', dataType: 'json', data: { _method: 'DELETE', _token: '{{ csrf_token() }}' } })
          .done(function(res) {
            $('.AjaxDataTable').DataTable().ajax.reload(null, false);
            if (window.Swal) Swal.fire({ icon: 'success', title: 'Deleted', text: res?.msg || 'Deleted', timer: 1000, showConfirmButton: false });
          }).fail(function() { if (window.Swal) Swal.fire({ icon: 'error', title: 'Failed', text: 'Delete failed' }); });
      };
      if (window.Swal) {
        Swal.fire({ icon: 'warning', title: 'Delete teacher?', text: 'This action cannot be undone.', showCancelButton: true, confirmButtonText: 'Yes, delete', confirmButtonColor: '#d33' }).then(function(result) {
          if (result.isConfirmed) remove();
        });
      } else if (confirm('Delete this teacher?')) remove();
    });
  </script>
@endsection
