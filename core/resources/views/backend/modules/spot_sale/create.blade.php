{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Add Spot Sale</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="spotSaleCreateForm" action="{{ route('spot-sales.store') }}" method="POST" data-ajax="true">

        @csrf

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
                        </select>
                    </div>

                    <div class="col-md-6 mb-12">
                        <label class="form-label text-sm mb-8">Customer Type <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="customer_type" id="customerType" required>
                            <option value="">Select Type</option>
                            <option value="teacher">Teacher</option>
                            <option value="library">Library</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-12" id="teacherWrap" style="display:none">
                        <label class="form-label text-sm mb-8">Select Teacher <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="teacher_id" id="teacherSelect"
                            data-url="{{ route('teacher.teachers.institutions.select2') }}"
                            data-placeholder="Select Teacher">
                        </select>
                    </div>

                    <div class="col-md-6 mb-12" id="libraryWrap" style="display:none">
                        <label class="form-label text-sm mb-8">Select Library <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="library_id" id="librarySelect"
                            data-url="{{ route('library.libraries.select2') }}"
                            data-placeholder="Select Library">
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-sm">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            placeholder="Optional note">
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
                <tr class="spot-sale-item-row">
                    <td>
                        <select name="items[0][product_id]" class="form-control form-control-sm js-s2-ajax spot-product"
                            data-url="{{ route('product.select2') }}" required></select>
                    </td>
                    <td class="text-end">
                        <span class="spot-price-text">0.00</span>
                    </td>
                    <td>
                        <input type="number" step="1" min="1" value="1" name="items[0][quantity]"
                            class="form-control form-control-sm text-end spot-qty" required>
                    </td>
                    <td class="text-end">
                        <span class="spot-line-total-text">0.00</span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveRow">
                            ✕
                        </button>
                    </td>
                </tr>
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
                    <span id="spotSaleTotalText">0.00</span>
                </h5>
            </div>
        </div>

        {{-- ================= ACTION ================= --}}
        <input type="hidden" name="status" id="spotSaleStatus" value="">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                Cancel
            </button>

            <button type="button" class="btn btn-sm btn-outline-warning" id="btnSavePending">
                Save as Pending
            </button>

            <button type="button" class="btn btn-sm btn-success" id="btnApproveSale">
                Approve Sale
            </button>
        </div>

    </form>
</div>

{{-- ================= JS HOOKS ================= --}}
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
        let rowIndex = 1;

        function toggleCustomer() {
            const type = $('#customerType').val();
            $('#teacherWrap').toggle(type === 'teacher');
            $('#libraryWrap').toggle(type === 'library');
            if (type !== 'teacher') $('#teacherSelect').val(null).trigger('change');
            if (type !== 'library') $('#librarySelect').val(null).trigger('change');
        }

        $('#customerType').on('change', toggleCustomer);

        /* ================= ADD ROW ================= */
        $('#btnAddSpotSaleItem').on('click', function() {
            const $firstRow = $table.find('tr:first');

            $firstRow.find('.select2-hidden-accessible').each(function() {
                $(this).select2('destroy');
            });

            const $row = $firstRow.clone();

            $row.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (!name) return;
                const newName = name.replace(/\[\d+]/, `[${rowIndex}]`);
                $(this).attr('name', newName);
                if ($(this).hasClass('spot-qty')) {
                    $(this).val(1);
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

        /* ================= REMOVE ROW ================= */
        $table.on('click', '.btnRemoveRow', function() {
            if ($table.find('tr').length === 1) return;
            $(this).closest('tr').remove();
            calculateTotal();
        });

        /* ================= PRODUCT PRICE ================= */
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
            if (!$('#customerType').val()) {
                Swal && Swal.fire({ icon: 'warning', title: 'Select customer type' });
                return;
            }
            $('#spotSaleStatus').val('pending');
            $('#spotSaleCreateForm').submit();
        });

        $('#btnApproveSale').on('click', function() {
            if (!$('#customerType').val()) {
                Swal && Swal.fire({ icon: 'warning', title: 'Select customer type' });
                return;
            }
            $('#spotSaleStatus').val('approved');
            $('#spotSaleCreateForm').submit();
        });

        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto();
        }
    })();
</script>
