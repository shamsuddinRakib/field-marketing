@extends('backend.layouts.master')

@section('meta')
    <title>Edit Distribution #{{ $productDistribution->id }}</title>
@endsection

@section('content')
    {{-- Breadcrumb & Title --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Product Distribution</h6>
            <p class="fw-semibold mb-0">Edit Distribution #{{ $productDistribution->id }}</p>
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
            <li class="fw-medium">Edit #{{ $productDistribution->id }}</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100">
            <a href="{{ route('product-distribution.index') }}" class="btn btn-secondary bg-dark">
                <i data-feather="arrow-left" class="me-2"></i>Back to Distribution
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form id="productDistEditForm" action="{{ route('product-distribution.update', $productDistribution->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- 1. DISTRIBUTION INFORMATION --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Distribution Information</h6>

                <div class="row g-3">
                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="distRepSelect"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($productDistribution->user)
                                <option value="{{ $productDistribution->user->id }}" selected>{{ $productDistribution->user->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Distribution Type <span class="text-danger">*</span></label>
                        <select name="distribution_type" class="form-select form-select-sm radius-8" required>
                            <option value="specimen" @selected(old('distribution_type', $productDistribution->distribution_type ?? 'specimen') === 'specimen')>Specimen Copy</option>
                            <option value="sale" @selected(old('distribution_type', $productDistribution->distribution_type ?? 'specimen') === 'sale')>Sale Copy</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            value="{{ $productDistribution->note }}" placeholder="Optional note">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REUSABLE POS PRODUCT PICKER COMPONENT (Pre-populated with existing items) --}}
        @include('backend.modules.products.partial.product_item_picker', [
            'items' => $productDistribution->items
        ])

        {{-- 3. ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-24 mb-32">
            <a href="{{ route('product-distribution.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitDist">Update Distribution</button>
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
            $('#productDistEditForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#btnSubmitDist');

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...');

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
                                    text: res.msg || 'Updated Successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = res.redirect || "{{ route('product-distribution.index') }}";
                                });
                            } else {
                                alert(res.msg || 'Updated Successfully');
                                window.location.href = res.redirect || "{{ route('product-distribution.index') }}";
                            }
                        } else {
                            $btn.prop('disabled', false).text('Update Distribution');
                            if (window.Swal) {
                                Swal.fire({ icon: 'error', title: 'Error', text: res?.msg || 'Something went wrong' });
                            } else {
                                alert(res?.msg || 'Something went wrong');
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Update Distribution');
                        const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to update distribution.';
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
