<div class="modal-header py-16 px-24 border-0" data-modal-key="teacher-edit">
  <h5 class="modal-title">Edit Teacher</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
  <form action="{{ route('teacher.teachers.update', $teacher->id) }}" method="post" data-ajax="true">
    @csrf
    @method('PUT')
    <div class="row">
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Teacher Name <span class="text-danger">*</span></label><input name="teacher_name" value="{{ old('teacher_name', $teacher->teacher_name) }}" class="form-control radius-8" required><div class="invalid-feedback d-block teacher_name-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Phone</label><input name="phone" value="{{ old('phone', $teacher->phone) }}" class="form-control radius-8" placeholder="Enter Phone Number"><div class="invalid-feedback d-block phone-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Institution</label><select name="institution_id" class="form-control radius-8 js-institution-select" data-init-id="{{ $teacher->institution_id }}" data-init-text="{{ optional($teacher->institution)->institution_name ?? optional($teacher->institution)->name }}">@if($teacher->institution_id && $teacher->institution)<option value="{{ $teacher->institution_id }}" selected>{{ $teacher->institution->institution_name ?? $teacher->institution->name }}@if($teacher->institution->code) ({{ $teacher->institution->code }})@endif</option>@endif</select><div class="invalid-feedback d-block institution_id-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Designation</label><input name="designation" value="{{ old('designation', $teacher->designation) }}" class="form-control radius-8"><div class="invalid-feedback d-block designation-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Department</label><select name="department" class="form-control radius-8 js-department-select" data-placeholder="Select Department">@if(old('department', $teacher->department))<option value="{{ old('department', $teacher->department) }}" selected>{{ old('department', $teacher->department) }}</option>@endif</select><div class="invalid-feedback d-block department-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Subject</label><select name="subject" class="form-control radius-8 js-subject-select" data-placeholder="Select Subject">@if(old('subject', $teacher->subject))<option value="{{ old('subject', $teacher->subject) }}" selected>{{ old('subject', $teacher->subject) }}</option>@endif</select><div class="invalid-feedback d-block subject-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Class</label><select name="class_name" class="form-control radius-8 js-class-select" data-placeholder="Select Class">@if(old('class_name', $teacher->class_name))<option value="{{ old('class_name', $teacher->class_name) }}" selected>{{ old('class_name', $teacher->class_name) }}</option>@endif</select><div class="invalid-feedback d-block class_name-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status" class="form-select radius-8"><option value="1" {{ ($teacher->status ? 'selected' : '') }}>Active</option><option value="0" {{ (!$teacher->status ? 'selected' : '') }}>Inactive</option></select></div>
    </div>
    <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button></div>
  </form>
</div>
