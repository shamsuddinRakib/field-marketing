@php
    $firstItem = $bookRequests->first();
    $currentCategory = $firstItem?->product?->category;
    $currentSubcategory = $firstItem?->product?->subcategory;
@endphp
<div class="modal-header py-16 px-24 border-0" data-modal-key="book-request-edit">
    <h5 class="modal-title">Edit Book Request</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<style>
    /* Desktop & Tablet Modal Width */
    @media (min-width: 992px) {
        #AjaxModal .modal-dialog {
            max-width: 960px !important;
        }
    }
    @media (min-width: 1200px) {
        #AjaxModal .modal-dialog {
            max-width: 1050px !important;
        }
    }

    /* Mobile Modal Width & Spacing */
    @media (max-width: 767.98px) {
        #AjaxModal .modal-dialog {
            margin: 0.5rem auto !important;
            max-width: calc(100% - 1rem) !important;
            width: calc(100% - 1rem) !important;
        }
        #AjaxModal .modal-header {
            padding: 14px 16px !important;
        }
        #AjaxModal .modal-body {
            padding: 16px 14px !important;
        }
    }
</style>

<div class="modal-body p-24">
    <form action="{{ route('book-request.book-requests.update', $bookRequest->id) }}" method="post"
        data-ajax="true">
        @csrf
        @method('PUT')
        <input type="hidden" name="request_group_id" value="{{ $bookRequest->request_group_id }}">
        <input type="hidden" name="exclude_request_id" value="{{ $bookRequest->id }}">
        <div class="row">
            <div class="col-md-4 mb-16">
                <label class="form-label text-sm mb-6">Representative <span class="text-danger">*</span></label>
                <select name="marketing_representative_id" class="form-control radius-8 js-representative-select"
                    required>
                    @if ($bookRequest->marketing_representative_id)
                        <option value="{{ $bookRequest->marketing_representative_id }}" selected>
                            @if ($bookRequest->representative?->user?->name)
                                {{ $bookRequest->representative->user->name }}
                            @else
                                {{ $bookRequest->representative?->employee_id ?: '#' . $bookRequest->marketing_representative_id }}
                            @endif
                        </option>
                    @endif
                </select>
                <div class="invalid-feedback d-block marketing_representative_id-error" style="display:none"></div>
            </div>

            <div class="col-md-4 mb-16">
                <label class="form-label text-sm mb-6">Request Type <span class="text-danger">*</span></label>
                <select name="request_type" class="form-select radius-8" required>
                    @foreach (\App\Models\backend\BookRequest::REQUEST_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('request_type', $bookRequest->request_type ?? 'specimen') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback d-block request_type-error" style="display:none"></div>
            </div>

            <div class="col-md-4 mb-16">
                <label class="form-label text-sm mb-6">Date <span class="text-danger">*</span></label>
                <input type="date" name="request_date" class="form-control radius-8"
                    value="{{ old('request_date', $bookRequest->request_date?->format('Y-m-d')) }}" required>
                <div class="invalid-feedback d-block request_date-error" style="display:none"></div>
            </div>

            {{-- Category & Subcategory Filter Card --}}
            <div class="col-12 mb-16">
                <div class="card border">
                    <div class="card-body p-16">
                        <h6 class="text-sm fw-bold mb-12 text-dark">Product Filter (Category & Subcategory)</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-sm mb-6">Category <span class="text-danger">*</span></label>
                                <select id="bookRequestCategorySelect" class="form-control radius-8 js-s2-ajax"
                                    data-url="{{ route('category.cat.select2') }}"
                                    data-placeholder="Select Category">
                                    @if ($currentCategory)
                                        <option value="{{ $currentCategory->id }}" selected>{{ $currentCategory->name }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-sm mb-6">Subcategory <span class="text-danger">*</span></label>
                                <select id="bookRequestSubcategorySelect" class="form-control radius-8"
                                    data-placeholder="Select Subcategory"
                                    @disabled(!$currentCategory)>
                                    @if ($currentSubcategory)
                                        <option value="{{ $currentSubcategory->id }}" selected>{{ $currentSubcategory->name }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Products Section --}}
            <div class="col-12 mb-16">
                <div class="d-flex align-items-center justify-content-between mb-6">
                    <label class="form-label text-sm mb-0">Products <span class="text-danger">*</span></label>
                    <small id="bookRequestSelectionHint" class="{{ $currentSubcategory ? 'text-success' : 'text-muted' }}" style="font-size: 11px;">
                        {{ $currentSubcategory ? 'Showing products for selected subcategory' : 'Please select a Category and Subcategory above to enable products' }}
                    </small>
                </div>
                <div class="row g-2 book-request-product-items">
                    @foreach ($bookRequests as $index => $requestItem)
                        <div class="col-12 row g-2 align-items-start book-request-product-row">
                            <div class="col-md-8">
                                <select name="product_items[{{ $index }}][product_id]" class="form-control radius-8 js-product-select" required>
                                    @if ($requestItem->product_id)
                                        <option value="{{ $requestItem->product_id }}" selected>
                                            {{ $requestItem->product?->name ?? '#' . $requestItem->product_id }}</option>
                                    @endif
                                </select>
                                <div class="invalid-feedback d-block product_items.{{ $index }}.product_id-error" style="display:none"></div>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="product_items[{{ $index }}][quantity]" class="form-control radius-8 js-quantity-input"
                                    value="{{ $requestItem->quantity }}" min="1" required>
                                <div class="invalid-feedback d-block product_items.{{ $index }}.quantity-error" style="display:none"></div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger radius-8 w-100 btn-remove-product" aria-label="Remove product" @disabled($loop->first && $loop->last)>&times;</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-8 btn-add-product" @disabled(!$currentSubcategory)>Add product</button>
            </div>

            <div class="col-12 mb-16">
                <label class="form-label text-sm mb-6">Note</label>
                <textarea name="note" class="form-control radius-8" rows="3">{{ old('note', $bookRequest->note) }}</textarea>
                <div class="invalid-feedback d-block note-error" style="display:none"></div>
            </div>
            <div class="col-md-6 mb-16">
                <label class="form-label text-sm mb-6">Status</label>
                <select name="status" class="form-select radius-8">
                    @foreach (\App\Models\backend\BookRequest::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $bookRequest->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-center gap-3 mt-12">
            <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Update</button>
        </div>
    </form>
</div>
<template id="book-request-product-template">
    <div class="col-12 row g-2 align-items-start book-request-product-row">
        <div class="col-md-8">
            <select class="form-control radius-8 js-product-select" required></select>
            <div class="invalid-feedback d-block" style="display:none"></div>
        </div>
        <div class="col-md-3">
            <input type="number" class="form-control radius-8 js-quantity-input" value="1" min="1" required>
            <div class="invalid-feedback d-block" style="display:none"></div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger radius-8 w-100 btn-remove-product" aria-label="Remove product">&times;</button>
        </div>
    </div>
</template>
