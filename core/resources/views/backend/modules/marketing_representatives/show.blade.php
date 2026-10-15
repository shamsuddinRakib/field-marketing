@extends('backend.layouts.master')

@section('meta')
    <title>Marketing Representative Profile</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Marketing Representative Profile</h6>
            <p class="m-0">View representative details</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="{{ route('marketing-representative.marketing-representatives.index') }}"
                    class="hover-text-primary">Marketing Representatives</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Profile</li>
        </ul>
    </div>

    <div class="row gy-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="card-title mb-0">Representative Information</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('marketing-representative.marketing-representatives.index') }}"
                            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center">
                            <iconify-icon icon="lucide:arrow-left" class="me-1"></iconify-icon> Back to List
                        </a>
                        <a href="#" class="btn btn-sm btn-primary AjaxModal d-inline-flex align-items-center"
                            data-ajax-modal="{{ route('marketing-representative.marketing-representatives.editModal', $representative->id) }}"
                            data-size="lg" data-onload="MarketingRepresentativesIndex.onLoad"
                            data-onsuccess="MarketingRepresentativesIndex.onSaved">
                            <iconify-icon icon="lucide:edit" class="me-1"></iconify-icon> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Name</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->user->name }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Employee ID</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->employee_id }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Mobile</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $representative->user->phone ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Email</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $representative->user->email ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Organization</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $representative->organization ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Status</label>
                            <div>
                                @if ($representative->status)
                                    <span
                                        class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Active</span>
                                @else
                                    <span
                                        class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-24">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title mb-0">Address & Location</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-16">
                            <label class="form-label text-sm mb-6">Division</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $representative->division->name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-4 mb-16">
                            <label class="form-label text-sm mb-6">District</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $representative->district->district_name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-4 mb-16">
                            <label class="form-label text-sm mb-6">Upazila</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $representative->upazila->upazila_name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Branch</label>
                            <input class="form-control" type="text" disabled
                                value="{{ $representative->branch->name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Address</label>
                            <textarea class="form-control" rows="2" disabled>{{ $representative->address ?? 'N/A' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 border-0">
                    <h5 class="card-title mb-0">Profile Summary</h5>
                </div>

                <div class="card-body px-3 pt-0">
                    <div class="row g-3">

                        {{-- Current Fund --}}
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light text-center h-100 border border-primary-subtle d-flex flex-column justify-content-between transition-hover">
                                <div class="w-40-px h-40-px rounded-circle bg-primary-focus text-primary-main d-inline-flex align-items-center justify-content-center text-lg mx-auto mb-2 shadow-sm">
                                    <iconify-icon icon="solar:wallet-money-outline"></iconify-icon>
                                </div>
                                <div>
                                    <div class="text-muted text-xs fw-semibold mb-1 text-uppercase tracking-wider">Current Fund</div>
                                    <h6 class="fw-bold mb-0 text-dark text-sm">
                                        {{ number_format((float) ($currentFund ?? 0), 2) }}
                                    </h6>
                                </div>
                            </div>
                        </div>

                        {{-- Total Product --}}
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light text-center h-100 border border-success-subtle d-flex flex-column justify-content-between transition-hover">
                                <div class="w-40-px h-40-px rounded-circle bg-success-focus text-success-main d-inline-flex align-items-center justify-content-center text-lg mx-auto mb-2 shadow-sm">
                                    <iconify-icon icon="solar:box-outline"></iconify-icon>
                                </div>
                                <div>
                                    <div class="text-muted text-xs fw-semibold mb-1 text-uppercase tracking-wider">Total Product</div>
                                    <h6 class="fw-bold mb-0 text-dark text-sm">
                                        {{ number_format((int) ($totalProduct ?? 0)) }}
                                    </h6>
                                </div>
                            </div>
                        </div>

                        {{-- Spot Sales Count --}}
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light text-center h-100 border border-info-subtle d-flex flex-column justify-content-between transition-hover">
                                <div class="w-40-px h-40-px rounded-circle bg-info-focus text-info-main d-inline-flex align-items-center justify-content-center text-lg mx-auto mb-2 shadow-sm">
                                    <iconify-icon icon="solar:cart-large-2-outline"></iconify-icon>
                                </div>
                                <div>
                                    <div class="text-muted text-xs fw-semibold mb-1 text-uppercase tracking-wider">Spot Sales</div>
                                    <h6 class="fw-bold mb-0 text-dark text-sm">
                                        {{ number_format((int) ($totalSpotSalesCount ?? 0)) }}
                                    </h6>
                                </div>
                            </div>
                        </div>

                        {{-- Spot Sale Amount --}}
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light text-center h-100 border border-warning-subtle d-flex flex-column justify-content-between transition-hover">
                                <div class="w-40-px h-40-px rounded-circle bg-warning-focus text-warning-main d-inline-flex align-items-center justify-content-center text-lg mx-auto mb-2 shadow-sm">
                                    <iconify-icon icon="solar:wallet-outline"></iconify-icon>
                                </div>
                                <div>
                                    <div class="text-muted text-xs fw-semibold mb-1 text-uppercase tracking-wider">Spot Amount</div>
                                    <h6 class="fw-bold mb-0 text-dark text-sm">
                                        {{ number_format((float) ($totalSpotSalesAmount ?? 0), 2) }}
                                    </h6>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.MarketingRepresentativesIndex = window.MarketingRepresentativesIndex || {};
        window.MarketingRepresentativesIndex.onLoad = function($modal) {
            $modal.find('.js-branch-select').each(function() {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select branch',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('org.branches.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({
                            q: params.term || ''
                        }),
                        processResults: data => data
                    }
                });
            });
            $modal.find('.js-upazila-select').each(function() {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: 'Select upazila',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('marketing-representative.marketing-representatives.upazilas.select2') }}",
                        dataType: 'json',
                        delay: 200,
                        data: params => ({
                            q: params.term || ''
                        }),
                        processResults: data => data
                    }
                }).off('select2:select').on('select2:select', function(e) {
                    const item = e.params.data;
                    const $form = $select.closest('form');
                    $form.find('[name="district_id"]').val(item.district_id || '');
                    $form.find('[name="division_id"]').val(item.division_id || '');
                    $form.find('[name="district_name"]').val(item.district_name || '');
                    $form.find('[name="division_name"]').val(item.division_name || '');
                }).off('select2:clear').on('select2:clear', function() {
                    const $form = $select.closest('form');
                    $form.find(
                        '[name="district_id"], [name="division_id"], [name="district_name"], [name="division_name"]'
                        ).val('');
                });
            });
        };
        window.MarketingRepresentativesIndex.onSaved = function(res) {
            $window.location.reload();
        };
    </script>
@endsection