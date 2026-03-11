<?php
// filepath: c:\xampp\htdocs\enroll\registrar\prospectus.php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
</head>
<body>
<div class="wrapper">
    <?php include_once DOMAIN_PATH . '/global/sidebar.php';?>
    <div class="main-panel">
        <?php include_once DOMAIN_PATH . '/global/header.php';?>
        <div class="container">
            <section class="card m-2 border">
                <header class="card-header bg-primary text-white rounded-2 rounded-bottom-0" 
                    style="padding:0.75rem; padding-left:1.25em; padding-bottom:0.5rem;">
                    <label class="fs-2 text-white fw-bolder">Prospectus</label>
                </header>
                <div class="card-body pt-1" style="padding-right: 0.5rem;padding-left: 0.5rem;">
                    <div class="row mb-2 align-items-center">
                        <div class="col-md-6">
                            <label for="curriculumSelect" class="form-label text-black fw-bold">Curriculum</label>
                            <select id="curriculumSelect">
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="requiredUnits" class="form-label text-black fw-bold">Required Units</label>
                            <input type="number" id="requiredUnits" class="form-control" placeholder="Units">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="summary-box bg-success-subtle rounded-2 p-2">
                                <div class="small text-muted">Total Encoded Units</div>
                                <div id="encodedUnitsDisplay" class="fs-4 fw-semibold">0.00</div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="summary-box bg-info-subtle rounded-2 p-2">
                                <div class="small text-muted">Required Units</div>
                                <div id="requiredUnitsDisplay" class="fs-4 fw-semibold">0.00</div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="summary-box bg-warning-subtle rounded-2 p-2">
                                <div class="small text-muted">Units Gap (Required - Encoded)</div>
                                <div id="unitsGapDisplay" class="fs-4 fw-semibold">0.00</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">

                    <div id="prospectusBlocksViewport" class="border-0">
                        <div id="prospectusBlocks" class="d-flex flex-column gap-4"></div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button id="saveProspectusBtn" type="button" class="btn btn-success">
                            <i class="bi bi-save me-1"></i> Save Prospectus
                        </button>
                    </div>
                </div>
            </section>
        </div>
        <?php include_once FOOTER_PATH; ?>
    </div>
</div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const curriculumSelect = document.getElementById('curriculumSelect');
    const requiredUnitsInput = document.getElementById('requiredUnits');
    const blocksContainer = document.getElementById('prospectusBlocks');
    const encodedUnitsDisplay = document.getElementById('encodedUnitsDisplay');
    const requiredUnitsDisplay = document.getElementById('requiredUnitsDisplay');
    const unitsGapDisplay = document.getElementById('unitsGapDisplay');

    if (!curriculumSelect || !requiredUnitsInput || !blocksContainer) return;

    const YEARS = [
        { key: 1, label: 'FIRST YEAR', optional: false },
        { key: 2, label: 'SECOND YEAR', optional: false },
        { key: 3, label: 'THIRD YEAR', optional: false },
        { key: 4, label: 'FOURTH YEAR', optional: false },
        { key: 5, label: 'FIFTH YEAR', optional: true }
    ];

    function toNumber(v) {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
    }

    function formatUnits(v) {
        return toNumber(v).toFixed(2);
    }

    function renderTotals() {
        let encodedTotal = 0;
        document.querySelectorAll('.subject-row-units').forEach(function (cell) {
            encodedTotal += toNumber(cell.textContent);
        });

        const requiredTotal = toNumber(requiredUnitsInput.value);
        const gap = requiredTotal - encodedTotal;

        encodedUnitsDisplay.textContent = formatUnits(encodedTotal);
        requiredUnitsDisplay.textContent = formatUnits(requiredTotal);
        unitsGapDisplay.textContent = formatUnits(gap);

        unitsGapDisplay.classList.remove('text-danger', 'text-success');
        if (gap < 0) unitsGapDisplay.classList.add('text-danger');
        if (gap > 0) unitsGapDisplay.classList.add('text-success');
    }

    let courseCatalog = [];
    function extractCourseList(res) {
        if (Array.isArray(res)) return res;
        if (Array.isArray(res?.data)) return res.data;
        if (Array.isArray(res?.courses)) return res.courses;
        return [];
    }
    function parseLecLab(raw) {
        // raw example: "[3,2]"
        if (Array.isArray(raw)) return [toNumber(raw[0]), toNumber(raw[1])];
        if (typeof raw === 'string') {
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) return [toNumber(parsed[0]), toNumber(parsed[1])];
            } catch (_) {
                const cleaned = raw.replace(/[\[\]\s]/g, '');
                const parts = cleaned.split(',');
                return [toNumber(parts[0]), toNumber(parts[1])];
            }
        }
        return [0, 0];
    }
    function normalizeCourse(x) {
        const [lec, lab] = parseLecLab(x.lec_lab);

        return {
            id: x.subject_id ?? x.id ?? '',
            code: String(x.subject_code ?? x.code ?? '').trim(),
            title: String(x.subject_title ?? x.title ?? '').trim(),
            lec: lec,
            lab: lab,
            units: toNumber(x.unit ?? x.units ?? 0)
        };
    }

    function escapeHtml(v) {
        return String(v).replace(/[&<>"']/g, s => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[s]));
    }

    function courseOptionsHtml(selected = '') {
        let html = `<option value="">Select Course</option>`;
        courseCatalog.forEach(function (c) {
            const sel = String(c.id) === String(selected) ? 'selected' : '';
            html += `<option value="${escapeHtml(c.id)}" ${sel}>${escapeHtml(c.code)} - ${escapeHtml(c.title)}</option>`;
        });
        return html;
    }
    function prereqOptionsHtml(selected = '') {
        let html = `<option value="">Select Pre-req</option>`;
        courseCatalog.forEach(function (c) {
            const sel = String(c.id) === String(selected) ? 'selected' : '';
            html += `<option value="${escapeHtml(c.id)}" ${sel}>${escapeHtml(c.code)} - ${escapeHtml(c.title)}</option>`;
        });
        return html;
    }

    function renderEntryRow(blockId, semester) {
        const entryBody = document.getElementById(`entry-block-${blockId}-semester-${semester}`);
        if (!entryBody) return;

        entryBody.innerHTML = `
            <tr class="semester-entry-row" data-block-id="${blockId}" data-semester="${semester}">
                <td colspan="2"><select class="entry-course">${courseOptionsHtml()}</select></td>
                <td><input style="min-width: 74px; text-align: center;" type="number" min="0" step="1" class="form-control form-control-sm entry-lec" value="0"></td>
                <td><input style="min-width: 74px; text-align: center;" type="number" min="0" step="1" class="form-control form-control-sm entry-lab" value="0"></td>
                <td><input style="min-width: 74px; text-align: center;" type="number" min="0" step="0.5" class="form-control form-control-sm entry-units" value="0"></td>
                <td><select  style="min-width: 150px; text-align: center;" class="entry-prereq">${prereqOptionsHtml()}</select></td>
                <td class="text-center"><button type="button" title="Clear entries" class="btn btn-sm btn-outline-secondary clear-entry-btn"><i class="fas fa-times"></i></button></td>
            </tr>
        `;
    }

    function addSubjectFromEntry(blockId, semester) {
        const entryBody = document.getElementById(`entry-block-${blockId}-semester-${semester}`);
        const rowsBody = document.getElementById(`rows-block-${blockId}-semester-${semester}`);
        if (!entryBody || !rowsBody) return;

        const row = entryBody.querySelector('tr');
        if (!row) return;

        const courseSel = row.querySelector('.entry-course');
        const prereqSel = row.querySelector('.entry-prereq');
        const lecEl = row.querySelector('.entry-lec');
        const labEl = row.querySelector('.entry-lab');
        const unitsEl = row.querySelector('.entry-units');

        if (!courseSel.value) {
            swal({ title: 'Please enter a course', icon: 'warning' });
            return;
        }

        const chosen = courseCatalog.find(c => String(c.id) === String(courseSel.value));
        const code = chosen ? chosen.code : '';
        const title = chosen ? chosen.title : '';

        const lec = toNumber(lecEl.value);
        const lab = toNumber(labEl.value);
        const units = toNumber(unitsEl.value);
        const prereqCourse = courseCatalog.find(c => String(c.id) === String(prereqSel.value));
        const prereqText = prereqCourse ? prereqCourse.code : '';
        
        const tr = document.createElement('tr');
        tr.setAttribute('data-block-id', String(blockId));
        tr.setAttribute('data-semester', String(semester));
        tr.innerHTML = `
            <td>${escapeHtml(code)}</td>
            <td>${escapeHtml(title)}</td>
            <td>${lec}</td>
            <td>${lab}</td>
            <td class="subject-row-units">${formatUnits(units)}</td>
            <td>${escapeHtml(prereqText)}</td>
            <td class="text-center"><button type="button" title="Remove row" class="btn btn-sm btn-outline-danger remove-subject-btn"><i class="fas fa-times"></i></button></td>
        `;
        rowsBody.appendChild(tr);

        courseSel.value = '';
        prereqSel.value = '';
        lecEl.value = '0';
        labEl.value = '0';
        unitsEl.value = '0';

        updateSemesterTotal(blockId, semester);

        const courseSelObj = $(row).find('.entry-course')[0].selectize;
        const prereqSelObj = $(row).find('.entry-prereq')[0].selectize;

        courseSelObj.clear(true);
        prereqSelObj.clear(true);
        
        tr.dataset.subjectId = chosen ? String(chosen.id) : '';
        tr.dataset.prereqId = prereqCourse ? String(prereqCourse.id) : '';
    }

    function updateSemesterTotal(blockId, semester) {
        let total = 0;
        document.querySelectorAll(`#rows-block-${blockId}-semester-${semester} .subject-row-units`).forEach(function (el) {
            total += toNumber(el.textContent);
        });

        const totalEl = document.getElementById(`total-block-${blockId}-semester-${semester}`);
        if (totalEl) totalEl.textContent = formatUnits(total);

        renderTotals();
    }

    function semesterTableHtml(blockId, semester, title) {
        const isFirst = title.trim().toUpperCase() === 'FIRST SEMESTER';
        const padStyle = isFirst ? 'pe-xl-0 border-end border-black' : 'ps-xl-0';
        return `
            <div class="col-12 col-xl-6 ${padStyle}">
                <div class="semester-title d-flex justify-content-between align-items-center rounded-0">
                    ${title}
                    <button type="button" class="btn btn-sm btn-light add-subject-btn" data-block-id="${blockId}" data-semester="${semester}">
                        <i class="fas fa-plus-circle"></i> Add Subject
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table semester-table mb-2">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Title</th>
                                <th style="min-width: 74px; text-align: center;">Lec</th>
                                <th style="min-width: 74px; text-align: center;">Lab</th>
                                <th style="min-width: 74px; text-align: center;">Units</th>
                                <th style="min-width: 170px;  width: 170px;">Pre-Req</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="entry-block-${blockId}-semester-${semester}"></tbody>
                        <tbody id="rows-block-${blockId}-semester-${semester}"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total Units</th>
                                <th id="total-block-${blockId}-semester-${semester}">0.00</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        `;
    }

    function yearBlockHtml(y) {
        return `
            <section class="prospectus-block-card">
                <div class="prospectus-block-header px-3 py-2 d-flex justify-content-center align-items-center">
                    <h3 class="h5 mb-0">${y.label}</h3>
                    ${y.optional ? '<span class="badge bg-warning text-dark">Optional</span>' : ''}
                </div>
                <div>
                    <div class="row">
                        ${semesterTableHtml(y.key, 1, 'FIRST SEMESTER')}
                        ${semesterTableHtml(y.key, 2, 'SECOND SEMESTER')}
                    </div>
                </div>
            </section>
        `;
    }

    function renderFixedTemplate() {
        blocksContainer.innerHTML = YEARS.map(yearBlockHtml).join('');
        YEARS.forEach(function (y) {
            renderEntryRow(y.key, 1);
            renderEntryRow(y.key, 2);

            const rowSem1 = document.querySelector(`#entry-block-${y.key}-semester-1 tr`);
            const rowSem2 = document.querySelector(`#entry-block-${y.key}-semester-2 tr`);

            if (rowSem1) initEntrySelectize(rowSem1);
            if (rowSem2) initEntrySelectize(rowSem2);
        });
    }
    function loadCourseCatalog() {
        $.ajax({
            url: '<?php echo BASE_URL; ?>/registrar/actions/ferchCourseForForm.php',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                const raw = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : []);

                courseCatalog = raw.map(function (x) {
                    const lecLab = (() => {
                        if (Array.isArray(x.lec_lab)) return x.lec_lab;
                        if (typeof x.lec_lab === 'string') {
                            try { return JSON.parse(x.lec_lab); } catch (_) { return [0, 0]; }
                        }
                        return [0, 0];
                    })();

                    return {
                        id: String(x.subject_id ?? '').trim(),
                        code: String(x.subject_code ?? '').trim(),
                        title: String(x.subject_title ?? '').trim(),
                        lec: Number(lecLab[0] ?? 0),
                        lab: Number(lecLab[1] ?? 0),
                        units: Number(x.unit ?? 0)
                    };
                });

                YEARS.forEach(function (y) {
                    renderEntryRow(y.key, 1);
                    renderEntryRow(y.key, 2);

                    const rowSem1 = document.querySelector(`#entry-block-${y.key}-semester-1 tr`);
                    const rowSem2 = document.querySelector(`#entry-block-${y.key}-semester-2 tr`);

                    if (rowSem1) initEntrySelectize(rowSem1);
                    if (rowSem2) initEntrySelectize(rowSem2);
                });
            },
            error: function (xhr) {
                swal({ 
                    title: 'Failed to load courses', 
                    text:'An error occurred', 
                    icon: 'error' 
                });
            }
        });
    }

    function loadCurriculumOptions(selector = '#curriculumSelect', selectedId = null) {
        const $dropdown = $(selector);

        if ($dropdown[0].selectize) {
            $dropdown[0].selectize.destroy();
        }

        $dropdown.empty();
        $dropdown.append('<option value="" selected disabled>Select Curriculum</option>');

        $.ajax({
            url: '<?php echo BASE_URL; ?>/registrar/actions/fetchCurrForPros.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (!response || !response.msg_status || !Array.isArray(response.data)) return;

                response.data.forEach(function (curr) {
                    $dropdown.append(
                        $('<option>', {
                            value: curr.curriculum_id,
                            text: `${curr.header} (${curr.curriculum_code})`
                        })
                    );
                });

                $dropdown.selectize({
                    allowEmptyOption: true,
                    create: false,
                    sortField: 'text',
                    placeholder: 'Select Curriculum'
                });

                const selectize = $dropdown[0].selectize;
                selectize.clear(true); // do not auto-select

                if (selectedId) {
                    selectize.setValue(String(selectedId), true);
                }
            }
        });
    }

    function initEntrySelectize(row, selectedCourseId = null, selectedPrereqId = null) {
        const $course = $(row).find('.entry-course');
        const $prereq = $(row).find('.entry-prereq');

        if ($course[0].selectize) $course[0].selectize.destroy();
        if ($prereq[0].selectize) $prereq[0].selectize.destroy();

        $course.empty().append('<option value="" selected disabled>Select Course</option>');
        $prereq.empty().append('<option value="" selected disabled>Select Pre-req</option>');

        courseCatalog.forEach(function (c) {
            $course.append($('<option>', {
                value: c.id,
                text: `${c.code} - ${c.title}`
            }));
            $prereq.append($('<option>', {
                value: c.id,
                text: `${c.code} - ${c.title}`
            }));
        });

        $course.selectize({
            allowEmptyOption: true,
            create: false,
            sortField: 'text',
            placeholder: 'Select Course'
        });

        $prereq.selectize({
            allowEmptyOption: true,
            create: false,
            sortField: 'text',
            placeholder: 'Select Pre-req'
        });

        const courseSel = $course[0].selectize;
        const prereqSel = $prereq[0].selectize;

        courseSel.clear(true);
        prereqSel.clear(true); // keeps "None"/empty

        if (selectedCourseId) courseSel.setValue(String(selectedCourseId), true);
        if (selectedPrereqId) prereqSel.setValue(String(selectedPrereqId), true);

        courseSel.on('change', function (value) {
            const picked = courseCatalog.find(c => String(c.id) === String(value));
            if (!picked) return;
            row.querySelector('.entry-lec').value = picked.lec;
            row.querySelector('.entry-lab').value = picked.lab;
            row.querySelector('.entry-units').value = picked.units;
        });
    }



    blocksContainer.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.add-subject-btn');
        const removeRowBtn = e.target.closest('.remove-subject-btn');
        const clearEntryBtn = e.target.closest('.clear-entry-btn');

        if (addBtn) {
            addSubjectFromEntry(addBtn.getAttribute('data-block-id'), addBtn.getAttribute('data-semester'));
            return;
        }

        if (removeRowBtn) {
            const row = removeRowBtn.closest('tr');
            if (!row) return;
            const blockId = row.getAttribute('data-block-id');
            const semester = row.getAttribute('data-semester');
            row.remove();
            updateSemesterTotal(blockId, semester);
            return;
        }

        if (clearEntryBtn) {
            const row = clearEntryBtn.closest('tr');
            if (!row) return;

            const courseSelObj = $(row).find('.entry-course')[0]?.selectize;
            const prereqSelObj = $(row).find('.entry-prereq')[0]?.selectize;

            if (courseSelObj) courseSelObj.clear(true);
            if (prereqSelObj) prereqSelObj.clear(true);

            row.querySelector('.entry-lec').value = '0';
            row.querySelector('.entry-lab').value = '0';
            row.querySelector('.entry-units').value = '0';
        }
    });

    requiredUnitsInput.addEventListener('input', renderTotals);

    
    renderFixedTemplate();
    loadCurriculumOptions();
    renderTotals();
    loadCourseCatalog();


    function getCurriculumValue() {
        const sel = $('#curriculumSelect')[0];
        if (sel && sel.selectize) return sel.selectize.getValue();
        return $('#curriculumSelect').val();
    }

    function collectProspectusPayload() {
        const payload = {
            curriculum_id: getCurriculumValue(),
            required_units: toNumber($('#requiredUnits').val()),
            blocks: []
        };

        YEARS.forEach(function (y) {
            [1, 2].forEach(function (semester) {
                const rows = [];
                document.querySelectorAll(`#rows-block-${y.key}-semester-${semester} tr`).forEach(function (tr) {
                    rows.push({
                        subject_id: tr.dataset.subjectId || '',
                        subject_code: tr.children[0].textContent.trim(),
                        subject_title: tr.children[1].textContent.trim(),
                        lec: toNumber(tr.children[2].textContent),
                        lab: toNumber(tr.children[3].textContent),
                        units: toNumber(tr.children[4].textContent),
                        prereq_subject_id: tr.dataset.prereqId || '',
                        prereq_code: tr.children[5].textContent.trim()
                    });
                });

                if(y.key === 5 && rows.length === 0) return;
                payload.blocks.push({
                    year_level: y.key,
                    semester: semester,
                    subjects: rows
                });
            });
        });

        return payload;
    }

    $('#saveProspectusBtn').on('click', function () {
        const payload = collectProspectusPayload();
        console.log('Collected payload:', payload);

        const postData = [
            { name: 'submitProspectus', value: 'createProspectus' },
            { name: 'curriculum_id', value: payload.curriculum_id },
            { name: 'required_units', value: payload.required_units },
            { name: 'prospectus_json', value: JSON.stringify(payload.blocks) }
        ];

        console.log('Payload to submit:', postData);

        function loadingAPIrequest(status){
            if(status === true){
                swal({
                    title: "Loading",
                    icon: 'info',
                    text: "Please wait"
                });
            }
            if(status === false){
                swal.close();
            }

        }

        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/prospectus_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function (data) {
                if(data){
                    if(data.code === 200 && data.msg_status === true){
                        swal({
                            title: "Success",
                            icon: "success",
                            text: data.msg_response,
                            button: false,
                            timer:3000,
                        })
                    }
                    if(data.code === 501 && data.msg_status === false){
                        swal({
                            title: "Failed to create",
                            icon: "error",
                            text: data.msg_response,
                            button: true,
                            timer:3000,
                        })
                    }
                                        if(data.code === 500 && data.msg_status === false){
                        swal({
                            title: "Failed to create",
                            icon: "error",
                            text: data.msg_response,
                            button: true,
                            timer:3000,
                        })
                    }
                }
            }
        });
    });


});
</script>
</html>