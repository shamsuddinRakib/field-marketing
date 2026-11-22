<div class="modal-header py-16 px-24 border-0" data-modal-key="teacher-import">
    <h5 class="modal-title d-flex align-items-center gap-2">
        <iconify-icon icon="lucide:file-spreadsheet" class="text-success text-2xl"></iconify-icon>
        Bulk Import Teachers
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24" id="teacherImportContainer">
    <style>
        #teacherImportContainer .dropzone-box {
            border: 2px dashed #94a3b8 !important;
            background-color: #f8fafc !important;
            border-radius: 12px;
            padding: 32px 20px;
            text-align: center;
            transition: all 0.2s ease-in-out;
            position: relative;
            cursor: pointer;
        }

        #teacherImportContainer .dropzone-box:hover,
        #teacherImportContainer .dropzone-box.dragover {
            border-color: #4361ee !important;
            background-color: #f0f4ff !important;
        }

        #teacherImportContainer .dropzone-box.has-file {
            border: 2px dashed #16a34a !important;
            background-color: #f0fdf4 !important;
            padding: 22px 20px !important;
        }

        #teacherImportContainer .dropzone-box.has-file:hover,
        #teacherImportContainer .dropzone-box.has-file.dragover {
            border-color: #15803d !important;
            background-color: #dcfce7 !important;
        }

        #teacherImportContainer .dropzone-title {
            color: #0f172a !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            margin-bottom: 6px !important;
        }

        #teacherImportContainer .dropzone-subtitle {
            color: #475569 !important;
            font-size: 13px !important;
            margin-bottom: 12px !important;
        }

        #teacherImportContainer .policy-pill {
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

        #teacherImportContainer .report-tab-btn {
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

        #teacherImportContainer .report-tab-btn.active {
            color: #4361ee;
            border-bottom-color: #4361ee;
            background: #eff6ff;
            border-radius: 6px 6px 0 0;
        }

        #teacherImportContainer .report-scroll-table {
            max-height: 240px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        #teacherImportContainer .report-scroll-table table thead th {
            position: sticky;
            top: 0;
            background-color: #f8fafc;
            z-index: 2;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        #teacherImportContainer .btn-adjust-columns {
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
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        }

        #teacherImportContainer .btn-adjust-columns:hover,
        #teacherImportContainer .btn-adjust-columns:focus {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06) !important;
        }

        #teacherImportContainer .btn-adjust-columns.active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            border-color: #93c5fd !important;
        }

        #teacherImportContainer .btn-adjust-columns.active:hover {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
            border-color: #60a5fa !important;
        }

        /* Sticky Action Footer to guarantee buttons are always visible on any viewport */
        #teacherImportContainer #teacherModalFooterActions {
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
            <div
                class="w-36-px h-36-px rounded-circle bg-info-100 text-info-600 d-flex align-items-center justify-content-center flex-shrink-0 mt-2">
                <iconify-icon icon="lucide:info" class="text-xl"></iconify-icon>
            </div>
            <div class="flex-grow-1">
                <h6 class="text-sm fw-semibold mb-6" style="color: #0f172a;">Excel / CSV শিক্ষক তথ্য ফরম্যাট সংক্রান্ত
                    নির্দেশিকা</h6>
                <p class="text-xs text-secondary mb-8 leading-relaxed">
                    এক্সেল ফাইলে কলামের ক্রম সম্পূর্ণ ফ্লেক্সিবল। শিক্ষক এর নাম ও ফোন নম্বর মূল ফিল্ড হিসেবে
                    স্বয়ংক্রিয়ভাবে সংরক্ষিত হবে। যেকোনো কলাম হেডিং যেকোনো অর্ডারে থাকলে স্বয়ংক্রিয়ভাবে ডিটেক্ট হবে:
                </p>
                <div class="d-flex flex-wrap gap-2 mb-8">
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Teacher Name *:</strong> <code>NAME</code>, <code>Teacher
                            Name</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Phone Number *:</strong> <code>PHONE</code>, <code>Mobile</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Institution:</strong> <code>INSTITUTE NAME</code>,
                        <code>Institution</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Subject:</strong> <code>SUBJECT</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Class:</strong> <code>CLASS</code>
                    </span>
                    <span class="badge bg-white text-dark border px-10 py-6 text-xs">
                        <strong class="text-primary">Location:</strong> <code>DISTRICT</code>, <code>UPAZILLA</code>,
                        <code>ADDRESS</code>
                    </span>
                </div>
                <div class="text-xs text-muted">
                    <iconify-icon icon="lucide:check-circle-2" class="text-success me-1"></iconify-icon>
                    <strong>টিপস:</strong> ফোন নম্বরে হাইফেন বা স্পেস থাকলে স্বয়ংক্রিয়ভাবে ক্লিন হবে। কোনো রোতে
                    প্রতিষ্ঠান না থাকলে নিচে থেকে ডিফল্ট প্রতিষ্ঠান বেছে নিতে পারেন অথবা সরাসরি <code>+ Add
                        Institution</code> থেকে নতুন প্রতিষ্ঠান তৈরি করতে পারবেন।
                </div>
            </div>
        </div>
    </div>

    {{-- File Upload Dropzone Area --}}
    <div id="teacherImportUploadSection">
        <div class="dropzone-box mb-20" id="teacherDropzoneBox">
            <input type="file" id="teacherImportFileInput" accept=".xlsx, .xls, .csv"
                class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" style="z-index: 5;">

            {{-- Default State (Before File Selection) --}}
            <div id="teacherDropzoneDefaultState">
                <div class="mb-12">
                    <iconify-icon icon="solar:cloud-upload-bold-duotone"
                        style="font-size: 52px; color: #4361ee; line-height: 1;"></iconify-icon>
                </div>
                <div class="dropzone-title">Choose or Drag & Drop your Excel / CSV file here</div>
                <div class="dropzone-subtitle">Supports Microsoft Excel (<code>.xlsx</code>, <code>.xls</code>) and
                    <code>.csv</code> files.
                </div>
                <div class="policy-pill">
                    <iconify-icon icon="lucide:check-circle" style="font-size: 15px; color: #0284c7;"></iconify-icon>
                    Duplicate Policy: Existing teachers with the same phone will be automatically skipped
                </div>
            </div>

            {{-- File Selected State (Directly inside the Dropzone) --}}
            <div id="teacherDropzoneFileState" class="d-none">
                <div class="mb-10">
                    <iconify-icon icon="solar:file-spreadsheet-bold-duotone"
                        style="font-size: 52px; color: #16a34a; line-height: 1;"></iconify-icon>
                </div>
                <div
                    class="dropzone-title text-success d-flex align-items-center justify-content-center gap-2 mb-2 flex-wrap">
                    <iconify-icon icon="lucide:check-circle-2" class="text-success text-xl"></iconify-icon>
                    <span id="teacherDropzoneFileName" class="text-dark fw-bold">file.xlsx</span>
                    <span id="teacherDropzoneFileSize" class="text-secondary fw-normal text-xs">(0 KB)</span>
                </div>
                <div class="dropzone-subtitle mb-8 text-success-700 fw-semibold text-xs">
                    <iconify-icon icon="lucide:check" class="me-1"></iconify-icon>File attached & verified
                </div>
                <div>
                    <span class="badge bg-white text-secondary border px-12 py-6 radius-6 shadow-sm">
                        <iconify-icon icon="lucide:refresh-cw" class="text-primary me-1"></iconify-icon> Click or drop
                        another file here to change
                    </span>
                </div>
            </div>
        </div>

        {{-- File Selected Preview --}}
        <div id="teacherFileSelectedInfo"
            class="d-none alert alert-light-success border border-success-200 radius-8 p-16 mb-20">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-12">
                <div class="d-flex align-items-center gap-2">
                    <iconify-icon icon="lucide:file-spreadsheet" class="text-success text-xl"></iconify-icon>
                    <div>
                        <span class="text-xs text-muted">Selected File:</span>
                        <span class="fw-bold text-sm text-dark ms-1" id="teacherSelectedFileName">file.xlsx</span>
                        <span class="text-xs text-secondary ms-2" id="teacherSelectedFileSize">(0 KB)</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn-adjust-columns active" id="teacherBtnToggleMapping">
                        <iconify-icon icon="lucide:sliders-horizontal" class="text-sm"></iconify-icon>
                        <span id="teacherBtnToggleMappingText">Hide Columns</span>
                    </button>
                    <span class="badge bg-success-600 text-white px-12 py-6 radius-6"
                        id="teacherDetectedRowCountBadge">0 rows detected</span>
                </div>
            </div>

            {{-- Column Mapping Card --}}
            <div class="card border border-neutral-200 radius-8 mb-12 shadow-none bg-white" id="teacherMappingCard">
                <div
                    class="card-header bg-neutral-50 py-8 px-12 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <iconify-icon icon="lucide:columns" class="text-primary text-base"></iconify-icon>
                        <span class="text-xs fw-bold text-dark text-uppercase">Detected Column Mapping</span>
                    </div>
                    <span class="text-xs text-muted">Auto-mapped. You can adjust if needed:</span>
                </div>
                <div class="card-body p-12">
                    <div class="row g-2" id="teacherColumnMappingRow"></div>
                </div>
            </div>

            {{-- Table Preview of First 4 Rows --}}
            <div class="table-responsive bg-white rounded border border-neutral-200 mb-0">
                <table class="table table-sm table-striped text-xs mb-0" id="teacherPreviewTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Teacher Name</th>
                            <th>Phone</th>
                            <th>Institution</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Upazila</th>
                            <th>District</th>
                            <th>Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="teacherPreviewTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Progress & Status Section (Shown during / after import) --}}
    <div id="teacherImportProgressSection" class="d-none">
        <div class="p-20 bg-neutral-50 radius-12 border border-neutral-200 mb-20">
            <div class="d-flex align-items-center justify-content-between mb-8">
                <div>
                    <span class="fw-semibold text-sm" id="teacherProgressStatusText">Starting import...</span>
                    <div class="text-xs text-muted mt-1 d-flex align-items-center gap-1">
                        <iconify-icon icon="lucide:file-spreadsheet" class="text-success"></iconify-icon>
                        Importing File: <strong class="text-dark" id="teacherProgressFileName">file.xlsx</strong>
                    </div>
                </div>
                <span class="fw-bold text-primary-600 text-sm" id="teacherProgressPercentText">0%</span>
            </div>

            {{-- Bootstrap Progress Bar --}}
            <div class="progress mb-16 radius-8" style="height: 18px;">
                <div id="teacherImportProgressBar"
                    class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar"
                    style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            {{-- Real-time Counters --}}
            <div class="row g-2 text-center mb-16">
                <div class="col-4">
                    <div class="p-12 bg-white rounded border border-success-200">
                        <div class="text-xs text-secondary mb-1">Inserted</div>
                        <div class="text-xl fw-bold text-success" id="teacherCounterInserted">0</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-12 bg-white rounded border border-warning-200">
                        <div class="text-xs text-secondary mb-1">Skipped (Duplicate Phone)</div>
                        <div class="text-xl fw-bold text-warning" id="teacherCounterSkipped">0</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-12 bg-white rounded border border-danger-200">
                        <div class="text-xs text-secondary mb-1">Errors</div>
                        <div class="text-xl fw-bold text-danger" id="teacherCounterErrors">0</div>
                    </div>
                </div>
            </div>

            {{-- Post-Import Breakdown Tabs Section (Appears after import finishes) --}}
            <div id="teacherImportReportContainer" class="d-none mt-20 pt-16 border-top border-neutral-200">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-12">
                    <div class="d-flex align-items-center gap-1 border-bottom border-neutral-200 w-auto"
                        id="teacherReportTabs">
                        <button type="button" class="report-tab-btn active" data-target="#teacherTabSkippedPane">
                            Skipped (<span id="teacherTabSkippedCount">0</span>)
                        </button>
                        <button type="button" class="report-tab-btn" data-target="#teacherTabErrorsPane">
                            Errors (<span id="teacherTabErrorsCount">0</span>)
                        </button>
                        <button type="button" class="report-tab-btn" data-target="#teacherTabInsertedPane">
                            Inserted (<span id="teacherTabInsertedCount">0</span>)
                        </button>
                    </div>
                    <button type="button"
                        class="btn btn-sm btn-outline-secondary radius-8 d-flex align-items-center gap-1 py-6 px-12"
                        id="teacherBtnDownloadLog">
                        <iconify-icon icon="lucide:download" class="text-sm"></iconify-icon>
                        Download Log (.csv)
                    </button>
                </div>

                {{-- Tab Panes --}}
                <div class="report-tab-content">
                    {{-- 1. Skipped Pane --}}
                    <div class="report-pane" id="teacherTabSkippedPane">
                        <div class="report-scroll-table">
                            <table class="table table-sm table-hover text-xs mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 55px;">Row</th>
                                        <th>Teacher Name</th>
                                        <th>Phone</th>
                                        <th>Institution</th>
                                        <th>Reason / Note</th>
                                    </tr>
                                </thead>
                                <tbody id="teacherTableSkippedBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 2. Errors Pane --}}
                    <div class="report-pane d-none" id="teacherTabErrorsPane">
                        <div class="report-scroll-table">
                            <table class="table table-sm table-hover text-xs mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 55px;">Row</th>
                                        <th>Teacher Name</th>
                                        <th>Phone</th>
                                        <th>Error Message</th>
                                    </tr>
                                </thead>
                                <tbody id="teacherTableErrorsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Inserted Pane --}}
                    <div class="report-pane d-none" id="teacherTabInsertedPane">
                        <div class="report-scroll-table">
                            <table class="table table-sm table-hover text-xs mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 55px;">Row</th>
                                        <th>Teacher Name</th>
                                        <th>Phone</th>
                                        <th>Institution</th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Upazila</th>
                                        <th>District</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="teacherTableInsertedBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Footer Actions --}}
    <div class="d-flex align-items-center justify-content-between gap-3 pt-12 border-top border-neutral-200"
        id="teacherModalFooterActions">
        <div>
            <button type="button"
                class="btn btn-sm btn-outline-secondary px-16 py-8 radius-8 d-none align-items-center gap-1"
                id="teacherBtnUploadAnother">
                <iconify-icon icon="lucide:refresh-cw" class="text-base"></iconify-icon>
                Import Another File
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn border border-neutral-300 text-secondary px-24 py-10 radius-8"
                data-bs-dismiss="modal" id="teacherBtnCancel">Cancel</button>
            <button type="button" class="btn btn-primary px-32 py-10 radius-8 d-flex align-items-center gap-2"
                id="teacherBtnStartImport" disabled>
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
        const $container = $('#teacherImportContainer');
        const $fileInput = $container.find('#teacherImportFileInput');
        const $dropzoneBox = $container.find('#teacherDropzoneBox');
        const $fileSelectedInfo = $container.find('#teacherFileSelectedInfo');
        const $btnStartImport = $container.find('#teacherBtnStartImport');
        const $btnCancel = $container.find('#teacherBtnCancel');
        const $btnUploadAnother = $container.find('#teacherBtnUploadAnother');
        const $btnDownloadLog = $container.find('#teacherBtnDownloadLog');
        const $progressSection = $container.find('#teacherImportProgressSection');
        const $uploadSection = $container.find('#teacherImportUploadSection');
        const $progressBar = $container.find('#teacherImportProgressBar');
        const $progressStatusText = $container.find('#teacherProgressStatusText');
        const $progressPercentText = $container.find('#teacherProgressPercentText');
        const $counterInserted = $container.find('#teacherCounterInserted');
        const $counterSkipped = $container.find('#teacherCounterSkipped');
        const $counterErrors = $container.find('#teacherCounterErrors');
        const $importReportContainer = $container.find('#teacherImportReportContainer');

        let parsedRows = [];
        let isImporting = false;
        let allInsertedItems = [];
        let allSkippedItems = [];
        let allErrors = [];
        const CHUNK_SIZE = 25;

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
            $container.find('#teacherSelectedFileName').text(file.name);
            $container.find('#teacherSelectedFileSize').text(sizeFormatted);
            $container.find('#teacherDropzoneFileName').text(file.name);
            $container.find('#teacherDropzoneFileSize').text(sizeFormatted);
            $container.find('#teacherProgressFileName').text(file.name);

            $dropzoneBox.addClass('has-file');
            $container.find('#teacherDropzoneDefaultState').addClass('d-none');
            $container.find('#teacherDropzoneFileState').removeClass('d-none');

            const reader = new FileReader();
            const extension = file.name.split('.').pop().toLowerCase();

            reader.onload = function(evt) {
                try {
                    if (typeof XLSX !== 'undefined') {
                        const data = new Uint8Array(evt.target.result);
                        const workbook = XLSX.read(data, {
                            type: 'array'
                        });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const rawJson = XLSX.utils.sheet_to_json(worksheet, {
                            header: 1,
                            defval: ''
                        });
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
                    $container.find('#teacherDropzoneDefaultState').removeClass('d-none');
                    $container.find('#teacherDropzoneFileState').addClass('d-none');
                }
            };

            reader.readAsArrayBuffer(file);
        });

        let currentSheetRows = [];
        let currentHeaderRowIndex = -1;
        let currentDetectedColumns = [];
        let currentMapping = {};

        const FIELD_DEFINITIONS = [{
                key: 'teacher_name',
                label: 'Teacher Name *',
                required: true,
                aliases: [
                    'name', 'teacher_name', 'teacher', 'name_of_teacher', 'teachers_name',
                    'নাম', 'শিক্ষক', 'শিক্ষকের_নাম', 'শিক্ষক_নাম'
                ]
            },
            {
                key: 'phone',
                label: 'Phone Number *',
                required: true,
                aliases: [
                    'phone', 'phone_number', 'mobile', 'cell', 'contact', 'mobile_no',
                    'ফোন', 'মোবাইল', 'মুঠোফোন', 'যোগাযোগ'
                ]
            },
            {
                key: 'institution',
                label: 'Institution Name',
                required: false,
                aliases: [
                    'institute_name', 'institute', 'institution', 'institution_name', 'school',
                    'college', 'academy', 'madrasa', 'coaching', 'batch', 'প্রতিষ্ঠানের_নাম', 'প্রতিষ্ঠান',
                    'স্কুল', 'কলেজ', 'কোচিং', 'ব্যাচ'
                ]
            },
            {
                key: 'subject',
                label: 'Subject',
                required: false,
                aliases: [
                    'subject', 'subjects', 'subject_name', 'course', 'বিষয়', 'বিষয়', 'সাবজেক্ট'
                ]
            },
            {
                key: 'class_name',
                label: 'Class',
                required: false,
                aliases: [
                    'class', 'class_name', 'grade', 'শ্রেণী', 'শ্রেণি', 'ক্লাস'
                ]
            },
            {
                key: 'designation',
                label: 'Designation',
                required: false,
                aliases: [
                    'designation', 'post', 'title', 'role', 'পদবী', 'পদবি', 'পদ'
                ]
            },
            {
                key: 'department',
                label: 'Department',
                required: false,
                aliases: [
                    'department', 'dept', 'বিভাগ'
                ]
            },
            {
                key: 'district',
                label: 'District',
                required: false,
                aliases: [
                    'district', 'district_name', 'dist', 'zilla', 'zila', 'জেলা', 'জেলার_নাম'
                ]
            },
            {
                key: 'upazila',
                label: 'Upazila',
                required: false,
                aliases: [
                    'upazilla', 'upazila', 'thana', 'sub_district', 'upazila_name', 'উপজেলা', 'থানা'
                ]
            },
            {
                key: 'address',
                label: 'Address',
                required: false,
                aliases: [
                    'address', 'location', 'village', 'road', 'ঠিকানা', 'অবস্থান'
                ]
            },
            {
                key: 'status',
                label: 'Status',
                required: false,
                aliases: [
                    'status', 'state', 'condition', 'স্ট্যাটাস'
                ]
            }
        ];
        // Toggle column mapping card
        $container.on('click', '#teacherBtnToggleMapping', function() {
            const $card = $container.find('#teacherMappingCard');
            const $btn = $(this);
            if ($card.hasClass('d-none')) {
                $card.removeClass('d-none');
                $btn.addClass('active');
                $('#teacherBtnToggleMappingText').text('Hide Columns');
            } else {
                $card.addClass('d-none');
                $btn.removeClass('active');
                $('#teacherBtnToggleMappingText').text('Adjust Columns');
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
                    const s = String(cell || '').trim().toLowerCase().replace(/[^a-z0-9\u0980-\u09FF]/g,
                        '_');
                    if (s === '') return;
                    for (const def of FIELD_DEFINITIONS) {
                        if (def.aliases.some(alias => s === alias || s.includes(alias) || alias.includes(
                                s))) {
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
            rawHeaders.forEach((val, idx) => {
                const strVal = String(val || '').trim();
                if (strVal !== '') {
                    const clean = strVal.toLowerCase().replace(/[^a-z0-9\u0980-\u09FF]/g, '_');
                    currentDetectedColumns.push({
                        index: idx,
                        name: strVal,
                        clean: clean,
                        letter: getExcelColLetter(idx)
                    });
                }
            });

            // If no headers detected, create default A, B, C...
            if (currentDetectedColumns.length === 0) {
                const maxCols = Math.max(...sheetRows.map(r => (r || []).length));
                for (let c = 0; c < Math.min(maxCols, 26); c++) {
                    currentDetectedColumns.push({
                        index: c,
                        name: 'Column ' + getExcelColLetter(c),
                        clean: 'col_' + c,
                        letter: getExcelColLetter(c)
                    });
                }
            }

            // Auto-match fields to columns
            currentMapping = {};
            FIELD_DEFINITIONS.forEach(def => {
                let matched = -1;
                // 1. Exact match
                for (const col of currentDetectedColumns) {
                    if (def.aliases.some(alias => col.clean === alias)) {
                        matched = col.index;
                        break;
                    }
                }
                // 2. Partial match
                if (matched === -1) {
                    for (const col of currentDetectedColumns) {
                        if (def.aliases.some(alias => col.clean === alias || col.clean.includes(alias) ||
                                alias.includes(col.clean))) {
                            matched = col.index;
                            break;
                        }
                    }
                }
                currentMapping[def.key] = matched;
            });

            renderColumnMappingControls();

            rebuildParsedRowsAndPreview();
        }

        function renderColumnMappingControls() {
            const $row = $container.find('#teacherColumnMappingRow').empty();

            FIELD_DEFINITIONS.forEach(def => {
                const currentSelected = currentMapping[def.key];
                let optionsHtml = `<option value="-1">— Not in Sheet —</option>`;
                currentDetectedColumns.forEach(col => {
                    const isSel = (col.index === currentSelected);
                    optionsHtml +=
                        `<option value="${col.index}" ${isSel ? 'selected' : ''}>${escapeHtml(col.name)} (${col.letter})</option>`;
                });

                const isMatched = (currentSelected !== -1 && currentSelected !== undefined);
                const badgeHtml = isMatched ?
                    '<span class="text-success" style="font-size: 10px;"><iconify-icon icon="lucide:check"></iconify-icon> Detected</span>' :
                    (def.required ? '<span class="text-danger" style="font-size: 10px;">Required</span>' :
                        '');

                $row.append(`
                <div class="col-md-6 col-sm-12 mb-2">
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
                rebuildParsedRowsAndPreview();
            });
        }

        function cleanPhoneJs(num) {
            if (!num) return '';
            let s = String(num).replace(/[^\d+]/g, '').trim();
            if (s.startsWith('00880')) {
                s = '0' + s.substring(5);
            } else if (s.startsWith('+880')) {
                s = '0' + s.substring(4);
            } else if (s.startsWith('880')) {
                s = '0' + s.substring(3);
            } else if (s.length === 10 && s.startsWith('1')) {
                s = '0' + s;
            }
            return s;
        }

        function rebuildParsedRowsAndPreview() {
            parsedRows = [];
            const nameIdx = currentMapping.teacher_name;
            const phoneIdx = currentMapping.phone;
            const instIdx = currentMapping.institution;
            const subjectIdx = currentMapping.subject;
            const classIdx = currentMapping.class_name;
            const districtIdx = currentMapping.district;
            const upazilaIdx = currentMapping.upazila;
            const addressIdx = currentMapping.address;
            const designationIdx = currentMapping.designation;
            const deptIdx = currentMapping.department;
            const statusIdx = currentMapping.status;

            for (let r = currentHeaderRowIndex + 1; r < currentSheetRows.length; r++) {
                const row = currentSheetRows[r] || [];
                if (!row || row.length === 0) continue;

                const nameVal = (nameIdx !== -1 && nameIdx !== undefined) ? String(row[nameIdx] || '').trim() : '';
                const rawPhone = (phoneIdx !== -1 && phoneIdx !== undefined) ? String(row[phoneIdx] || '').trim() :
                    '';
                const phoneVal = cleanPhoneJs(rawPhone);

                // Skip row if name is empty or phone is empty (e.g. section subheaders like "জামালপুর সদর" without phone)
                if (!nameVal || !phoneVal) {
                    continue;
                }

                const instVal = (instIdx !== -1 && instIdx !== undefined) ? String(row[instIdx] || '').trim() : '';
                const subjectVal = (subjectIdx !== -1 && subjectIdx !== undefined) ? String(row[subjectIdx] || '')
                    .trim() : '';
                const classVal = (classIdx !== -1 && classIdx !== undefined) ? String(row[classIdx] || '').trim() :
                    '';
                const districtVal = (districtIdx !== -1 && districtIdx !== undefined) ? String(row[districtIdx] ||
                    '').trim() : '';
                const upazilaVal = (upazilaIdx !== -1 && upazilaIdx !== undefined) ? String(row[upazilaIdx] || '')
                    .trim() : '';
                const addressVal = (addressIdx !== -1 && addressIdx !== undefined) ? String(row[addressIdx] || '')
                    .trim() : '';
                const designationVal = (designationIdx !== -1 && designationIdx !== undefined) ? String(row[
                    designationIdx] || '').trim() : '';
                const deptVal = (deptIdx !== -1 && deptIdx !== undefined) ? String(row[deptIdx] || '').trim() : '';
                const statusVal = (statusIdx !== -1 && statusIdx !== undefined) ? String(row[statusIdx] || '')
                .trim() : '';

                parsedRows.push({
                    row_num: r + 1,
                    teacher_name: nameVal || (phoneVal ? 'Teacher ' + phoneVal.slice(-4) : ''),
                    phone: phoneVal,
                    institution: instVal,
                    subject: subjectVal,
                    class_name: classVal,
                    district: districtVal,
                    upazila: upazilaVal,
                    address: addressVal,
                    designation: designationVal,
                    department: deptVal,
                    status: statusVal
                });
            }

            if (parsedRows.length === 0) {
                $btnStartImport.prop('disabled', true);
                $dropzoneBox.removeClass('has-file');
                $container.find('#teacherDropzoneDefaultState').removeClass('d-none');
                $container.find('#teacherDropzoneFileState').addClass('d-none');
                $fileSelectedInfo.addClass('d-none');
                alert('No valid teacher rows found. Please check column mapping.');
                return;
            }

            // Render Preview Table (first 4 rows)
            const $tbody = $container.find('#teacherPreviewTableBody').empty();
            parsedRows.slice(0, 4).forEach((item, idx) => {
                $tbody.append(`
                <tr>
                    <td>${idx + 1}</td>
                    <td class="fw-semibold text-dark">${escapeHtml(item.teacher_name || '-')}</td>
                    <td><span class="badge bg-light text-primary border">${escapeHtml(item.phone || '-')}</span></td>
                    <td>${escapeHtml(item.institution || '-')}</td>
                    <td>${escapeHtml(item.subject || '-')}</td>
                    <td>${escapeHtml(item.class_name || '-')}</td>
                    <td>${escapeHtml(item.upazila || '-')}</td>
                    <td>${escapeHtml(item.district || '-')}</td>
                    <td>${escapeHtml(item.address || '-')}</td>
                    <td><span class="badge bg-success-focus text-success-600 border border-success-main px-8 py-2 radius-4 text-xs">Ready</span></td>
                </tr>
            `);
            });

            $container.find('#teacherDetectedRowCountBadge').text(parsedRows.length + ' rows detected');
            $fileSelectedInfo.removeClass('d-none');
            $btnStartImport.prop('disabled', (nameIdx === -1 && phoneIdx === -1));
        }

        // Tab Switcher for Post-Import Report
        $container.on('click', '.report-tab-btn', function() {
            const targetId = $(this).data('target');
            $container.find('.report-tab-btn').removeClass('active');
            $(this).addClass('active');
            $container.find('.report-pane').addClass('d-none');
            $container.find(targetId).removeClass('d-none');
        });

        // Start Batch Import
        $btnStartImport.on('click', async function() {
            if (isImporting || parsedRows.length === 0) return;

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
                        url: "{{ route('teacher.teachers.importChunk') }}",
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
                        phone: '-',
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
            $progressStatusText.html(
                '<span class="text-success fw-bold"><iconify-icon icon="lucide:check-circle"></iconify-icon> Import Completed!</span>'
            );

            // Render Breakdown Tables
            renderReportBreakdown();

            // Reveal Report Container
            $importReportContainer.removeClass('d-none');

            // Footer Buttons Update
            $btnCancel.prop('disabled', false).text('Done & Close').addClass('btn-primary text-white')
                .removeClass('border border-neutral-300 text-secondary');
            $btnStartImport.addClass('d-none');
            $btnUploadAnother.removeClass('d-none').addClass('d-inline-flex');

            // Automatically reload DataTable
            if ($.fn.DataTable && $('#teachersTable').length) {
                $('#teachersTable').DataTable().ajax.reload(null, false);
            }

            if (window.Swal) {
                Swal.fire({
                    icon: totalInserted > 0 ? 'success' : (allErrors.length > 0 ? 'warning' :
                        'info'),
                    title: 'Import Processed!',
                    text: `${totalInserted} teachers inserted. ${totalSkipped} duplicate phones skipped.`,
                    confirmButtonText: 'View Details'
                });
            }
        });

        function renderReportBreakdown() {
            $container.find('#teacherTabSkippedCount').text(allSkippedItems.length);
            $container.find('#teacherTabErrorsCount').text(allErrors.length);
            $container.find('#teacherTabInsertedCount').text(allInsertedItems.length);

            // 1. Skipped Table
            const $skippedTbody = $container.find('#teacherTableSkippedBody').empty();
            if (allSkippedItems.length === 0) {
                $skippedTbody.html(
                    '<tr><td colspan="5" class="text-center text-muted py-12">No duplicate teachers were skipped.</td></tr>'
                );
            } else {
                allSkippedItems.forEach(item => {
                    $skippedTbody.append(`
                    <tr>
                        <td class="text-secondary fw-semibold">#${item.row || '-'}</td>
                        <td class="fw-medium text-dark">${escapeHtml(item.name || '-')}</td>
                        <td><span class="badge bg-light text-primary border">${escapeHtml(item.phone || '-')}</span></td>
                        <td>${escapeHtml(item.institution || '-')}</td>
                        <td><span class="badge bg-warning-50 text-warning-700 border border-warning-200">${escapeHtml(item.reason || 'Duplicate skipped')}</span></td>
                    </tr>
                `);
                });
            }

            // 2. Errors Table
            const $errorsTbody = $container.find('#teacherTableErrorsBody').empty();
            if (allErrors.length === 0) {
                $errorsTbody.html(
                    '<tr><td colspan="4" class="text-center text-muted py-12">No errors encountered.</td></tr>');
            } else {
                allErrors.forEach(item => {
                    $errorsTbody.append(`
                    <tr>
                        <td class="text-danger fw-semibold">#${item.row || '-'}</td>
                        <td class="fw-medium text-dark">${escapeHtml(item.name || '-')}</td>
                        <td>${escapeHtml(item.phone || '-')}</td>
                        <td class="text-danger">${escapeHtml(item.message || '-')}</td>
                    </tr>
                `);
                });
            }

            // 3. Inserted Table
            const $insertedTbody = $container.find('#teacherTableInsertedBody').empty();
            if (allInsertedItems.length === 0) {
                $insertedTbody.html(
                    '<tr><td colspan="10" class="text-center text-muted py-12">No new teachers were inserted.</td></tr>'
                );
            } else {
                allInsertedItems.slice(0, 100).forEach(item => {
                    $insertedTbody.append(`
                    <tr>
                        <td class="text-secondary fw-semibold">#${item.row || '-'}</td>
                        <td class="fw-medium text-dark">${escapeHtml(item.name || '-')}</td>
                        <td><span class="badge bg-light text-success border">${escapeHtml(item.phone || '-')}</span></td>
                        <td>${escapeHtml(item.institution || '-')}</td>
                        <td>${escapeHtml(item.subject || '-')}</td>
                        <td>${escapeHtml(item.class || '-')}</td>
                        <td>${escapeHtml(item.upazila || '-')}</td>
                        <td>${escapeHtml(item.district || '-')}</td>
                        <td>${escapeHtml(item.address || '-')}</td>
                        <td><span class="badge bg-success-focus text-success-600 border border-success-main px-8 py-2 radius-4 text-xs">Inserted</span></td>
                    </tr>
                `);
                });
                if (allInsertedItems.length > 100) {
                    $insertedTbody.append(`
                    <tr>
                        <td colspan="10" class="text-center text-muted py-6">
                            ... and ${allInsertedItems.length - 100} more inserted rows (download CSV log to view all).
                        </td>
                    </tr>
                `);
                }
            }

            // Default active tab
            if (allSkippedItems.length > 0) {
                $container.find('.report-tab-btn[data-target="#teacherTabSkippedPane"]').trigger('click');
            } else if (allErrors.length > 0) {
                $container.find('.report-tab-btn[data-target="#teacherTabErrorsPane"]').trigger('click');
            } else {
                $container.find('.report-tab-btn[data-target="#teacherTabInsertedPane"]').trigger('click');
            }
        }

        // Download Log CSV button click
        $btnDownloadLog.on('click', function() {
            let csv =
                '\uFEFFRow Number,Teacher Name,Phone,Institution,Subject,Class,Upazila,District,Address,Status,Details / Reason\n';

            allErrors.forEach(err => {
                const row = err.row || '-';
                const name = (err.name || '').replace(/"/g, '""');
                const phone = (err.phone || '').replace(/"/g, '""');
                const msg = (err.message || String(err)).replace(/"/g, '""');
                csv += `"${row}","${name}","${phone}","","","","","","","Error","${msg}"\n`;
            });

            allSkippedItems.forEach(item => {
                const row = item.row || '-';
                const name = (item.name || '').replace(/"/g, '""');
                const phone = (item.phone || '').replace(/"/g, '""');
                const inst = (item.institution || '').replace(/"/g, '""');
                const subj = (item.subject || '').replace(/"/g, '""');
                const cls = (item.class || '').replace(/"/g, '""');
                const reason = (item.reason || 'Duplicate skipped').replace(/"/g, '""');
                csv +=
                    `"${row}","${name}","${phone}","${inst}","${subj}","${cls}","","","","Skipped","${reason}"\n`;
            });

            allInsertedItems.forEach(item => {
                const row = item.row || '-';
                const name = (item.name || '').replace(/"/g, '""');
                const phone = (item.phone || '').replace(/"/g, '""');
                const inst = (item.institution || '').replace(/"/g, '""');
                const subj = (item.subject || '').replace(/"/g, '""');
                const cls = (item.class || '').replace(/"/g, '""');
                const upz = (item.upazila || '').replace(/"/g, '""');
                const dist = (item.district || '').replace(/"/g, '""');
                const addr = (item.address || '').replace(/"/g, '""');
                csv +=
                    `"${row}","${name}","${phone}","${inst}","${subj}","${cls}","${upz}","${dist}","${addr}","Inserted",""\n`;
            });

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download',
                `teacher_import_log_${new Date().toISOString().slice(0, 10)}.csv`);
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
            $container.find('#teacherDropzoneDefaultState').removeClass('d-none');
            $container.find('#teacherDropzoneFileState').addClass('d-none');
            $container.find('#teacherPreviewTableBody').empty();
            $container.find('#teacherColumnMappingRow').empty();
            $container.find('#teacherMappingCard').removeClass('d-none');
            $container.find('#teacherBtnToggleMapping').addClass('active');
            $container.find('#teacherBtnToggleMappingText').text('Hide Columns');
            $fileSelectedInfo.addClass('d-none');
            $progressSection.addClass('d-none');
            $uploadSection.removeClass('d-none');
            $importReportContainer.addClass('d-none');

            $counterInserted.text('0');
            $counterSkipped.text('0');
            $counterErrors.text('0');
            $progressBar.css('width', '0%').addClass(
                'progress-bar-animated progress-bar-striped bg-primary').removeClass('bg-success');
            $progressPercentText.text('0%');
            $progressStatusText.text('Starting import...');

            $btnStartImport.removeClass('d-none').prop('disabled', true);
            $btnCancel.text('Cancel').removeClass('btn-primary text-white').addClass(
                'border border-neutral-300 text-secondary').prop('disabled', false);
            $btnUploadAnother.addClass('d-none').removeClass('d-inline-flex');
        });

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/[&<>"']/g, function(m) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                } [m];
            });
        }

        function getExcelColLetter(colIdx) {
            let temp, letter = '';
            while (colIdx >= 0) {
                temp = (colIdx) % 26;
                letter = String.fromCharCode(temp + 65) + letter;
                colIdx = Math.floor((colIdx) / 26) - 1;
            }
            return letter;
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
