{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Edit Spot Sale #{{ $spotSale->id }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="spotSaleEditForm" action="{{ route('spot-sales.update', $spotSale->id) }}" method="POST" data-ajax="true">

        @csrf
        @method('PUT')

        @php $customerType = $spotSale->teacher_id ? 'teacher' : ($spotSale->library_id ? 'library' : ''); @endphp

        {{-- ================= SALE INFO ================= --}}
        <div class="card mb-16">
            <div class="card-body">

                <h6 class="mb-12 fw-semibold">Spot Sale Information</h6>

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
                        </select>
                    </div>

                    <div class="col-md-6 mb-12" id="teacherWrap" style="display:{{ $customerType === 'teacher' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Select Teacher <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="teacher_id" id="teacherSelect"
                            data-url="{{ route('teacher.teachers.institutions.select2') }}"
                            data-placeholder="Select Teacher">
                            @if ($spotSale->teacher)
                                <option value="{{ $spotSale->teacher->id }}" selected>{{ $spotSale->teacher->teacher_name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-12" id="libraryWrap" style="display:{{ $customerType === 'library' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Select Library <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="library_id" id="librarySelect"
                            data-url="{{ route('library.libraries.select2') }}"
                            data-placeholder="Select Library">
                            @if ($spotSale->library)
                                <option value="{{ $spotSale->library->id }}" selected>{{ $spotSale->library->library_name ?: $spotSale->library->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-sm">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            value="{{ $spotSale->note }}" placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- ================= SALE ITEMS ================= --}}
        <table class="table table-sm align-middle" id="spotSaleItemsTable">
            <thead class="table-light">
                <tr>
                    <th style="width:38%">Product</th>
                    <th class="text-end" style="width:110px">Price</th>
                    <th class="text-end" style="width:110px">Qty</th>
                    <th class="text-end" style="width:120px">Total</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($spotSale->items as $i => $item)
                    <tr class="spot-sale-item-row">
                        <td>
                            <select name="items[{{ $i }}][product_id]"
                                class="form-control form-control-sm js-s2-ajax spot-product"
                                data-url="{{ route('product.select2') }}" required>
                                @if ($item->product)
                                    <option value="{{ $item->product->id }}" selected>{{ $item->product->name }}</option>
                                @endif
                            </select>
                        </td>
                        <td class="text-end">
                            <span class="spot-price-text">{{ number_format((float) $item->price, 2) }}</span>
                        </td>
                        <td>
                            <input type="number" step="1" min="1" name="items[{{ $i }}][quantity]"
                                class="form-control form-control-sm text-end spot-qty"
                                value="{{ $item->quantity }}" required>
                        </td>
                        <td class="text-end">
                            <span class="spot-line-total-text">{{ number_format((float) $item->total, 2) }}</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">
                                ✕
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-end mb-8">
            <button type="button" id="btnAddSpotSaleItem" class="btn btn-sm btn-outline-primary">
                + Add Item
            </button>
        </div>

        {{-- ================= SUMMARY ================= --}}
        <div class="card mb-16">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Grand Total</h6>
                <h5 class="mb-0 fw-bold">
                    <span id="spotSaleTotalText">{{ number_format((float) $spotSale->total, 2) }}</span>
                </h5>
            </div>
        </div>

        {{-- ================= ACTION ================= --}}
        <input type="hidden" name="status" id="spotSaleStatus" value="">

        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                Cancel
            </button>

            @if ($spotSale->status === 'pending')
                <button type="button" class="btn btn-sm btn-outline-warning" id="btnSavePending">
                    Update Pending
                </button>

                <button type="button" class="btn btn-sm btn-success" id="btnApproveSale">
                    Approve Sale
                </button>
            @else
                <span class="badge bg-success px-12 py-8">Approved</span>
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
        const PRODUCT_PRICE_URL = "{{ route('spot-sales.productPrice', ':id') }}";
        const $table = $('#spotSaleItemsTable tbody');
        let rowIndex = {{ $spotSale->items->count() }};

        function toggleCustomer() {
            const type = $('#customerType').val();
            $('#teacherWrap').toggle(type === 'teacher');
            $('#libraryWrap').toggle(type === 'library');
        }

        $('#customerType').on('change', toggleCustomer);

        /* ADD ROW */
        $('#btnAddSpotSaleItem').on('click', function() {
            const $first = $table.find('tr:first');

            $first.find('.select2-hidden-accessible').each(function() {
                $(this).select2('destroy');
            });

            const $row = $first.clone();

            $row.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[\d+]/, `[${rowIndex}]`));
                if ($(this).hasClass('spot-qty')) {
                    $(this).val(1);
                } else if ($(this).is('select')) {
                    $(this).html('');
                } else {
                    $(this).val('');
                }
            });
            $row.find('.spot-price-text').text('0.00');
            $row.find('.spot-line-total-text').text('0.00');

            $table.append($row);
            if (window.S2 && typeof window.S2.auto === 'function') {
                window.S2.auto();
            }
            rowIndex++;
        });

        /* REMOVE ROW */
        $table.on('click', '.btnRemoveRow', function() {
            if ($table.find('tr').length === 1) return;
            $(this).closest('tr').remove();
            calculateTotal();
        });

        /* PRODUCT PRICE */
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

        $('#btnSavePending').on('click', function() {
            $('#spotSaleStatus').val('pending');
            $('#spotSaleEditForm').submit();
        });

        $('#btnApproveSale').on('click', function() {
            $('#spotSaleStatus').val('approved');
            $('#spotSaleEditForm').submit();
        });

        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto();
        }
    })();
</script>
