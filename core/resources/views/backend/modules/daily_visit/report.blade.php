@extends('backend.layouts.master')

@section('meta')
    <title>Daily Visit Quarter Report | {{ config('app.name', 'Field Marketing') }}</title>
    <style>
        #visitReportFilterForm .select2-container {
            width: 100% !important;
        }
        #visitReportFilterForm .select2-container--default .select2-selection--single {
            height: 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        #visitReportFilterForm .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
            padding-left: 12px;
            padding-right: 30px;
        }
        #visitReportFilterForm .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }
        #visitReportFilterForm .select2-container--default .select2-selection--single .select2-selection__clear {
            position: relative;
            z-index: 2;
            margin-right: 4px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-1">Daily Visit Report (Quarter-Based Unique Count)</h6>
            <p class="text-sm text-secondary-light mb-0">
                1 unique count per Teacher or Library within each 3-month calendar quarter (Q1–Q4); resets automatically next quarter.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 d-print-none">
            <a href="{{ route('daily-visit.index') }}"
                class="btn btn-outline-secondary btn-sm px-12 py-8 radius-8 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:arrow-left-outline" class="text-lg"></iconify-icon>
                Back to Daily Visits
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-24 d-print-none">
        <div class="card-body">
            <form id="visitReportFilterForm" onsubmit="return false;">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label fw-medium mb-1">Quarter / Period</label>
                        <select id="filterPeriod" name="period" class="form-select">
                            <option value="current_quarter" selected>Current Quarter ({{ $currentYear }} Q{{ $currentQuarter }})</option>
                            <option value="last_quarter">Last Quarter</option>
                            <option value="q1">Q1 (Jan – Mar)</option>
                            <option value="q2">Q2 (Apr – Jun)</option>
                            <option value="q3">Q3 (Jul – Sep)</option>
                            <option value="q4">Q4 (Oct – Dec)</option>
                            <option value="all_quarters">Full Year (All Quarters)</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-2 col-md-6 d-none" id="yearFilterWrap">
                        <label class="form-label fw-medium mb-1">Year</label>
                        <select id="filterYear" name="year" class="form-select">
                            @foreach ($years as $yr)
                                <option value="{{ $yr }}" @selected((int) $yr === (int) $currentYear)>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-3 col-md-6 d-none custom-date-wrap">
                        <label class="form-label fw-medium mb-1">Start Date</label>
                        <input type="date" id="filterStartDate" name="start_date" class="form-control"
                            value="{{ now()->firstOfQuarter()->toDateString() }}">
                    </div>

                    <div class="col-xl-2 col-lg-3 col-md-6 d-none custom-date-wrap">
                        <label class="form-label fw-medium mb-1">End Date</label>
                        <input type="date" id="filterEndDate" name="end_date" class="form-control"
                            value="{{ now()->lastOfQuarter()->toDateString() }}">
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label fw-medium mb-1">Representative</label>
                        <select id="filterUserId" name="user_id" class="form-select js-s2-ajax"
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="All Representatives" data-allow-clear="true">
                            <option value="">All Representatives</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label fw-medium mb-1">Visit Type</label>
                        <select id="filterType" name="type" class="form-select">
                            <option value="all" selected>All (Teacher &amp; Library)</option>
                            <option value="teacher">Teacher Only</option>
                            <option value="library">Library Only</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label fw-medium mb-1">Status</label>
                        <select id="filterStatus" name="status" class="form-select">
                            <option value="all" selected>All Status</option>
                            <option value="approved">Approved Only</option>
                            <option value="pending">Pending Only</option>
                        </select>
                    </div>

                    <div class="col-xl-auto col-md-4 d-flex gap-2">
                        <button type="button" id="btnApplyFilter"
                            class="btn btn-primary px-16 py-9 d-inline-flex align-items-center gap-1">
                            <iconify-icon icon="solar:filter-outline" class="text-lg"></iconify-icon>
                            Apply
                        </button>
                        <button type="button" id="btnResetFilter"
                            class="btn btn-outline-secondary px-14 py-9">
                            Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary KPI Cards --}}
    <div class="row g-3 mb-24">
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-1 h-100">
                <div class="card-body p-20">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Unique Teacher Visits</p>
                            <h5 class="mb-0 fw-bold" id="kpiUniqueTeachers">0</h5>
                        </div>
                        <div class="w-44-px h-44-px bg-cyan rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                            <iconify-icon icon="mdi:account-tie-outline" class="text-white text-2xl"></iconify-icon>
                        </div>
                    </div>
                    <p class="fw-medium text-xs text-primary-light mt-12 mb-0">
                        Unique teachers counted per quarter
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-2 h-100">
                <div class="card-body p-20">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Unique Library Visits</p>
                            <h5 class="mb-0 fw-bold" id="kpiUniqueLibraries">0</h5>
                        </div>
                        <div class="w-44-px h-44-px bg-purple rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                            <iconify-icon icon="mdi:library-outline" class="text-white text-2xl"></iconify-icon>
                        </div>
                    </div>
                    <p class="fw-medium text-xs text-primary-light mt-12 mb-0">
                        Unique libraries counted per quarter
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-4 h-100">
                <div class="card-body p-20">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Total Unique Visits</p>
                            <h5 class="mb-0 fw-bold text-success-main" id="kpiTotalUnique">0</h5>
                        </div>
                        <div class="w-44-px h-44-px bg-success-main rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                            <iconify-icon icon="solar:check-circle-outline" class="text-white text-2xl"></iconify-icon>
                        </div>
                    </div>
                    <p class="fw-medium text-xs text-primary-light mt-12 mb-0">
                        Combined Teacher + Library unique score
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-3 h-100">
                <div class="card-body p-20">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Total Actual Visits (Raw)</p>
                            <h5 class="mb-0 fw-bold" id="kpiTotalRaw">0</h5>
                        </div>
                        <div class="w-44-px h-44-px bg-info rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                            <iconify-icon icon="solar:clipboard-list-outline" class="text-white text-2xl"></iconify-icon>
                        </div>
                    </div>
                    <p class="fw-medium text-xs text-primary-light mt-12 mb-0">
                        Across <span id="kpiTotalMrs" class="fw-semibold">0</span> Marketing Representatives
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Report Table Card --}}
    <div class="card basic-data-table">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h5 class="card-title mb-0">Representative Visit Summary</h5>
                <span id="activePeriodBadge"
                    class="badge bg-primary-50 text-primary-600 fw-semibold px-12 py-6 radius-4">Loading...</span>
                <span id="activeDateSpan" class="text-xs text-secondary-light"></span>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 d-print-none">
                <input type="text" id="reportSearchInput" class="form-control form-control-sm"
                    style="width: 210px;" placeholder="Search MR or ID...">
                <button type="button" id="btnExportCsv"
                    class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="mdi:file-excel-outline" class="text-lg"></iconify-icon>
                    Export CSV
                </button>
                <button type="button" id="btnPrintReport"
                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                    onclick="window.print()">
                    <iconify-icon icon="solar:printer-outline" class="text-lg"></iconify-icon>
                    Print
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0 align-middle" id="visitReportTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Marketing Representative</th>
                            <th class="text-center">Unique Teacher Visits</th>
                            <th class="text-center">Unique Library Visits</th>
                            <th class="text-center">Total Unique Visits</th>
                            <th class="text-center">Total Actual Visits (Raw)</th>
                            <th class="text-end d-print-none" style="width: 150px;">Details</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary-light">Loading report data...</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light d-none" id="reportTableFoot">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Total (<span id="footMrCount">0</span> MRs):</td>
                            <td class="text-center" id="footUniqueTeachers">0</td>
                            <td class="text-center" id="footUniqueLibraries">0</td>
                            <td class="text-center text-success-main" id="footTotalUnique">0</td>
                            <td class="text-center" id="footTotalRaw">0</td>
                            <td class="d-print-none"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(function() {
            const reportUrl = @json(route('daily-visit.report.list'));
            let loadedRows = [];
            let lastFilters = {};

            function escapeHtml(str) {
                return String(str ?? '').replace(/[&<>"']/g, function(m) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    })[m];
                });
            }

            function syncFilterVisibility() {
                const period = $('#filterPeriod').val();
                const needsYear = ['q1', 'q2', 'q3', 'q4', 'all_quarters'].includes(period);
                const isCustom = period === 'custom';

                $('#yearFilterWrap').toggleClass('d-none', !needsYear);
                $('.custom-date-wrap').toggleClass('d-none', !isCustom);
            }

            function renderTableRows() {
                const search = ($('#reportSearchInput').val() || '').trim().toLowerCase();
                const filtered = search === ''
                    ? loadedRows
                    : loadedRows.filter(function(r) {
                        const haystack = `${r.mr_name || ''} ${r.employee_id || ''} ${r.mr_phone || ''}`.toLowerCase();
                        return haystack.includes(search);
                    });

                const $tbody = $('#reportTableBody');
                const $tfoot = $('#reportTableFoot');

                if (!filtered.length) {
                    $tbody.html('<tr><td colspan="7" class="text-center py-4 text-secondary-light">No visit records found for the selected filters.</td></tr>');
                    $tfoot.addClass('d-none');
                    return;
                }

                let html = '';
                let sumTeachers = 0;
                let sumLibraries = 0;
                let sumUnique = 0;
                let sumRaw = 0;

                filtered.forEach(function(r, idx) {
                    sumTeachers += Number(r.unique_teacher_visits || 0);
                    sumLibraries += Number(r.unique_library_visits || 0);
                    sumUnique += Number(r.total_unique_visits || 0);
                    sumRaw += Number(r.total_raw_visits || 0);

                    const empBadge = r.employee_id
                        ? `<span class="badge bg-neutral-200 text-neutral-700 ms-1 fw-medium">${escapeHtml(r.employee_id)}</span>`
                        : '';
                    const phoneSub = r.mr_phone
                        ? `<div class="text-xs text-secondary-light">${escapeHtml(r.mr_phone)}</div>`
                        : '';

                    html += `
                        <tr>
                            <td>${idx + 1}</td>
                            <td>
                                <a href="javascript:void(0)"
                                   class="fw-semibold text-primary-light hover-text-primary AjaxModal"
                                   data-ajax-modal="${escapeHtml(r.details_url)}"
                                   data-size="xl">
                                    ${escapeHtml(r.mr_name)}
                                </a>${empBadge}
                                ${phoneSub}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info-focus text-info-main px-12 py-6 radius-4 fw-semibold">${r.unique_teacher_visits}</span>
                                <div class="text-xs text-secondary-light mt-1">Raw: ${r.raw_teacher_visits}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-lilac-100 text-lilac-600 px-12 py-6 radius-4 fw-semibold">${r.unique_library_visits}</span>
                                <div class="text-xs text-secondary-light mt-1">Raw: ${r.raw_library_visits}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-focus text-success-main px-14 py-6 radius-4 fw-bold text-sm">${r.total_unique_visits}</span>
                            </td>
                            <td class="text-center fw-semibold">${r.total_raw_visits}</td>
                            <td class="text-end d-print-none">
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 AjaxModal"
                                    data-ajax-modal="${escapeHtml(r.details_url)}"
                                    data-size="xl">
                                    <iconify-icon icon="solar:eye-outline" class="text-base"></iconify-icon>
                                    View Details
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $tbody.html(html);
                $('#footMrCount').text(filtered.length);
                $('#footUniqueTeachers').text(sumTeachers);
                $('#footUniqueLibraries').text(sumLibraries);
                $('#footTotalUnique').text(sumUnique);
                $('#footTotalRaw').text(sumRaw);
                $tfoot.removeClass('d-none');
            }

            function loadReport() {
                $('#reportTableBody').html(
                    '<tr><td colspan="7" class="text-center py-4 text-secondary-light">Loading report data...</td></tr>'
                );
                $('#reportTableFoot').addClass('d-none');

                const csrfToken = $('meta[name="csrf-token"]').attr('content')
                    || $('#visitReportFilterForm input[name="_token"]').val()
                    || @json(csrf_token());

                const payload = {
                    _token: csrfToken,
                    period: $('#filterPeriod').val(),
                    year: $('#filterYear').val(),
                    start_date: $('#filterStartDate').val(),
                    end_date: $('#filterEndDate').val(),
                    user_id: $('#filterUserId').val(),
                    type: $('#filterType').val(),
                    status: $('#filterStatus').val(),
                };

                $.ajax({
                    url: reportUrl,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: payload,
                    dataType: 'json'
                })
                    .done(function(res) {
                        if (!res || !res.ok) {
                            Swal.fire('Error', 'Unable to load visit report.', 'error');
                            return;
                        }

                        loadedRows = res.rows || [];
                        lastFilters = res.filters || {};
                        const s = res.summary || {};

                        $('#kpiUniqueTeachers').text(s.unique_teacher_visits ?? 0);
                        $('#kpiUniqueLibraries').text(s.unique_library_visits ?? 0);
                        $('#kpiTotalUnique').text(s.total_unique_visits ?? 0);
                        $('#kpiTotalRaw').text(s.total_raw_visits ?? 0);
                        $('#kpiTotalMrs').text(s.total_mrs ?? 0);

                        $('#activePeriodBadge').text(lastFilters.period_label || '');
                        if (lastFilters.start_date && lastFilters.end_date) {
                            $('#activeDateSpan').text(`(${lastFilters.start_date} to ${lastFilters.end_date})`);
                            $('#filterStartDate').val(lastFilters.start_date);
                            $('#filterEndDate').val(lastFilters.end_date);
                        }

                        renderTableRows();
                    })
                    .fail(function(xhr) {
                        const msg = xhr?.responseJSON?.message || 'Failed to load visit report.';
                        $('#reportTableBody').html(
                            `<tr><td colspan="7" class="text-center py-4 text-danger">${escapeHtml(msg)}</td></tr>`
                        );
                    });
            }

            $('#filterPeriod').on('change', function() {
                syncFilterVisibility();
                if ($(this).val() !== 'custom') {
                    loadReport();
                }
            });

            $('#filterYear, #filterType, #filterStatus, #filterUserId').on('change', function() {
                loadReport();
            });

            $('#btnApplyFilter').on('click', function() {
                loadReport();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterPeriod').val('current_quarter');
                $('#filterYear').val(@json($currentYear));
                $('#filterType').val('all');
                $('#filterStatus').val('all');
                $('#reportSearchInput').val('');
                $('#filterUserId').val(null).trigger('change.select2');
                syncFilterVisibility();
                loadReport();
            });

            $('#reportSearchInput').on('input', function() {
                renderTableRows();
            });

            $('#btnExportCsv').on('click', function() {
                if (!loadedRows.length) {
                    Swal.fire('Info', 'No data to export.', 'info');
                    return;
                }

                const headers = [
                    'SL', 'Representative', 'Employee ID', 'Phone',
                    'Unique Teacher Visits', 'Unique Library Visits',
                    'Total Unique Visits', 'Raw Teacher Visits', 'Raw Library Visits', 'Total Actual Visits'
                ];

                const csvCell = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
                const lines = [headers.map(csvCell).join(',')];

                loadedRows.forEach(function(r, idx) {
                    lines.push([
                        idx + 1,
                        r.mr_name,
                        r.employee_id || '',
                        r.mr_phone || '',
                        r.unique_teacher_visits,
                        r.unique_library_visits,
                        r.total_unique_visits,
                        r.raw_teacher_visits,
                        r.raw_library_visits,
                        r.total_raw_visits,
                    ].map(csvCell).join(','));
                });

                const blob = new Blob(['\uFEFF' + lines.join('\r\n')], {
                    type: 'text/csv;charset=utf-8;'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                const periodTag = (lastFilters.start_date || 'report') + '_to_' + (lastFilters.end_date || '');
                a.href = url;
                a.download = `daily_visit_quarter_report_${periodTag}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });

            if (window.S2 && typeof window.S2.auto === 'function') {
                window.S2.auto('#visitReportFilterForm');
            }

            syncFilterVisibility();
            loadReport();
        });
    </script>
@endsection
