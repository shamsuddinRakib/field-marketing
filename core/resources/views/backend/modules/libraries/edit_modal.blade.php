<div class="modal-header py-16 px-24 border-0" data-modal-key="library-edit">
  <h5 class="modal-title">Edit Library</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
  <form action="{{ route('library.libraries.update', $library->id) }}" method="post" data-ajax="true">
    @csrf
    @method('PUT')
    <div class="row">
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Library Name <span class="text-danger">*</span></label><input name="library_name" value="{{ old('library_name', $library->library_name) }}" class="form-control radius-8" required><div class="invalid-feedback d-block library_name-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Library Code</label><input name="code" value="{{ old('code', $library->code) }}" class="form-control radius-8" placeholder="LIB-001"><div class="invalid-feedback d-block code-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Email</label><input type="email" name="email" value="{{ old('email', $library->email) }}" class="form-control radius-8"><div class="invalid-feedback d-block email-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Phone <span class="text-danger">*</span></label><input name="phone" value="{{ old('phone', $library->phone) }}" class="form-control radius-8" required><div class="invalid-feedback d-block phone-error" style="display:none"></div></div>

        <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Upazila</label><select name="upazila_id" class="form-control radius-8 js-upazila-select" data-init-id="{{ $library->upazila_id }}" data-init-text="{{ optional($library->upazila)->upazila_name }}">@if($library->upazila_id && $library->upazila)<option value="{{ $library->upazila_id }}" selected>{{ $library->upazila->upazila_name }}</option>@endif</select><div class="invalid-feedback d-block upazila_id-error" style="display:none"></div></div>

        <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">District</label>
        <input type="text" name="district_name" class="form-control radius-8" readonly tabindex="-1" aria-readonly="true"
          placeholder="Auto from Upazila" value="{{ old('district_name', optional($library->district)->district_name) }}">
        <input type="hidden" name="district_id" value="{{ old('district_id', $library->district_id) }}">
        <div class="invalid-feedback d-block district_id-error" style="display:none"></div>
      </div>


      <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">Division</label>
        <input type="text" name="division_name" class="form-control radius-8" readonly tabindex="-1" aria-readonly="true"
          placeholder="Auto from Upazila" value="{{ old('division_name', optional($library->division)->name) }}">
        <input type="hidden" name="division_id" value="{{ old('division_id', $library->division_id) }}">
        <div class="invalid-feedback d-block division_id-error" style="display:none"></div>
      </div>

      <!-- <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Upazila</label><select name="upazila_id" class="form-control radius-8 js-upazila-select" data-init-id="{{ $library->upazila_id }}" data-init-text="{{ optional($library->upazila)->upazila_name }}">@if($library->upazila_id && $library->upazila)<option value="{{ $library->upazila_id }}" selected>{{ $library->upazila->upazila_name }}</option>@endif</select><div class="invalid-feedback d-block upazila_id-error" style="display:none"></div></div> -->
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Address</label><textarea name="address" class="form-control radius-8" rows="2">{{ old('address', $library->address) }}</textarea></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status" class="form-select radius-8"><option value="1" {{ ($library->status ? 'selected' : '') }}>Active</option><option value="0" {{ (!$library->status ? 'selected' : '') }}>Inactive</option></select></div>
    </div>
    <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button></div>
  </form>
</div>