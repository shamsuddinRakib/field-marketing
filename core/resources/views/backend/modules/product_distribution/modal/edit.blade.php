{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Edit Distribution #{{ $productDistribution->id }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

@php
    $firstItem = $productDistribution->items->first();
    $currentCategory = $firstItem?->product?->category;
    $currentSubcategory = $firstItem?->product?->subcategory;
@endphp

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="productDistEditForm" action="{{ route('product-distribution.update', $productDistribution->id) }}" method="POST" data-ajax="true">

        @csrf
        @method('PUT')

        {{-- ================= DISTRIBUTION INFO ================= --}}
        <div class="card mb-16">
            <div class="card-body">

                <h6 class="mb-12 fw-semibold">Distribution Information</h6>

                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($productDistribution->user)
                                <option value="{{ $productDistribution->user->id }}" selected>{{ $productDistribution->user->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            value="{{ $productDistribution->note }}" placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- ================= CATEGORY & SUBCATEGORY SELECTION ================= --}}
        <div class="card mb-16">
            <div class="card-body">
                <h6 class="mb-12 fw-semibold">Product Filter (Category & Subcategory)</h6>
                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Category <span class="text-danger">*</span></label>
                        <select id="distCategorySelect" class="form-control form-control-sm js-s2-ajax"
                            data-url="{{ route('category.cat.select2') }}"
                            data-placeholder="Select Category">
                            @if ($currentCategory)
                                <option value="{{ $currentCategory->id }}" selected>{{ $currentCategory->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Subcategory <span class="text-danger">*</span></label>
                        <select id="distSubcategorySelect" class="form-control form-control-sm"
                            data-placeholder="Select Subcategory" {{ $currentCategory ? '' : 'disabled' }}>
                            @if ($currentSubcategory)
                                <option value="{{ $currentSubcategory->id }}" selected>{{ $currentSubcategory->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= DISTRIBUTION ITEMS ================= --}}
        <div class="card mb-16">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-12">
                    <h6 class="mb-0 fw-semibold">Products</h6>
                    <span class="text-xs {{ $currentSubcategory ? 'text-success' : 'text-muted' }}" id="distSelectionHint">
                        {{ $currentSubcategory ? 'Showing products for selected subcategory' : 'Please select a Category and Subcategory above to enable products' }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle items-table-fixed" id="productDistItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70%">Product</th>
                                <th class="text-end" style="width:140px">Qty</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productDistribution->items as $i => $item)
                                <tr class="product-dist-item-row">
                                    <td>
                                        <select name="items[{{ $i }}][product_id]"
                                            class="form-control form-control-sm dist-product"
                                            data-placeholder="Select Product" required>
                                            @if ($item->product)
                                                <option value="{{ $item->product->id }}" selected>{{ $item->product->name }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" name="items[{{ $i }}][quantity]"
                                            class="form-control form-control-sm text-end dist-qty"
                                            value="{{ $item->quantity }}" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="product-dist-item-row">
                                    <td>
                                        <select name="items[0][product_id]" class="form-control form-control-sm dist-product"
                                            data-placeholder="Select Product" disabled required></select>
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" value="1" name="items[0][quantity]"
                                            class="form-control form-control-sm text-end dist-qty" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-12">
                    <button type="button" id="btnAddDistItem" class="btn btn-sm btn-outline-primary" {{ $currentSubcategory ? '' : 'disabled' }}>+ Add Item</button>
                </div>
            </div>
        </div>

        {{-- ================= SUMMARY ================= --}}
        <div class="card mb-16">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Total Quantity</h6>
                <h5 class="mb-0 fw-bold"><span id="distTotalText">{{ $productDistribution->items->sum('quantity') }}</span></h5>
            </div>
        </div>

        {{-- ================= ACTION ================= --}}
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-primary">Update Pending</button>
        </div>

    </form>
</div>

{{-- ================= JS ================= --}}
<script>
    window.ProductDistIndex = window.ProductDistIndex || { onLoad: function () {} };

    (function () {
        const $modal = $('#AjaxModal');
        const $table = $('#productDistItemsTable tbody');
        const $categorySelect = $('#distCategorySelect');
        const $subcategorySelect = $('#distSubcategorySelect');
        const $btnAddItem = $('#btnAddDistItem');
        const $hint = $('#distSelectionHint');
        let rowIndex = {{ max($productDistribution->items->count(), 1) }};

        const subcategoryUrl = "{{ route('subcategory.select2') }}";
        const productUrl = "{{ route('product.select2') }}";

        function parentModal() {
            return $modal.length ? $modal : $(document.body);
        }

        function initSubcategorySelect2() {
            if ($subcategorySelect.hasClass('select2-hidden-accessible')) {
                $subcategorySelect.select2('destroy');
            }

            $subcategorySelect.prop('disabled', false).select2({
                dropdownParent: parentModal(),
                width: '100%',
                placeholder: 'Select Subcategory',
                allowClear: true,
                ajax: {
                    url: subcategoryUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            category_id: $categorySelect.val(),
                            type: $categorySelect.val()
                        };
                    },
                    processResults: function (data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });
        }

        function initProductSelect2($select) {
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            const currentSubcategory = $subcategorySelect.val();

            if (!currentSubcategory && !$select.val()) {
                $select.prop('disabled', true).empty();
                return;
            }

            $select.prop('disabled', false).select2({
                dropdownParent: parentModal(),
                width: '100%',
                placeholder: 'Select Product',
                allowClear: true,
                ajax: {
                    url: productUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            category_id: $categorySelect.val(),
                            subcategory_id: $subcategorySelect.val()
                        };
                    },
                    processResults: function (data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });
        }

        function updateAllProductSelects() {
            const hasSubcategory = !!$subcategorySelect.val();
            if (hasSubcategory) {
                $btnAddItem.prop('disabled', false);
                $hint.text('Showing products for selected subcategory').removeClass('text-muted').addClass('text-success');
                $table.find('.dist-product').each(function () {
                    initProductSelect2($(this));
                });
            } else {
                $btnAddItem.prop('disabled', true);
                $hint.text('Please select a Category and Subcategory above to enable products').removeClass('text-success').addClass('text-muted');
                $table.find('.dist-product').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).empty().prop('disabled', true);
                });
            }
        }

        // Initialize existing subcategory & products if loaded
        if ($categorySelect.val()) {
            initSubcategorySelect2();
        }
        $table.find('.dist-product').each(function () {
            initProductSelect2($(this));
        });

        // On Category change -> reset Subcategory & Products
        $categorySelect.on('change', function () {
            const catId = $(this).val();

            if ($subcategorySelect.hasClass('select2-hidden-accessible')) {
                $subcategorySelect.select2('destroy');
            }
            $subcategorySelect.empty().prop('disabled', true);

            // Reset table rows
            $table.find('tr:gt(0)').remove();
            const $firstRowProduct = $table.find('tr:first .dist-product');
            if ($firstRowProduct.hasClass('select2-hidden-accessible')) {
                $firstRowProduct.select2('destroy');
            }
            $firstRowProduct.empty().prop('disabled', true);
            $table.find('tr:first .dist-qty').val(1);
            calculateTotal();

            if (catId) {
                initSubcategorySelect2();
            } else {
                updateAllProductSelects();
            }
        });

        // On Subcategory change -> reload product selects
        $subcategorySelect.on('change', function () {
            $table.find('tr:gt(0)').remove();
            const $firstRowProduct = $table.find('tr:first .dist-product');
            if ($firstRowProduct.hasClass('select2-hidden-accessible')) {
                $firstRowProduct.select2('destroy');
            }
            $firstRowProduct.empty();
            $table.find('tr:first .dist-qty').val(1);
            calculateTotal();

            updateAllProductSelects();
        });

        // Add Item row
        $('#btnAddDistItem').on('click', function () {
            if (!$subcategorySelect.val()) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Select Subcategory',
                        text: 'Please select a Subcategory first to add products.'
                    });
                }
                return;
            }

            const newRowHtml = `
                <tr class="product-dist-item-row">
                    <td>
                        <select name="items[${rowIndex}][product_id]" class="form-control form-control-sm dist-product"
                            data-placeholder="Select Product" required></select>
                    </td>
                    <td>
                        <input type="number" step="1" min="1" value="1" name="items[${rowIndex}][quantity]"
                            class="form-control form-control-sm text-end dist-qty" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                    </td>
                </tr>
            `;

            const $newRow = $(newRowHtml);
            $table.append($newRow);
            initProductSelect2($newRow.find('.dist-product'));

            rowIndex++;
            calculateTotal();
        });

        // Remove row
        $table.on('click', '.btnRemoveRow', function () {
            if ($table.find('tr').length === 1) {
                const $firstRowProduct = $table.find('tr:first .dist-product');
                $firstRowProduct.val(null).trigger('change');
                $table.find('tr:first .dist-qty').val(1);
            } else {
                $(this).closest('tr').remove();
            }
            calculateTotal();
        });

        // Quantity input
        $table.on('input', '.dist-qty', function () {
            calculateTotal();
        });

        function calculateTotal() {
            let total = 0;
            $('.dist-qty').each(function () {
                total += parseInt($(this).val() || 0, 10);
            });
            $('#distTotalText').text(total);
        }

        calculateTotal();

        // Auto initialize normal S2 components
        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto($modal.length ? $modal : undefined);
        }
    })();
</script>