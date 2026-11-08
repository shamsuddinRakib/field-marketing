<div class="modal-header py-16 px-24 border-0" data-modal-key="institution-import">
    <h5 class="modal-title d-flex align-items-center gap-2">
        <iconify-icon icon="lucide:file-spreadsheet" class="text-success text-2xl"></iconify-icon>
        Bulk Import Institutions
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24" id="institutionImportContainer">
    <style>
        #institutionImportContainer .dropzone-box {
            border: 2px dashed #94a3b8 !important;
            background-color: #f8fafc !important;
            border-radius: 12px;
            padding: 32px 20px;
            text-align: center;
            transition: all 0.2s ease-in-out;
            position: relative;
            cursor: pointer;
        }
        #institutionImportContainer .dropzone-box:hover,
        #institutionImportContainer .dropzone-box.dragover {
            border-color: #4361ee !important;
            background-color: #f0f4ff !important;
        }
        #institutionImportContainer .dropzone-box.has-file {
            border: 2px dashed #16a34a !important;
            background-color: #f0fdf4 !important;
            padding: 22px 20px !important;
        }
        #institutionImportContainer .dropzone-box.has-file:hover,
        #institutionImportContainer .dropzone-box.has-file.dragover {
            border-color: #15803d !important;
            background-color: #dcfce7 !important;
        }
        #institutionImportContainer .dropzone-title {
            color: #0f172a !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            margin-bottom: 6px !important;
        }
        #institutionImportContainer .dropzone-subtitle {
            color: #475569 !important;
            font-size: 13px !important;
            margin-bottom: 12px !important;
        }
        #institutionImportContainer .policy-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #e0f2fe;
            color: #0369a1;
            font-size: 12px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid #bae6fd;
        }
        #institutionImportContainer .report-tab-btn {
            border: none;
            background: transparent;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
        }
        #institutionImportContainer .report-tab-btn.active {
            color: #4361ee;
            border-bottom-color: #4361ee;
            background: #eff6ff;
            border-radius: 6px 6px 0 0;
        }
        #institutionImportContainer .report-scroll-table {
            max-height: 240px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        #institutionImportContainer .report-scroll-table table thead th {
            position: sticky;
            top: 0;
            background-color: #f8fafc;
            z-index: 2;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        #institutionImportContainer .btn-adjust-columns {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            background-color: #ffffff !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            padding: 5px 12px !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            transition: all 0.2s ease-in-out !important;
            cursor: pointer !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04) !important;
        }
        #institutionImportContainer .btn-adjust-columns:hover,
        #institutionImportContainer .btn-adjust-columns:focus {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06) !important;
        }
        #institutionImportContainer .btn-adjust-columns.active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            border-color: #93c5fd !important;
        }
        #institutionImportContainer .btn-adjust-columns.active:hover {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
            border-color: #60a5fa !important;
        }

        /* Sticky Action Footer to guarantee buttons are always visible on any viewport */
        #institutionImportContainer #institutionModalFooterActions {
            position: sticky;
            bottom: -24px;
            background: #ffffff;
            z-index: 20;
            margin-left: -24px;
            margin-right: -24px;
            margin-bottom: -24px;
            padding-left: 24px;
            padding-right: 24px;
            padding-top: 14px;
            padding-bottom: 16px;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.04);
        }
    </style>

    {{-- Note on Accepted Excel Heading / Format --}}
    <div class="alert alert-light-info border border-info-200 radius-8 p-16 mb-20">
        <div class="d-flex align-items-start gap-3">
            <div class="w-36-px h-36-px rounded-circle bg-info-100 text-info-600 d-flex align-items-center justify-content-center flex-shrink-0 mt-2">
                <iconify-icon icon="lucide:info" class="text-xl"></iconify-icon>
            </div>
            <div class="flex-grow-1">
                <h6 class="text-sm fw-semibold mb-6" style="color: #0f172a;">Excel / CSV ফাইল ফরম্যাট সংক্রান্ত তথ্য ও নির্দেশনা</h6>
                <p class="text-xs text-secondary mb-8 leading-relaxed">
                    এক্সেল ফাইলে কলামের নাম ও ক্রমানুসার সম্পূর্ণ ফ্লেক্সিবল। নিচের মূল হেডিংগুলো স্বয়ংক্রিয়ভাবে ডিটেক্ট হবে (যেকোনো ক্রমে):
                </p>
                <div class="d-flex flex-wrap gap-2 mb-8">
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">EIIN / Code:</strong> <code>EIIN</code>, <code>Code</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Institution Name:</strong> <code>INSTITUTION_NAME</code>, <code>Name</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">District:</strong> <code>DISTRICT</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Upazila:</strong> <code>UPAZILA</code>, <code>Thana</code>
                    </span>
                    <span class="badge bg-white text-muted border px-10 py-6 text-xs">
                        <em class="text-secondary">Optional:</em> <code>Division</code>, <code>Address</code>, <code>Email</code>
                    </span>
                </div>
                <div class="text-xs text-muted">
                    <iconify-icon icon="lucide:check-circle-2" class="text-success me-1"></iconify-icon>
                    <strong>টিপস:</strong> এক্সেল ফাইলে কোনো কলাম আগে বা পরে থাকলে অথবা খালি রো বা কলাম থাকলেও স্বয়ংক্রিয়ভাবে ডিটেক্ট হবে। ফাইল আপলোড করার পর যেকোনো কলামের ম্যাপিং পরিবর্তন বা ফিক্স করা যাবে।
                </div>
            </div>
        </div>
    </div>

    {{-- File Upload Dropzone Area --}}
    <div id="importUploadSection">
        <div class="dropzone-box mb-20" id="dropzoneBox">
            <input type="file" id="importFileInput" accept=".xlsx, .xls, .csv" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" style="z-index: 5;">
            
            {{-- Default State (Before File Selection) --}}
            <div id="dropzoneDefaultState">
                <div class="mb-12">
                    <iconify-icon icon="solar:cloud-upload-bold-duotone" style="font-size: 52px; color: #4361ee; line-height: 1;"></iconify-icon>
                </div>
                <div class="dropzone-title">Choose or Drag & Drop your Excel / CSV file here</div>
                <div class="dropzone-subtitle">Supports Microsoft Excel (<code>.xlsx</code>, <code>.xls</code>) and <code>.csv</code> files.</div>
                <div class="policy-pill">
                    <iconify-icon icon="lucide:check-circle" style="font-size: 15px; color: #0284c7;"></iconify-icon>
                    Duplicate Policy: Existing institutions will be automatically skipped
                </div>
            </div>

            {{-- File Selected State (Directly inside the Dropzone) --}}
            <div id="dropzoneFileState" class="d-none">
                <div class="mb-10">
                    <iconify-icon icon="solar:file-spreadsheet-bold-duotone" style="font-size: 52px; color: #16a34a; line-height: 1;"></iconify-icon>
                </div>
                <div class="dropzone-title text-success d-flex align-items-center justify-content-center gap-2 mb-2 flex-wrap">
                    <iconify-icon icon="lucide:check-circle-2" class="text-success text-xl"></iconify-icon>
                    <span id="dropzoneFileName" class="text-dark fw-bold">file.xlsx</span>
                    <span id="dropzoneFileSize" class="text-secondary fw-normal text-xs">(0 KB)</span>
                </div>
                <div class="dropzone-subtitle mb-8 text-success-700 fw-semibold text-xs">
                    <iconify-icon icon="lucide:check" class="me-1"></iconify-icon>File attached & verified
                </div>
                <div>
                    <span class="badge bg-white text-secondary border px-12 py-6 radius-6 shadow-sm">
                        <iconify-icon icon="lucide:refresh-cw" class="text-primary me-1"></iconify-icon> Click or drop another file here to change
                    </span>
                </div>
            </div>
        </div>

        {{-- File Selected Preview --}}
        <div id="fileSelectedInfo" class="d-none alert alert-light-success border border-success-200 radius-8 p-16 mb-20">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-12">
                <div class="d-flex align-items-center gap-2">
                    <iconify-icon icon="lucide:file-spreadsheet" class="text-success text-xl"></iconify-icon>
                    <div>
                        <span class="text-xs text-muted">Selected File:</span>
                        <span class="fw-bold text-sm text-dark ms-1" id="selectedFileName">file.xlsx</span>
                        <span class="text-xs text-secondary ms-2" id="selectedFileSize">(0 KB)</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn-adjust-columns active" id="btnToggleMapping">
                        <iconify-icon icon="lucide:sliders-horizontal" class="text-sm"></iconify-icon>
                        <span id="btnToggleMappingText">Hide Columns</span>
                    </button>
                    <span class="badge bg-success-600 text-white px-12 py-6 radius-6" id="detectedRowCountBadge">0 rows detected</span>
                </div>
            </div>

            {{-- Column Mapping Card --}}
            <div class="card border border-neutral-200 radius-8 mb-12 shadow-none bg-white" id="mappingCard">
                <div class="card-header bg-neutral-50 py-8 px-12 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <iconify-icon icon="lucide:columns" class="text-primary text-base"></iconify-icon>
                        <span class="text-xs fw-bold text-dark text-uppercase">Detected Column Mapping</span>
                    </div>
                    <span class="text-xs text-muted">Auto-mapped. You can adjust if needed:</span>
                </div>
                <div class="card-body p-12">
                    <div class="row g-2" id="columnMappingRow"></div>
                </div>
            </div>

            {{-- Table Preview of First 4 Rows --}}
            <div class="table-responsive bg-white rounded border border-neutral-200 mb-0">
                <table class="table table-sm table-striped text-xs mb-0" id="previewTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>EIIN / Code</th>
                            <th>Institution Name</th>
                            <th>District</th>
                            <th>Upazila</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Progress & Status Section (Shown during / after import) --}}
    <div id="importProgressSection" class="d-none">
        <div class="p-20 bg-neutral-50 radius-12 border border-neutral-200 mb-20">
            <div class="d-flex align-items-center justify-content-between mb-8">
                <div>
                    <span class="fw-semibold text-sm" id="progressStatusText">Starting import...</span>
                    <div class="text-xs text-muted mt-1 d-flex align-items-center gap-1">
                        <iconify-icon icon="lucide:file-spreadsheet" class="text-success"></iconify-icon>
                        Importing File: <strong class="text-dark" id="progressFileName">file.xlsx</strong>
                    </div>
                </div>
                <span class="fw-bold text-primary-600 text-sm" id="progressPercentText">0%</span>
            </div>

            {{-- Bootstrap Progress Bar --}}
            <div class="progress mb-16 radius-8" style="height: 18px;">
                <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            {{-- Real-time Counters --}}
            <div class="row g-2 text-center mb-16">
                <div class="col-4">
                    <div class="p-12 bg-white rounded border border-success-200">
                        <div class="text-xs text-secondary mb-1">Inserted</div>
                        <div class="text-xl fw-bold text-success" id="counterInserted">0</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-12 bg-white rounded border border-warning-200">
                        <div class="text-xs text-secondary mb-1">Skipped (Duplicate)</div>
                        <div class="text-xl fw-bold text-warning" id="counterSkipped">0</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-12 bg-white rounded border border-danger-200">
                        <div class="text-xs text-secondary mb-1">Errors</div>
                        <div class="text-xl fw-bold text-danger" id="counterErrors">0</div>
                    </div>
                </div>
            </div>

            {{-- Post-Import Breakdown Tabs Section (Appears after import finishes) --}}
            <div id="importReportContainer" class="d-none mt-20 pt-16 border-top border-neutral-200">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-12">
                    <div class="d-flex align-items-center gap-1 border-bottom border-neutral-200 w-auto" id="reportTabs">
                        <button type="button" class="report-tab-btn active" data-target="#tabSkippedPane">
                            Skipped (<span id="tabSkippedCount">0</span>)
                        </button>
                        <button type="button" class="report-tab-btn" data-target="#tabErrorsPane">
                            Errors (<span id="tabErrorsCount">0</span>)
                        </button>
                        <button type="button" class="report-tab-btn" data-target="#tabInsertedPane">
                            Inserted (<span id="tabInsertedCount">0</span>)
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary radius-8 d-flex align-items-center gap-1 py-6 px-12" id="btnDownloadLog">
                        <iconify-icon icon="lucide:download" class="text-sm"></iconify-icon>
                        Download Log (.csv)
                    </button>
                </div>

                {{-- Tab Panes --}}
                <div class="report-tab-content">
                    {{-- 1. Skipped Pane --}}
                    <div class="report-pane" id="tabSkippedPane">
                        <div class="report-scroll-table">
                            <table class="table table-sm table-hover text-xs mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 55px;">Row</th>
                                        <th>Institution Name</th>
                                        <th>District</th>
                                        <th>Reason / Note</th>
                                    </tr>
                                </thead>
                                <tbody id="tableSkippedBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 2. Errors Pane --}}
                    <div class="report-pane d-none" id="tabErrorsPane">
                        <div class="report-scroll-table">
                            <table class="table table-sm table-hover text-xs mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 55px;">Row</th>
                                        <th>Institution Name</th>
                                        <th>Error Message</th>
                                    </tr>
                                </thead>
                                <tbody id="tableErrorsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Inserted Pane --}}
                    <div class="report-pane d-none" id="tabInsertedPane">
                        <div class="report-scroll-table">
                            <table class="table table-sm table-hover text-xs mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 55px;">Row</th>
                                        <th>Institution Name</th>
                                        <th>District</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="tableInsertedBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Footer Actions --}}
    <div class="d-flex align-items-center justify-content-between gap-3 pt-12 border-top border-neutral-200" id="modalFooterActions">
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary px-16 py-8 radius-8 d-none align-items-center gap-1" id="btnUploadAnother">
                <iconify-icon icon="lucide:refresh-cw" class="text-base"></iconify-icon>
                Import Another File
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn border border-neutral-300 text-secondary px-24 py-10 radius-8" data-bs-dismiss="modal" id="btnCancel">Cancel</button>
            <button type="button" class="btn btn-primary px-32 py-10 radius-8 d-flex align-items-center gap-2" id="btnStartImport" disabled>
                <iconify-icon icon="solar:upload-track-line-duotone" class="text-lg"></iconify-icon>
                Start Import
            </button>
        </div>
    </div>
</div>

{{-- SheetJS CDN --}}
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
(function() {
    const $container = $('#institutionImportContainer');
    const $fileInput = $container.find('#importFileInput');
    const $dropzoneBox = $container.find('#dropzoneBox');
    const $fileSelectedInfo = $container.find('#fileSelectedInfo');
    const $btnStartImport = $container.find('#btnStartImport');
    const $btnCancel = $container.find('#btnCancel');
    const $btnUploadAnother = $container.find('#btnUploadAnother');
    const $btnDownloadLog = $container.find('#btnDownloadLog');
    const $progressSection = $container.find('#importProgressSection');
    const $uploadSection = $container.find('#importUploadSection');
    const $progressBar = $container.find('#importProgressBar');
    const $progressStatusText = $container.find('#progressStatusText');
    const $progressPercentText = $container.find('#progressPercentText');
    const $counterInserted = $container.find('#counterInserted');
    const $counterSkipped = $container.find('#counterSkipped');
    const $counterErrors = $container.find('#counterErrors');
    const $importReportContainer = $container.find('#importReportContainer');

    let parsedRows = [];
    let isImporting = false;
    let allInsertedItems = [];
    let allSkippedItems = [];
    let allErrors = [];
    const CHUNK_SIZE = 25; // 25 rows per batch for smooth and fast progress updates

    // Drag and Drop styling
    $dropzoneBox.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    }).on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    // Handle File Selection
    $fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const sizeFormatted = '(' + (file.size / 1024).toFixed(1) + ' KB)';
        $container.find('#selectedFileName').text(file.name);
        $container.find('#selectedFileSize').text(sizeFormatted);
        $container.find('#dropzoneFileName').text(file.name);
        $container.find('#dropzoneFileSize').text(sizeFormatted);
        $container.find('#progressFileName').text(file.name);

        $dropzoneBox.addClass('has-file');
        $container.find('#dropzoneDefaultState').addClass('d-none');
        $container.find('#dropzoneFileState').removeClass('d-none');

        const reader = new FileReader();
        const extension = file.name.split('.').pop().toLowerCase();

        reader.onload = function(evt) {
            try {
                if (typeof XLSX !== 'undefined') {
                    const data = new Uint8Array(evt.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const rawJson = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
                    processRawRows(rawJson);
                } else if (extension === 'csv') {
                    const text = new TextDecoder('utf-8').decode(evt.target.result);
                    const rawJson = parseCSVFallback(text);
                    processRawRows(rawJson);
                } else {
                    alert('Please allow SheetJS to parse Excel files, or upload as .csv.');
                }
            } catch (err) {
                console.error(err);
                alert('Could not read the file: ' + err.message);
                $dropzoneBox.removeClass('has-file');
                $container.find('#dropzoneDefaultState').removeClass('d-none');
                $container.find('#dropzoneFileState').addClass('d-none');
            }
        };

        reader.readAsArrayBuffer(file);
    });

    let currentSheetRows = [];
    let currentHeaderRowIndex = -1;
    let currentDetectedColumns = [];
    let currentMapping = {};

    const FIELD_DEFINITIONS = [
        { key: 'code', label: 'EIIN / Code', required: false, aliases: [
            'eiin', 'eiin_no', 'eiin_number', 'eiin_code', 'code', 'institution_code', 
            'inst_code', 'school_code', 'college_code', 'id', 'কোড', 'ইআইআইএন'
        ]},
        { key: 'institution_name', label: 'Institution Name *', required: true, aliases: [
            'institution_name', 'institute_name', 'institution', 'institute', 'school_name', 
            'college_name', 'academy_name', 'madrasa_name', 'name_of_institution', 'name_of_institute', 
            'name', 'প্রতিষ্ঠানের_নাম', 'প্রতিষ্ঠান', 'নাম'
        ]},
        { key: 'district', label: 'District', required: false, aliases: [
            'district', 'district_name', 'dist', 'dist_name', 'zilla', 'zila', 'জেলা', 'জেলার_নাম'
        ]},
        { key: 'upazila', label: 'Upazila', required: false, aliases: [
            'upazila', 'upazilla', 'thana', 'sub_district', 'subdistrict', 'upazila_name', 'উপজেলা', 'থানা'
        ]},
        { key: 'division', label: 'Division', required: false, aliases: [
            'division', 'division_name', 'div', 'বিভাগ'
        ]},
        { key: 'address', label: 'Address', required: false, aliases: [
            'address', 'location', 'village', 'union', 'road', 'ঠিকানা', 'অবস্থান'
        ]},
        { key: 'email', label: 'Email', required: false, aliases: [
            'email', 'e_mail', 'email_address', 'mail', 'ইমেইল'
        ]},
        { key: 'status', label: 'Status', required: false, aliases: [
            'status', 'state', 'condition', 'স্ট্যাটাস'
        ]}
    ];

    // Toggle column mapping card
    $container.on('click', '#btnToggleMapping', function() {
        const $card = $container.find('#mappingCard');
        const $btn = $(this);
        if ($card.hasClass('d-none')) {
            $card.removeClass('d-none');
            $btn.addClass('active');
            $('#btnToggleMappingText').text('Hide Columns');
        } else {
            $card.addClass('d-none');
            $btn.removeClass('active');
            $('#btnToggleMappingText').text('Adjust Columns');
        }
    });

    // Detect header row, match columns, and render mapping
    function processRawRows(sheetRows) {
        if (!sheetRows || sheetRows.length < 2) {
            alert('The file is empty or does not have enough rows.');
            return;
        }

        currentSheetRows = sheetRows;

        // Find header row: scan first 15 rows with scoring algorithm
        let bestHeaderIndex = -1;
        let bestScore = 0;

        for (let i = 0; i < Math.min(sheetRows.length, 15); i++) {
            const row = sheetRows[i] || [];
            let score = 0;
            row.forEach(cell => {
                const s = String(cell || '').trim().toLowerCase().replace(/[^a-z0-9\u0980-\u09FF]/g, '_');
                if (s === '') return;
                for (const def of FIELD_DEFINITIONS) {
                    if (def.aliases.some(alias => s === alias || s.includes(alias) || alias.includes(s))) {
                        score++;
                        break;
                    }
                }
            });
            if (score > bestScore) {
                bestScore = score;
                bestHeaderIndex = i;
            }
        }

        currentHeaderRowIndex = (bestScore >= 2) ? bestHeaderIndex : 0;

        // Build list of non-empty columns in the header row
        currentDetectedColumns = [];
        const rawHeaders = sheetRows[currentHeaderRowIndex] || [];
        rawHeaders.forEach((cell, idx) => {
            const text = String(cell || '').trim();
            if (text !== '') {
                const colLetter = (typeof XLSX !== 'undefined' && XLSX.utils && XLSX.utils.encode_col) 
                    ? XLSX.utils.encode_col(idx) 
                    : ('Col ' + (idx + 1));
                currentDetectedColumns.push({
                    index: idx,
                    name: text,
                    letter: colLetter,
                    clean: text.toLowerCase().replace(/[^a-z0-9\u0980-\u09FF]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '')
                });
            }
        });

        // Auto-detect column mapping
        currentMapping = {};
        FIELD_DEFINITIONS.forEach(def => {
            let matched = -1;
            // 1. Exact alias match
            for (const col of currentDetectedColumns) {
                if (def.aliases.includes(col.clean)) {
                    matched = col.index;
                    break;
                }
            }
            // 2. Partial match if no exact match
            if (matched === -1) {
                for (const col of currentDetectedColumns) {
                    if (def.aliases.some(alias => col.clean === alias || col.clean.includes(alias) || alias.includes(col.clean))) {
                        matched = col.index;
                        break;
                    }
                }
            }
            currentMapping[def.key] = matched;
        });

        // Render Column Mapping Controls
        renderColumnMappingControls();

        // Parse rows and render preview
        rebuildParsedRowsAndPreview();
    }

    function renderColumnMappingControls() {
        const $row = $container.find('#columnMappingRow').empty();

        FIELD_DEFINITIONS.forEach(def => {
            const currentSelected = currentMapping[def.key];
            let optionsHtml = `<option value="-1">— Not in Sheet —</option>`;
            currentDetectedColumns.forEach(col => {
                const isSel = (col.index === currentSelected);
                optionsHtml += `<option value="${col.index}" ${isSel ? 'selected' : ''}>${escapeHtml(col.name)} (${col.letter})</option>`;
            });

            const isMatched = (currentSelected !== -1 && currentSelected !== undefined);
            const badgeHtml = isMatched 
                ? '<span class="text-success" style="font-size: 10px;"><iconify-icon icon="lucide:check"></iconify-icon> Detected</span>' 
                : (def.required ? '<span class="text-danger" style="font-size: 10px;">Required</span>' : '');

            $row.append(`
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="form-label text-xs fw-semibold mb-1 text-dark d-flex align-items-center justify-content-between">
                        <span>${escapeHtml(def.label)}</span>
                        ${badgeHtml}
                    </label>
                    <select class="form-select form-select-sm js-col-map ${isMatched ? 'border-primary-subtle' : (def.required ? 'border-danger-subtle bg-danger-subtle' : '')}" data-field="${def.key}">
                        ${optionsHtml}
                    </select>
                </div>
            `);
        });

        // Bind change event to column mapping dropdowns
        $container.off('change.colmap', '.js-col-map').on('change.colmap', '.js-col-map', function() {
            const fieldKey = $(this).data('field');
            const colIdx = parseInt($(this).val(), 10);
            currentMapping[fieldKey] = isNaN(colIdx) ? -1 : colIdx;
            
            // Re-render controls to refresh badges
            renderColumnMappingControls();
            
            // Rebuild rows with new mapping
            rebuildParsedRowsAndPreview();
        });
    }

    function rebuildParsedRowsAndPreview() {
        parsedRows = [];
        let currentDistrict = '';
        let currentUpazila = '';

        const nameIdx = currentMapping.institution_name;
        const codeIdx = currentMapping.code;
        const distIdx = currentMapping.district;
        const upaIdx = currentMapping.upazila;
        const divIdx = currentMapping.division;
        const addrIdx = currentMapping.address;
        const emailIdx = currentMapping.email;
        const statusIdx = currentMapping.status;

        for (let r = currentHeaderRowIndex + 1; r < currentSheetRows.length; r++) {
            const row = currentSheetRows[r] || [];
            const filledCells = row.map(c => String(c || '').trim()).filter(Boolean);
            if (filledCells.length === 0) continue;

            const rawInstName = (nameIdx !== -1 && row[nameIdx] !== undefined) ? String(row[nameIdx]).trim() : '';
            if (!rawInstName) continue;

            // Extract Code / EIIN
            let codeVal = (codeIdx !== -1 && row[codeIdx] !== undefined) ? String(row[codeIdx]).trim() : '';
            // If code ended with .0 (from Excel numeric formatting like 114751.0), clean it
            if (codeVal.endsWith('.0')) {
                codeVal = codeVal.slice(0, -2);
            }

            // Extract District & Upazila with fallback propagation
            let distVal = (distIdx !== -1 && row[distIdx] !== undefined) ? String(row[distIdx]).trim() : '';
            let upaVal = (upaIdx !== -1 && row[upaIdx] !== undefined) ? String(row[upaIdx]).trim() : '';

            if (distVal) currentDistrict = distVal;
            if (upaVal) currentUpazila = upaVal;

            if (!distVal && currentDistrict) distVal = currentDistrict;
            if (!upaVal && currentUpazila) upaVal = currentUpazila;

            let divVal = (divIdx !== -1 && row[divIdx] !== undefined) ? String(row[divIdx]).trim() : '';
            let addrVal = (addrIdx !== -1 && row[addrIdx] !== undefined) ? String(row[addrIdx]).trim() : '';

            let emailVal = '';
            if (emailIdx !== -1 && row[emailIdx] !== undefined) {
                const em = String(row[emailIdx]).trim();
                if (em.includes('@') && em.includes('.')) {
                    emailVal = em;
                }
            }

            let statusVal = (statusIdx !== -1 && row[statusIdx] !== undefined) ? String(row[statusIdx]).trim() : 'Active';

            parsedRows.push({
                row_num: r + 1,
                institution_name: rawInstName,
                code: codeVal,
                district: distVal,
                upazila: upaVal,
                division: divVal,
                address: addrVal,
                email: emailVal,
                status: statusVal || 'Active'
            });
        }

        if (parsedRows.length === 0) {
            $btnStartImport.prop('disabled', true);
            $dropzoneBox.removeClass('has-file');
            $container.find('#dropzoneDefaultState').removeClass('d-none');
            $container.find('#dropzoneFileState').addClass('d-none');
            $fileSelectedInfo.addClass('d-none');
            alert('No valid institution rows found. Please check column mapping.');
            return;
        }

        // Render Preview Table (first 4 rows)
        const $tbody = $container.find('#previewTableBody').empty();
        parsedRows.slice(0, 4).forEach((item, idx) => {
            $tbody.append(`
                <tr>
                    <td>${idx + 1}</td>
                    <td><span class="badge bg-light text-primary border">${escapeHtml(item.code || '-')}</span></td>
                    <td class="fw-semibold text-dark">${escapeHtml(item.institution_name)}</td>
                    <td>${escapeHtml(item.district || '-')}</td>
                    <td>${escapeHtml(item.upazila || '-')}</td>
                </tr>
            `);
        });

        $container.find('#detectedRowCountBadge').text(parsedRows.length + ' rows detected');
        $fileSelectedInfo.removeClass('d-none');
        $btnStartImport.prop('disabled', (nameIdx === -1));
    }

    // Tab Switcher for Post-Import Report
    $container.on('click', '.report-tab-btn', function() {
        const targetId = $(this).data('target');
        $container.find('.report-tab-btn').removeClass('active');
        $(this).addClass('active');
        $container.find('.report-pane').addClass('d-none');
        $container.find(targetId).removeClass('d-none');
    });

    // Start Import button click
    $btnStartImport.on('click', async function() {
        if (parsedRows.length === 0 || isImporting) return;

        isImporting = true;
        allInsertedItems = [];
        allSkippedItems = [];
        allErrors = [];

        $btnStartImport.prop('disabled', true);
        $btnCancel.prop('disabled', true);
        $uploadSection.addClass('d-none');
        $progressSection.removeClass('d-none');
        $importReportContainer.addClass('d-none');

        let totalInserted = 0;
        let totalSkipped = 0;
        const totalRows = parsedRows.length;
        const totalChunks = Math.ceil(totalRows / CHUNK_SIZE);

        for (let c = 0; c < totalChunks; c++) {
            const startIdx = c * CHUNK_SIZE;
            const chunkRows = parsedRows.slice(startIdx, startIdx + CHUNK_SIZE);
            const currentProcessedCount = Math.min(startIdx + chunkRows.length, totalRows);
            const percent = Math.round((currentProcessedCount / totalRows) * 100);

            // Update UI status
            $progressStatusText.text(`Processing ${currentProcessedCount} of ${totalRows} rows...`);
            $progressPercentText.text(percent + '%');
            $progressBar.css('width', percent + '%').attr('aria-valuenow', percent);

            try {
                const response = await $.ajax({
                    url: "{{ route('institution.institutions.importChunk') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        rows: chunkRows
                    }
                });

                if (response && response.ok) {
                    totalInserted += (response.inserted || 0);
                    totalSkipped += (response.skipped || 0);

                    if (response.inserted_items && response.inserted_items.length) {
                        allInsertedItems = allInsertedItems.concat(response.inserted_items);
                    }
                    if (response.skipped_items && response.skipped_items.length) {
                        allSkippedItems = allSkippedItems.concat(response.skipped_items);
                    }
                    if (response.errors && response.errors.length) {
                        allErrors = allErrors.concat(response.errors);
                    }
                }
            } catch (err) {
                console.error(err);
                allErrors.push({
                    row: `Batch ${c + 1}`,
                    name: 'Network/Server Error',
                    message: err.responseJSON?.message || err.statusText || 'Request failed'
                });
            }

            // Update live numbers
            $counterInserted.text(totalInserted);
            $counterSkipped.text(totalSkipped);
            $counterErrors.text(allErrors.length);

            // Small delay for smooth animation visual
            await new Promise(r => setTimeout(r, 50));
        }

        // Import Finished!
        $progressBar.removeClass('progress-bar-animated progress-bar-striped')
                    .addClass('bg-success')
                    .css('width', '100%');
        $progressPercentText.text('100%');
        $progressStatusText.html('<span class="text-success fw-bold"><iconify-icon icon="lucide:check-circle"></iconify-icon> Import Completed!</span>');

        // Render Breakdown Tables
        renderReportBreakdown();

        // Reveal Report Container
        $importReportContainer.removeClass('d-none');

        // Footer Buttons Update
        $btnCancel.prop('disabled', false).text('Done & Close').addClass('btn-primary text-white').removeClass('border border-neutral-300 text-secondary');
        $btnStartImport.addClass('d-none');
        $btnUploadAnother.removeClass('d-none').addClass('d-inline-flex');

        // Automatically reload DataTable
        if ($.fn.DataTable && $('#institutionsTable').length) {
            $('#institutionsTable').DataTable().ajax.reload(null, false);
        }

        if (window.Swal) {
            Swal.fire({
                icon: totalInserted > 0 ? 'success' : (allErrors.length > 0 ? 'warning' : 'info'),
                title: 'Import Processed!',
                text: `${totalInserted} new institutions inserted. ${totalSkipped} duplicates skipped.`,
                confirmButtonText: 'View Details'
            });
        }
    });

    // Populate the Post-Import Report Tables
    function renderReportBreakdown() {
        $container.find('#tabSkippedCount').text(allSkippedItems.length);
        $container.find('#tabErrorsCount').text(allErrors.length);
        $container.find('#tabInsertedCount').text(allInsertedItems.length);

        // 1. Populate Skipped Table
        const $skippedTbody = $container.find('#tableSkippedBody').empty();
        if (allSkippedItems.length === 0) {
            $skippedTbody.append('<tr><td colspan="4" class="text-center py-16 text-muted">No institutions were skipped.</td></tr>');
        } else {
            allSkippedItems.forEach(item => {
                $skippedTbody.append(`
                    <tr>
                        <td class="text-secondary fw-semibold">#${item.row}</td>
                        <td class="fw-semibold text-dark">${escapeHtml(item.name || '-')}</td>
                        <td>${escapeHtml(item.district || '-')}</td>
                        <td><span class="badge bg-warning-subtle text-warning-main border border-warning-200">${escapeHtml(item.reason || 'Duplicate skipped')}</span></td>
                    </tr>
                `);
            });
        }

        // 2. Populate Errors Table
        const $errorsTbody = $container.find('#tableErrorsBody').empty();
        if (allErrors.length === 0) {
            $errorsTbody.append('<tr><td colspan="3" class="text-center py-16 text-success"><iconify-icon icon="lucide:check-circle" class="text-base me-1"></iconify-icon>No errors encountered! All rows were processed cleanly.</td></tr>');
        } else {
            allErrors.forEach(err => {
                const row = err.row || '-';
                const name = err.name || '-';
                const msg = err.message || (typeof err === 'string' ? err : 'Unknown error');
                $errorsTbody.append(`
                    <tr>
                        <td class="text-danger fw-semibold">#${row}</td>
                        <td class="fw-semibold text-dark">${escapeHtml(name)}</td>
                        <td class="text-danger">${escapeHtml(msg)}</td>
                    </tr>
                `);
            });
        }

        // 3. Populate Inserted Table
        const $insertedTbody = $container.find('#tableInsertedBody').empty();
        if (allInsertedItems.length === 0) {
            $insertedTbody.append('<tr><td colspan="4" class="text-center py-16 text-muted">No new institutions were added (all existing or empty).</td></tr>');
        } else {
            allInsertedItems.forEach(item => {
                $insertedTbody.append(`
                    <tr>
                        <td class="text-success fw-semibold">#${item.row}</td>
                        <td class="fw-semibold text-dark">${escapeHtml(item.name || '-')}</td>
                        <td>${escapeHtml(item.district || '-')}</td>
                        <td><span class="badge bg-success-subtle text-success-main border border-success-200">Inserted</span></td>
                    </tr>
                `);
            });
        }

        // Default active tab: If skipped > 0, activate skipped. Else if errors > 0, activate errors. Else inserted.
        if (allSkippedItems.length > 0) {
            $container.find('.report-tab-btn[data-target="#tabSkippedPane"]').trigger('click');
        } else if (allErrors.length > 0) {
            $container.find('.report-tab-btn[data-target="#tabErrorsPane"]').trigger('click');
        } else {
            $container.find('.report-tab-btn[data-target="#tabInsertedPane"]').trigger('click');
        }
    }

    // Download Log CSV button click
    $btnDownloadLog.on('click', function() {
        let csv = '\uFEFFRow Number,Institution Name,District,Status,Details / Reason\n';

        allErrors.forEach(err => {
            const row = err.row || '-';
            const name = (err.name || '').replace(/"/g, '""');
            const msg = (err.message || String(err)).replace(/"/g, '""');
            csv += `"${row}","${name}","","Error","${msg}"\n`;
        });

        allSkippedItems.forEach(item => {
            const row = item.row || '-';
            const name = (item.name || '').replace(/"/g, '""');
            const dist = (item.district || '').replace(/"/g, '""');
            const reason = (item.reason || 'Duplicate skipped').replace(/"/g, '""');
            csv += `"${row}","${name}","${dist}","Skipped (Duplicate)","${reason}"\n`;
        });

        allInsertedItems.forEach(item => {
            const row = item.row || '-';
            const name = (item.name || '').replace(/"/g, '""');
            const dist = (item.district || '').replace(/"/g, '""');
            csv += `"${row}","${name}","${dist}","Inserted","Successfully saved in database"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `institution_import_log_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });

    // Import Another File button click: resets back to initial upload step
    $btnUploadAnother.on('click', function() {
        isImporting = false;
        parsedRows = [];
        currentSheetRows = [];
        currentHeaderRowIndex = -1;
        currentDetectedColumns = [];
        currentMapping = {};
        allInsertedItems = [];
        allSkippedItems = [];
        allErrors = [];

        $fileInput.val('');
        $dropzoneBox.removeClass('has-file');
        $container.find('#dropzoneDefaultState').removeClass('d-none');
        $container.find('#dropzoneFileState').addClass('d-none');
        $container.find('#previewTableBody').empty();
        $container.find('#columnMappingRow').empty();
        $container.find('#mappingCard').removeClass('d-none');
        $container.find('#btnToggleMapping').addClass('active');
        $container.find('#btnToggleMappingText').text('Hide Columns');
        $fileSelectedInfo.addClass('d-none');
        $progressSection.addClass('d-none');
        $uploadSection.removeClass('d-none');
        $importReportContainer.addClass('d-none');

        $counterInserted.text('0');
        $counterSkipped.text('0');
        $counterErrors.text('0');
        $progressBar.css('width', '0%').addClass('progress-bar-animated progress-bar-striped bg-primary').removeClass('bg-success');
        $progressPercentText.text('0%');
        $progressStatusText.text('Starting import...');

        $btnStartImport.removeClass('d-none').prop('disabled', true);
        $btnCancel.text('Cancel').removeClass('btn-primary text-white').addClass('border border-neutral-300 text-secondary').prop('disabled', false);
        $btnUploadAnother.addClass('d-none').removeClass('d-inline-flex');
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function parseCSVFallback(text) {
        const lines = text.split(/\r\n|\n/);
        return lines.map(line => {
            const row = [];
            let inQuotes = false;
            let current = '';
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                if (char === '"') {
                    inQuotes = !inQuotes;
                } else if (char === ',' && !inQuotes) {
                    row.push(current);
                    current = '';
                } else {
                    current += char;
                }
            }
            row.push(current);
            return row;
        });
    }
})();
</script>