@extends('backend.layouts.master')

@section('meta')
    <title>Cost Per Result</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Cost Per Result</h6>
            <p class="m-0">Review visit and expense results by representative and date range.</p>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Cost Per Result</li>
        </ul>
    </div>

    <form id="costPerResultForm" action="{{ route('cost-per-result.submit') }}" method="POST">
        @csrf
        <div class="card radius-12 mb-24">
            <div class="card-header">
                <h5 class="card-title mb-0">Report Filters</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <label for="user" class="form-label text-sm mb-8">Representative</label>
                        <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="user" required
                            data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                            data-placeholder="Select Marketing Representative">
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <label for="start_date" class="form-label text-sm mb-8">Start Date</label>
                        <input id="start_date" name="start_date" type="date"
                            class="form-control form-control-sm radius-8" required>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <label for="end_date" class="form-label text-sm mb-8">End Date</label>
                        <input id="end_date" name="end_date" type="date"
                            class="form-control form-control-sm radius-8" required>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                        <button type="submit" class="btn btn-primary d-flex btn-sm px-16 py-10 radius-8"
                            id="costPerResultSubmit">
                            <iconify-icon icon="solar:magnifer-outline" class="text-lg px-2"></iconify-icon>
                            Generate Report
                        </button>
                        <div id="costPerResultError" class="text-danger text-sm" role="alert" aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row gy-4">
        <div class="col-xxl-4 col-md-6">
            <div class="card radius-12 h-100">
                <div class="card-body p-24">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="text-secondary-light fw-medium mb-8">Total Visit</p>
                            <h4 class="fw-semibold mb-0" id="totalVisit">—</h4>
                        </div>
                        <span
                            class="w-56-px h-56-px radius-12 d-inline-flex justify-content-center align-items-center bg-primary-100 text-primary-600">
                            <iconify-icon icon="solar:map-point-wave-outline" class="text-2xl"></iconify-icon>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card radius-12 h-100">
                <div class="card-body p-24">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="text-secondary-light fw-medium mb-8">Total Expense</p>
                            <h4 class="fw-semibold mb-0" id="totalExpense">—</h4>
                        </div>
                        <span
                            class="w-56-px h-56-px radius-12 d-inline-flex justify-content-center align-items-center bg-warning-focus text-warning-600">
                            <iconify-icon icon="solar:wallet-money-outline" class="text-2xl"></iconify-icon>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card radius-12 h-100">
                <div class="card-body p-24">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="text-secondary-light fw-medium mb-8">Cost Per Result</p>
                            <h4 class="fw-semibold mb-0" id="costPerResult">—</h4>
                        </div>
                        <span
                            class="w-56-px h-56-px radius-12 d-inline-flex justify-content-center align-items-center bg-success-100 text-success-600">
                            <iconify-icon icon="solar:chart-2-outline" class="text-2xl"></iconify-icon>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        window.S2 && window.S2.auto && window.S2.auto();

        $('#costPerResultForm').on('submit', function(event) {
            event.preventDefault();

            const $form = $(this);
            const $submit = $('#costPerResultSubmit');
            const $error = $('#costPerResultError');

            $error.text('');
            $submit.prop('disabled', true).text('Loading...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    $('#totalVisit').text(Number(response.total_visit).toLocaleString());
                    $('#totalExpense').text(Number(response.total_expense).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    $('#costPerResult').text(Number(response.cost_per_result).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    const validationErrors = response.errors ? Object.values(response.errors).flat() : [];
                    $error.text(validationErrors[0] || response.message ||
                        'Unable to generate the report. Please try again.');
                },
                complete: function() {
                    $submit.prop('disabled', false).html(
                        '<iconify-icon icon="solar:magnifer-outline" class="text-lg"></iconify-icon> Generate Report'
                    );
                }
            });
        });
    </script>   
@endsection