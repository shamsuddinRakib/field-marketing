<div class="modal-header py-16 px-24 border-0" data-modal-key="teacher-create">
  <h5 class="modal-title">Add Teacher</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
  <form action="{{ route('teacher.teachers.store') }}" method="post" data-ajax="true">
    @csrf
    <div class="row">
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Teacher Name <span class="text-danger">*</span></label><input name="teacher_name" class="form-control radius-8" placeholder="Enter Teacher Name" required><div class="invalid-feedback d-block teacher_name-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Phone</label><input name="phone" class="form-control radius-8" placeholder="Enter Phone Number"><div class="invalid-feedback d-block phone-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Institution</label><select name="institution_id" class="form-control radius-8 js-institution-select"></select><div class="invalid-feedback d-block institution_id-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Designation</label><input name="designation" class="form-control radius-8" placeholder="e.g. Senior Teacher"><div class="invalid-feedback d-block designation-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Department</label><select name="department" class="form-control radius-8 js-department-select" data-placeholder="Select Department"><option value=""></option></select><div class="invalid-feedback d-block department-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Subject</label><select name="subject" class="form-control radius-8 js-subject-select" data-placeholder="Select Subject"><option value=""></option></select><div class="invalid-feedback d-block subject-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Class</label><select name="class_name" class="form-control radius-8 js-class-select" data-placeholder="Select Class"><option value=""></option></select><div class="invalid-feedback d-block class_name-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status" class="form-select radius-8"><option value="1" selected>Active</option><option value="0">Inactive</option></select><div class="invalid-feedback d-block status-error" style="display:none"></div></div>
    </div>
    <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-48 py-12 radius-8">Save</button></div>
  </form>
</div>
