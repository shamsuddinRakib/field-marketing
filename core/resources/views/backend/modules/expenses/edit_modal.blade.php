{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Edit Expense</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="expenseEditForm" action="{{ route('expenses.update', $expense->id) }}" method="POST" enctype="multipart/form-data" data-ajax="true" data-branch-id="{{ current_branch_id() }}">

        @csrf
        @method('PUT')

        {{-- ================= EXPENSE INFO ================= --}}
        <div class="card mb-16">
            <div class="card-body">

                <h6 class="mb-12 fw-semibold">Expense Information</h6>

                <div class="row g-3">
                    <div class="col-md-6 mb-20">
                        <label class="form-label text-sm mb-8">Select <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="user"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}" data-placeholder="Select Marketing Representative">
                            @if ($expense->dailyVisit && $expense->dailyVisit->user)
                                <option value="{{ $expense->dailyVisit->user->id }}" selected>{{ $expense->dailyVisit->user->name }}</option>
                            @endif
                        </select>
                        <div class="invalid-feedback d-block user_id-error" style="display:none"></div>
                    </div>

                    <div class="col-md-6 mb-20">
                        <label class="form-label text-sm mb-8">Select Visit <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="daily_visit_id" id="visit"
                            data-url="{{ route('daily-visit.select2') }}" data-placeholder="Select Daily Visit">
                            @if ($expense->dailyVisit)
                                <option value="{{ $expense->dailyVisit->id }}" selected>
                                    {{ $expense->dailyVisit->teacher?->teacher_name ?? $expense->dailyVisit->library?->name ?? 'Daily Visit' }}
                                </option>
                            @endif
                        </select>
                        <div class="invalid-feedback d-block user_id-error" style="display:none"></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-sm">Expense Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm radius-8"
                            value="{{ $expense->name }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-sm">Expense Date <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control form-control-sm radius-8"
                            value="{{ ($expense->expense_date) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-sm">Attachment</label>
                        <input type="file" name="attachment" class="form-control form-control-sm radius-8 p-1"
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        @if ($expense->attachment)
                            <small class="d-block mt-8 text-muted">
                                Current file: <a href="{{ asset($expense->attachment) }}" target="_blank" class="text-primary">Open</a>
                            </small>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-sm">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm radius-8"
                            value="{{ $expense->description }}" placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- ================= EXPENSE ITEMS ================= --}}
        <table class="table table-sm align-middle" id="expenseItemsTable">
            <thead class="table-light">
                <tr>
                    <th style="width:30%">Category</th>
                    <th>Description</th>
                    <th class="text-end" style="width:140px">Amount</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>

                @foreach ($expense->items as $i => $item)
                    <tr class="expense-item-row">
                        <td>
                            <select name="items[{{ $i }}][expense_category_id]"
                                class="form-control form-control-sm js-s2-ajax"
                                data-url="{{ route('expenseCategories.select2') }}" required>
                                @if ($item->category)
                                    <option value="{{ $item->category->id }}" selected>
                                        {{ $item->category->name }}
                                    </option>
                                @endif
                            </select>
                        </td>

                        <td>
                            <input type="text" name="items[{{ $i }}][description]"
                                class="form-control form-control-sm" value="{{ $item->description }}">
                        </td>

                        <td>
                            <input type="number" step="0.01" min="0"
                                name="items[{{ $i }}][amount]"
                                class="form-control form-control-sm text-end expense-amount"
                                value="{{ $item->amount }}" required>
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
            <button type="button" id="btnAddExpenseItem" class="btn btn-sm btn-outline-primary">
                + Add Item
            </button>
        </div>

        {{-- ================= SUMMARY ================= --}}
        <div class="card mb-16">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Total Amount</h6>
                <h5 class="mb-0 fw-bold">
                    <span id="expenseTotalText">
                        {{ number_format($expense->total_amount, 2) }}
                    </span>
                </h5>
                <input type="hidden" name="total_amount" id="expenseTotalInput" value="{{ $expense->total_amount }}">
            </div>
        </div>

        {{-- ================= PAYMENT METHOD ================= --}}
        {{-- <div class="card mb-16">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label text-sm">Payment Method</label>
                        <select name="payment_type_id" class="form-control form-control-sm js-s2-ajax"
                            data-url="{{ route('paymentTypes.select2') }}">
                            @if ($expense->payment?->paymentType)
                                <option value="{{ $expense->payment->paymentType->id }}" selected>
                                    {{ $expense->payment->paymentType->name }}
                                </option>
                            @endif
                        </select>
                        <small class="text-muted">
                            Only payment medium (account handled automatically)
                        </small>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- ================= ACTION ================= --}}
        <input type="hidden" name="status" id="expenseStatus" value="">

        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                Cancel
            </button>

            @if ($expense->status === 'draft')
                <button type="button" class="btn btn-sm btn-outline-warning" id="btnSaveDraft">
                    Update Draft
                </button>

                <button type="button" class="btn btn-sm btn-success" id="btnPostExpense">
                    Post Expense
                </button>
            @else
                <span class="badge bg-success px-12 py-8">Posted</span>
            @endif
        </div>

    </form>
</div>

{{-- ================= JS ================= --}}
<script>
    window.EXPENSE_INVOICE_URL = "{{ route('expenses.invoice', ':id') }}";
    window.ExpenseIndex = {
        onSaved: function(res) {

            // 🔹 Table reload
            if ($.fn.DataTable) {
                $('.AjaxDataTable').DataTable().ajax.reload(null, false);
            }

            const expenseId = res?.data?.id || res?.id;

           
            // 🔹 Close modal
            $('.modal').modal('hide');
        }
    };

    $('#user').on('change', function() {
        const userId = $(this).val();
        const $visitSelect = $('#visit');

        if (userId) {
            const newUrl = "{{ route('daily-visit.select2') }}?user_id=" + encodeURIComponent(userId);
            window.S2.ajax($visitSelect, newUrl);
        }
    });

    (function() {

        const $table = $('#expenseItemsTable tbody');
        let rowIndex = {{ $expense->items->count() }};

        /* ADD ROW */
        $('#btnAddExpenseItem').on('click', function() {

            const $first = $table.find('tr:first');

            $first.find('.select2-hidden-accessible').select2('destroy');

            const $row = $first.clone();

            $row.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[\d+]/, `[${rowIndex}]`)).val('');
            });

            $table.append($row);
            window.S2.auto();
            rowIndex++;
        });

        /* REMOVE ROW */
        $table.on('click', '.btnRemoveRow', function() {
            if ($table.find('tr').length === 1) return;
            $(this).closest('tr').remove();
            calcTotal();
        });

        /* TOTAL */
        $table.on('input', '.expense-amount', calcTotal);

        function calcTotal() {
            let total = 0;
            $('.expense-amount').each(function() {
                total += parseFloat($(this).val() || 0);
            });
            $('#expenseTotalText').text(total.toFixed(2));
            $('#expenseTotalInput').val(total.toFixed(2));
        }

        /* STATUS BUTTONS */
        $('#btnSaveDraft').on('click', function() {
           
            $('#expenseStatus').val('draft');
            $('#expenseEditForm').submit();
        });

        $('#btnPostExpense').on('click', function() {
            // Check if branch is selected
            const branchId = parseInt($('#expenseEditForm').data('branch-id') || 0);
            if (!branchId || branchId === 0) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Branch Not Selected',
                        text: 'Please select a branch first before editing expense',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#FFC107'
                    });
                } else {
                    alert('Please select a branch first before editing expense');
                }
                return false;
            }
            $('#expenseStatus').val('posted');
            $('#expenseEditForm').submit();
        });

        /* INIT */
        window.S2.auto();

    })();
</script>
