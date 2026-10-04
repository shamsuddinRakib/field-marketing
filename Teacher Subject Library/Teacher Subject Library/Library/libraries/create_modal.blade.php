<div class="modal-header py-16 px-24 border-0" data-modal-key="library-create">
  <h5 class="modal-title">Add Library</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
  <form action="{{ route('library.libraries.store') }}" method="post" data-ajax="true">
    @csrf
    <div class="row">
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Library Name <span class="text-danger">*</span></label><input name="library_name" class="form-control radius-8" placeholder="Enter Library Name" required><div class="invalid-feedback d-block library_name-error" style="display:none"></div><div class="invalid-feedback d-block name-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Library Code</label><input name="code" class="form-control radius-8" placeholder="LIB-001"><div class="invalid-feedback d-block code-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Email</label><input type="email" name="email" class="form-control radius-8" placeholder="info@library.com"><div class="invalid-feedback d-block email-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Phone <span class="text-danger">*</span></label><input name="phone" class="form-control radius-8" placeholder="01XXXXXXXXX" required><div class="invalid-feedback d-block phone-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Upazila</label><select name="upazila_id" class="form-control radius-8 js-upazila-select"></select><div class="invalid-feedback d-block upazila_id-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">District</label><input name="district_name" class="form-control radius-8" readonly placeholder="Auto from Upazila"><input type="hidden" name="district_id"><div class="invalid-feedback d-block district_id-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Division</label><input name="division_name" class="form-control radius-8" readonly placeholder="Auto from Upazila"><input type="hidden" name="division_id"><div class="invalid-feedback d-block division_id-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Address</label><textarea name="address" class="form-control radius-8" rows="2" placeholder="Enter Address"></textarea><div class="invalid-feedback d-block address-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status" class="form-select radius-8"><option value="1" selected>Active</option><option value="0">Inactive</option></select><div class="invalid-feedback d-block status-error" style="display:none"></div></div>
    </div>
    <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-48 py-12 radius-8">Save</button></div>
  </form>
</div>
