<div class="modal-header py-16 px-24 border-0" data-modal-key="daily-visit-create">
    <h5 class="modal-title">Add Daily Visit</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
    <form id="dailyVisitCreateForm" action="{{ route('daily-visit.store') }}" method="post" data-ajax="true">
        @csrf

        <div class="row">

            <div class="col-md-6 mb-20">
                <label class="form-label text-sm mb-8">
                    Marketing Representative <span class="text-danger">*</span>
                </label>

                <select class="form-control form-control-sm js-s2-ajax" name="user_id" id="user"
                    data-url="{{ route('marketing-representative.marketing-representatives.select2') }}"
                    data-placeholder="Select Marketing Representative">
                </select>

                <div class="invalid-feedback d-block user_id-error" style="display:none"></div>
            </div>

            <div class="col-md-6 mb-20">
                <label class="form-label text-sm mb-8">
                    Type <span class="text-danger">*</span>
                </label>

                <select class="form-control form-control-sm" name="type" id="type">
                    <option value="">Select Type</option>
                    <option value="teacher">Teacher</option>
                    <option value="library">Library</option>
                </select>

                <div class="invalid-feedback d-block type-error" style="display:none"></div>
            </div>

            {{-- Teacher Selection --}}
            <div class="col-md-6 mb-20 d-none" id="teacher_div">

                <div class="d-flex align-items-center justify-content-between mb-8">
                    <label class="form-label text-sm mb-0">
                        Teacher <span class="text-danger">*</span>
                    </label>

                    <button type="button"
                        class="btn btn-primary btn-xs px-10 py-4 radius-6 d-inline-flex align-items-center gap-1 AjaxModal"
                        data-ajax-modal="{{ route('teacher.teachers.createModal') }}" data-size="lg"
                        data-onload="DailyVisitIndex.onTeacherModalLoad"
                        data-onsuccess="DailyVisitIndex.onTeacherCreated">
                        <iconify-icon icon="ic:baseline-plus" class="text-md"></iconify-icon>
                        <span>Add Teacher</span>
                    </button>
                </div>

                <select class="form-control form-control-sm js-s2-ajax w-100" name="visitable_id" id="teacher"
                    data-url="{{ route('teacher.teachers.select2') }}" data-placeholder="Select Teacher" disabled>
                </select>

                <div class="invalid-feedback d-block visitable_id-error" style="display:none"></div>
            </div>

            {{-- Library Selection --}}
            <div class="col-md-6 mb-20 d-none" id="library_div">

                <div class="d-flex align-items-center justify-content-between mb-8">
                    <label class="form-label text-sm mb-0">
                        Library <span class="text-danger">*</span>
                    </label>

                    <button type="button"
                        class="btn btn-primary btn-xs px-10 py-4 radius-6 d-inline-flex align-items-center gap-1 AjaxModal"
                        data-ajax-modal="{{ route('library.libraries.createModal') }}" data-size="lg"
                        data-onload="DailyVisitIndex.onLibraryModalLoad"
                        data-onsuccess="DailyVisitIndex.onLibraryCreated">
                        <iconify-icon icon="ic:baseline-plus" class="text-md"></iconify-icon>
                        <span>Add Library</span>
                    </button>
                </div>

                <select class="form-control form-control-sm js-s2-ajax w-100" name="visitable_id" id="library"
                    data-url="{{ route('library.libraries.select2') }}" data-placeholder="Select Library" disabled>
                </select>

                <div class="invalid-feedback d-block visitable_id-error" style="display:none"></div>
            </div>

            <div class="col-12 mb-20">
                <label class="form-label text-sm mb-8">Note</label>
                <textarea name="note" rows="4" class="form-control radius-8" placeholder="Write note..."></textarea>
                <div class="invalid-feedback d-block note-error" style="display:none"></div>
            </div>

        </div>

        <div class="d-flex align-items-center justify-content-center gap-3 mt-16">

            <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8"
                data-bs-dismiss="modal">
                Cancel
            </button>

            <button type="submit" class="btn btn-primary px-48 py-12 radius-8">
                Save
            </button>

        </div>
    </form>
</div>

{{-- <script>
    window.S2 && window.S2.auto && window.S2.auto();

    $('#type').on('change', function() {
        const type = $(this).val();

        $('#teacher_div, #library_div').addClass('d-none');
        $('#teacher, #library').prop('disabled', true);

        if (type === 'teacher') {

            $('#teacher_div').removeClass('d-none');
            $('#teacher').prop('disabled', false);

        } else if (type === 'library') {

            $('#library_div').removeClass('d-none');
            $('#library').prop('disabled', false);

        }
    });
</script> --}}


<script>
    window.S2 && window.S2.auto && window.S2.auto();

    $('#type').off('change').on('change', function() {
        const type = $(this).val();

        $('#teacher_div, #library_div').addClass('d-none');
        $('#teacher, #library').prop('disabled', true);

        if (type === 'teacher') {
            $('#teacher_div').removeClass('d-none');
            $('#teacher').prop('disabled', false);
        } else if (type === 'library') {
            $('#library_div').removeClass('d-none');
            $('#library').prop('disabled', false);
        }
    });

    // --- Nested Modal Back/Restore Logic ---
    (function() {
        const $modalContent = $('#dailyVisitCreateForm').closest('.modal-content');
        const $modal = $modalContent.closest('.modal');

        // Jokhon + Add Teacher ba + Add Library button-e click kora hobe
        $('#dailyVisitCreateForm').find('.AjaxModal').off('click.nestedModal').on('click.nestedModal', function() {
            const selectedUserOpt = $('#user').find('option:selected').clone();
            const selectedType = $('#type').val();
            const currentNote = $('textarea[name="note"]').val();

            // Select2 temporarily destroy kora jate clean HTML save hoy
            $('#dailyVisitCreateForm .js-s2-ajax').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            // Main modal-er HTML backup rakha
            const savedDailyVisitHtml = $modalContent.html();

            // Child modal-e Cancel ba Close (X) click korle modal close atkiye ager form restore kora
            $modal.off('hide.bs.modal.nestedModal').on('hide.bs.modal.nestedModal', function(e) {
                const isDailyVisitModal = $modalContent.find(
                    '[data-modal-key="daily-visit-create"]').length > 0;

                // Jodi ekhon child modal (Add Teacher / Add Library) open thake
                if (!isDailyVisitModal) {
                    e.preventDefault(); // Puro modal kete jawa bondho korbe
                    $modal.off('hide.bs.modal.nestedModal');

                    // Ager Add Daily Visit modal ফিরিয়ে আনা
                    $modalContent.html(savedDailyVisitHtml);

                    // Select kora previous value gulo abar boshiye dewa
                    if (selectedUserOpt.length && selectedUserOpt.val()) {
                        $('#user').append(selectedUserOpt).val(selectedUserOpt.val());
                    }
                    $('#type').val(selectedType).trigger('change');
                    $('textarea[name="note"]').val(currentNote);

                    // Select2 re-initialize kora
                    window.S2 && window.S2.auto && window.S2.auto();
                }
            });
        });
    })();
</script>
