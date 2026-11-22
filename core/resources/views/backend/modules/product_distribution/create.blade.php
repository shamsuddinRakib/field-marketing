@extends('backend.layouts.master')

@section('meta')
    <title>Assign Products Distribution</title>
@endsection

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Product Distribution</h6>
            <p class="fw-semibold mb-0">Assign Products</p>
        </div>

        <ul class="d-flex align-items-center gap-2 mb-0">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="{{ route('product-distribution.index') }}" class="hover-text-primary">Product Distribution</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Assign Products</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('product-distribution.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Distribution
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="productDistCreateForm" action="{{ route('product-distribution.store') }}" method="POST">
        @csrf

        {{-- 1. DISTRIBUTION INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Distribution Information</h6>

                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        @php
                            $userRep = auth()->user()->representative ?? \App\Models\backend\MarketingRepresentative::where('user_id', auth()->id())->first();
                        @endphp
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="distRepSelect"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($userRep)
                                <option value="{{ $userRep->user_id ?? auth()->id() }}" selected>{{ $userRep->user?->name ?? auth()->user()->name }} ({{ $userRep->employee_id }})</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8" placeholder="Optional note">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER COMPONENT --}}
        @include('backend.modules.products.partial.product_item_picker')

        {{-- 3. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('product-distribution.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitDist">Save as Pending</button>
        </div>
    </form>
@endsection

@section('script')

<script>
    (function checkDeps() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            setTimeout(checkDeps, 30);
            return;
        }

        jQuery(function($) {
            // Initialize Representative Select2
            $('#distRepSelect').select2({
                width: '100%',
                placeholder: 'Select Marketing Representative',
                allowClear: true,
                ajax: {
                    url: "{{ route('marketing-representative.marketing-representatives.select2') }}",
                    dataType: 'json',
                    delay: 200,
                    data: function(params) {
                        return { q: params.term || '' };
                    },
                    processResults: function(data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });

            // AJAX Form Submit with redirect
            $('#productDistCreateForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#btnSubmitDist');

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res?.ok) {
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: res.msg || 'Products distributed successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('product-distribution.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Products distributed successfully');
                                window.location.href = res.redirect || "{{ route('product-distribution.index') }}";
                            }
                        } else {
                            $btn.prop('disabled', false).text('Save as Pending');
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: res?.msg || 'Something went wrong' });
                            } else {
                                alert(res?.msg || 'Something went wrong');
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Save as Pending');
                        const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to save product distribution.';
                        if (window.Swal) {
                            Swal.fire({ icon: 'error', title: 'Validation Error', text: errorMsg });
                        } else {
                            alert(errorMsg);
                        }
                    }
                });
            });
        });
    })();
</script>

@endsection
