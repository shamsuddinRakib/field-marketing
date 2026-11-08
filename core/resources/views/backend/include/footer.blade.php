{{-- <footer class="d-footer">
    <div class="row align-items-center justify-content-between">
        <div class="col-auto">
            <p class="mb-0">© <span><?php echo date('Y'); ?></span> Field Marketing | All Rights Reserved.</p>
        </div>
        <div class="col-auto">
            <p class="mb-0">Made by <span class="text-primary-600"><a href="https://bintelfuturetech.com/en">Bintel Future Tech </a></span></p>
        </div>
    </div>
</footer> --}}

<footer class="d-footer py-3 bg-white border-top">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-between gy-2">
            <!-- Left Side: Copyright -->
            <div class="col-12 col-md-auto text-center text-md-start">
                <p class="mb-0 text-muted">
                    &copy; <span><?php echo date('Y'); ?></span>
                    <strong class="text-danger">Field Marketing</strong> | All Rights Reserved.
                </p>
            </div>

            <!-- Right Side: Brand Credit with Red Color -->
            <div class="col-12 col-md-auto text-center text-md-end">
                <p class="mb-0 text-muted">
                    Made by
                    <a href="https://bintelfuturetech.com/en" target="_blank" rel="noopener noreferrer"
                        class="text-danger text-decoration-none fw-semibold">
                        Bintel Future Tech
                    </a>
                </p>
            </div>
        </div>
    </div>
</footer>

</main>
<!-- jQuery library js -->
<script src="{{ asset('theme/admin/assets/js/lib/jquery-3.7.1.min.js') }}" type="text/javascript"></script>
<!-- Bootstrap js -->
<script src="{{ asset('theme/admin/assets/js/lib/bootstrap.bundle.min.js') }}" type="text/javascript"></script>
<!-- Apex Chart js -->
<script src="{{ asset('theme/admin/assets/js/lib/apexcharts.min.js') }}" type="text/javascript"></script>
<!-- Data Table js -->
<script src="{{ asset('theme/admin/assets/js/lib/dataTables.min.js') }}" type="text/javascript"></script>
<!-- Iconify Font js -->
<script src="{{ asset('theme/admin/assets/js/lib/iconify-icon.min.js') }}" type="text/javascript"></script>
<!-- jQuery UI js -->
<script src="{{ asset('theme/admin/assets/js/lib/jquery-ui.min.js') }}" type="text/javascript"></script>
<!-- Vector Map js -->
<script src="{{ asset('theme/admin/assets/js/lib/jquery-jvectormap-2.0.5.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('theme/admin/assets/js/lib/jquery-jvectormap-world-mill-en.js') }}" type="text/javascript">
</script>
<!-- Popup js -->
<script src="{{ asset('theme/admin/assets/js/lib/magnifc-popup.min.js') }}" type="text/javascript"></script>
<!-- Slick Slider js -->
<script src="{{ asset('theme/admin/assets/js/lib/slick.min.js') }}" type="text/javascript"></script>
<!-- Prism js -->
<script src="{{ asset('theme/admin/assets/js/lib/prism.js') }}" type="text/javascript"></script>
<!-- File Upload js -->
<script src="{{ asset('theme/admin/assets/js/lib/file-upload.js') }}" type="text/javascript"></script>
<!-- Audio Player js -->
<script src="{{ asset('theme/admin/assets/js/lib/audioplayer.js') }}" type="text/javascript"></script>

<!-- Main js -->
<script src="{{ asset('theme/admin/assets/js/app.js') }}" type="text/javascript"></script>

{{-- sweetalert js --}}
<script src="{{ asset('theme/admin/assets/js/lib/sweetalert/sweetalerts.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('theme/admin/assets/js/lib/sweetalert/sweetalert2.all.min.js') }}" type="text/javascript">
</script>

{{-- select tow js --}}
<script src="{{ asset('theme/admin/assets/js/lib/select2/js/select2.min.js') }}" type="text/javascript"></script>

{{-- ajax data table js --}}
<script src="{{ asset('theme/admin/assets/js/lib/ajaxdatatable/dataTables.bootstrap5.min.js') }}"
    type="text/javascript"></script>
{{-- <script src="{{asset('theme/admin/assets/js/lib/ajaxdatatable/jquery.dataTables.min.js')}}" type="text/javascript"></script> --}}
{{-- <script src="{{asset('theme/admin/assets/js/lib/ajaxdatatable/datatable.js')}}" type="text/javascript"></script> --}}

<!-- DataTables core + Bootstrap 5 skin Buttons extension (CSS + JS)-->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">
<!-- Buttons features -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>
{{-- <script src="/path/to/dist/js/spartan-multi-image-picker.min.js"></script>  --}}
<link rel="stylesheet" href="{{ asset('theme/admin/assets/css/image-uploader.min.css') }}">
<script src="{{ asset('theme/admin/assets/plugins/fileupload/image-uploader.min.js') }}" type="text/javascript">
</script>
<script src="{{ asset('theme/admin/assets/plugins/fileupload/spartan-multi-image-picker-min.js') }}"
    type="text/javascript"></script>


{{-- Global Form submit Modal  --}}
<div class="modal fade" id="AjaxModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content">


        </div>
    </div>
</div>

{{-- Global Ajax View Modal --}}
<div class="modal fade" id="AjaxViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            {{-- AJAX content will be loaded here --}}
        </div>
    </div>
</div>

{{-- Secondary Nested Modal (For Quick Add while inside another modal, e.g. Add Institution) --}}
<div class="modal fade" id="secondaryAjaxModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content radius-16 border-0 shadow-lg">
            {{-- Secondary AJAX content will be loaded here --}}
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   Global Modal Scroll & Viewport Stabilization Fix
   Ensures modals scroll smoothly, never freeze or clip off-screen
   ========================================================================== */
.modal {
    overflow-x: hidden !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
}

/* Fix Bootstrap modal-dialog-centered vertical clipping bug on dynamic content */
.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - (var(--bs-modal-margin, 1.75rem) * 2));
}

.modal-dialog-centered::before {
    display: block;
    height: calc(100vh - (var(--bs-modal-margin, 1.75rem) * 2));
    height: -webkit-min-content;
    height: -moz-min-content;
    height: min-content;
    content: "";
}

.modal-dialog-centered .modal-content {
    margin: auto;
}

/* Guard against vertical clipping on smaller laptop screens or tall dynamic modals */
@media (max-height: 860px) {
    .modal-dialog-centered {
        align-items: flex-start !important;
        margin-top: 1.5rem !important;
        margin-bottom: 1.5rem !important;
    }
}

/* Maintain body scroll lock when modal is open */
body.modal-open {
    overflow: hidden !important;
}

#secondaryAjaxModal .modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.22);
}
#secondaryAjaxModal .select2-container .select2-selection--single {
    height: 44px !important;
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    display: flex !important;
    align-items: center !important;
}
#secondaryAjaxModal .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 42px !important;
    padding-left: 14px !important;
    font-size: 14px !important;
    color: var(--text-primary-light, #212529) !important;
}
#secondaryAjaxModal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 42px !important;
    right: 12px !important;
}
#secondaryAjaxModal .select2-dropdown {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    z-index: 1075 !important;
}
</style>
