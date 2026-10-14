<div class="modal-header py-16 px-24 border-0" data-modal-key="department-edit">
    <h5 class="modal-title">Edit Department</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
    <form id="departmentEditForm" action="{{ route('department.update', $department->id) }}" method="post" data-ajax="true" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- name --}}
            <div class="col-md-12 mb-20">
                <label class="form-label text-sm mb-8">Department Name <span class="text-danger">*</span></label>
                <input type="text" name="department_name" class="form-control radius-8" placeholder="Add department" required
                       value="{{ old('department_name', $department->department_name) }}" data-original="{{ $department->department_name }}">
                <div class="invalid-feedback d-block department_name-error name-error" style="display:none"></div>
            </div>



        <div class="d-flex align-items-center justify-content-center gap-3 mt-16">
            <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button>
        </div>
    </form>
</div>
