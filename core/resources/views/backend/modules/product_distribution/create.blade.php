{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Assign Products</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="productDistCreateForm" action="{{ route('product-distribution.store') }}" method="POST" data-ajax="true">

        @csrf

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
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- ================= DISTRIBUTION ITEMS ================= --}}
        <div class="table-responsive"><table class="table table-sm align-middle items-table-fixed" id="productDistItemsTable">
            <thead class="table-light">
                <tr>
                    <th style="width:55%">Product</th>
                    <th class="text-end" style="width:140px">Qty</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
                <tr class="product-dist-item-row">
                    <td>
                        <select name="items[0][product_id]" class="form-control form-control-sm js-s2-ajax dist-product"
                            data-url="{{ route('product.select2') }}" data-placeholder="Select Product" required></select>
                    </td>
                    <td>
                        <input type="number" step="1" min="1" value="1" name="items[0][quantity]"
                            class="form-control form-control-sm text-end dist-qty" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">✕</button>
                    </td>
                </tr>
            </tbody>
        </table></div>
        <div class="d-flex justify-content-end mb-8">
            <button type="button" id="btnAddDistItem" class="btn btn-sm btn-outline-primary">+ Add Item</button>
        </div>

        {{-- ================= SUMMARY ================= --}}
        <div class="card mb-16">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Total Quantity</h6>
                <h5 class="mb-0 fw-bold"><span id="distTotalText">1</span></h5>
            </div>
        </div>

        {{-- ================= ACTION ================= --}}
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-primary">Save as Pending</button>
        </div>

    </form>
</div>

{{-- ================= JS HOOKS ================= --}}
<script>
    window.ProductDistIndex = window.ProductDistIndex || { onLoad: function () {} };

    (function () {
        const $table = $('#productDistItemsTable tbody');
        let rowIndex = 1;

        $('#btnAddDistItem').on('click', function () {
            const $firstRow = $table.find('tr:first');

            $firstRow.find('.select2-hidden-accessible').each(function () {
                $(this).select2('destroy');
            });

            const $row = $firstRow.clone();

            $row.find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[\d+]/, `[${rowIndex}]`));
                if ($(this).hasClass('dist-qty')) {
                    $(this).val(1);
                } else {
                    $(this).val('').html('');
                }
            });

            $table.append($row);

            if (window.S2 && typeof window.S2.auto === 'function') {
                window.S2.auto();
            }

            rowIndex++;
            calculateTotal();
        });

        $table.on('click', '.btnRemoveRow', function () {
            if ($table.find('tr').length === 1) return;
            $(this).closest('tr').remove();
            calculateTotal();
        });

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

        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto();
        }
    })();
</script>
