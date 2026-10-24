{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Edit Distribution #{{ $productDistribution->id }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

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
                @foreach ($productDistribution->items as $i => $item)
                    <tr class="product-dist-item-row">
                        <td>
                            <select name="items[{{ $i }}][product_id]"
                                class="form-control form-control-sm js-s2-ajax dist-product"
                                data-url="{{ route('product.select2') }}" data-placeholder="Select Product" required>
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
                @endforeach
            </tbody>
        </table></div>

        <div class="d-flex justify-content-end mb-8">
            <button type="button" id="btnAddDistItem" class="btn btn-sm btn-outline-primary">+ Add Item</button>
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
        const $table = $('#productDistItemsTable tbody');
        let rowIndex = {{ $productDistribution->items->count() }};

        $('#btnAddDistItem').on('click', function () {
            const $first = $table.find('tr:first');

            $first.find('.select2-hidden-accessible').each(function () {
                $(this).select2('destroy');
            });

            const $row = $first.clone();

            $row.find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[\d+]/, `[${rowIndex}]`));
                if ($(this).hasClass('dist-qty')) {
                    $(this).val(1);
                } else if ($(this).is('select')) {
                    $(this).html('');
                } else {
                    $(this).val('');
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

        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto();
        }
    })();
</script>
