<div class="modal-header py-16 px-24 border-0" data-modal-key="marketing-representative-edit">
    <h5 class="modal-title">Edit Marketing Representative</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-24">
    <form action="{{ route('marketing-representative.marketing-representatives.update', $representative->id) }}"
        method="post" data-ajax="true">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Name <span
                        class="text-danger">*</span></label><input name="name" class="form-control radius-8"
                    value="{{ $representative->user->name }}" required></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Employee ID <span
                        class="text-danger">*</span></label><input name="employee_id" class="form-control radius-8"
                    value="{{ $representative->employee_id }}" required></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Password</label><input type="password"
                    name="password" class="form-control radius-8" minlength="8"
                    placeholder="Leave blank to keep current password"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Confirm Password</label><input
                    type="password" name="password_confirmation" class="form-control radius-8" minlength="8"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Mobile</label><input name="mobile"
                    class="form-control radius-8" value="{{ $representative->user->phone }}"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Email</label><input type="email"
                    name="email" class="form-control radius-8" value="{{ $representative->user->email}}"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Organization</label><input
                    name="organization" class="form-control radius-8" value="{{ $representative->organization }}"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Branch</label><select name="branch_id"
                    class="form-control radius-8 js-branch-select">
                    @if ($representative->branch_id)
                        <option value="{{ $representative->branch_id }}" selected>
                            {{ $representative->branch->name ?? '#' . $representative->branch_id }}</option>
                    @endif
                </select>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Upazila</label><select name="upazila_id"
                    class="form-control radius-8 js-upazila-select">
                    @if ($representative->upazila_id)
                        <option value="{{ $representative->upazila_id }}" selected>
                            {{ $representative->upazila->upazila_name ?? '#' . $representative->upazila_id }}</option>
                    @endif
                </select></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">District</label><input
                    name="district_name" class="form-control radius-8"
                    value="{{ $representative->district->district_name ?? '' }}" readonly><input type="hidden"
                    name="district_id" value="{{ $representative->district_id }}"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Division</label><input
                    name="division_name" class="form-control radius-8"
                    value="{{ $representative->division->name ?? '' }}" readonly><input type="hidden"
                    name="division_id" value="{{ $representative->division_id }}"></div>
            <div class="col-12 mb-16"><label class="form-label text-sm mb-6">Address</label>
                <textarea name="address" class="form-control radius-8" rows="2">{{ $representative->address }}</textarea>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Status</label><select name="status"
                    class="form-select radius-8">
                    <option value="1" @selected($representative->status)>Active</option>
                    <option value="0" @selected(!$representative->status)>Inactive</option>
                </select></div>
        </div>
        <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button"
                class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">Cancel</button><button type="submit"
                class="btn btn-primary px-48 py-12 radius-8">Update</button></div>
    </form>
</div>
