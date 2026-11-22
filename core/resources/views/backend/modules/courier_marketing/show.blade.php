@extends('backend.layouts.master')

@section('meta')
  <title>Courier Dispatch #CM-{{ $courierMarketing->id }} Details - Field Marketing</title>
@endsection

@section('content')
  @php
    $statusClass = match ($courierMarketing->status) {
        'received'           => 'success',
        'partially_received' => 'info',
        'dispatched', 'in_transit' => 'primary',
        'failed', 'returned' => 'danger',
        default              => 'warning',
    };
    $totalQty = (int) $courierMarketing->items->sum('quantity');
    $itemsCount = $courierMarketing->items->count();
  @endphp

  {{-- ================= PAGE HEADER & BREADCRUMB ================= --}}
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h6 class="fw-semibold mb-0">Courier Dispatch #CM-{{ $courierMarketing->id }} Details</h6>
        <span class="badge bg-{{ $statusClass }}-focus text-{{ $statusClass }}-600 border border-{{ $statusClass }}-main px-12 py-4 radius-4 fw-medium text-xs">
          {{ $courierMarketing->statusLabel() }}
        </span>
      </div>
      <p class="m-0 text-secondary">
        Parcel dispatch details, recipient teacher and institution profile, logistics status, and itemized product list.
      </p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('courier-marketings.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 radius-8 px-16 py-8">
        <iconify-icon icon="solar:arrow-left-linear" class="text-lg"></iconify-icon>
        <span>Back to Courier List</span>
      </a>
    </div>
  </div>

  {{-- ================= TOP IDENTITY / PARTIES CARD ================= --}}
  <div class="card border radius-8 shadow-none mb-24">
    <div class="card-body p-20">
      <div class="row g-4 align-items-center">
        {{-- 1. Representative Info --}}
        <div class="col-12 col-md-4 border-end-md">
          <div class="d-flex align-items-center gap-3">
            <div class="w-48-px h-48-px rounded-circle bg-primary-50 text-primary-600 d-flex align-items-center justify-content-center flex-shrink-0">
              <iconify-icon icon="solar:user-bold" class="text-2xl"></iconify-icon>
            </div>
            <div>
              <span class="text-muted text-xs text-uppercase fw-semibold d-block">Marketing Representative</span>
              <h6 class="mb-1 fw-bold text-dark">{{ $courierMarketing->representative?->user?->name ?? '—' }}</h6>
              <div class="d-flex flex-wrap align-items-center gap-1">
                @if ($courierMarketing->representative?->employee_id)
                  <span class="badge bg-primary-100 text-primary-700 text-xs radius-4">
                    ID: {{ $courierMarketing->representative->employee_id }}
                  </span>
                @endif
                @if ($courierMarketing->representative?->user?->phone)
                  <span class="text-secondary text-xs d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:phone-calling-rounded-bold" class="text-primary"></iconify-icon>
                    {{ $courierMarketing->representative->user->phone }}
                  </span>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- 2. Teacher & Institution Info --}}
        <div class="col-12 col-md-4 border-end-md">
          <div class="d-flex align-items-center gap-3">
            <div class="w-48-px h-48-px rounded-circle bg-info-50 text-info-600 d-flex align-items-center justify-content-center flex-shrink-0">
              <iconify-icon icon="solar:square-academic-cap-bold" class="text-2xl"></iconify-icon>
            </div>
            <div>
              <span class="text-muted text-xs text-uppercase fw-semibold d-block">Teacher & Institution</span>
              <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-bold text-dark">{{ $courierMarketing->teacher?->teacher_name ?? '—' }}</h6>
                @if ($courierMarketing->teacher?->phone)
                  <span class="badge bg-light text-primary border text-xs">
                    {{ $courierMarketing->teacher->phone }}
                  </span>
                @endif
              </div>
              <div class="text-secondary text-sm mt-1 d-flex align-items-center gap-1">
                <iconify-icon icon="ri-store-3-line" class="text-secondary flex-shrink-0"></iconify-icon>
                <span class="text-truncate">{{ $courierMarketing->institution?->institution_name ?? ($courierMarketing->teacher?->institution?->institution_name ?? '—') }}</span>
              </div>
              @php
                $upazilaName = $courierMarketing->teacher?->institution?->upazila?->upazila_name;
                $districtName = $courierMarketing->teacher?->institution?->district?->name;
              @endphp
              @if ($upazilaName || $districtName)
                <div class="text-muted text-xs mt-1 d-flex align-items-center gap-1">
                  <iconify-icon icon="mdi:map-marker-outline" class="text-primary flex-shrink-0"></iconify-icon>
                  <span>{{ implode(', ', array_filter([$upazilaName, $districtName])) }}</span>
                </div>
              @endif
            </div>
          </div>
        </div>

        {{-- 3. Courier Logistics & Status --}}
        <div class="col-12 col-md-4">
          <div class="d-flex align-items-center gap-3">
            <div class="w-48-px h-48-px rounded-circle bg-warning-50 text-warning-main d-flex align-items-center justify-content-center flex-shrink-0">
              <iconify-icon icon="mdi:truck-delivery-outline" class="text-2xl"></iconify-icon>
            </div>
            <div class="w-100">
              <span class="text-muted text-xs text-uppercase fw-semibold d-block">Logistics Provider</span>
              <h6 class="mb-1 fw-bold text-dark">{{ $courierMarketing->courier_company ?: 'Direct Courier' }}</h6>
              <div class="d-flex align-items-center justify-content-between text-sm">
                <span class="text-secondary">Dispatch:</span>
                <span class="fw-medium text-dark">{{ $courierMarketing->dispatch_date?->format('d M, Y') ?? '—' }}</span>
              </div>
              <div class="d-flex align-items-center justify-content-between text-sm">
                <span class="text-secondary">Delivery:</span>
                <span class="fw-medium text-dark">{{ $courierMarketing->delivery_date?->format('d M, Y') ?? ($courierMarketing->received_at?->format('d M, Y') ?? 'Pending') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= 4 VIBRANT KPI SUMMARY CARDS ================= --}}
  <div class="row g-3 mb-24">
    {{-- 1. Total Dispatched Quantity --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border shadow-none radius-8 bg-primary-50 border-primary-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-primary-600 fw-medium text-sm">Total Dispatched Qty</span>
            <span class="w-32-px h-32-px radius-8 bg-primary-600 text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:package-variant-closed-check" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-primary-700">{{ number_format($totalQty) }}</h4>
          <small class="text-primary-600">Total copies / units sent</small>
        </div>
      </div>
    </div>

    {{-- 2. Product Items Count --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border shadow-none radius-8 bg-info-50 border-info-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-info-600 fw-medium text-sm">Product Titles</span>
            <span class="w-32-px h-32-px radius-8 bg-info-600 text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="mdi:book-open-page-variant" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h4 class="mb-0 fw-bold text-info-700">{{ $itemsCount }}</h4>
          <small class="text-info-600">Distinct product titles</small>
        </div>
      </div>
    </div>

    {{-- 3. Dispatch Date --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border shadow-none radius-8 bg-warning-50 border-warning-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-warning-600 fw-medium text-sm">Dispatch Date</span>
            <span class="w-32-px h-32-px radius-8 bg-warning-main text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="solar:calendar-date-bold" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h5 class="mb-0 fw-bold text-warning-main">{{ $courierMarketing->dispatch_date?->format('d M, Y') ?? '—' }}</h5>
          <small class="text-warning-600">Handed over to carrier</small>
        </div>
      </div>
    </div>

    {{-- 4. Delivery / Confirmed Date --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border shadow-none radius-8 bg-success-50 border-success-200">
        <div class="card-body p-16">
          <div class="d-flex align-items-center justify-content-between mb-8">
            <span class="text-success-600 fw-medium text-sm">Delivery / Confirmed</span>
            <span class="w-32-px h-32-px radius-8 bg-success-600 text-white d-inline-flex align-items-center justify-content-center">
              <iconify-icon icon="solar:calendar-check-bold" class="text-lg"></iconify-icon>
            </span>
          </div>
          <h5 class="mb-0 fw-bold text-success-700">
            {{ $courierMarketing->delivery_date?->format('d M, Y') ?? ($courierMarketing->received_at?->format('d M, Y') ?? 'Pending') }}
          </h5>
          <small class="text-success-600">Verification status</small>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= CALL CENTER VERIFICATION & AUDIT CARD ================= --}}
  <div class="card border radius-8 shadow-none mb-24">
    <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 py-16 px-20">
      <div class="d-flex align-items-center gap-2">
        <span class="w-32-px h-32-px rounded-circle bg-primary-50 text-primary-600 d-inline-flex align-items-center justify-content-center">
          <iconify-icon icon="solar:phone-calling-rounded-bold" class="text-lg"></iconify-icon>
        </span>
        <h6 class="card-title mb-0 fw-bold">Call Center Verification & Audit Log</h6>
      </div>
    </div>
    <div class="card-body p-20">
      <div class="row g-4">
        {{-- Audit Log Details --}}
        <div class="col-12 col-md-4 border-end-md">
          <div class="d-flex flex-column gap-3 text-sm">
            <div>
              <span class="text-muted text-xs text-uppercase fw-semibold d-block mb-1">Created By</span>
              <div class="d-flex align-items-center gap-2">
                <iconify-icon icon="solar:user-circle-bold" class="text-secondary text-lg"></iconify-icon>
                <strong class="text-dark">{{ $courierMarketing->creator?->name ?? 'System' }}</strong>
              </div>
              <small class="text-muted ms-4">{{ $courierMarketing->created_at?->format('d M, Y h:i A') }}</small>
            </div>

            <div>
              <span class="text-muted text-xs text-uppercase fw-semibold d-block mb-1">Verified By (Call Center)</span>
              @if ($courierMarketing->receiver)
                <div class="d-flex align-items-center gap-2">
                  <iconify-icon icon="solar:verified-check-bold" class="text-success text-lg"></iconify-icon>
                  <strong class="text-dark">{{ $courierMarketing->receiver->name }}</strong>
                </div>
                <small class="text-muted ms-4">
                  {{ $courierMarketing->received_at?->format('d M, Y h:i A') ?? 'Verified' }}
                </small>
              @else
                <span class="badge bg-light text-secondary border text-xs radius-4 px-8 py-4">
                  Pending Verification
                </span>
              @endif
            </div>
          </div>
        </div>

        {{-- Call Center & Activity Notes --}}
        <div class="col-12 col-md-8">
          <span class="text-muted text-xs text-uppercase fw-semibold d-inline-flex align-items-center gap-1 mb-1">
            <iconify-icon icon="solar:chat-round-dots-bold" class="text-primary text-base"></iconify-icon>
            Call Center & Activity Notes
          </span>
          @if (trim((string) $courierMarketing->note) !== '')
            <div class="px-16 py-10 bg-light rounded text-sm text-dark border radius-8" style="line-height: 1.5;">{!! nl2br(e(trim((string) $courierMarketing->note))) !!}</div>
          @else
            <div class="px-16 py-10 bg-light rounded text-sm text-muted border radius-8 d-flex align-items-center gap-2">
              <iconify-icon icon="solar:info-circle-linear" class="text-lg"></iconify-icon>
              <span>No verification notes or call activity recorded yet for this dispatch.</span>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ================= DISPATCHED PRODUCTS BREAKDOWN TABLE ================= --}}
  <div class="card border radius-8 shadow-none">
    <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 py-16 px-20">
      <div>
        <h6 class="card-title mb-0 fw-bold">Dispatched Products Breakdown</h6>
        <span class="text-secondary text-sm">Itemized list of specimen / books included in parcel #CM-{{ $courierMarketing->id }}</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-100 text-primary-700 px-12 py-6 radius-4 fw-semibold">
          Total Products: {{ $itemsCount }}
        </span>
        <span class="badge bg-success-100 text-success-700 px-12 py-6 radius-4 fw-semibold">
          Total Qty: {{ number_format($totalQty) }}
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
              <th style="width: 180px" class="text-center">Product Code</th>
              <th style="width: 180px" class="text-end">Dispatched Qty</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($courierMarketing->items as $idx => $item)
              @php
                $qty = (int) $item->quantity;
              @endphp
              <tr>
                <td class="text-center text-muted fw-medium">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>
                  <div class="fw-semibold text-dark">{{ $item->product?->name ?? ('Product #' . $item->product_id) }}</div>
                  @if (!empty($item->product?->category?->name))
                    <div class="text-secondary text-xs mt-1">
                      <span class="badge bg-light text-secondary border text-xs radius-4">
                        {{ $item->product->category->name }}
                      </span>
                    </div>
                  @endif
                </td>
                <td class="text-center">
                  @if (!empty($item->product?->product_code))
                    <span class="badge bg-light text-secondary border text-xs radius-4 px-8 py-4">
                      {{ $item->product->product_code }}
                    </span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end">
                  <span class="fw-bold text-primary fs-6">{{ number_format($qty) }}</span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-40">
                  <div class="d-flex flex-column align-items-center justify-content-center">
                    <iconify-icon icon="solar:box-minimalistic-linear" class="text-4xl text-secondary mb-2"></iconify-icon>
                    <h6 class="text-secondary fw-medium mb-1">No products found</h6>
                    <p class="text-muted text-sm mb-0">No dispatched product items were recorded under this dispatch.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
          @if ($courierMarketing->items->isNotEmpty())
            <tfoot class="table-light fw-bold">
              <tr class="align-middle">
                <td colspan="3" class="text-end text-dark pe-20 fs-6">GRAND TOTAL:</td>
                <td class="text-end text-primary fs-6 fw-bold pe-16">{{ number_format($totalQty) }}</td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    {{-- Single Card Footer (No duplicate) --}}
    <div class="card-footer bg-white border-top py-16 px-24 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <span class="text-secondary text-sm">
        Showing <strong>{{ $itemsCount }}</strong> product items for dispatch <strong>#CM-{{ $courierMarketing->id }}</strong>
      </span>
      {{-- <div class="d-flex align-items-center gap-2">
        <a href="{{ route('courier-marketings.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 radius-8 px-16">
          <iconify-icon icon="solar:arrow-left-linear"></iconify-icon>
          <span>Back to Courier List</span>
        </a>
      </div> --}}
    </div>
  </div>
@endsection
