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
                <a href="{{ route('marketing-representative.marketing-representatives.index') }}" class="hover-text-primary">Marketing Representatives</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Profile</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Representative Information</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('marketing-representative.marketing-representatives.index') }}" class="btn btn-sm btn-outline-primary">
                            <iconify-icon icon="lucide:arrow-left" class="me-1"></iconify-icon> Back to List
                        </a>
                        <a href="#" class="btn btn-sm btn-primary AjaxModal"
                            data-ajax-modal="{{ route('marketing-representative.marketing-representatives.editModal', $representative->id) }}"
                            data-size="lg" data-onsuccess="MarketingRepresentativesIndex.onSaved">
                            <iconify-icon icon="lucide:edit" class="me-1"></iconify-icon> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Name</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->name }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Employee ID</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->employee_id }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Mobile</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->mobile ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Email</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->email ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Organization</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->organization ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Territory</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->territory ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Assigned Institutions</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->assigned_institutions ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Status</label>
                            <div>
                                @if($representative->status)
                                    <span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Active</span>
                                @else
                                    <span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-24">
                <div class="card-header">
                    <h5 class="card-title mb-0">Address & Location</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-16">
                            <label class="form-label text-sm mb-6">Division</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->division->name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-4 mb-16">
                            <label class="form-label text-sm mb-6">District</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->district->district_name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-4 mb-16">
                            <label class="form-label text-sm mb-6">Upazila</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->upazila->upazila_name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-6 mb-16">
                            <label class="form-label text-sm mb-6">Branch</label>
                            <input class="form-control" type="text" disabled value="{{ $representative->branch->name ?? 'N/A' }}">
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Summary</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-16">
                        <div class="w-80-px h-80-px rounded-circle bg-primary-focus text-primary-main d-inline-flex align-items-center justify-content-center fw-bold text-xl">
                            {{ strtoupper(substr($representative->name, 0, 2)) }}
                        </div>
                    </div>
                    <h6 class="fw-semibold">{{ $representative->name }}</h6>
                    <p class="text-muted text-sm mb-16">{{ $representative->employee_id }}</p>
                    <div class="d-flex justify-content-center gap-2">
                        @if($representative->mobile)
                            <a href="tel:{{ $representative->mobile }}" class="btn btn-sm btn-outline-primary" title="Call">
                                <iconify-icon icon="lucide:phone"></iconify-icon>
                            </a>
                        @endif
                        @if($representative->email)
                            <a href="mailto:{{ $representative->email }}" class="btn btn-sm btn-outline-primary" title="Email">
                                <iconify-icon icon="lucide:mail"></iconify-icon>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between text-sm mb-8">
                        <span class="text-muted">Created</span>
                        <span>{{ $representative->created_at?->format('d M, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-sm mb-8">
                        <span class="text-muted">Last Updated</span>
                        <span>{{ $representative->updated_at?->format('d M, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-sm">
                        <span class="text-muted">Deleted</span>
                        <span>{{ $representative->deleted_at?->format('d M, Y') ?? 'No' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.MarketingRepresentativesIndex = {
            onSaved: function(res) {
                if (res?.id) {
                    window.location.reload();
                }
            }
        };
    </script>
@endsection