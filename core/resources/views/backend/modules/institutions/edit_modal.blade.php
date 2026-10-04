<div class="modal-header py-16 px-24 border-0" data-modal-key="institution-edit">
  <h5 class="modal-title">Edit Institution</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
  <form action="{{ route('institution.institutions.update', $institution->id) }}" method="post" data-ajax="true">
    @csrf
    @method('PUT')
    <div class="row">
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Institution Name <span class="text-danger">*</span></label><input name="institution_name" value="{{ old('institution_name', $institution->institution_name ?? $institution->name) }}" class="form-control radius-8" required><div class="invalid-feedback d-block institution_name-error" style="display:none"></div><div class="invalid-feedback d-block name-error" style="display:none"></div></div>
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
        <select name="district_id" class="form-select radius-8 js-district-select">
          <option value="">Select District</option>
          @foreach(($districts ?? []) as $district)
            <option value="{{ $district->district_id }}" data-division-id="{{ $district->district_division_id }}" {{ (string) old('district_id', $institution->district_id) === (string) $district->district_id ? 'selected' : '' }}>{{ $district->district_name }}</option>
          @endforeach
        </select>
        <div class="invalid-feedback d-block district_id-error" style="display:none"></div>
      </div>

      <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">Division</label>
        <select name="division_id" class="form-select radius-8 js-division-select">
          <option value="">Select Division</option>
          @foreach(($divisions ?? []) as $division)
            <option value="{{ $division->id }}" {{ (string) old('division_id', $institution->division_id) === (string) $division->id ? 'selected' : '' }}>{{ $division->name }}</option>
          @endforeach
        </select>
        <div class="invalid-feedback d-block division_id-error" style="display:none"></div>
      </div>
      
     
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status" class="form-select radius-8"><option value="1" {{ ($institution->status ? 'selected' : '') }}>Active</option><option value="0" {{ (!$institution->status ? 'selected' : '') }}>Inactive</option></select></div>
      <div class="col-12 mb-16"><label class="form-label text-sm mb-6">Address</label><textarea name="address" class="form-control radius-8" rows="2">{{ old('address', $institution->address) }}</textarea></div>
    </div>
    <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button></div>
  </form>
</div>
