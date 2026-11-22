@extends('backend.layouts.master')

@section('meta')
  <title>Product Inventory Breakdown - {{ optional($representative->user)->name ?? 'Representative' }} - Field Marketing</title>
@endsection

@section('content')
  {{-- ================= PAGE HEADER & BREADCRUMB ================= --}}
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div>
      <h6 class="fw-semibold mb-0">Product Inventory Breakdown</h6>
      <p class="m-0 text-secondary">
        Real-time stock balance, distributions, specimen assignments, spot sales, and returns for
        <strong class="text-dark">{{ optional($representative->user)->name ?? ('#' . $representative->user_id) }}</strong>
        @if ($representative->employee_id)
          <span class="badge bg-light text-secondary ms-1">ID: {{ $representative->employee_id }}</span>
        @endif
      </p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('representative-inventory.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 radius-8 px-16 py-8">
        <iconify-icon icon="solar:arrow-left-linear" class="text-lg"></iconify-icon>
        <span>Back to Inventory List</span>
      </a>
    </div>
  </div>

  {{-- ================= REPRESENTATIVE INFO CARD ================= --}}
  <div class="card border radius-8 shadow-none mb-24">
    <div class="card-body p-20">
      <div class="row g-3 align-items-center">
        {{-- Profile identity --}}
        <div class="col-12 col-md-4 border-end-md">
          <div class="d-flex align-items-center gap-3">
            <div class="w-48-px h-48-px rounded-circle bg-primary-50 text-primary-600 d-flex align-items-center justify-content-center flex-shrink-0">
              <iconify-icon icon="solar:user-bold" class="text-2xl"></iconify-icon>
            </div>
            <div>
              <h6 class="mb-1 fw-bold text-dark">{{ optional($representative->user)->name ?? '—' }}</h6>
              <span class="text-secondary text-sm d-block">{{ optional($representative->mrDesignation)->designation_name ?? 'Field Representative' }}</span>
              @if ($representative->employee_id)
                <span class="badge bg-primary-100 text-primary-700 text-xs mt-1 radius-4">Employee ID: {{ $representative->employee_id }}</span>
              @endif
            </div>
          </div>
        </div>

        {{-- Contact Details --}}
        <div class="col-12 col-sm-6 col-md-3 border-end-md">
          <div class="d-flex flex-column gap-2 text-sm">
            <div class="d-flex align-items-center gap-2 text-secondary">
              <iconify-icon icon="solar:phone-calling-rounded-bold" class="text-primary text-base flex-shrink-0"></iconify-icon>
              <span class="text-dark fw-medium">{{ optional($representative->user)->phone ?? '—' }}</span>
            </div>
            <div class="d-flex align-items-center gap-2 text-secondary">
              <iconify-icon icon="solar:letter-bold" class="text-info text-base flex-shrink-0"></iconify-icon>
              <span class="text-dark fw-medium text-break">{{ optional($representative->user)->email ?? '—' }}</span>
            </div>
          </div>
        </div>

        {{-- Branch & Assigned Upazilas --}}
        <div class="col-12 col-sm-6 col-md-5">
          <div class="d-flex flex-column gap-2 text-sm">
            <div class="d-flex align-items-center gap-2">
              <span class="text-secondary d-flex align-items-center gap-1">
                <iconify-icon icon="ri-store-3-line" class="text-secondary"></iconify-icon> Branch:
              </span>
              <span class="fw-semibold text-dark">{{ optional($representative->branch)->name ?? '—' }}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="text-secondary d-flex align-items-center gap-1 flex-shrink-0">
                <iconify-icon icon="mdi:map-marker-multiple-outline" class="text-primary"></iconify-icon> Upazilas:
              </span>
              <div class="d-flex flex-wrap align-items-center gap-1">
                @if ($representative->territories && $representative->territories->isNotEmpty())
                  @foreach ($representative->territories as $territory)
                    @if ($territory->upazila)
                      <span class="badge bg-light text-dark border text-xs radius-4 px-8 py-4 d-inline-flex align-items-center">
                        {{ $territory->upazila->upazila_name }}
                      </span>
                    @endif
                  @endforeach
                @elseif (optional($representative->upazila)->upazila_name)
                  <span class="badge bg-light text-dark border text-xs radius-4 px-8 py-4 d-inline-flex align-items-center">
                    {{ $representative->upazila->upazila_name }}
                  </span>
                @else
                  <span class="text-secondary">—</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= 5 KPI SUMMARY CARDS ================= --}}
  <div class="row g-3 mb-24">
    {{-- 1. Available in Hand --}}
    <div class="col-12 col-sm-6 col-xl-2-4" style="flex: 1 1 200px;">
      <div class="card h-100 border shadow-none radius-8 bg-success-50 border-success-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-success-600 fw-medium text-sm">Available in Hand</span>
            <span class="w-32-px h-32-px radius-8 bg-success-600 text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:package-variant-closed-check" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-success-700">{{ number_format($totalInHand) }}</h4>
          <small class="text-success-600">Current active stock</small>
        </div>
      </div>
    </div>

    {{-- 2. Total Distributed --}}
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
          <small class="text-primary-600">Delivered to representative</small>
        </div>
      </div>
    </div>

    {{-- 3. Specimen Given --}}
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
          <small class="text-info-600">Distributed specimen books</small>
        </div>
      </div>
    </div>

    {{-- 4. Spot Sold --}}
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
          <small class="text-warning-600">Spot sale approvals</small>
        </div>
      </div>
    </div>

    {{-- 5. Returned --}}
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
          <small class="text-danger-600">Returned to central stock</small>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= PRODUCT INVENTORY BREAKDOWN TABLE ================= --}}
  <div class="card border radius-8">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-16 px-24">
      <div>
        <h5 class="card-title mb-0">Product Inventory Breakdown</h5>
        <span class="text-secondary text-sm">Itemized balance and transaction history for each product</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-100 text-primary-700 px-12 py-6 radius-4 fw-semibold">
          Total Products: {{ $products->count() }}
        </span>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table bordered-table table-hover align-middle mb-0" style="width:100%">
          <thead class="table-light">
            <tr>
              <th style="width: 60px" class="text-center">#</th>
              <th>Product Details</th>
              <th style="width: 130px" class="text-center">Distributed <span class="badge bg-primary-50 text-primary-700 text-xs ms-1">IN</span></th>
              <th style="width: 140px" class="text-center">Specimen Given <span class="badge bg-info-50 text-info-700 text-xs ms-1">OUT</span></th>
              <th style="width: 130px" class="text-center">Spot Sold <span class="badge bg-success-50 text-success-700 text-xs ms-1">OUT</span></th>
              <th style="width: 130px" class="text-center">Returned <span class="badge bg-warning-50 text-warning-700 text-xs ms-1">OUT</span></th>
              <th style="width: 160px" class="text-center">Available in Hand <span class="badge bg-success text-white text-xs ms-1">Balance</span></th>
            </tr>
          </thead>
          <tbody>
            @php
              $sumDist  = 0;
              $sumAsgn  = 0;
              $sumSpot  = 0;
              $sumRet   = 0;
              $sumStock = 0;
            @endphp
            @forelse ($products as $idx => $product)
              @php
                $dist  = (int) $product->distributed_qty;
                $asgn  = (int) $product->assigned_qty;
                $spot  = (int) $product->spot_sold_qty;
                $ret   = (int) $product->returned_qty;
                $stock = (int) $product->current_stock;

                $sumDist  += $dist;
                $sumAsgn  += $asgn;
                $sumSpot  += $spot;
                $sumRet   += $ret;
                $sumStock += $stock;

                $badgeClass = $stock > 0 ? 'bg-success-600 text-white' : 'bg-secondary text-white';
              @endphp
              <tr>
                <td class="text-center text-muted fw-medium">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>
                  <div class="fw-semibold text-dark">{{ $product->product_name }}</div>
                  @if (!empty($product->product_code))
                    <div class="text-secondary text-xs mt-1">
                      <span class="badge bg-light text-secondary border text-xs radius-4">Code: {{ $product->product_code }}</span>
                    </div>
                  @endif
                </td>
                <td class="text-center">
                  <span class="fw-semibold text-primary fs-6">{{ number_format($dist) }}</span>
                </td>
                <td class="text-center">
                  <span class="fw-semibold text-info fs-6">{{ number_format($asgn) }}</span>
                </td>
                <td class="text-center">
                  <span class="fw-semibold text-success fs-6">{{ number_format($spot) }}</span>
                </td>
                <td class="text-center">
                  <span class="fw-semibold text-warning fs-6">{{ number_format($ret) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge text-sm fw-semibold {{ $badgeClass }} px-16 py-6 radius-4">
                    {{ number_format($stock) }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-40">
                  <div class="d-flex flex-column align-items-center justify-content-center">
                    <iconify-icon icon="solar:box-minimalistic-linear" class="text-4xl text-secondary mb-2"></iconify-icon>
                    <h6 class="text-secondary fw-medium mb-1">No inventory records found</h6>
                    <p class="text-muted text-sm mb-0">This representative currently has no distributed or assigned products.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
          @if ($products->isNotEmpty())
            <tfoot class="table-light fw-bold">
              <tr class="align-middle">
                <td colspan="2" class="text-end text-dark pe-20 fs-6">GRAND TOTAL:</td>
                <td class="text-center text-primary fs-6 fw-bold">{{ number_format($sumDist) }}</td>
                <td class="text-center text-info fs-6 fw-bold">{{ number_format($sumAsgn) }}</td>
                <td class="text-center text-success fs-6 fw-bold">{{ number_format($sumSpot) }}</td>
                <td class="text-center text-warning fs-6 fw-bold">{{ number_format($sumRet) }}</td>
                <td class="text-center">
                  <span class="badge fs-6 fw-bold bg-success-700 text-white px-16 py-8 radius-4">
                    {{ number_format($sumStock) }}
                  </span>
                </td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    <div class="card-footer bg-white border-top py-16 px-24 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <span class="text-secondary text-sm">
        Showing <strong>{{ $products->count() }}</strong> product items for <strong>{{ optional($representative->user)->name ?? 'this representative' }}</strong>
      </span>
      {{-- <a href="{{ route('representative-inventory.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 radius-8 px-16">
        <iconify-icon icon="solar:arrow-left-linear"></iconify-icon>
        <span>Back to Representative Inventory</span>
      </a> --}}
    </div>
  </div>

@endsection
