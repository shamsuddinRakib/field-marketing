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
                    value="{{ old('name', $representative->user?->name ?? $representative->name) }}" required></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Employee ID <span
                        class="text-danger">*</span></label><input name="employee_id" class="form-control radius-8"
                    value="{{ old('employee_id', $representative->employee_id) }}" required></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Password</label><input type="password"
                    name="password" class="form-control radius-8" minlength="8"
                    placeholder="Leave blank to keep current password"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Confirm Password</label><input
                    type="password" name="password_confirmation" class="form-control radius-8" minlength="8"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Mobile</label><input name="mobile"
                    class="form-control radius-8" value="{{ old('mobile', $representative->user?->phone ?? $representative->mobile) }}"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Email</label><input type="email"
                    name="email" class="form-control radius-8" value="{{ old('email', $representative->user?->email ?? $representative->email) }}"></div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Designation</label><select
                    name="mr_designation_id" class="form-control radius-8 js-designation-select"
                    data-selected="{{ $representative->mr_designation_id }}">
                    @if ($representative->mr_designation_id)
                        <option value="{{ $representative->mr_designation_id }}" selected>
                            {{ $representative->mrDesignation?->designation_name ?? $representative->designation?->designation_name ?? '#' . $representative->mr_designation_id }}
                        </option>
                    @endif
                </select>
                <div class="invalid-feedback d-block mr_designation_id-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16"><label class="form-label text-sm mb-6">Branch</label><select name="branch_id"
                    class="form-control radius-8 js-branch-select" data-selected="{{ $representative->branch_id }}">
                    @if ($representative->branch_id)
                        <option value="{{ $representative->branch_id }}" selected>
                            {{ $representative->branch?->name ?? '#' . $representative->branch_id }}</option>
                    @endif
                </select>
            </div>
            @php
                if ($representative->territories && $representative->territories->isNotEmpty()) {
                    $savedDistricts = $representative->territories->map(fn($t) => $t->district)->filter()->unique('district_id');
                    $divisionNames = $representative->territories->map(fn($t) => $t->division?->name)->filter()->unique()->implode(', ');
                } else {
                    $savedDistricts = $representative->district ? collect([$representative->district]) : collect();
                    $divisionNames = $representative->division?->name ?? '';
                }
            @endphp
            <div class="col-md-6 mb-20">
                <label class="form-label text-sm mb-6 d-block">District (Multiple) <span class="text-danger">*</span></label>
                <select name="district_ids[]" class="form-control radius-8 js-district-select" multiple="multiple" data-placeholder="Select District(s)">
                    @foreach ($savedDistricts as $d)
                        @if ($d)
                            <option value="{{ $d->district_id }}" selected
                                data-division-id="{{ $d->district_division_id }}"
                                data-division-name="{{ $d->district_division?->name }}">
                                {{ $d->district_name }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <div class="invalid-feedback d-block district_ids-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-20">
                <label class="form-label text-sm mb-6 d-block">Upazila (Multiple)</label>
                <select name="upazila_ids[]" class="form-control radius-8 js-upazila-select" multiple="multiple"
                    data-placeholder="{{ $savedDistricts->isNotEmpty() ? 'Select Upazila(s)' : 'Select District first' }}"
                    {{ $savedDistricts->isEmpty() ? 'disabled' : '' }}>
                    @if ($representative->territories && $representative->territories->isNotEmpty())
                        @foreach ($representative->territories as $t)
                            @if ($t->upazila)
                                <option value="{{ $t->upazila_id }}" selected
                                    data-district-id="{{ $t->district_id }}"
                                    data-district-name="{{ $t->district?->district_name }}"
                                    data-division-id="{{ $t->division_id }}"
                                    data-division-name="{{ $t->division?->name }}">
                                    {{ $t->upazila->upazila_name }} ({{ $t->district?->district_name }})
                                </option>
                            @endif
                        @endforeach
                    @elseif ($representative->upazila_id)
                        <option value="{{ $representative->upazila_id }}" selected>
                            {{ $representative->upazila?->upazila_name ?? '#' . $representative->upazila_id }}
                        </option>
                    @endif
                </select>
                <div class="invalid-feedback d-block upazila_ids-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-20">
                <label class="form-label text-sm mb-6 d-block">Division</label>
                <input name="division_name" class="form-control radius-8 bg-light" value="{{ old('division_name', $divisionNames) }}" readonly placeholder="Auto calculated from district">
                <input type="hidden" name="division_id" value="{{ old('division_id', $representative->division_id) }}">
            </div>
            <div class="col-md-6 mb-20"><label class="form-label text-sm mb-6 d-block">Status</label><select name="status"
                    class="form-select radius-8">
                    <option value="1" @selected($representative->status)>Active</option>
                    <option value="0" @selected(!$representative->status)>Inactive</option>
                </select></div>
            <div class="col-12 mb-16"><label class="form-label text-sm mb-6">Address</label>
                <textarea name="address" class="form-control radius-8" rows="2" placeholder="Enter Address">{{ old('address', $representative->address) }}</textarea>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-center gap-3 mt-12"><button type="button"
                class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">Cancel</button><button type="submit"
                class="btn btn-primary px-48 py-12 radius-8">Update</button></div>
    </form>
</div>

<script>
    (function() {
        const $modal = $('#AjaxModal');
        if (typeof window.initMarketingRepresentativeTerritory === 'function') {
            window.initMarketingRepresentativeTerritory($modal);
        }
    })();
</script>
