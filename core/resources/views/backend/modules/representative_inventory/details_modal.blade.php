{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header py-16 px-24">
    <div>
        <h5 class="modal-title fw-semibold mb-0">Product Inventory Breakdown</h5>
        <span class="text-secondary text-sm">
            Representative: <strong>{{ optional($representative->user)->name ?? ('#' . $representative->user_id) }}</strong>
            @if ($representative->employee_id)
                <span class="badge bg-light text-secondary text-xs">ID: {{ $representative->employee_id }}</span>
            @endif
        </span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body px-24 py-16">

    {{-- Representative Quick Info --}}
    <div class="card mb-16 bg-light border shadow-none radius-8">
        <div class="card-body p-12">
            <div class="row g-2 text-sm">
                <div class="col-sm-3">
                    <span class="text-muted d-block text-xs">Phone</span>
                    <strong>{{ optional($representative->user)->phone ?? '—' }}</strong>
                </div>
                <div class="col-sm-3">
                    <span class="text-muted d-block text-xs">Designation</span>
                    <strong>{{ optional($representative->mrDesignation)->designation_name ?? '—' }}</strong>
                </div>
                <div class="col-sm-3">
                    <span class="text-muted d-block text-xs">Branch</span>
                    <strong><iconify-icon icon="ri-store-3-line" class="text-secondary me-1"></iconify-icon>{{ optional($representative->branch)->name ?? '—' }}</strong>
                </div>
                <div class="col-sm-3">
                    <span class="text-muted d-block text-xs">Assigned Upazilas</span>
                    @if ($representative->territories && $representative->territories->isNotEmpty())
                        @foreach ($representative->territories as $t)
                            @if ($t->upazila)
                                <span class="badge bg-white text-dark border text-xs me-1 mb-1">{{ $t->upazila->upazila_name }}</span>
                            @endif
                        @endforeach
                    @else
                        <strong>{{ optional($representative->upazila)->upazila_name ?? '—' }}</strong>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Product Breakdown Table --}}
    <div class="table-responsive">
        <table class="table table-sm bordered-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px">#</th>
                    <th>Product Name</th>
                    <th class="text-end" style="width:100px">Distributed</th>
                    <th class="text-end" style="width:100px">Specimen</th>
                    <th class="text-end" style="width:90px">Sold</th>
                    <th class="text-end" style="width:90px">Returned</th>
                    <th class="text-end" style="width:120px">Available in Hand</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumDist = 0;
                    $sumAsgn = 0;
                    $sumSpot = 0;
                    $sumRet = 0;
                    $sumStock = 0;
                @endphp
                @forelse ($products as $idx => $p)
                    @php
                        $dist = (int) $p->distributed_qty;
                        $asgn = (int) $p->assigned_qty;
                        $spot = (int) $p->spot_sold_qty;
                        $ret  = (int) $p->returned_qty;
                        $stock = (int) $p->current_stock;

                        $sumDist += $dist;
                        $sumAsgn += $asgn;
                        $sumSpot += $spot;
                        $sumRet  += $ret;
                        $sumStock += $stock;
                    @endphp
                    <tr>
                        <td class="text-center text-muted text-xs">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $p->product_name }}</strong>
                            @if ($p->product_code)
                                <span class="text-secondary text-xs d-block">Code: {{ $p->product_code }}</span>
                            @endif
                        </td>
                        <td class="text-end text-primary fw-medium">{{ number_format($dist) }}</td>
                        <td class="text-end text-info fw-medium">{{ number_format($asgn) }}</td>
                        <td class="text-end text-success fw-medium">{{ number_format($spot) }}</td>
                        <td class="text-end text-warning fw-medium">{{ number_format($ret) }}</td>
                        <td class="text-end">
                            <span class="badge {{ $stock > 0 ? 'bg-success-600' : 'bg-secondary' }} px-12 py-4 radius-4 text-white">
                                {{ number_format($stock) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-20 text-muted">No stock or distribution records found for this representative.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($products->isNotEmpty())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <th colspan="2" class="text-end">Total</th>
                        <th class="text-end text-primary">{{ number_format($sumDist) }}</th>
                        <th class="text-end text-info">{{ number_format($sumAsgn) }}</th>
                        <th class="text-end text-success">{{ number_format($sumSpot) }}</th>
                        <th class="text-end text-warning">{{ number_format($sumRet) }}</th>
                        <th class="text-end text-success-700 fs-6">{{ number_format($sumStock) }}</th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-16">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>

</div>
