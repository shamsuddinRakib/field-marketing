@php
    $showPrice = !empty($show_price);
    $copyType = $copy_type ?? null;
    $userSelector = $user_selector ?? null;
    $existingItems = isset($items) ? $items : (isset($assignSpecimen->items) ? $assignSpecimen->items : collect());
    $firstExistingItem = $existingItems->first();
    $initCategory = $firstExistingItem?->product?->category;
    $initSubcategory = $firstExistingItem?->product?->subcategory;
    $defaultBookImg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="44" height="54" viewBox="0 0 44 54" fill="none"><rect width="44" height="54" rx="6" fill="#f1f5f9"/><path d="M14 18h16M14 24h16M14 30h10" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/><rect x="8" y="10" width="28" height="34" rx="2" stroke="#cbd5e1" stroke-width="1.5"/></svg>');
@endphp

<style>
    .pos-picker-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
    }
    .pos-picker-header {
        padding: 12px 18px;
        background-color: #f8fafc;
        border-bottom: 1px solid #eef2f6;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    .pos-picker-body {
        padding: 16px 18px;
    }
    .pos-product-list-box {
        max-height: 400px;
        min-height: 240px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .pos-product-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        margin-bottom: 8px;
        background: #ffffff;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    .pos-product-item.d-none {
        display: none !important;
    }
    .pos-product-item:hover {
        border-color: #487fff;
        background-color: #f8faff;
        box-shadow: 0 2px 6px rgba(72, 127, 255, 0.12);
        transform: translateY(-1px);
    }
    .pos-product-item.in-cart {
        border-color: #10b981;
        background-color: #f0fdf4;
    }
    .pos-product-thumb {
        width: 44px;
        height: 54px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        flex-shrink: 0;
    }
    .pos-cart-thumb {
        width: 36px;
        height: 44px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        flex-shrink: 0;
    }
    .pos-cart-table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }
    .pos-cart-table thead th {
        vertical-align: middle !important;
        padding: 8px 10px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .pos-cart-table tbody tr {
        vertical-align: middle !important;
        border-bottom: 1px solid #f1f5f9;
    }
    .pos-cart-table tbody td {
        vertical-align: middle !important;
        padding: 8px 10px !important;
    }
    .pos-cart-price-cell,
    .pos-cart-total-cell {
        white-space: nowrap !important;
    }
    .pos-qty-stepper {
        display: inline-flex;
        align-items: center;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }
    .pos-qty-stepper .btn-step {
        border: none;
        background: #f8fafc;
        color: #374151;
        width: 28px;
        height: 32px;
        line-height: 1;
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }
    .pos-qty-stepper .btn-step:hover {
        background: #e2e8f0;
        color: #111827;
    }
    .pos-qty-stepper input.pos-qty-input {
        width: 46px;
        height: 32px;
        border: none;
        text-align: center;
        font-weight: 600;
        font-size: 13px;
        padding: 0;
        margin: 0;
        outline: none;
        -moz-appearance: textfield;
    }
    .pos-qty-stepper input.pos-qty-input::-webkit-outer-spin-button,
    .pos-qty-stepper input.pos-qty-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .pos-btn-remove {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 13px;
        line-height: 1;
    }
</style>

<div class="row g-3 mb-24" id="posProductPickerRoot">

    {{-- LEFT COLUMN: PRODUCT CATALOG BROWSER --}}
    <div class="col-lg-6 col-12">
        <div class="card pos-picker-card h-100 mb-0">
            <div class="pos-picker-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="mb-0 fw-semibold text-neutral-800 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:box-bold-duotone" class="text-primary-600 text-lg"></iconify-icon>
                    Product Catalog
                </h6>
                <span class="text-xs text-muted" id="posCatalogStatusBadge">Select Category & Subcategory</span>
            </div>

            <div class="pos-picker-body">
                {{-- Filter Row: Category & Subcategory --}}
                <div class="row g-2 mb-12">
                    <div class="col-sm-6">
                        <label class="form-label text-xs fw-semibold mb-1 text-neutral-700">Category <span class="text-danger">*</span></label>
                        <select id="posCategorySelect" class="form-control form-control-sm"
                            data-placeholder="Select Category">
                            <option value="">Select Category</option>
                            @if ($initCategory)
                                <option value="{{ $initCategory->id }}" selected>{{ $initCategory->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label text-xs fw-semibold mb-1 text-neutral-700">Subcategory <span class="text-danger">*</span></label>
                        <select id="posSubcategorySelect" class="form-control form-control-sm"
                            data-placeholder="Select Subcategory" {{ $initCategory ? '' : 'disabled' }}>
                            <option value="">Select Subcategory</option>
                            @if ($initSubcategory)
                                <option value="{{ $initSubcategory->id }}" selected>{{ $initSubcategory->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                {{-- Live Search Inside Loaded Products --}}
                <div class="mb-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <iconify-icon icon="solar:magnifer-linear"></iconify-icon>
                        </span>
                        <input type="text" id="posProductSearchInput" class="form-control form-control-sm border-start-0 ps-0 radius-8"
                            placeholder="Search by book name..." disabled>
                    </div>
                </div>

                {{-- Products List Container --}}
                <div class="pos-product-list-box" id="posProductListBox">
                    <div class="text-center py-40 text-muted" id="posEmptyCatalogHint">
                        <iconify-icon icon="solar:book-2-linear" class="text-4xl text-neutral-400 mb-2"></iconify-icon>
                        <p class="m-0 text-sm">Please select a Category and Subcategory above to view products.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT COLUMN: CART / SELECTED PRODUCTS --}}
    <div class="col-lg-6 col-12">
        <div class="card pos-picker-card h-100 mb-0 d-flex flex-column">
            <div class="pos-picker-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-semibold text-neutral-800 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:cart-large-minimalistic-bold-duotone" class="text-success-main text-lg"></iconify-icon>
                        Selected Products (Cart)
                    </h6>
                    <span class="badge bg-primary-50 text-primary-600 border border-primary-200 text-xs radius-4" id="posCartCountBadge">0 items</span>
                </div>
                <button type="button" class="btn btn-xs btn-outline-danger radius-6 py-2 px-8" id="posBtnClearCart" style="display:none;">Clear All</button>
            </div>

            <div class="pos-picker-body flex-grow-1 d-flex flex-column p-0">
                {{-- Cart Table --}}
                <div class="table-responsive flex-grow-1" style="max-height: 400px; overflow-y: auto;">
                    <table class="table pos-cart-table mb-0" id="posCartTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                @if ($showPrice)
                                    <th class="text-end text-nowrap" style="width: 100px;">Price</th>
                                @endif
                                <th class="text-center text-nowrap" style="width: 120px;">Qty</th>
                                @if ($showPrice)
                                    <th class="text-end text-nowrap" style="width: 110px;">Total</th>
                                @endif
                                <th class="text-center" style="width: 44px;"></th>
                            </tr>
                        </thead>
                        <tbody id="posCartTableBody">
                            <tr id="posEmptyCartRow">
                                <td colspan="{{ $showPrice ? '5' : '3' }}" class="text-center py-40 text-muted">
                                    <iconify-icon icon="solar:cart-large-minimalistic-linear" class="text-4xl text-neutral-400 d-block mb-1"></iconify-icon>
                                    <span class="text-sm">No products selected yet.<br>Click <strong>+ Add</strong> on any product from the left.</span>
                                </td>
                            </tr>

                            {{-- Pre-populated existing items for EDIT mode --}}
                            @if ($existingItems->count() > 0)
                                @foreach ($existingItems as $idx => $item)
                                    @php
                                        $prod = $item->product;
                                        $pId = $item->product_id;
                                        $pName = $prod?->name ?? 'Product #' . $pId;
                                        $pQty = (int) $item->quantity;
                                        $pPrice = (float) ($item->price ?? ($prod?->price ?? 0));
                                        $pLineTotal = round($pPrice * $pQty, 2);
                                        $pCat = $prod?->category?->name;
                                        $pSubcat = $prod?->subcategory?->name;
                                        $meta = array_filter([$pCat, $pSubcat]);
                                        $metaStr = implode(' • ', $meta);
                                        $thumbImg = !empty($prod?->thumbnail_image) ? image($prod->thumbnail_image) : $defaultBookImg;
                                    @endphp
                                    <tr class="pos-cart-row" data-product-id="{{ $pId }}" data-product-name="{{ $pName }}" data-price="{{ $pPrice }}">
                                        <td>
                                            <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $pId }}">
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $thumbImg }}" class="pos-cart-thumb" alt="Book" onerror="this.src='{{ $defaultBookImg }}';">
                                                <div class="overflow-hidden">
                                                    <div class="fw-semibold text-neutral-800 text-sm lh-sm text-truncate" title="{{ $pName }}">{{ $pName }}</div>
                                                    @if ($metaStr)
                                                        <span class="badge bg-light text-secondary text-xs mt-1 radius-4">{{ $metaStr }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        @if ($showPrice)
                                            <td class="text-end text-nowrap pos-cart-price-cell">
                                                <span class="fw-semibold text-neutral-800 text-nowrap">৳&nbsp;<span class="pos-row-price-text">{{ number_format($pPrice, 2, '.', '') }}</span></span>
                                            </td>
                                        @endif
                                        <td class="text-center">
                                            <div class="pos-qty-stepper mx-auto">
                                                <button type="button" class="btn-step btn-step-minus">−</button>
                                                <input type="number" step="1" min="1" value="{{ $pQty }}" name="items[{{ $idx }}][quantity]" class="pos-qty-input" required>
                                                <button type="button" class="btn-step btn-step-plus">+</button>
                                            </div>
                                        </td>
                                        @if ($showPrice)
                                            <td class="text-end text-nowrap pos-cart-total-cell">
                                                <span class="fw-bold text-primary-600 text-nowrap">৳&nbsp;<span class="pos-row-total-text">{{ number_format($pLineTotal, 2, '.', '') }}</span></span>
                                            </td>
                                        @endif
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger pos-btn-remove" title="Remove">✕</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Summary Footer --}}
                <div class="p-16 border-top bg-neutral-50 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-xs text-muted d-block">Distinct Items: <strong id="posDistinctCountText">0</strong></span>
                        <span class="fw-semibold text-neutral-800 text-sm">Total Quantity</span>
                        <h5 class="mb-0 fw-bold text-neutral-900" id="posTotalQuantityText">0</h5>
                    </div>
                    <div class="text-end">
                        @if ($showPrice)
                            <div class="d-flex flex-column align-items-end gap-1">
                                <div class="text-sm">
                                    <span class="text-muted me-2">Subtotal:</span>
                                    <span class="fw-semibold text-neutral-800 text-nowrap">৳&nbsp;<span id="posSubtotalText">0.00</span></span>
                                    <input type="hidden" name="subtotal" id="posSubtotalInput" value="0.00">
                                </div>
                                <div class="d-flex align-items-center justify-content-end text-sm">
                                    <label for="posDiscountInput" class="text-muted me-2 mb-0">Discount (৳):</label>
                                    <input type="number" step="0.01" min="0" name="discount" id="posDiscountInput" 
                                           class="form-control form-control-sm text-end fw-semibold py-1 px-8 radius-6" 
                                           style="width: 110px; height: 28px;"
                                           placeholder="0.00" 
                                           value="{{ isset($spotSale) && $spotSale->discount !== null ? number_format((float) $spotSale->discount, 2, '.', '') : '0.00' }}">
                                </div>
                                <div class="border-top pt-1 mt-1">
                                    <span class="text-xs text-muted me-1 fw-bold">Grand Total:</span>
                                    <span class="fw-bold text-primary-600 fs-6 text-nowrap">৳&nbsp;<span id="posGrandTotalText">0.00</span></span>
                                    <input type="hidden" name="total" id="posGrandTotalInput" value="0.00">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    (function checkDependencies() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            setTimeout(checkDependencies, 30);
            return;
        }

        jQuery(function($) {
            const showPrice      = {{ $showPrice ? 'true' : 'false' }};
            const copyType       = "{{ $copyType ?? '' }}";
            const userSelector   = "{{ $userSelector ?? '' }}";
            const categoryUrl    = "{{ route('category.cat.select2') }}";
            const subcategoryUrl = "{{ route('subcategory.select2') }}";
            const productUrl     = "{{ route('product.select2') }}";
            const defaultBookImg = "{{ $defaultBookImg }}";

            const $root           = $('#posProductPickerRoot');
            const $catSelect      = $('#posCategorySelect');
            const $subcatSelect   = $('#posSubcategorySelect');
            const $searchInput    = $('#posProductSearchInput');
            const $listBox        = $('#posProductListBox');
            const $statusBadge    = $('#posCatalogStatusBadge');
            const $cartBody       = $('#posCartTableBody');
            const $emptyCartRow   = $('#posEmptyCartRow');
            const $btnClearCart   = $('#posBtnClearCart');
            const $cartCountBadge = $('#posCartCountBadge');
            const $totalQtyText   = $('#posTotalQuantityText');
            const $distinctText   = $('#posDistinctCountText');

            let loadedProducts = [];
            let nextItemIndex = {{ $existingItems->count() > 0 ? $existingItems->count() : 0 }};
            let searchDebounceTimer = null;

            // 1. Initialize Category Select2
            if ($catSelect.hasClass('select2-hidden-accessible')) {
                $catSelect.select2('destroy');
            }
            $catSelect.select2({
                width: '100%',
                placeholder: 'Select Category',
                allowClear: true,
                ajax: {
                    url: categoryUrl,
                    dataType: 'json',
                    delay: 200,
                    data: function(params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });

            // 2. Initialize Subcategory Select2
            function initSubcategorySelect2(selectedId = null, selectedText = null) {
                const catId = $catSelect.val();

                if ($subcatSelect.hasClass('select2-hidden-accessible')) {
                    $subcatSelect.select2('destroy');
                }

                $subcatSelect.empty().append(new Option('Select Subcategory', '', !selectedId, !selectedId));

                if (selectedId && selectedText) {
                    $subcatSelect.append(new Option(selectedText, selectedId, true, true));
                }

                if (!catId) {
                    $subcatSelect.prop('disabled', true);
                    return;
                }

                $subcatSelect.prop('disabled', false).select2({
                    width: '100%',
                    placeholder: 'Select Subcategory',
                    allowClear: true,
                    ajax: {
                        url: subcategoryUrl,
                        dataType: 'json',
                        delay: 200,
                        data: function(params) {
                            return {
                                q: params.term || '',
                                category_id: $catSelect.val(),
                                type: $catSelect.val()
                            };
                        },
                        processResults: function(data) {
                            return { results: Array.isArray(data?.results) ? data.results : [] };
                        }
                    }
                });
            }

            // 3. Fetch products by subcategory with optional search query
            function fetchProducts(categoryId, subcategoryId, searchQuery = '') {
                if (!categoryId || !subcategoryId) return;

                const userId = userSelector ? $(userSelector).val() : '';

                if (copyType && !userId) {
                    $listBox.html(`
                        <div class="text-center py-40 text-muted">
                            <iconify-icon icon="solar:user-speak-linear" class="text-4xl text-warning mb-2"></iconify-icon>
                            <p class="m-0 text-sm fw-medium text-warning">Please select a Marketing Representative first to view their available stock.</p>
                        </div>
                    `);
                    $statusBadge.text('Select Representative first')
                        .removeClass('text-success text-danger').addClass('text-warning');
                    return;
                }

                if (!searchQuery) {
                    $listBox.html(`
                        <div class="text-center py-40 text-primary">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            <span class="text-sm">Loading available products...</span>
                        </div>
                    `);
                }

                $.ajax({
                    url: productUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        category_id: categoryId,
                        subcategory_id: subcategoryId,
                        q: searchQuery,
                        user_id: userId,
                        copy_type: copyType,
                        limit: 100
                    },
                    success: function(res) {
                        loadedProducts = Array.isArray(res?.results) ? res.results : [];
                        renderProducts(loadedProducts, searchQuery);
                        $searchInput.prop('disabled', false);

                        const countMsg = searchQuery 
                            ? (loadedProducts.length + ' matching products') 
                            : (loadedProducts.length + ' products found');
                        $statusBadge.text(countMsg)
                            .removeClass('text-muted text-danger text-warning').addClass('text-success');
                    },
                    error: function() {
                        $listBox.html(`
                            <div class="text-center py-30 text-danger">
                                <iconify-icon icon="solar:danger-triangle-linear" class="text-3xl mb-1"></iconify-icon>
                                <p class="m-0 text-sm">Failed to load products. Please try again.</p>
                            </div>
                        `);
                        $statusBadge.text('Error loading products')
                            .removeClass('text-muted text-success text-warning').addClass('text-danger');
                    }
                });
            }

            // Optional: Listen for MR selection change to automatically reload products
            if (userSelector) {
                $(document).on('change', userSelector, function() {
                    const catId = $catSelect.val();
                    const subcatId = $subcatSelect.val();
                    if (catId && subcatId) {
                        fetchProducts(catId, subcatId, $searchInput.val());
                    }
                });
            }

            // 4. Render product cards in left catalog with images
            function renderProducts(products, searchQuery = '') {
                if (!products || products.length === 0) {
                    $listBox.html(`
                        <div class="text-center py-40 text-muted">
                            <iconify-icon icon="solar:box-minimalistic-linear" class="text-4xl text-neutral-400 mb-2"></iconify-icon>
                            <p class="m-0 text-sm">${searchQuery ? 'No products match your search.' : (copyType ? 'No available stock found for this representative.' : 'No products found for this subcategory.')}</p>
                        </div>
                    `);
                    return;
                }

                const catText = $catSelect.find('option:selected').text() || '';
                const subcatText = $subcatSelect.find('option:selected').text() || '';
                const metaBadge = [catText, subcatText].filter(Boolean).join(' • ');

                let html = '';
                products.forEach(p => {
                    const pId = String(p.id);
                    const pName = p.text || 'Product #' + pId;
                    const pImg = p.image || defaultBookImg;
                    const pPrice = parseFloat(p.price || 0);
                    const pStock = (p.available_stock !== undefined && p.available_stock !== null) ? parseInt(p.available_stock, 10) : null;
                    const existingRow = $cartBody.find(`.pos-cart-row[data-product-id="${pId}"]`);
                    const inCart = existingRow.length > 0;
                    const currentQty = inCart ? existingRow.find('.pos-qty-input').val() : 0;
                    const priceBadge = showPrice 
                        ? `<span class="badge bg-primary-50 text-primary-600 text-xs radius-4 mt-1 fw-semibold">৳ ${pPrice.toFixed(2)}</span>` 
                        : '';
                    const stockBadge = (pStock !== null)
                        ? `<span class="badge ${pStock > 0 ? 'bg-info-subtle text-info-700 border border-info-subtle' : 'bg-danger-subtle text-danger-700 border border-danger-subtle'} text-xs radius-4 mt-1 fw-semibold d-inline-flex flex-row align-items-center gap-1 text-nowrap py-1 px-2" style="display:inline-flex !important; flex-direction:row !important; align-items:center !important; white-space:nowrap !important;">
                             <iconify-icon icon="solar:box-minimalistic-bold" class="text-sm flex-shrink-0" style="vertical-align: middle; display:inline-block;"></iconify-icon>
                             <span style="white-space:nowrap; display:inline-block;">Stock: <strong>${pStock}</strong></span>
                           </span>`
                        : '';

                    html += `
                        <div class="pos-product-item ${inCart ? 'in-cart' : ''}"
                             data-id="${pId}" data-name="${escapeHtml(pName)}" data-meta="${escapeHtml(metaBadge)}" data-image="${escapeHtml(pImg)}" data-price="${pPrice}" data-stock="${pStock !== null ? pStock : ''}">
                            <div class="d-flex align-items-center gap-2 pe-2 flex-grow-1 overflow-hidden">
                                <img src="${escapeHtml(pImg)}" class="pos-product-thumb" alt="Book" onerror="this.src='${defaultBookImg}';">
                                <div class="overflow-hidden">
                                    <div class="fw-semibold text-neutral-800 text-sm pos-p-name text-truncate" title="${escapeHtml(pName)}">${escapeHtml(pName)}</div>
                                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                        ${metaBadge ? `<span class="badge bg-light text-secondary text-xs radius-4">${escapeHtml(metaBadge)}</span>` : ''}
                                        ${priceBadge}
                                        ${stockBadge}
                                        <span class="badge bg-success-subtle text-success-700 text-xs in-cart-badge radius-4" style="display:${inCart ? 'inline-block' : 'none'};">In Cart: <strong class="in-cart-qty">${currentQty}</strong></span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary radius-6 px-12 py-6 btn-add-pos-item d-inline-flex align-items-center gap-1 flex-shrink-0">
                                <iconify-icon icon="ic:baseline-plus" class="text-base"></iconify-icon>
                                <span class="text-xs fw-semibold">Add</span>
                            </button>
                        </div>
                    `;
                });

                $listBox.html(html);
            }

            // 5. Dual Live Search: Instant Client Filter + Debounced Server Query
            $searchInput.off('input').on('input', function() {
                const query = $(this).val().trim();
                const lower = query.toLowerCase();

                // 5a. Instant client-side visual filtering
                let visibleCount = 0;
                $listBox.find('.pos-product-item').each(function() {
                    const name = $(this).find('.pos-p-name').text().toLowerCase();
                    const matches = !lower || name.includes(lower);
                    $(this).toggleClass('d-none', !matches);
                    if (matches) visibleCount++;
                });

                // 5b. Debounced server-side query (handles partial/Unicode/large product sets)
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function() {
                    const catId = $catSelect.val();
                    const subcatId = $subcatSelect.val();
                    if (catId && subcatId) {
                        fetchProducts(catId, subcatId, query);
                    }
                }, 300);
            });

            // 6. Add Product to Cart (fires once per click on card or Add button)
            $listBox.off('click', '.pos-product-item').on('click', '.pos-product-item', function(e) {
                const $item = $(this);
                const pId = String($item.attr('data-id'));
                const pName = $item.attr('data-name');
                const pMeta = $item.attr('data-meta') || '';
                const pImg = $item.attr('data-image') || defaultBookImg;
                const pPrice = parseFloat($item.attr('data-price') || 0);
                const stockAttr = $item.attr('data-stock');
                const pStock = (stockAttr !== '' && stockAttr !== undefined) ? parseInt(stockAttr, 10) : null;

                addToCart(pId, pName, pMeta, pImg, pPrice, 1, pStock);
            });

            function addToCart(pId, pName, pMeta, pImg, pPrice, qtyToAdd, maxStock = null) {
                const $existingRow = $cartBody.find(`.pos-cart-row[data-product-id="${pId}"]`);

                if ($existingRow.length > 0) {
                    const $qtyInput = $existingRow.find('.pos-qty-input');
                    const curMax = $existingRow.attr('data-max-stock') ? parseInt($existingRow.attr('data-max-stock'), 10) : maxStock;
                    let newQty = parseInt($qtyInput.val() || 0, 10) + qtyToAdd;
                    if (curMax !== null && newQty > curMax) {
                        newQty = curMax;
                    }
                    $qtyInput.val(newQty);

                    $existingRow.css('background-color', '#e0f2fe');
                    setTimeout(() => $existingRow.css('background-color', ''), 400);
                } else {
                    $emptyCartRow.hide();

                    if (maxStock !== null && qtyToAdd > maxStock) {
                        qtyToAdd = maxStock;
                    }

                    const priceCellHtml = showPrice 
                        ? `<td class="text-end text-nowrap pos-cart-price-cell">
                               <span class="fw-semibold text-neutral-800 text-nowrap">৳&nbsp;<span class="pos-row-price-text">${parseFloat(pPrice).toFixed(2)}</span></span>
                           </td>` 
                        : '';

                    const totalCellHtml = showPrice 
                        ? `<td class="text-end text-nowrap pos-cart-total-cell">
                               <span class="fw-bold text-primary-600 text-nowrap">৳&nbsp;<span class="pos-row-total-text">${(parseFloat(pPrice) * qtyToAdd).toFixed(2)}</span></span>
                           </td>` 
                        : '';

                    const maxAttr = maxStock !== null ? `max="${maxStock}"` : '';
                    const stockDataAttr = maxStock !== null ? `data-max-stock="${maxStock}"` : '';

                    const rowHtml = `
                        <tr class="pos-cart-row" data-product-id="${pId}" data-product-name="${escapeHtml(pName)}" data-price="${pPrice}" ${stockDataAttr}>
                            <td>
                                <input type="hidden" name="items[${nextItemIndex}][product_id]" value="${pId}">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="${escapeHtml(pImg)}" class="pos-cart-thumb" alt="Book" onerror="this.src='${defaultBookImg}';">
                                    <div class="overflow-hidden">
                                        <div class="fw-semibold text-neutral-800 text-sm lh-sm text-truncate" title="${escapeHtml(pName)}">${escapeHtml(pName)}</div>
                                        ${pMeta ? `<span class="badge bg-light text-secondary text-xs mt-1 radius-4">${escapeHtml(pMeta)}</span>` : ''}
                                    </div>
                                </div>
                            </td>
                            ${priceCellHtml}
                            <td class="text-center">
                                <div class="pos-qty-stepper mx-auto">
                                    <button type="button" class="btn-step btn-step-minus">−</button>
                                    <input type="number" step="1" min="1" ${maxAttr} value="${qtyToAdd}" name="items[${nextItemIndex}][quantity]" class="pos-qty-input" required>
                                    <button type="button" class="btn-step btn-step-plus">+</button>
                                </div>
                            </td>
                            ${totalCellHtml}
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger pos-btn-remove" title="Remove">✕</button>
                            </td>
                        </tr>
                    `;

                    $cartBody.append(rowHtml);
                    nextItemIndex++;
                }

                syncProductCatalogItem(pId);
                updateCartSummary();
            }

            // Stepper Minus
            $cartBody.off('click', '.btn-step-minus').on('click', '.btn-step-minus', function() {
                const $input = $(this).siblings('.pos-qty-input');
                const current = parseInt($input.val() || 1, 10);
                if (current > 1) {
                    $input.val(current - 1).trigger('change');
                } else {
                    const $row = $(this).closest('.pos-cart-row');
                    const pId = $row.attr('data-product-id');
                    $row.remove();
                    syncProductCatalogItem(pId);
                    updateCartSummary();
                }
            });

            // Stepper Plus
            $cartBody.off('click', '.btn-step-plus').on('click', '.btn-step-plus', function() {
                const $input = $(this).siblings('.pos-qty-input');
                const $row = $(this).closest('.pos-cart-row');
                const maxStock = $row.attr('data-max-stock') ? parseInt($row.attr('data-max-stock'), 10) : null;
                const current = parseInt($input.val() || 0, 10);
                if (maxStock !== null && current >= maxStock) {
                    return;
                }
                $input.val(current + 1).trigger('change');
            });

            // Manual Qty Input Change
            $cartBody.off('input change', '.pos-qty-input').on('input change', '.pos-qty-input', function() {
                const $row = $(this).closest('.pos-cart-row');
                const maxStock = $row.attr('data-max-stock') ? parseInt($row.attr('data-max-stock'), 10) : null;
                let val = parseInt($(this).val() || 1, 10);
                if (val < 1) val = 1;
                if (maxStock !== null && val > maxStock) {
                    val = maxStock;
                }
                $(this).val(val);

                const pId = $row.attr('data-product-id');
                syncProductCatalogItem(pId);
                updateCartSummary();
            });

            // Remove item from Cart
            $cartBody.off('click', '.pos-btn-remove').on('click', '.pos-btn-remove', function() {
                const $row = $(this).closest('.pos-cart-row');
                const pId = $row.attr('data-product-id');
                $row.remove();
                syncProductCatalogItem(pId);
                updateCartSummary();
            });

            // Clear All Cart
            $btnClearCart.off('click').on('click', function() {
                $cartBody.find('.pos-cart-row').remove();
                $listBox.find('.pos-product-item').removeClass('in-cart').find('.in-cart-badge').hide();
                updateCartSummary();
            });

            function syncProductCatalogItem(pId) {
                const $catalogItem = $listBox.find(`.pos-product-item[data-id="${pId}"]`);
                if ($catalogItem.length === 0) return;

                const $cartRow = $cartBody.find(`.pos-cart-row[data-product-id="${pId}"]`);
                if ($cartRow.length > 0) {
                    const qty = $cartRow.find('.pos-qty-input').val();
                    $catalogItem.addClass('in-cart');
                    $catalogItem.find('.in-cart-badge').show();
                    $catalogItem.find('.in-cart-qty').text(qty);
                } else {
                    $catalogItem.removeClass('in-cart');
                    $catalogItem.find('.in-cart-badge').hide();
                }
            }

            function updateCartSummary() {
                const $rows = $cartBody.find('.pos-cart-row');
                const distinctCount = $rows.length;
                let totalQty = 0;
                let subtotal = 0;

                $rows.each(function() {
                    const qty = parseInt($(this).find('.pos-qty-input').val() || 0, 10);
                    totalQty += qty;
                    if (showPrice) {
                        const price = parseFloat($(this).attr('data-price') || 0);
                        const lineTotal = price * qty;
                        $(this).find('.pos-row-total-text').text(lineTotal.toFixed(2));
                        subtotal += lineTotal;
                    }
                });

                if (distinctCount === 0) {
                    $emptyCartRow.show();
                    $btnClearCart.hide();
                } else {
                    $emptyCartRow.hide();
                    $btnClearCart.show();
                }

                $cartCountBadge.text(distinctCount + ' items');
                $distinctText.text(distinctCount);
                $totalQtyText.text(totalQty);
                if (showPrice) {
                    let discount = parseFloat($('#posDiscountInput').val() || 0);
                    if (isNaN(discount) || discount < 0) {
                        discount = 0;
                    }
                    if (discount > subtotal && subtotal > 0) {
                        discount = subtotal;
                        $('#posDiscountInput').val(discount.toFixed(2));
                    }
                    const grandTotal = Math.max(0, subtotal - discount);

                    $('#posSubtotalText').text(subtotal.toFixed(2));
                    $('#posSubtotalInput').val(subtotal.toFixed(2));
                    $('#posGrandTotalText').text(grandTotal.toFixed(2));
                    $('#posGrandTotalInput').val(grandTotal.toFixed(2));
                }
            }

            if (showPrice) {
                $('#posDiscountInput').on('input change', function() {
                    updateCartSummary();
                });
            }

            // Category Change Event Listener
            $catSelect.on('change select2:select select2:clear', function() {
                const catId = $(this).val();

                if ($subcatSelect.hasClass('select2-hidden-accessible')) {
                    $subcatSelect.select2('destroy');
                }
                $subcatSelect.empty().append(new Option('Select Subcategory', '', true, true)).prop('disabled', true);

                $listBox.html(`
                    <div class="text-center py-40 text-muted">
                        <iconify-icon icon="solar:book-2-linear" class="text-4xl text-neutral-400 mb-2"></iconify-icon>
                        <p class="m-0 text-sm">Please select a Subcategory to view products.</p>
                    </div>
                `);
                $statusBadge.text('Select Subcategory').removeClass('text-success text-danger').addClass('text-muted');
                $searchInput.prop('disabled', true).val('');

                if (catId) {
                    initSubcategorySelect2();
                }
            });

            // Subcategory Change Event Listener
            $subcatSelect.on('change select2:select select2:clear', function() {
                const subcatId = $(this).val();
                const catId = $catSelect.val();

                if (catId && subcatId) {
                    $searchInput.val('');
                    fetchProducts(catId, subcatId, '');
                } else {
                    $listBox.html(`
                        <div class="text-center py-40 text-muted">
                            <iconify-icon icon="solar:book-2-linear" class="text-4xl text-neutral-400 mb-2"></iconify-icon>
                            <p class="m-0 text-sm">Please select a Category and Subcategory above to view products.</p>
                        </div>
                    `);
                    $statusBadge.text('Select Category & Subcategory').removeClass('text-success text-danger').addClass('text-muted');
                    $searchInput.prop('disabled', true).val('');
                }
            });

            function escapeHtml(text) {
                return String(text)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Edit Mode Auto-Init
            @if ($initCategory)
                @if ($initSubcategory)
                    initSubcategorySelect2("{{ $initSubcategory->id }}", {!! json_encode($initSubcategory->name) !!});
                    fetchProducts("{{ $initCategory->id }}", "{{ $initSubcategory->id }}", '');
                @else
                    initSubcategorySelect2();
                @endif
            @endif

            updateCartSummary();

            // Submit Validation
            const $parentForm = $root.closest('form');
            if ($parentForm.length > 0) {
                $parentForm.off('submit.posPickerValidate').on('submit.posPickerValidate', function(e) {
                    const count = $cartBody.find('.pos-cart-row').length;
                    if (count === 0) {
                        e.preventDefault();
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Cart is Empty',
                                text: 'Please add at least one product to the cart before saving.'
                            });
                        } else {
                            alert('Please add at least one product to the cart before saving.');
                        }
                        return false;
                    }
                });
            }
        });
    })();
</script>
