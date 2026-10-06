<div class="modal-header py-16 px-24 border-0" data-modal-key="institution-edit">
  <h5 class="modal-title">Edit Institution</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
  <form action="{{ route('institution.institutions.update', $institution->id) }}" method="post" data-ajax="true">
    @csrf
    @method('PUT')
    <div class="row">
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Institution Name <span class="text-danger">*</span></label><input name="institution_name" value="{{ old('institution_name', $institution->institution_name) }}" class="form-control radius-8" required><div class="invalid-feedback d-block institution_name-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Code</label><input name="code" value="{{ old('code', $institution->code) }}" class="form-control radius-8"><div class="invalid-feedback d-block code-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Email</label><input type="email" name="email" value="{{ old('email', $institution->email) }}" class="form-control radius-8"><div class="invalid-feedback d-block email-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Phone</label><input name="phone" value="{{ old('phone', $institution->phone) }}" class="form-control radius-8"><div class="invalid-feedback d-block phone-error" style="display:none"></div></div>

       <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">Upazila</label>
        <select name="upazila_id" class="form-control radius-8 js-upazila-select" data-init-id="{{ $institution->upazila_id }}" data-init-text="{{ optional($institution->upazila)->upazila_name }}">
          @if($institution->upazila_id && $institution->upazila)
            <option value="{{ $institution->upazila_id }}" selected>{{ $institution->upazila->upazila_name }}</option>
          @endif
        </select>
        <div class="invalid-feedback d-block upazila_id-error" style="display:none"></div>
      </div>

      <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">District</label>
        <input type="text" id="district_name" name="district_name" class="form-control radius-8"
          readonly placeholder="Auto from Upazila" tabindex="-1" aria-readonly="true"
          aria-describedby="district_id-error"
          value="{{ old('district_name', optional($institution->district)->district_name) }}">
        <input type="hidden" name="district_id" id="district_id" value="{{ old('district_id', $institution->district_id) }}">
        <div class="invalid-feedback d-block district_id-error" style="display:none"></div>
      </div>

      <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">Division</label>
        <input type="text" id="division_name" name="division_name" class="form-control radius-8"
          readonly placeholder="Auto from Upazila" tabindex="-1" aria-readonly="true"
          aria-describedby="division_id-error"
          value="{{ old('division_name', optional($institution->division)->name) }}">
        <input type="hidden" name="division_id" id="division_id" value="{{ old('division_id', $institution->division_id) }}">
        <div class="invalid-feedback d-block division_id-error" style="display:none"></div>
      </div>
      
     
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status" class="form-select radius-8"><option value="1" {{ ($institution->status ? 'selected' : '') }}>Active</option><option value="0" {{ (!$institution->status ? 'selected' : '') }}>Inactive</option></select></div>
      <div class="col-12 mb-16"><label class="form-label text-sm mb-6">Address</label><textarea name="address" class="form-control radius-8" rows="2">{{ old('address', $institution->address) }}</textarea></div>
    </div>
    <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button></div>
  </form>
</div>
