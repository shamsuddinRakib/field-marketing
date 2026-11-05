{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <h5 class="modal-title fw-semibold">Edit Assignment #{{ $assignSpecimen->id }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<style>
    @media (min-width: 992px) {
        #AjaxModal .modal-dialog.modal-lg {
            max-width: 860px;
        }
    }
</style>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    <form id="assignSpecimenEditForm" action="{{ route('assign-specimen.update', $assignSpecimen->id) }}" method="POST" data-ajax="true">

        @csrf
        @method('PUT')

        @php
            $customerType = $assignSpecimen->teacher_id ? 'teacher' : ($assignSpecimen->library_id ? 'library' : '');
            $teacherPhone = $assignSpecimen->teacher?->phone ?? '';
            $teacherName = $assignSpecimen->teacher?->teacher_name ?? '';
            $institutionName = $assignSpecimen->teacher?->institution?->institution_name ?? '';
        @endphp

        {{-- ================= ASSIGN INFO ================= --}}
        <div class="card mb-16">
            <div class="card-body">

                <h6 class="mb-12 fw-semibold">Assignment Information</h6>

                <div class="row g-3">
                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative" required>
                            @if ($assignSpecimen->user)
                                <option value="{{ $assignSpecimen->user->id }}" selected>{{ $assignSpecimen->user->name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-4 mb-12">
                        <label class="form-label text-sm mb-8">Recipient Type <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="customer_type" id="specimenCustomerType" required>
                            <option value="">Select Type</option>
                            <option value="teacher" {{ $customerType === 'teacher' ? 'selected' : '' }}>Teacher</option>
                            <option value="library" {{ $customerType === 'library' ? 'selected' : '' }}>Library</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-12" id="specimenTeacherPhoneWrap" style="display:{{ $customerType === 'teacher' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Select Phone Number <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="teacher_id" id="specimenTeacherPhoneSelect"
                            data-url="{{ route('assign-specimen.teachersByPhone.select2') }}"
                            data-placeholder="Search by Phone Number">
                            @if ($assignSpecimen->teacher)
                                <option value="{{ $assignSpecimen->teacher->id }}" selected>
                                    {{ $teacherPhone ?: '—' }}{{ $teacherName ? " ({$teacherName})" : '' }}
                                </option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-4 mb-12" id="specimenLibraryWrap" style="display:{{ $customerType === 'library' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Select Library <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm js-s2-ajax" name="library_id" id="specimenLibrarySelect"
                            data-url="{{ route('library.libraries.select2') }}"
                            data-placeholder="Select Library">
                            @if ($assignSpecimen->library)
                                <option value="{{ $assignSpecimen->library->id }}" selected>{{ $assignSpecimen->library->library_name }}</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-4 mb-12" id="specimenTeacherNameWrap" style="display:{{ $customerType === 'teacher' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Teacher Name</label>
                        <input type="text" id="specimenTeacherName" class="form-control form-control-sm radius-8 bg-light"
                            value="{{ $teacherName }}" placeholder="Teacher Name" readonly>
                    </div>

                    <div class="col-md-4 mb-12" id="specimenInstitutionNameWrap" style="display:{{ $customerType === 'teacher' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Institution Name</label>
                        <input type="text" id="specimenInstitutionName" class="form-control form-control-sm radius-8 bg-light"
                            value="{{ $institutionName }}" placeholder="Institution Name" readonly>
                    </div>

                    <div class="col-md-4 mb-12" id="specimenInstTypeWrap" style="display:{{ $customerType === 'teacher' ? 'block' : 'none' }}">
                        <label class="form-label text-sm mb-8">Type <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="assign_inst_type" id="specimenAssignInstType">
                            <option value="">Select Type</option>
                            <option value="school" {{ $assignSpecimen->assign_inst_type === 'school' ? 'selected' : '' }}>School</option>
                            <option value="college" {{ $assignSpecimen->assign_inst_type === 'college' ? 'selected' : '' }}>College</option>
                            <option value="coaching" {{ $assignSpecimen->assign_inst_type === 'coaching' ? 'selected' : '' }}>Coaching</option>
                            <option value="batch" {{ $assignSpecimen->assign_inst_type === 'batch' ? 'selected' : '' }}>Batch</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-sm">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm radius-8"
                            value="{{ $assignSpecimen->note }}" placeholder="Optional note">
                    </div>
                </div>

            </div>
        </div>

        {{-- ================= SPECIMEN ITEMS ================= --}}
        <div class="table-responsive"><table class="table table-sm align-middle items-table-fixed" id="specimenItemsTable">
            <thead class="table-light">
                <tr>
                    <th style="width:75%">Product</th>
                    <th class="text-end" style="width:140px">Qty</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assignSpecimen->items as $i => $item)
                    <tr class="specimen-item-row">
                        <td>
                            <select name="items[{{ $i }}][product_id]"
                                class="form-control form-control-sm js-s2-ajax specimen-product"
                                data-url="{{ route('product.select2') }}" data-placeholder="Select Product" required>
                                @if ($item->product)
                                    <option value="{{ $item->product->id }}" selected>{{ $item->product->name }}</option>
                                @endif
                            </select>
                        </td>
                        <td>
                            <input type="number" step="1" min="1" name="items[{{ $i }}][quantity]"
                                class="form-control form-control-sm text-end specimen-qty"
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
            <button type="button" id="btnAddSpecimenItem" class="btn btn-sm btn-outline-primary">+ Add Item</button>
        </div>

        {{-- ================= SUMMARY ================= --}}
        <div class="card mb-16 bg-light border-0">
            <div class="card-body py-12 px-20 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-sm fw-semibold">Total Quantity</h6>
                <h5 class="mb-0 fw-bold"><span id="specimenTotalText">{{ $assignSpecimen->items->sum('quantity') }}</span></h5>
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
    window.AssignSpecimenIndex = window.AssignSpecimenIndex || { onLoad: function () {} };

    (function () {
        const $table = $('#specimenItemsTable tbody');
        let rowIndex = {{ $assignSpecimen->items->count() }};

        function toggleRecipient() {
            const type = $('#specimenCustomerType').val();
            const isTeacher = type === 'teacher';
            const isLibrary = type === 'library';

            $('#specimenTeacherPhoneWrap').toggle(isTeacher);
            $('#specimenTeacherNameWrap').toggle(isTeacher);
            $('#specimenInstitutionNameWrap').toggle(isTeacher);
            $('#specimenInstTypeWrap').toggle(isTeacher);
            $('#specimenLibraryWrap').toggle(isLibrary);
        }

        $('#specimenCustomerType').on('change', toggleRecipient);

        $('#specimenTeacherPhoneSelect').on('select2:select', function (e) {
            const data = e.params.data || {};
            $('#specimenTeacherName').val(data.teacher_name || '');
            $('#specimenInstitutionName').val(data.institution_name || '');
        });

        $('#specimenTeacherPhoneSelect').on('select2:clear', function () {
            $('#specimenTeacherName').val('');
            $('#specimenInstitutionName').val('');
        });

        $('#specimenTeacherPhoneSelect').on('change', function () {
            if (!$(this).val()) {
                $('#specimenTeacherName').val('');
                $('#specimenInstitutionName').val('');
            }
        });

        $('#btnAddSpecimenItem').on('click', function () {
            const $first = $table.find('tr:first');

            $first.find('.select2-hidden-accessible').each(function () {
                $(this).select2('destroy');
            });

            const $row = $first.clone();

            $row.find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[\d+]/, `[${rowIndex}]`));
                if ($(this).hasClass('specimen-qty')) {
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

        $table.on('input', '.specimen-qty', function () {
            calculateTotal();
        });

        function calculateTotal() {
            let total = 0;
            $('.specimen-qty').each(function () {
                total += parseInt($(this).val() || 0, 10);
            });
            $('#specimenTotalText').text(total);
        }

        if (window.S2 && typeof window.S2.auto === 'function') {
            window.S2.auto();
        }
    })();
</script>
