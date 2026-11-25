@extends('backend.layouts.master')

@section('meta')
  <title>Representative Inventory - Field Marketing</title>
@endsection

@section('content')
  <style>
    /* Center align Action column header and content */
    #repInventoryTable th:last-child,
    #repInventoryTable td:last-child,
    #repInventoryTable td.text-end {
      text-align: center !important;
      vertical-align: middle !important;
    }
    #repInventoryTable td:last-child div {
      margin: 0 auto !important;
      justify-content: center !important;
    }
    /* Ensure branch and upazila icons and text stay cleanly side-by-side */
    #repInventoryTable td:nth-child(4) iconify-icon {
      font-size: 16px;
      vertical-align: middle;
    }
  </style>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div>
      <h6 class="fw-semibold mb-0">Representative Inventory</h6>
      <p class="m-0 text-secondary">Track real-time stock balances, distributions, specimen assignments, and returns per representative</p>
    </div>
    <ul class="d-flex align-items-center gap-2">
      <li class="fw-medium">
        <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
          <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon> Dashboard
        </a>
      </li>
      <li>-</li>
      <li class="fw-medium text-secondary">Representative Inventory</li>
    </ul>
  </div>

  {{-- ================= KPI SUMMARY CARDS ================= --}}
  <div class="row g-3 mb-24">
    {{-- Card 1: Available in Field --}}
    <div class="col-12 col-sm-6 col-xl-2-4" style="flex: 1 1 200px;">
      <div class="card h-100 border shadow-none radius-8 bg-success-50 border-success-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-success-600 fw-medium text-sm">Available in Hand</span>
            <span class="w-32-px h-32-px radius-8 bg-success-600 text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:package-variant-closed-check" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-success-700">{{ number_format($totalInField) }}</h4>
          <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
            <span class="badge bg-info-subtle text-info-700 border border-info-subtle text-xs px-6 py-2" title="Specimen Copy Stock">Specimen: {{ number_format($totalSpecimenInField) }}</span>
            <span class="badge bg-primary-subtle text-primary-700 border border-primary-subtle text-xs px-6 py-2" title="Sale Copy Stock">Sale: {{ number_format($totalSaleInField) }}</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Card 2: Total Distributed --}}
    <div class="col-12 col-sm-6 col-xl-2-4" style="flex: 1 1 200px;">
      <div class="card h-100 border shadow-none radius-8 bg-primary-50 border-primary-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-primary-600 fw-medium text-sm">Total Distributed</span>
            <span class="w-32-px h-32-px radius-8 bg-primary-600 text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:truck-delivery-outline" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-primary-700">{{ number_format($totalDistributed) }}</h4>
          <small class="text-primary-600">Delivered distributions</small>
        </div>
      </div>
    </div>

    {{-- Card 3: Total Specimen --}}
    <div class="col-12 col-sm-6 col-xl-2-4" style="flex: 1 1 200px;">
      <div class="card h-100 border shadow-none radius-8 bg-info-50 border-info-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-info-600 fw-medium text-sm">Specimen Given</span>
            <span class="w-32-px h-32-px radius-8 bg-info-600 text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:book-open-page-variant" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-info-700">{{ number_format($totalAssigned) }}</h4>
          <small class="text-info-600">Approved specimen books</small>
        </div>
      </div>
    </div>

    {{-- Card 4: Total Spot Sold --}}
    <div class="col-12 col-sm-6 col-xl-2-4" style="flex: 1 1 200px;">
      <div class="card h-100 border shadow-none radius-8 bg-warning-50 border-warning-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-warning-600 fw-medium text-sm">Spot Sold</span>
            <span class="w-32-px h-32-px radius-8 bg-warning-main text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:cash-register" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-warning-main">{{ number_format($totalSpotSold) }}</h4>
          <small class="text-warning-600">Approved spot sales</small>
        </div>
      </div>
    </div>

    {{-- Card 5: Total Returned --}}
    <div class="col-12 col-sm-6 col-xl-2-4" style="flex: 1 1 200px;">
      <div class="card h-100 border shadow-none radius-8 bg-danger-50 border-danger-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-danger-600 fw-medium text-sm">Total Returned</span>
            <span class="w-32-px h-32-px radius-8 bg-danger-main text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:keyboard-return" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-danger-700">{{ number_format($totalReturned) }}</h4>
          <small class="text-danger-600">Received book returns</small>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= DATATABLE ================= --}}
  <div class="card basic-data-table border radius-8">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">Representative Inventory Summary</h5>
      <div class="actions-bar d-flex align-items-center gap-2 flex-wrap">
        <div class="search-set me-2">
          <div id="tableSearch" class="search-input"></div>
        </div>

        <ul class="table-top-head list-unstyled d-flex align-items-center gap-2 mb-0">
          @include('backend.include.buttons')
        </ul>
      </div>
    </div>

    <div class="card-body">
      <table class="table bordered-table table-scroll mb-0 AjaxDataTable" id="repInventoryTable" style="width:100%">
        <thead>
          <tr>
            <th style="width:50px" class="text-center">
              <div class="form-check style-check d-flex align-items-center justify-content-center">
                <input class="form-check-input" type="checkbox" id="select-all">
                <label class="form-check-label ms-1">S.L</label>
              </div>
            </th>
            <th>Representative</th>
            <th>Designation</th>
            <th>Branch & Upazilas</th>
            <th class="text-center">Distributed</th>
            <th class="text-center">Specimen Given</th>
            <th class="text-center">Spot Sold</th>
            <th class="text-center">Returned</th>
            <th class="text-center">Available in Hand</th>
            <th style="width:80px" class="text-center">Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

@endsection

@section('script')
  <script>
    var DATATABLE_URL = "{{ route('representative-inventory.listAjax') }}";
  </script>
@endsection
