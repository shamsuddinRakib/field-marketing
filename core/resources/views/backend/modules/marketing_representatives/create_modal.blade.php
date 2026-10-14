<div class="modal-header py-16 px-24 border-0" data-modal-key="marketing-representative-create">
    <h5 class="modal-title">Add Marketing Representative</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
    <form action="{{ route('marketing-representative.marketing-representatives.store') }}" method="post"
        data-ajax="true">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Name <span
                        class="text-danger">*</span></label><input name="name" class="form-control radius-8"
                    required>
                <div class="invalid-feedback d-block name-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Employee ID <span
                        class="text-danger">*</span></label><input name="employee_id" class="form-control radius-8"
                    placeholder="EMP-1023" required>
                <div class="invalid-feedback d-block employee_id-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Password <span
                        class="text-danger">*</span></label><input type="password" name="password"
                    class="form-control radius-8" minlength="8" required>
                <div class="invalid-feedback d-block password-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Confirm Password <span
                        class="text-danger">*</span></label><input type="password" name="password_confirmation"
                    class="form-control radius-8" minlength="8" required></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Mobile</label><input name="mobile"
                    class="form-control radius-8" placeholder="017XXXXXXXX">
                <div class="invalid-feedback d-block mobile-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Email</label><input type="email"
                    name="email" class="form-control radius-8" placeholder="rahim@example.com">
                <div class="invalid-feedback d-block email-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Organization</label><input
                    name="organization" class="form-control radius-8"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Branch</label><select name="branch_id"
                    class="form-control radius-8 js-branch-select"></select>
                <div class="invalid-feedback d-block branch_id-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Upazila</label><select name="upazila_id"
                    class="form-control radius-8 js-upazila-select"></select></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">District</label><input
                    name="district_name" class="form-control radius-8" readonly><input type="hidden"
                    name="district_id"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Division</label><input
                    name="division_name" class="form-control radius-8" readonly><input type="hidden"
                    name="division_id"></div>
            <div class="col-12 mb-16"><label class="form-label text-sm mb-6">Address</label>
                <textarea name="address" class="form-control radius-8" rows="2"></textarea>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status"
                    class="form-select radius-8">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select></div>
        </div>
        <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button"
                class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">Cancel</button><button type="submit"
                class="btn btn-primary px-48 py-12 radius-8">Save</button></div>
    </form>
</div>
