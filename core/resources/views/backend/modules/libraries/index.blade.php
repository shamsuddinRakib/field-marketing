@extends('backend.layouts.master')

@section('meta')
  <title>Libraries</title>
@endsection

@section('content')
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div>
      <h6 class="fw-semibold mb-0">Library List</h6>
      <p class="m-0">Manage Libraries</p>
    </div>
    <ul class="d-flex align-items-center gap-2">
      <li class="fw-medium">
        <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
          <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
        </a>
      </li>
      <li>-</li>
      <li class="fw-medium">Libraries</li>
    </ul>
  </div>

  <div class="card basic-data-table">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="card-title mb-0">Library List</h5>
      <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
        <div class="search-set me-2"><div id="tableSearch" class="search-input"></div></div>
        <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
          @include('backend.include.buttons')
        </ul>
        @if(auth()->user()->canDo('library.libraries.store') || auth()->user()->canDo('institution.institutions.store') || auth()->user()->isSuper())
          <button type="button" class="btn btn-primary btn-sm px-12 py-8 radius-8 d-flex align-items-center gap-2 AjaxModal"
              data-ajax-modal="{{ Route::has('library.libraries.createModal') ? route('library.libraries.createModal') : '#' }}" data-size="lg"
              data-onload="LibrariesIndex.onLoad" data-onsuccess="LibrariesIndex.onSaved">
            <iconify-icon icon="ic:baseline-plus" class="text-xl"></iconify-icon>Add Library
          </button>
        @endif
      </div>
    </div>
    <div class="card-body">
      <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="librariesTable" style="width:100%">
        <thead>
          <tr>
            <th style="width:60px">
              <div class="form-check style-check d-flex align-items-center">
                <input class="form-check-input" type="checkbox" id="select-all">
                <label class="form-check-label">S.L</label>
              </div>
            </th>
            <th>Library Name <span class="text-danger">*</span></th>
            <th>Library Code</th>
            <th>Email</th>
            <th>Phone <span class="text-danger">*</span></th>
            <th>Division</th>
            <th>District</th>
            <th>Upazila</th>
            <th>Address</th>
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
    var DATATABLE_URL = "{{ Route::has('library.libraries.list.ajax') ? route('library.libraries.list.ajax') : url('library/libraries/list') }}";

    window.LibrariesIndex = {
      onLoad: function($modal) {
        $modal.find('form').each(function() {
          const $form = $(this);
          const $division = $form.find('[name="division_id"]');
          const $district = $form.find('[name="district_id"]');
          const $upazila = $form.find('.js-upazila-select');
          const isDivisionSelect = $division.is('select');
          const isDistrictSelect = $district.is('select');
          let syncing = false;

          let $allDistrictOptions = null;
          if (isDistrictSelect) {
            $allDistrictOptions = $district.find('option').clone();
          }

          function filterDistricts() {
            if (!isDistrictSelect || !$allDistrictOptions) return;
            const divVal = String($division.val() || '');
            const curVal = String($district.val() || '');
            $district.empty().append($allDistrictOptions.clone());
            if (divVal !== '') {
              $district.find('option').each(function() {
                const $opt = $(this);
                const optVal = String($opt.attr('value') || '');
                if (optVal === '' || optVal === curVal) return;
                if (String($opt.data('division-id') || '') !== divVal) $opt.remove();
              });
            }
            $district.val(curVal);
          }

          function setDivisionAndDistrict(districtId, divisionId, districtName, divisionName) {
            const dId = districtId ? String(districtId) : '';
            const vId = divisionId ? String(divisionId) : '';
            syncing = true;
            try {
              if (isDistrictSelect) {
                if (dId && !$district.find('option[value="' + dId + '"]').length && $allDistrictOptions) {
                  const $missing = $allDistrictOptions.filter('option[value="' + dId + '"]');
                  if ($missing.length) $district.append($missing.clone());
                }
                $district.val(dId);
              } else {
                $form.find('[name="district_id"]').val(dId);
                $form.find('[name="district_name"]').val(districtName || '');
              }
              if (isDivisionSelect) {
                $division.val(vId);
                filterDistricts();
                if (isDistrictSelect && dId) $district.val(dId);
              } else {
                $form.find('[name="division_id"]').val(vId);
                $form.find('[name="division_name"]').val(divisionName || '');
              }
            } finally {
              syncing = false;
            }
          }

          if (isDivisionSelect) {
            $division.off('change.library').on('change.library', function() {
              if (syncing) return;
              const divVal = String($division.val() || '');
              if (isDistrictSelect) {
                const selDiv = String($district.find('option:selected').data('division-id') || '');
                if ($district.val() && divVal !== '' && selDiv !== '' && selDiv !== divVal) {
                  $district.val('');
                }
                filterDistricts();
              }
              if ($upazila.val() && $district.val() === '') {
                $upazila.val(null).trigger('change');
              }
            });
            filterDistricts();
          }

          if (isDistrictSelect) {
            $district.off('change.library').on('change.library', function() {
              if (syncing) return;
              const $sel = $district.find('option:selected');
              const divId = $sel.data('division-id');
              if (isDivisionSelect && divId !== undefined && divId !== '') {
                syncing = true;
                try { $division.val(String(divId)); } finally { syncing = false; }
              }
              if ($upazila.val()) {
                $upazila.val(null).trigger('change');
              }
            });
          }

          $upazila.each(function() {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) return;
            $select.select2({
              dropdownParent: $modal,
              width: '100%',
              placeholder: 'Select upazila',
              allowClear: true,
              ajax: {
                url: "{{ Route::has('library.libraries.upazilas.select2') ? route('library.libraries.upazilas.select2') : (Route::has('institution.institutions.upazilas.select2') ? route('institution.institutions.upazilas.select2') : url('library/libraries/upazilas-select2')) }}",
                dataType: 'json',
                delay: 200,
                data: params => ({ q: params.term || '' }),
                processResults: data => data
              }
            }).on('select2:select', function(e) {
              const item = e.params.data || {};
              setDivisionAndDistrict(item.district_id, item.division_id, item.district_name, item.division_name);
            }).on('select2:clear select2:unselect', function() {
              if (!isDistrictSelect) $form.find('[name="district_id"], [name="district_name"]').val('');
              if (!isDivisionSelect) $form.find('[name="division_id"], [name="division_name"]').val('');
            });
          });
        });
      },
      onSaved: function(res) {
        $('.AjaxDataTable').DataTable().ajax.reload(null, false);
        if (window.Swal) Swal.fire({ icon: 'success', title: 'Saved', text: res?.msg || 'Saved', timer: 1000, showConfirmButton: false });
      }
    };

    $(document).on('click', '.btn-library-delete', function(e) {
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
        Swal.fire({ icon: 'warning', title: 'Delete library?', text: 'This action cannot be undone.', showCancelButton: true, confirmButtonText: 'Yes, delete', confirmButtonColor: '#d33' }).then(function(result) {
          if (result.isConfirmed) remove();
        });
      } else if (confirm('Delete this library?')) remove();
    });
  </script>
@endsection
