<div class="modal-header py-16 px-24 border-0" data-modal-key="courier-marketing-edit">
    <h5 class="modal-title d-flex align-items-center gap-2">
        <iconify-icon icon="lucide:edit" class="text-success text-xl"></iconify-icon>
        Edit Courier Dispatch #CM-{{ $courierMarketing->id }}
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
    <form action="{{ route('courier-marketings.update', $courierMarketing->id) }}" method="post" data-ajax="true">
        @csrf
        @method('PUT')
        
        <div class="mb-20">
            <h6 class="text-sm fw-bold mb-12 text-dark">Assignment Details</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Marketing Representative</label>
                    <select name="marketing_representative_id" class="form-control radius-8 js-s2-ajax js-rep-select"
                        data-url="{{ route('marketing-representative.marketing-representatives.select2', ['use_rep_id' => 1]) }}"
                        data-placeholder="Select Representative">
                        @if ($courierMarketing->representative)
                            <option value="{{ $courierMarketing->marketing_representative_id }}" selected>
                                {{ $courierMarketing->representative->user?->name ?? 'Representative' }} ({{ $courierMarketing->representative->employee_id }})
                            </option>
                        @endif
                    </select>
                    <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-sm mb-6">Teacher</label>
                    <input type="text" class="form-control radius-8 bg-light"
                        value="{{ $courierMarketing->teacher?->teacher_name ?? '-' }} ({{ $courierMarketing->teacher?->phone ?? 'No Phone' }})"
                        disabled>
                </div>

                <div class="col-12">
                    <label class="form-label text-sm mb-6 text-muted">Institution & Location</label>
                    <input type="text" class="form-control radius-8 bg-light"
                        value="{{ $courierMarketing->institution?->institution_name ?? ($courierMarketing->teacher?->institution?->institution_name ?? '-') }}"
                        disabled>
                </div>
            </div>
        </div>

        <hr class="border-neutral-200 my-20">

        <div class="mb-20">
            <h6 class="text-sm fw-bold mb-12 text-dark">Courier & Dispatch Details</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-sm mb-6">Courier Company</label>
                    <input type="text" name="courier_company" class="form-control radius-8"
                        value="{{ old('courier_company', $courierMarketing->courier_company) }}" list="editCourierCompanyList" placeholder="e.g. Sundarban, Steadfast">
                    <datalist id="editCourierCompanyList">
                        <option value="Sundarban Courier Service">
                        <option value="Steadfast Courier">
                        <option value="SA Paribahan">
                        <option value="Pathao Courier">
                        <option value="RedX Logistics">
                        <option value="Karatoa Courier">
                        <option value="eCourier">
                    </datalist>
                    <div class="invalid-feedback d-block courier_company-error" style="display:none"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-sm mb-6">Dispatch Date <span class="text-danger">*</span></label>
                    <input type="date" name="dispatch_date" class="form-control radius-8"
                        value="{{ old('dispatch_date', $courierMarketing->dispatch_date?->format('Y-m-d')) }}" required>
                    <div class="invalid-feedback d-block dispatch_date-error" style="display:none"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-sm mb-6">Expected Delivery Date</label>
                    <input type="date" name="delivery_date" class="form-control radius-8"
                        value="{{ old('delivery_date', $courierMarketing->delivery_date?->format('Y-m-d')) }}">
                    <div class="invalid-feedback d-block delivery_date-error" style="display:none"></div>
                </div>

                <div class="col-12">
                    <label class="form-label text-sm mb-6">Dispatched Items</label>
                    <div class="table-responsive">
                        <table class="table table-sm bordered-table mb-0 text-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th class="text-end">Dispatched Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($courierMarketing->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->product?->name ?? ('#' . $item->product_id) }}</td>
                                        <td class="text-end fw-semibold text-primary">{{ number_format((int) $item->quantity) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No items found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label text-sm mb-6">Note</label>
                    <textarea name="note" class="form-control radius-8" rows="2">{{ old('note', $courierMarketing->note) }}</textarea>
                    <div class="invalid-feedback d-block note-error" style="display:none"></div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-center gap-3 mt-16 pt-12 border-top">
            <button type="button" class="btn border border-secondary-400 text-secondary-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update Dispatch</button>
        </div>
    </form>
</div>
