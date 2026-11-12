<div class="modal-header border-bottom px-24 py-16">
    <div>
        <h5 class="modal-title fw-bold mb-1 d-flex align-items-center gap-2">
            <span>{{ $user->name }}</span>
            @if (!empty($mr->employee_id))
                <span class="badge bg-primary-50 text-primary-600 fw-semibold fs-6">{{ $mr->employee_id }}</span>
            @endif
        </h5>
        <div class="text-xs text-secondary-light d-flex flex-wrap gap-3">
            @if (!empty($user->phone))
                <span>Phone: {{ $user->phone }}</span>
            @endif
            <span>Period: <strong>{{ $filters['period_label'] }}</strong> ({{ $filters['start_date'] }} to {{ $filters['end_date'] }})</span>
            <span>Type: <strong>{{ ucfirst($filters['type']) }}</strong></span>
            <span>Status: <strong>{{ ucfirst($filters['status']) }}</strong></span>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
    {{-- Mini Totals Bar --}}
    <div class="row g-3 mb-20">
        <div class="col-md-3 col-6">
            <div class="p-12 radius-8 border bg-light text-center">
                <div class="text-xs text-secondary-light mb-1">Unique Teachers</div>
                <div class="fs-5 fw-bold text-info-main">{{ $totals['unique_teachers'] }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-12 radius-8 border bg-light text-center">
                <div class="text-xs text-secondary-light mb-1">Unique Libraries</div>
                <div class="fs-5 fw-bold text-lilac-600">{{ $totals['unique_libraries'] }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-12 radius-8 border bg-light text-center">
                <div class="text-xs text-secondary-light mb-1">Total Unique Counted</div>
                <div class="fs-5 fw-bold text-success-main">{{ $totals['total_unique'] }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-12 radius-8 border bg-light text-center">
                <div class="text-xs text-secondary-light mb-1">Total Actual Visits (Raw)</div>
                <div class="fs-5 fw-bold text-primary-light">{{ $totals['total_raw'] }}</div>
            </div>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="alert alert-light border text-center py-4 mb-0">
            No visit details found for this representative under the selected filters.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 100px;">Type</th>
                        <th>Teacher / Library</th>
                        <th>Quarters Visited</th>
                        <th class="text-center" style="width: 140px;">Actual Visits (Raw)</th>
                        <th class="text-center" style="width: 140px;">Unique Counted</th>
                        <th style="width: 155px;">Last Visit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if ($item->visit_type === 'teacher')
                                    <span class="badge bg-info-focus text-info-main px-10 py-6 radius-4 fw-semibold">Teacher</span>
                                @else
                                    <span class="badge bg-lilac-100 text-lilac-600 px-10 py-6 radius-4 fw-semibold">Library</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-primary-light">{{ $item->target_name }}</div>
                                @if (!empty($item->extra_info) || !empty($item->phone))
                                    <div class="text-xs text-secondary-light">
                                        {{ $item->extra_info ?: '' }}{{ !empty($item->extra_info) && !empty($item->phone) ? ' • ' : '' }}{{ $item->phone ?: '' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @foreach (array_filter(array_map('trim', explode(',', (string) $item->quarters_visited))) as $qLabel)
                                    <span class="badge bg-neutral-200 text-neutral-700 fw-medium me-1 mb-1">{{ $qLabel }}</span>
                                @endforeach
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ (int) $item->raw_visits }}</span>
                                <div class="text-xs text-secondary-light">
                                    App: {{ (int) $item->approved_raw_visits }} | Pen: {{ (int) $item->pending_raw_visits }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-focus text-success-main px-12 py-6 radius-4 fw-bold">
                                    {{ (int) $item->unique_visits }}
                                </span>
                            </td>
                            <td class="text-xs text-secondary-light">
                                {{ $item->last_visit_at ? \Illuminate\Support\Carbon::parse($item->last_visit_at)->format('d M Y, h:i A') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-16 p-12 radius-8 bg-primary-50 text-xs text-primary-light">
        <strong>Quarter Counting Rule:</strong> Multiple visits by this representative to the same Teacher or Library within the same 3-month calendar quarter (Q1: Jan–Mar, Q2: Apr–Jun, Q3: Jul–Sep, Q4: Oct–Dec) count as <strong>1 unique visit</strong>. Visits in separate quarters reset and count separately.
    </div>
</div>

<div class="modal-footer border-top px-24 py-12">
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
</div>

