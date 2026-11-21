@php
    $customerType = $spotSale->teacher_id ? 'teacher' : ($spotSale->library_id ? 'library' : ($spotSale->student_id ? 'student' : ''));
    $firstItem = $spotSale->items->first();
    $currentCategory = $firstItem?->product?->category;
    $currentSubcategory = $firstItem?->product?->subcategory;
@endphp

{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Edit Spot Sale #{{ $spotSale->id }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

    /* Table & Select2 Styling Fixes (Eliminates dropdown width squeeze) */
    #spotSaleItemsTable {
        width: 100% !important;
        border-collapse: separate;
        border-spacing: 0;
    }

    #spotSaleItemsTable .col-product {
        width: 45%;
        min-width: 240px;
    }

    #spotSaleItemsTable .col-price {
        width: 16%;
        min-width: 90px;
    }

    #spotSaleItemsTable .col-qty {
        width: 16%;
        min-width: 90px;
    }

    #spotSaleItemsTable .col-total {
        width: 17%;
        min-width: 100px;
    }

    #spotSaleItemsTable .col-action {
        width: 6%;
        min-width: 44px;
    }

    #spotSaleItemsTable .select2-container {
        width: 100% !important;
        min-width: 100% !important;
        display: block !important;
        margin: 0 !important;
    }

    #spotSaleItemsTable .select2-container .selection {
        display: block !important;
        width: 100% !important;
    }

    #spotSaleItemsTable .select2-container--default .select2-selection--single {
        height: 38px !important;
        min-height: 38px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        background-color: #fff !important;
        display: flex !important;
        align-items: center !important;
        box-sizing: border-box !important;
    }

    #spotSaleItemsTable .select2-container--default .select2-selection--single .select2-selection__rendered {
        display: block !important;
        width: 100% !important;
        height: 36px !important;
        line-height: 36px !important;
        padding-left: 10px !important;
        padding-right: 28px !important;
        font-size: 0.875rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    #spotSaleItemsTable .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        position: absolute !important;
        right: 6px !important;
        top: 0 !important;
        width: 20px !important;
    }

    #spotSaleItemsTable .spot-qty {
        height: 38px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        font-size: 0.875rem !important;
    }
</style>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="spotSaleEditForm" action="{{ route('spot-sales.update', $spotSale->id) }}" method="POST" data-ajax="true">

        @csrf
        @method('PUT')

        {{-- ================= SALE INFO CARD ================= --}}
        <div class="card border mb-16">
            <div class="card-body p-16">

                <h6 class="mb-12 fw-semibold text-dark">Spot Sale Information</h6>

                <div class="row g-3">
                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Select User <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($spotSale->user)
                                <option value="{{ $spotSale->user->id }}" selected>{{ $spotSale->user->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Customer Type <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="customer_type" id="customerType" required>
                            <option value="">Select Type</option>
                            <option value="teacher" {{ $customerType === 'teacher' ? 'selected' : '' }}>Teacher</option>
                            <option value="library" {{ $customerType === 'library' ? 'selected' : '' }}>Library</option>
                            <option value="student" {{ $customerType === 'student' ? 'selected' : '' }}>Student</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-12" id="teacherWrap"
                        style="display:{{ $customerType === 'teacher' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Select Teacher <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="teacher_id" id="teacherSelect"
                            data-url="{{ route('teacher.teachers.select2') }}" data-placeholder="Select Teacher">
                            @if ($spotSale->teacher)
                                <option value="{{ $spotSale->teacher->id }}" selected>
                                    {{ $spotSale->teacher->teacher_name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12" id="libraryWrap"
                        style="display:{{ $customerType === 'library' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Select Library <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="library_id" id="librarySelect"
                            data-url="{{ route('library.libraries.select2') }}" data-placeholder="Select Library">
                            @if ($spotSale->library)
                                <option value="{{ $spotSale->library->id }}" selected>
                                    {{ $spotSale->library->library_name ?: $spotSale->library->name }}</option>
                            @endif
                        </select>
                    </div>

                    {{-- STUDENT FIELDS --}}
                    <div class="col-md-12" id="studentWrap"
                        style="display:{{ $customerType === 'student' ? 'block' : 'none' }}">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-sm mb-8">Student Name <span class="text-danger">*</span></label>
                                <input type="text" name="student_name" id="studentName"
                                    class="form-control form-control-sm"
                                    value="{{ optional($spotSale->student)->student_name }}" maxlength="255">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-sm mb-8">Class <span class="text-danger">*</span></label>
                                <select name="course_class_id" id="courseClassSelect"
                                    class="form-control form-control-sm">
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ optional($spotSale->student)->course_class_id == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-sm mb-8">Institution <span class="text-danger">*</span></label>
                                <select name="institution_id" id="institutionSelect"
                                    class="form-control form-control-sm js-s2-ajax"
                                    data-url="{{ route('institution.institutions.select2') }}"
                                    data-placeholder="Select Institution">
                                    @if(optional($spotSale->student)->institution)
                                        <option value="{{ $spotSale->student->institution->id }}" selected>
                                            {{ $spotSale->student->institution->institution_name }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            value="{{ $spotSale->note }}" placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- ================= PRODUCT FILTER (CATEGORY & SUBCATEGORY) CARD ================= --}}
        <div class="card border mb-16">
            <div class="card-body p-16">
                <h6 class="text-sm fw-bold mb-12 text-dark">Product Filter (Category & Subcategory)</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-sm mb-6">Category <span class="text-danger">*</span></label>
                        <select id="spotSaleCategorySelect" class="form-control radius-8 js-s2-ajax"
                            data-url="{{ route('category.cat.select2') }}"
                            data-placeholder="Select Category">
                            @if ($currentCategory)
                                <option value="{{ $currentCategory->id }}" selected>{{ $currentCategory->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-sm mb-6">Subcategory <span class="text-danger">*</span></label>
                        <select id="spotSaleSubcategorySelect" class="form-control radius-8"
                            data-placeholder="Select Subcategory" {{ !$currentCategory ? 'disabled' : '' }}>
                            @if ($currentSubcategory)
                                <option value="{{ $currentSubcategory->id }}" selected>{{ $currentSubcategory->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SALE ITEMS SECTION ================= --}}
        <div class="d-flex align-items-center justify-content-between mb-8">
            <label class="form-label text-sm mb-0 fw-semibold text-dark">Sale Items <span class="text-danger">*</span></label>
            <small id="spotSaleSelectionHint" class="{{ $currentSubcategory ? 'text-success' : 'text-muted' }}" style="font-size: 11px;">
                {{ $currentSubcategory ? 'Showing products for selected subcategory' : 'Please select a Category and Subcategory above to enable products' }}
            </small>
        </div>

        <div class="table-responsive mb-12">
            <table class="table table-sm align-middle" id="spotSaleItemsTable">
                <thead class="table-light">
                    <tr>
                        <th class="col-product">Product</th>
                        <th class="text-end col-price">Price</th>
                        <th class="text-end col-qty">Qty</th>
                        <th class="text-end col-total">Total</th>
                        <th class="text-center col-action"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($spotSale->items as $i => $item)
                        <tr class="spot-sale-item-row">
                            <td class="col-product">
                                <select name="items[{{ $i }}][product_id]"
                                    class="form-control form-control-sm spot-product" required>
                                    @if ($item->product)
                                        <option value="{{ $item->product->id }}" selected>{{ $item->product->name }}</option>
                                    @endif
                                </select>
                            </td>
                            <td class="text-end col-price">
                                <span class="spot-price-text fw-medium">{{ number_format((float) $item->price, 2, '.', '') }}</span>
                            </td>
                            <td class="col-qty">
                                <input type="number" step="1" min="1"
                                    name="items[{{ $i }}][quantity]"
                                    class="form-control form-control-sm text-end spot-qty"
                                    value="{{ $item->quantity }}" required>
                            </td>
                            <td class="text-end col-total">
                                <span class="spot-line-total-text fw-semibold">{{ number_format((float) $item->total, 2, '.', '') }}</span>
                            </td>
                            <td class="text-center col-action">
                                <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow"
                                    {{ count($spotSale->items) <= 1 ? 'disabled' : '' }}>
                                    ✕
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mb-16">
            <button type="button" id="btnAddSpotSaleItem" class="btn btn-sm btn-outline-primary"
                {{ !$currentSubcategory ? 'disabled' : '' }}>
                + Add Item
            </button>
        </div>

        {{-- ================= SUMMARY CARD ================= --}}
        <div class="card border mb-16">
            <div class="card-body p-16 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark">Grand Total</h6>
                <h5 class="mb-0 fw-bold text-primary">
                    ৳ <span id="spotSaleTotalText">{{ number_format((float) $spotSale->total, 2, '.', '') }}</span>
                </h5>
            </div>
        </div>

        {{-- ================= ACTION BUTTONS ================= --}}
        <input type="hidden" name="status" id="spotSaleStatus" value="">

        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary px-20" data-bs-dismiss="modal">
                Cancel
            </button>

            @if ($spotSale->status === 'pending')
                <button type="button" class="btn btn-sm btn-outline-warning px-20" id="btnSavePending">
                    Update Pending
                </button>

                <button type="button" class="btn btn-sm btn-success px-24" id="btnApproveSale">
                    Approve Sale
                </button>
            @else
                <span class="badge bg-success px-16 py-10 radius-4">Approved</span>
            @endif
        </div>

    </form>
</div>

{{-- ================= JS ================= --}}
<script>
    window.SpotSaleIndex = window.SpotSaleIndex || {
        onSaved: function(res) {
            if ($.fn.DataTable) {
                $('.AjaxDataTable').DataTable().ajax.reload(null, false);
            }
            $('.modal').modal('hide');
        },
        onLoad: function() {}
    };

    (function() {
        const $modal = $('#AjaxModal');
        const PRODUCT_PRICE_URL = "{{ route('spot-sales.productPrice', ':id') }}";
        const subcategoryUrl = "{{ route('subcategory.select2') }}";
        const productUrl = "{{ route('product.select2') }}";

        const $table = $('#spotSaleItemsTable tbody');
        const $categorySelect = $('#spotSaleCategorySelect');
        const $subcategorySelect = $('#spotSaleSubcategorySelect');
        const $btnAddItem = $('#btnAddSpotSaleItem');
        const $hint = $('#spotSaleSelectionHint');
        let rowIndex = {{ count($spotSale->items) }};

        function parentModal() {
            return $modal.length ? $modal : $(document.body);
        }

        /* ================= CUSTOMER TOGGLE ================= */
        function toggleCustomer() {
            const type = $('#customerType').val();
            $('#teacherWrap').toggle(type === 'teacher');
            $('#libraryWrap').toggle(type === 'library');
            $('#studentWrap').toggle(type === 'student');

            $('#teacherSelect').prop('required', type === 'teacher');
            $('#librarySelect').prop('required', type === 'library');

            $('#studentName').prop('required', type === 'student');
            $('#courseClassSelect').prop('required', type === 'student');
            $('#institutionSelect').prop('required', type === 'student');

            if (type !== 'teacher') $('#teacherSelect').val(null).trigger('change');
            if (type !== 'library') $('#librarySelect').val(null).trigger('change');
            if (type !== 'student') {
                $('#studentName').val('');
                $('#courseClassSelect').val('');
                $('#institutionSelect').val(null).trigger('change');
            }
        }

        $('#customerType').on('change', toggleCustomer);

        /* ================= SUBCATEGORY SELECT2 ================= */
        function initSubcategorySelect2() {
            if ($subcategorySelect.hasClass('select2-hidden-accessible')) {
                $subcategorySelect.select2('destroy');
            }

            $subcategorySelect.empty().append(new Option('', '', true, true));
            $subcategorySelect.prop('disabled', false).select2({
                dropdownParent: parentModal(),
                width: '100%',
                placeholder: 'Select Subcategory',
                allowClear: true,
                ajax: {
                    url: subcategoryUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            category_id: $categorySelect.val(),
                            type: $categorySelect.val()
                        };
                    },
                    processResults: function(data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    }
                }
            });
        }

        /* ================= PRODUCT SELECT2 ================= */
        function initProductSelect2($select) {
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            const currentSubcat = $subcategorySelect.val();
            if (!currentSubcat) {
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
                    data: function(params) {
                        return {
                            q: params.term || '',
                            category_id: $categorySelect.val(),
                            subcategory_id: $subcategorySelect.val()
                        };
                    },
                    processResults: function(data) {
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
                $table.find('.spot-product').each(function() {
                    initProductSelect2($(this));
                });
            } else {
                $btnAddItem.prop('disabled', true);
                $hint.text('Please select a Category and Subcategory above to enable products').removeClass('text-success').addClass('text-muted');
                $table.find('.spot-product').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).empty().prop('disabled', true);
                });
                $table.find('.spot-price-text').text('0.00');
                $table.find('.spot-line-total-text').text('0.00');
                calculateTotal();
            }
        }

        /* ================= CATEGORY CASCADE ================= */
        $categorySelect.on('change', function() {
            const catId = $(this).val();

            if ($subcategorySelect.hasClass('select2-hidden-accessible')) {
                $subcategorySelect.select2('destroy');
            }
            $subcategorySelect.empty().prop('disabled', true);

            // Reset table to single row
            $table.find('tr:gt(0)').remove();
            const $firstRowProduct = $table.find('tr:first .spot-product');
            if ($firstRowProduct.hasClass('select2-hidden-accessible')) {
                $firstRowProduct.select2('destroy');
            }
            $firstRowProduct.empty().prop('disabled', true);
            $table.find('tr:first .spot-price-text').text('0.00');
            $table.find('tr:first .spot-qty').val(1);
            $table.find('tr:first .spot-line-total-text').text('0.00');
            $table.find('tr:first .btnRemoveRow').prop('disabled', true);
            calculateTotal();

            if (catId) {
                initSubcategorySelect2();
            }
            updateAllProductSelects();
        });

        /* ================= SUBCATEGORY CASCADE ================= */
        $subcategorySelect.on('change', function() {
            // Reset table to single row when subcategory changes
            $table.find('tr:gt(0)').remove();
            const $firstRowProduct = $table.find('tr:first .spot-product');
            if ($firstRowProduct.hasClass('select2-hidden-accessible')) {
                $firstRowProduct.select2('destroy');
            }
            $firstRowProduct.empty();
            $table.find('tr:first .spot-price-text').text('0.00');
            $table.find('tr:first .spot-qty').val(1);
            $table.find('tr:first .spot-line-total-text').text('0.00');
            $table.find('tr:first .btnRemoveRow').prop('disabled', true);
            calculateTotal();

            updateAllProductSelects();
        });

        /* ================= INITIALIZE FOR EDIT ================= */
        if ($categorySelect.val()) {
            if (!$subcategorySelect.hasClass('select2-hidden-accessible')) {
                $subcategorySelect.prop('disabled', false).select2({
                    dropdownParent: parentModal(),
                    width: '100%',
                    placeholder: 'Select Subcategory',
                    allowClear: true,
                    ajax: {
                        url: subcategoryUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || '',
                                category_id: $categorySelect.val(),
                                type: $categorySelect.val()
                            };
                        },
                        processResults: function(data) {
                            return { results: Array.isArray(data?.results) ? data.results : [] };
                        }
                    }
                });
            }
        }

        $table.find('.spot-product').each(function() {
            initProductSelect2($(this));
        });

        /* ================= ADD ROW ================= */
        $btnAddItem.on('click', function() {
            const subcat = $subcategorySelect.val();
            if (!subcat) return;

            const newRowHtml = `
                <tr class="spot-sale-item-row">
                    <td class="col-product">
                        <select name="items[${rowIndex}][product_id]"
                            class="form-control form-control-sm spot-product" required>
                        </select>
                    </td>
                    <td class="text-end col-price">
                        <span class="spot-price-text fw-medium">0.00</span>
                    </td>
                    <td class="col-qty">
                        <input type="number" step="1" min="1" value="1"
                            name="items[${rowIndex}][quantity]" class="form-control form-control-sm text-end spot-qty"
                            required>
                    </td>
                    <td class="text-end col-total">
                        <span class="spot-line-total-text fw-semibold">0.00</span>
                    </td>
                    <td class="text-center col-action">
                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">
                            ✕
                        </button>
                    </td>
                </tr>
            `;

            const $newRow = $(newRowHtml);
            $table.append($newRow);
            initProductSelect2($newRow.find('.spot-product'));
            $table.find('.btnRemoveRow').prop('disabled', false);
            rowIndex++;
        });

        /* ================= REMOVE ROW ================= */
        $table.on('click', '.btnRemoveRow', function() {
            if ($table.find('tr').length <= 1) return;
            $(this).closest('tr').remove();
            if ($table.find('tr').length === 1) {
                $table.find('tr:first .btnRemoveRow').prop('disabled', true);
            }
            calculateTotal();
        });

        /* ================= PRODUCT PRICE FETCH ================= */
        $table.on('change', '.spot-product', function() {
            const $row = $(this).closest('tr');
            const productId = $(this).val();
            if (!productId) {
                $row.find('.spot-price-text').text('0.00');
                calcRow($row);
                return;
            }
            $.get(PRODUCT_PRICE_URL.replace(':id', productId), function(res) {
                $row.find('.spot-price-text').text(parseFloat(res.price || 0).toFixed(2));
                calcRow($row);
            });
        });

        $table.on('input', '.spot-qty', function() {
            calcRow($(this).closest('tr'));
        });

        function calcRow($row) {
            const price = parseFloat($row.find('.spot-price-text').text() || 0);
            const qty = parseFloat($row.find('.spot-qty').val() || 0);
            $row.find('.spot-line-total-text').text((price * qty).toFixed(2));
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            $('.spot-line-total-text').each(function() {
                total += parseFloat($(this).text() || 0);
            });
            $('#spotSaleTotalText').text(total.toFixed(2));
        }

        /* ================= SUBMIT HANDLERS ================= */
        $('#btnSavePending').on('click', function() {
            if (!$('#customerType').val()) {
                Swal && Swal.fire({
                    icon: 'warning',
                    title: 'Select customer type'
                });
                return;
            }
            $('#spotSaleStatus').val('pending');
            $('#spotSaleEditForm').submit();
        });

        $('#btnApproveSale').on('click', function() {
            if (!$('#customerType').val()) {
                Swal && Swal.fire({
                    icon: 'warning',
                    title: 'Select customer type'
                });
                return;
            }
            $('#spotSaleStatus').val('approved');
            $('#spotSaleEditForm').submit();
        });

        // Initialize any general Select2 helper inputs in modal header/user
        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto($modal);
        }
    })();
</script>