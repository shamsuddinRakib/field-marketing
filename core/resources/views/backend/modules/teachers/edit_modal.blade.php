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
      <div class="col-md-6 mb-16">
        <div class="d-flex align-items-center justify-content-between mb-6">
          <label class="form-label text-sm mb-0">Institution</label>
          <button type="button" class="btn btn-xs btn-outline-primary radius-6 d-inline-flex align-items-center gap-1 js-quick-add-institution" style="padding: 2px 8px; font-size: 11px;">
            <iconify-icon icon="ic:baseline-plus"></iconify-icon> Add Institution
          </button>
        </div>
        <select name="institution_id" class="form-control radius-8 js-institution-select" data-placeholder="Select Institution" data-init-id="{{ $teacher->institution_id }}" data-init-text="{{ optional($teacher->institution)->institution_name ?? optional($teacher->institution)->name }}">@if($teacher->institution_id && $teacher->institution)<option value="{{ $teacher->institution_id }}" selected>{{ $teacher->institution->institution_name ?? $teacher->institution->name }}@if($teacher->institution->code) ({{ $teacher->institution->code }})@endif</option>@endif</select>
        <div class="invalid-feedback d-block institution_id-error" style="display:none"></div>
      </div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Designation</label><input name="designation" value="{{ old('designation', $teacher->designation) }}" class="form-control radius-8"><div class="invalid-feedback d-block designation-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Department</label><select name="department" class="form-control radius-8 js-department-select" data-placeholder="Select Department">@if(old('department', $teacher->department))<option value="{{ old('department', $teacher->department) }}" selected>{{ old('department', $teacher->department) }}</option>@endif</select><div class="invalid-feedback d-block department-error" style="display:none"></div></div>
      <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">Subject</label>
        @php
          $selectedSubjects = [];
          $currentSubject = old('subject', $teacher->subject);
          if (is_array($currentSubject)) {
              $selectedSubjects = $currentSubject;
          } elseif (is_string($currentSubject) && trim($currentSubject) !== '') {
              $selectedSubjects = array_filter(array_map('trim', explode(',', $currentSubject)));
          }
        @endphp
        <select name="subject[]" class="form-control radius-8 js-subject-select" multiple="multiple" data-placeholder="Select Subject(s)">
          @foreach($selectedSubjects as $subj)
            <option value="{{ $subj }}" selected>{{ $subj }}</option>
          @endforeach
        </select>
        <div class="invalid-feedback d-block subject-error" style="display:none"></div>
      </div>
      <div class="col-md-6 mb-16">
        <label class="form-label text-sm mb-6">Class</label>
        @php
          $selectedClasses = [];
          $currentClass = old('class_name', $teacher->class_name);
          if (is_array($currentClass)) {
              $selectedClasses = $currentClass;
          } elseif (is_string($currentClass) && trim($currentClass) !== '') {
              $selectedClasses = array_filter(array_map('trim', explode(',', $currentClass)));
          }
        @endphp
        <select name="class_name[]" class="form-control radius-8 js-class-select" multiple="multiple" data-placeholder="Select Class(es)">
          @foreach($selectedClasses as $cls)
            <option value="{{ $cls }}" selected>{{ $cls }}</option>
          @endforeach
        </select>
        <div class="invalid-feedback d-block class_name-error" style="display:none"></div>
      </div>
      <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status" class="form-select radius-8"><option value="1" {{ ($teacher->status ? 'selected' : '') }}>Active</option><option value="0" {{ (!$teacher->status ? 'selected' : '') }}>Inactive</option></select></div>
      @php
        $inst = $teacher->institution;
        $upazilaName = $teacher->upazila?->upazila_name ?? ($inst?->upazila?->upazila_name ?? '');
        $districtName = $teacher->district?->district_name ?? ($inst?->district?->district_name ?? '');
        $divisionName = $teacher->division?->name ?? ($teacher->division?->bn_name ?? ($inst?->division?->name ?? ($inst?->division?->bn_name ?? '')));
        $upazilaId = old('upazila_id', $teacher->upazila_id ?? ($inst?->upazila_id ?? ''));
        $districtId = old('district_id', $teacher->district_id ?? ($inst?->district_id ?? ''));
        $divisionId = old('division_id', $teacher->division_id ?? ($inst?->division_id ?? ''));
        $addressVal = old('address', $teacher->address ?? ($inst?->address ?? ''));
      @endphp
      <div class="col-md-4 mb-16">
        <label class="form-label text-sm mb-6">Upazila <small class="text-muted">(Auto)</small></label>
        <input type="text" id="teacher_upazila_display" class="form-control radius-8 bg-light" value="{{ $upazilaName }}" placeholder="Auto-populated" readonly>
        <input type="hidden" name="upazila_id" id="teacher_upazila_id" value="{{ $upazilaId }}">
      </div>
      <div class="col-md-4 mb-16">
        <label class="form-label text-sm mb-6">District <small class="text-muted">(Auto)</small></label>
        <input type="text" id="teacher_district_display" class="form-control radius-8 bg-light" value="{{ $districtName }}" placeholder="Auto-populated" readonly>
        <input type="hidden" name="district_id" id="teacher_district_id" value="{{ $districtId }}">
      </div>
      <div class="col-md-4 mb-16">
        <label class="form-label text-sm mb-6">Division <small class="text-muted">(Auto)</small></label>
        <input type="text" id="teacher_division_display" class="form-control radius-8 bg-light" value="{{ $divisionName }}" placeholder="Auto-populated" readonly>
        <input type="hidden" name="division_id" id="teacher_division_id" value="{{ $divisionId }}">
      </div>
      <div class="col-12 mb-16">
        <label class="form-label text-sm mb-6">Address</label>
        <textarea name="address" rows="2" class="form-control radius-8" placeholder="Enter Teacher Address">{{ $addressVal }}</textarea>
        <div class="invalid-feedback d-block address-error" style="display:none"></div>
      </div>
    </div>
    <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button></div>
  </form>
</div>
