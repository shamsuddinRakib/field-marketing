
@extends('backend.layouts.master')

@section('meta')
  <title>Specimen Assign</title>
@endsection

@section('content')
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div>
      <h6 class="fw-semibold mb-0">Specimen Assignment List</h6>
      <p class="m-0">Manage Specimen Assignment</p>
    </div>
    <ul class="d-flex align-items-center gap-2">
      <li class="fw-medium">
        <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
          <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
        </a>
      </li>
      <li>-</li>
      <li class="fw-medium">Specimen Assign</li>
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

        @perm('assign-specimen.store')
            <button
                class="d-flex btn btn-primary btn-sm px-12 py-8 radius-8 AjaxModal"
                data-ajax-modal="{{ route('assign-specimen.create') }}"
                data-size="lg"
                data-onload="AssignSpecimenIndex.onLoad"
                data-onsuccess="BranchesIndex.onSaved">
                <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Assign Specimen
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
            <th>Type</th>
            <th>Recipient</th>
            <th>When</th>
            <th>Total Qty</th>
            <th>Note</th>
            <th>Status</th>
            <th style="width:130px">Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>


@endsection

@section('script')
  <script>
    var DATATABLE_URL = "{{ route('assign-specimen.listAjax') }}";

    window.BranchesIndex = {
    onSaved: function (res) {
      $('.AjaxDataTable').DataTable().ajax.reload(null, false);
      if (window.Swal) Swal.fire({icon:'success', title:'Saved', text: res?.msg || 'Saved', timer:1000, showConfirmButton:false});
    }
  };

  window.AssignSpecimenIndex = { onLoad: function () {} };


  $(document).on('click', '.btn-branch-delete', function(e){
    e.preventDefault();
    const url = $(this).data('url');

    const doDelete = () => {
      $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
        success: function(res){
          $('.AjaxDataTable').DataTable().ajax.reload(null, false);
          if (window.Swal) {
            Swal.fire({ icon:'success', title: res?.msg || 'Deleted', timer: 1000, showConfirmButton:false });
          }
        },
        error: function(xhr){
          const msg = xhr.responseJSON?.msg || 'Cannot delete this record.';
          if (xhr.status === 422 || xhr.status === 403){
            Swal && Swal.fire({ icon:'warning', title:'Blocked', text: msg });
          } else {
            Swal && Swal.fire({ icon:'error', title:'Failed', text:'Delete failed' });
          }
        }
      });
    };

    if (window.Swal){
      Swal.fire({
        icon: 'warning',
        title: 'Delete this assignment?',
        text: 'Items under this assignment will also be deleted.',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        confirmButtonColor: '#d33'
      }).then(r => { if (r.isConfirmed) doDelete(); });
    } else {
      if (confirm('Delete this assignment?')) doDelete();
    }
  });


  </script>
@endsection
