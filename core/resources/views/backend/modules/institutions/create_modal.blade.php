<div class="modal-header py-16 px-24 border-0" data-modal-key="institution-create">
  <h5 class="modal-title">Add Institution</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
  <form action="{{ route('institution.institutions.store') }}" method="post" data-ajax="true">
    @csrf

    <div class="row">
      {{-- Institution Name --}}
      <div class="col-md-6 mb-16">
        <label for="institution_name" class="form-label text-sm mb-6">Institution Name <span class="text-danger">*</span></label>
        <input type="text" id="institution_name" name="institution_name" class="form-control radius-8"
          placeholder="Enter Institution Name" maxlength="191" autocomplete="organization" required
          aria-describedby="institution_name-error">
        <div id="institution_name-error" class="invalid-feedback d-block institution_name-error" style="display:none"></div>
        <div class="invalid-feedback d-block name-error" style="display:none"></div>
      </div>

      {{-- Code --}}
      <div class="col-md-6 mb-16">
        <label for="institution_code" class="form-label text-sm mb-6">Code</label>
        <input type="text" id="institution_code" name="code" class="form-control radius-8"
          placeholder="INST-001" maxlength="100" autocomplete="off"
          aria-describedby="code-error">
        <div id="code-error" class="invalid-feedback d-block code-error" style="display:none"></div>
      </div>

      {{-- Email --}}
      <div class="col-md-6 mb-16">
        <label for="institution_email" class="form-label text-sm mb-6">Email</label>
        <input type="email" id="institution_email" name="email" class="form-control radius-8"
          placeholder="info@institution.com" maxlength="191" autocomplete="email"
          aria-describedby="email-error">
        <div id="email-error" class="invalid-feedback d-block email-error" style="display:none"></div>
      </div>

      {{-- Phone --}}
      <div class="col-md-6 mb-16">
        <label for="institution_phone" class="form-label text-sm mb-6">Phone</label>
        <input type="tel" id="institution_phone" name="phone" class="form-control radius-8"
          placeholder="01XXXXXXXXX" maxlength="50" inputmode="tel" autocomplete="tel"
          aria-describedby="phone-error">
        <div id="phone-error" class="invalid-feedback d-block phone-error" style="display:none"></div>
      </div>

      {{-- Upazila (select2 is initialised by InstitutionsIndex.onLoad) --}}
      <div class="col-md-6 mb-16">
        <label for="upazila_id" class="form-label text-sm mb-6">Upazila</label>
        <select id="upazila_id" name="upazila_id" class="form-control radius-8 js-upazila-select"
          aria-describedby="upazila_id-error"></select>
        <div id="upazila_id-error" class="invalid-feedback d-block upazila_id-error" style="display:none"></div>
      </div>

      {{-- District (auto-filled from Upazila, editable again after reload) --}}
      <div class="col-md-6 mb-16">
        <label for="district_name" class="form-label text-sm mb-6">District</label>
        <input type="text" id="district_name" name="district_name" class="form-control radius-8"
          readonly placeholder="Auto from Upazila" tabindex="-1" aria-readonly="true"
          aria-describedby="district_id-error">
        <input type="hidden" name="district_id" id="district_id" value="">
        <div id="district_id-error" class="invalid-feedback d-block district_id-error" style="display:none"></div>
      </div>

      {{-- Division (auto-filled from Upazila) --}}
      <div class="col-md-6 mb-16">
        <label for="division_name" class="form-label text-sm mb-6">Division</label>
        <input type="text" id="division_name" name="division_name" class="form-control radius-8"
          readonly placeholder="Auto from Upazila" tabindex="-1" aria-readonly="true"
          aria-describedby="division_id-error">
        <input type="hidden" name="division_id" id="division_id" value="">
        <div id="division_id-error" class="invalid-feedback d-block division_id-error" style="display:none"></div>
      </div>

      {{-- Address --}}
      <div class="col-md-6 mb-16">
        <label for="institution_address" class="form-label text-sm mb-6">Address</label>
        <textarea id="institution_address" name="address" class="form-control radius-8" rows="2"
          placeholder="Enter Address" maxlength="1000" autocomplete="street-address"
          aria-describedby="address-error"></textarea>
        <div id="address-error" class="invalid-feedback d-block address-error" style="display:none"></div>
      </div>

      {{-- Status --}}
      <div class="col-md-6 mb-16">
        <label for="institution_status" class="form-label text-sm mb-6">Status</label>
        <select id="institution_status" name="status" class="form-select radius-8" required
          aria-describedby="status-error">
          <option value="1" selected>Active</option>
          <option value="0">Inactive</option>
        </select>
        <div id="status-error" class="invalid-feedback d-block status-error" style="display:none"></div>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-center gap-3 mt-12">
      <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
        data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Save</button>
    </div>
  </form>
</div>
