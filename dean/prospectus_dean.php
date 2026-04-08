<?php
// filepath: c:\xampp\htdocs\enroll\registrar\prospectus.php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!($g_user_role == "DEAN")) {
    header("Location: " . BASE_URL);
    exit();
}

$preselectCurriculumId = $_GET['curriculum_id'] ?? '';


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
                    <label class="fs-2 text-white fw-bolder">Curriculum Builder</label>
                </header>
                <div class="card-body pt-1" style="padding-right: 0.5rem;padding-left: 0.5rem;">
                    <div class="row">
                        <div class="col-md-3"></div>
                        <div class="col-md-6">
                            <div class="justify-content-center align-items-center">
                                <div class="row-md-6 text-center">
                                    <label id="program" name="program" class="form-label fs-3 text-black fw-bold"></label>
                                </div>
                                <div class="row-md-6 text-center">
                                    <label name="curriculum" id="curriculum" class="form-label fs-4 text-black fw-bold"></label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 d-flex flex-column justify-content-end">
                            <label id="units" class="form-label fs-5 text-black fw-bold"></label>
                        </div>
                    </div>


                    <hr class="my-2">

                    <!-- 1ST YEAR -->
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button id="btn_add_row1" type="button" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">1st Year, 1st Semester</label>
                            </div>
                          </div>
                         <div id="first_1sem"></div>
                    </div>
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button id="btn_add_row2" type="button" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">1st Year, 2nd Semester</label>
                            </div>
                          </div>
                         <div id="first_2sem"></div>
                    </div>

                    <!-- 2ND YEAR -->
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button id="scdYr_tb1" type="button" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">2nd Year, 1st Semester</label>
                            </div>
                          </div>
                         <div id="second_1sem"></div>
                    </div>
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button id="scdYr_tb2" type="button" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">2nd Year, 2nd Semester</label>
                            </div>
                          </div>
                         <div id="second_2sem"></div>
                    </div>

                    <!-- 3RD YEAR -->
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button type="button" id="trdYr_tb1" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">3rd Year, 1st Semester</label>
                            </div>
                          </div>
                         <div id="third_1sem"></div>
                    </div>
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button type="button" id="trdYr_tb2" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">3rd Year, 2nd Semester</label>
                            </div>
                          </div>
                         <div id="third_2sem"></div>
                    </div>

                    <!-- 4TH YEAR -->
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button type="button" id="frtYr_tb1" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">4th Year, 1st Semester</label>
                            </div>
                          </div>
                         <div id="fourth_1sem"></div>
                    </div>
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button type="button" id="frtYr_tb2" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">4th Year, 2nd Semester</label>
                            </div>
                          </div>
                         <div id="fourth_2sem"></div>
                    </div>

                    <!-- 5TH YEAR -->
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button type="button" id="fthYr_tb1" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">5th Year, 1st Semester</label>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge text-bg-warning fw-bold">Optional</span>
                            </div>
                          </div>
                         <div id="fifth_1sem"></div>
                    </div>
                    <div class="border-0">
                         <div class="row m-0 rounded-bottom-0 rounded-top-1 p-2 bg-primary align-items-center">
                            <div class="col-md-4 ps-0">
                                <button type="button" id="fthYr_tb2" class="btn btn-light btn-sm" style="padding: 6px 12px !important; "><i class="fas fa-plus"></i> Add Subject</button>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="form-label text-black mb-0 fw-bold fs-5">5th Year, 2nd Semester</label>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge text-bg-warning fw-bold">Optional</span>
                            </div>
                          </div>
                         <div id="fifth_2sem"></div>
                    </div>



                    <div class="d-flex justify-content-end mt-4">
                        <div class="gap-2">
                            <button id="saveProspectusBtn" type="button" class="text-black btn btn-success">
                                <i class="bi bi-save me-1"></i> Save Prospectus
                            </button>
                            <button id="toggleProspectusViewportBtn" type="button" class="text-black btn btn-secondary me-2">
                                View All Prospectus
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <div class="modal fade" id="saveProspectusModal" tabindex="-1" aria-labelledby="saveDescLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="saveDescLabel"></h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p id="saveDesc"></p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button id="confirmSaveProspectusBtn" type="button" class="btn btn-primary">Confirm Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include_once FOOTER_PATH; ?>
    </div>
</div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // const curriculumSelect = document.getElementById('curriculumSelect');
    // const requiredUnitsInput = document.getElementById('requiredUnits');
    // const blocksContainer = document.getElementById('prospectusBlocks');
    // const encodedUnitsDisplay = document.getElementById('encodedUnitsDisplay');
    // const requiredUnitsDisplay = document.getElementById('requiredUnitsDisplay');
    // const unitsGapDisplay = document.getElementById('unitsGapDisplay');
    // // const viewport = document.getElementById('prospectusBlocksViewport');
    // const toggleBtn = document.getElementById('toggleProspectusViewportBtn');

    // if (viewport && toggleBtn) {
    // toggleBtn.addEventListener('click', function () {
    //     const expanded = viewport.classList.toggle('is-expanded');
    //     toggleBtn.textContent = expanded ? 'Shorten View' : 'View All Prospectus';
    // });
    // }

    // if (!curriculumSelect || !requiredUnitsInput || !blocksContainer) return;

    // const YEARS = [
    //     { key: 1, label: 'FIRST YEAR', optional: false },
    //     { key: 2, label: 'SECOND YEAR', optional: false },
    //     { key: 3, label: 'THIRD YEAR', optional: false },
    //     { key: 4, label: 'FOURTH YEAR', optional: false },
    //     { key: 5, label: 'FIFTH YEAR', optional: true }
    // ];

    // function toNumber(v) {
    //     const n = parseFloat(v);
    //     return Number.isFinite(n) ? n : 0;
    // }

    // function formatUnits(v) {
    //     return toNumber(v).toFixed(2);
    // }

    // function renderTotals() {
    //     let encodedTotal = 0;
    //     document.querySelectorAll('.subject-row-units').forEach(function (cell) {
    //         encodedTotal += toNumber(cell.textContent);
    //     });

    //     const requiredTotal = toNumber(requiredUnitsInput.value);
    //     const gap = requiredTotal - encodedTotal;

    //     encodedUnitsDisplay.textContent = formatUnits(encodedTotal);
    //     requiredUnitsDisplay.textContent = formatUnits(requiredTotal);
    //     unitsGapDisplay.textContent = formatUnits(gap);

    //     unitsGapDisplay.classList.remove('text-danger', 'text-success');
    //     if (gap < 0) unitsGapDisplay.classList.add('text-danger');
    //     if (gap > 0) unitsGapDisplay.classList.add('text-success');
    // }

    // let courseCatalog = [];
    // function extractCourseList(res) {
    //     if (Array.isArray(res)) return res;
    //     if (Array.isArray(res?.data)) return res.data;
    //     if (Array.isArray(res?.courses)) return res.courses;
    //     return [];
    // }
    // function parseLecLab(raw) {
    //     // raw example: "[3,2]"
    //     if (Array.isArray(raw)) return [toNumber(raw[0]), toNumber(raw[1])];
    //     if (typeof raw === 'string') {
    //         try {
    //             const parsed = JSON.parse(raw);
    //             if (Array.isArray(parsed)) return [toNumber(parsed[0]), toNumber(parsed[1])];
    //         } catch (_) {
    //             const cleaned = raw.replace(/[\[\]\s]/g, '');
    //             const parts = cleaned.split(',');
    //             return [toNumber(parts[0]), toNumber(parts[1])];
    //         }
    //     }
    //     return [0, 0];
    // }
    // function normalizeCourse(x) {
    //     const [lec, lab] = parseLecLab(x.lec_lab);

    //     return {
    //         id: x.subject_id ?? x.id ?? '',
    //         code: String(x.subject_code ?? x.code ?? '').trim(),
    //         title: String(x.subject_title ?? x.title ?? '').trim(),
    //         lec: lec,
    //         lab: lab,
    //         units: toNumber(x.unit ?? x.units ?? 0)
    //     };
    // }

    // function escapeHtml(v) {
    //     return String(v).replace(/[&<>"']/g, s => ({
    //         '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    //     }[s]));
    // }

    // function courseOptionsHtml(selected = '') {
    //     let html = `<option value="">Select Course</option>`;
    //     courseCatalog.forEach(function (c) {
    //         const sel = String(c.id) === String(selected) ? 'selected' : '';
    //         html += `<option value="${escapeHtml(c.id)}" ${sel}>${escapeHtml(c.code)} - ${escapeHtml(c.title)}</option>`;
    //     });
    //     return html;
    // }
    // function prereqOptionsHtml(selected = '') {
    //     let html = `<option value="">Select Pre-req</option>`;
    //     courseCatalog.forEach(function (c) {
    //         const sel = String(c.id) === String(selected) ? 'selected' : '';
    //         html += `<option value="${escapeHtml(c.id)}" ${sel}>${escapeHtml(c.code)} - ${escapeHtml(c.title)}</option>`;
    //     });
    //     return html;
    // }

    // function renderEntryRow(blockId, semester) {
    //     const entryBody = document.getElementById(`entry-block-${blockId}-semester-${semester}`);
    //     if (!entryBody) return;

    //     entryBody.innerHTML = `
    //         <tr class="semester-entry-row" data-block-id="${blockId}" data-semester="${semester}">
    //             <td colspan="2"><select class="entry-course">${courseOptionsHtml()}</select></td>
    //             <td><input style="min-width: 74px; text-align: center;" type="number" min="0" step="1" class="form-control form-control-sm entry-lec" value="0"></td>
    //             <td><input style="min-width: 74px; text-align: center;" type="number" min="0" step="1" class="form-control form-control-sm entry-lab" value="0"></td>
    //             <td><input style="min-width: 74px; text-align: center;" type="number" min="0" step="0.5" class="form-control form-control-sm entry-units" value="0"></td>
    //             <td><select  style="min-width: 150px; text-align: center;" class="entry-prereq">${prereqOptionsHtml()}</select></td>
    //             <td class="text-center"><button type="button" title="Clear entries" class="btn btn-sm btn-outline-secondary clear-entry-btn"><i class="fas fa-times"></i></button></td>
    //         </tr>
    //     `;
    // }

    // function addSubjectFromEntry(blockId, semester) {
    //     const entryBody = document.getElementById(`entry-block-${blockId}-semester-${semester}`);
    //     const rowsBody = document.getElementById(`rows-block-${blockId}-semester-${semester}`);
    //     if (!entryBody || !rowsBody) return;

    //     const row = entryBody.querySelector('tr');
    //     if (!row) return;

    //     const courseSel = row.querySelector('.entry-course');
    //     const prereqSel = row.querySelector('.entry-prereq');
    //     const lecEl = row.querySelector('.entry-lec');
    //     const labEl = row.querySelector('.entry-lab');
    //     const unitsEl = row.querySelector('.entry-units');

    //     if (!courseSel.value) {
    //         swal({ title: 'Please enter a course', icon: 'warning' });
    //         return;
    //     }

    //     const chosen = courseCatalog.find(c => String(c.id) === String(courseSel.value));
    //     const code = chosen ? chosen.code : '';
    //     const title = chosen ? chosen.title : '';

    //     const lec = toNumber(lecEl.value);
    //     const lab = toNumber(labEl.value);
    //     const units = toNumber(unitsEl.value);
    //     const prereqCourse = courseCatalog.find(c => String(c.id) === String(prereqSel.value));
    //     const prereqText = prereqCourse ? prereqCourse.code : '';
        
    //     const tr = document.createElement('tr');
    //     tr.setAttribute('data-block-id', String(blockId));
    //     tr.setAttribute('data-semester', String(semester));
    //     tr.innerHTML = `
    //         <td>${escapeHtml(code)}</td>
    //         <td>${escapeHtml(title)}</td>
    //         <td>${lec}</td>
    //         <td>${lab}</td>
    //         <td class="subject-row-units">${formatUnits(units)}</td>
    //         <td>${escapeHtml(prereqText)}</td>
    //         <td class="text-center"><button type="button" title="Remove row" class="btn btn-sm btn-outline-danger remove-subject-btn"><i class="fas fa-times"></i></button></td>
    //     `;
    //     rowsBody.appendChild(tr);

    //     courseSel.value = '';
    //     prereqSel.value = '';
    //     lecEl.value = '0';
    //     labEl.value = '0';
    //     unitsEl.value = '0';

    //     updateSemesterTotal(blockId, semester);

    //     const courseSelObj = $(row).find('.entry-course')[0].selectize;
    //     const prereqSelObj = $(row).find('.entry-prereq')[0].selectize;

    //     courseSelObj.clear(true);
    //     prereqSelObj.clear(true);
        
    //     tr.dataset.subjectId = chosen ? String(chosen.id) : '';
    //     tr.dataset.prereqId = prereqCourse ? String(prereqCourse.id) : '';
    // }

    // function updateSemesterTotal(blockId, semester) {
    //     let total = 0;
    //     document.querySelectorAll(`#rows-block-${blockId}-semester-${semester} .subject-row-units`).forEach(function (el) {
    //         total += toNumber(el.textContent);
    //     });

    //     const totalEl = document.getElementById(`total-block-${blockId}-semester-${semester}`);
    //     if (totalEl) totalEl.textContent = formatUnits(total);

    //     renderTotals();
    // }

    // function semesterTableHtml(blockId, semester, title) {
    //     const isFirst = title.trim().toUpperCase() === 'FIRST SEMESTER';
    //     const padStyle = isFirst ? 'pe-xl-0 border-end border-black' : 'ps-xl-0';
    //     return `
    //         <div class="col-12 col-xl-6 ${padStyle}">
    //             <div class="semester-title d-flex justify-content-between align-items-center rounded-0">
    //                 ${title}
    //                 <button type="button" class="btn btn-sm btn-light add-subject-btn" data-block-id="${blockId}" data-semester="${semester}">
    //                     <i class="fas fa-plus-circle"></i> Add Subject
    //                 </button>
    //             </div>
    //             <div class="table-responsive">
    //                 <table class="table semester-table mb-2">
    //                     <thead>
    //                         <tr>
    //                             <th>Code</th>
    //                             <th>Course Title</th>
    //                             <th style="min-width: 74px; text-align: center;">Lec</th>
    //                             <th style="min-width: 74px; text-align: center;">Lab</th>
    //                             <th style="min-width: 74px; text-align: center;">Units</th>
    //                             <th style="min-width: 170px;  width: 170px;">Pre-Req</th>
    //                             <th class="text-center">Action</th>
    //                         </tr>
    //                     </thead>
    //                     <tbody id="entry-block-${blockId}-semester-${semester}"></tbody>
    //                     <tbody id="rows-block-${blockId}-semester-${semester}"></tbody>
    //                     <tfoot>
    //                         <tr>
    //                             <th colspan="4" class="text-end">Total Units</th>
    //                             <th id="total-block-${blockId}-semester-${semester}">0.00</th>
    //                             <th colspan="2"></th>
    //                         </tr>
    //                     </tfoot>
    //                 </table>
    //             </div>
    //         </div>
    //     `;
    // }

    // function yearBlockHtml(y) {
    //     return `
    //         <section class="prospectus-block-card">
    //             <div class="prospectus-block-header px-3 py-2 d-flex justify-content-center align-items-center">
    //                 <h3 class="h5 mb-0">${y.label}</h3>
    //                 ${y.optional ? '<span class="badge bg-warning text-dark">Optional</span>' : ''}
    //             </div>
    //             <div>
    //                 <div class="row">
    //                     ${semesterTableHtml(y.key, 1, 'FIRST SEMESTER')}
    //                     ${semesterTableHtml(y.key, 2, 'SECOND SEMESTER')}
    //                 </div>
    //             </div>
    //         </section>
    //     `;
    // }

    // function renderFixedTemplate() {
    //     blocksContainer.innerHTML = YEARS.map(yearBlockHtml).join('');
    //     YEARS.forEach(function (y) {
    //         renderEntryRow(y.key, 1);
    //         renderEntryRow(y.key, 2);

    //         const rowSem1 = document.querySelector(`#entry-block-${y.key}-semester-1 tr`);
    //         const rowSem2 = document.querySelector(`#entry-block-${y.key}-semester-2 tr`);

    //         if (rowSem1) initEntrySelectize(rowSem1);
    //         if (rowSem2) initEntrySelectize(rowSem2);
    //     });
    // }
    // function loadCourseCatalog() {
    //     $.ajax({
    //         url: '<?php echo BASE_URL; ?>registrar/actions/ferchCourseForForm.php',
    //         type: 'GET',
    //         dataType: 'json',
    //         success: function (res) {
    //             const raw = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : []);

    //             courseCatalog = raw.map(function (x) {
    //                 const lecLab = (() => {
    //                     if (Array.isArray(x.lec_lab)) return x.lec_lab;
    //                     if (typeof x.lec_lab === 'string') {
    //                         try { return JSON.parse(x.lec_lab); } catch (_) { return [0, 0]; }
    //                     }
    //                     return [0, 0];
    //                 })();

    //                 return {
    //                     id: String(x.subject_id ?? '').trim(),
    //                     code: String(x.subject_code ?? '').trim(),
    //                     title: String(x.subject_title ?? '').trim(),
    //                     lec: Number(lecLab[0] ?? 0),
    //                     lab: Number(lecLab[1] ?? 0),
    //                     units: Number(x.unit ?? 0)
    //                 };
    //             });

    //             YEARS.forEach(function (y) {
    //                 renderEntryRow(y.key, 1);
    //                 renderEntryRow(y.key, 2);

    //                 const rowSem1 = document.querySelector(`#entry-block-${y.key}-semester-1 tr`);
    //                 const rowSem2 = document.querySelector(`#entry-block-${y.key}-semester-2 tr`);

    //                 if (rowSem1) initEntrySelectize(rowSem1);
    //                 if (rowSem2) initEntrySelectize(rowSem2);
    //             });
    //         },
    //         error: function (xhr) {
    //             swal({ 
    //                 title: 'Failed to load courses', 
    //                 text:'An error occurred', 
    //                 icon: 'error' 
    //             });
    //         }
    //     });
    // }

    let currTitle = '';
    function loadCurriculumOptions(selector = '', selectedId = null) {
        const component = $(selector);
        console.log('selector:', selector, typeof selector, "ID:", selectedId);
        if(component.is("#curriculum")){
            $.ajax({
                url: '<?php echo BASE_URL; ?>dean/actions/fetchCurrForPros.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if(response.code === 200 && response.msg_status === true){
                        const data = response.data
                        const currData = data.find(d => d.curriculum_id === Number(selectedId));
                        currTitle = currData.header;
                        document.getElementById('curriculum').textContent = currData.header;
                        
                    }
                }
            });
        }

        console.log("fetch prog")
        if(component.is("#program")){
            console.log('progr fetched')
            $.ajax({
                url: '<?php echo BASE_URL; ?>dean/actions/fetchProgForSection.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if(response.code === 200 && response.status === true){
                        const data = response.data
                        const currData = data.find(d => Number(d.program_id) === Number(selectedId));
                        console.log('Selected program Data:', currData);

                        document.getElementById('program').textContent = currData.program;

                        
                    }
                }
            });
        }
    }

    // function initEntrySelectize(row, selectedCourseId = null, selectedPrereqId = null) {
    //     const $course = $(row).find('.entry-course');
    //     const $prereq = $(row).find('.entry-prereq');

    //     if ($course[0].selectize) $course[0].selectize.destroy();
    //     if ($prereq[0].selectize) $prereq[0].selectize.destroy();

    //     $course.empty().append('<option value="" selected disabled>Select Course</option>');
    //     $prereq.empty().append('<option value="" selected disabled>Select Pre-req</option>');

    //     courseCatalog.forEach(function (c) {
    //         $course.append($('<option>', {
    //             value: c.id,
    //             text: `${c.code} - ${c.title}`
    //         }));
    //         $prereq.append($('<option>', {
    //             value: c.id,
    //             text: `${c.code} - ${c.title}`
    //         }));
    //     });

    //     $course.selectize({
    //         allowEmptyOption: true,
    //         create: false,
    //         sortField: 'text',
    //         placeholder: 'Select Course'
    //     });

    //     $prereq.selectize({
    //         allowEmptyOption: true,
    //         create: false,
    //         sortField: 'text',
    //         placeholder: 'Select Pre-req'
    //     });

    //     const courseSel = $course[0].selectize;
    //     const prereqSel = $prereq[0].selectize;

    //     courseSel.clear(true);
    //     prereqSel.clear(true); // keeps "None"/empty

    //     if (selectedCourseId) courseSel.setValue(String(selectedCourseId), true);
    //     if (selectedPrereqId) prereqSel.setValue(String(selectedPrereqId), true);

    //     courseSel.on('change', function (value) {
    //         const picked = courseCatalog.find(c => String(c.id) === String(value));
    //         if (!picked) return;
    //         row.querySelector('.entry-lec').value = picked.lec;
    //         row.querySelector('.entry-lab').value = picked.lab;
    //         row.querySelector('.entry-units').value = picked.units;
    //     });
    // }



    // blocksContainer.addEventListener('click', function (e) {
    //     const addBtn = e.target.closest('.add-subject-btn');
    //     const removeRowBtn = e.target.closest('.remove-subject-btn');
    //     const clearEntryBtn = e.target.closest('.clear-entry-btn');

    //     if (addBtn) {
    //         addSubjectFromEntry(addBtn.getAttribute('data-block-id'), addBtn.getAttribute('data-semester'));
    //         return;
    //     }

    //     if (removeRowBtn) {
    //         const row = removeRowBtn.closest('tr');
    //         if (!row) return;
    //         const blockId = row.getAttribute('data-block-id');
    //         const semester = row.getAttribute('data-semester');
    //         row.remove();
    //         updateSemesterTotal(blockId, semester);
    //         return;
    //     }

    //     if (clearEntryBtn) {
    //         const row = clearEntryBtn.closest('tr');
    //         if (!row) return;

    //         const courseSelObj = $(row).find('.entry-course')[0]?.selectize;
    //         const prereqSelObj = $(row).find('.entry-prereq')[0]?.selectize;

    //         if (courseSelObj) courseSelObj.clear(true);
    //         if (prereqSelObj) prereqSelObj.clear(true);

    //         row.querySelector('.entry-lec').value = '0';
    //         row.querySelector('.entry-lab').value = '0';
    //         row.querySelector('.entry-units').value = '0';
    //     }
    // });

    // requiredUnitsInput.addEventListener('input', renderTotals);

    // const preselectCurriculumId = <?php echo json_encode($preselectCurriculumId); ?>;
    // renderFixedTemplate();
    // renderTotals();
    // loadCourseCatalog();

    let curr_id = "";
    let program_id = "";
    const coin = new URLSearchParams(window.location.search).get('coin');
    if(coin){
        $.ajax({
            url: "<?php echo BASE_URL.URL_FROMCURR; ?>",
            method: "POST",
            data: { coin: coin },
            dataType: "json",
            success: function(data){
                if(data){
                    if(data.code === 200 && data.msg_status === true){
                        console.log('Curriculum data fetched for coin:', data);
                        curr_id = data.curriculum_id;
                        program_id = data.program_id;
                        loadCurriculumOptions('#curriculum', data.curriculum_id);
                        loadCurriculumOptions('#program', data.program_id);
                    }
                }
            },
            error: function(){
                swal({
                    title: "Error",
                    text: "Could not find curriculum. You may manually select it.",
                    icon: "error"
                });
            }
        })
    }



    // function getCurriculumValue() {
    //     const sel = $('#curriculumSelect')[0];
    //     if (sel && sel.selectize) return sel.selectize.getValue();
    //     return $('#curriculumSelect').val();
    // }

    // function collectProspectusPayload() {
    //     const payload = {
    //         curriculum_id: getCurriculumValue(),
    //         required_units: toNumber($('#requiredUnits').val()),
    //         blocks: []
    //     };

    //     YEARS.forEach(function (y) {
    //         [1, 2].forEach(function (semester) {
    //             const rows = [];
    //             document.querySelectorAll(`#rows-block-${y.key}-semester-${semester} tr`).forEach(function (tr) {
    //                 rows.push({
    //                     subject_id: tr.dataset.subjectId || '',
    //                     subject_code: tr.children[0].textContent.trim(),
    //                     subject_title: tr.children[1].textContent.trim(),
    //                     lec: toNumber(tr.children[2].textContent),
    //                     lab: toNumber(tr.children[3].textContent),
    //                     units: toNumber(tr.children[4].textContent),
    //                     prereq_subject_id: tr.dataset.prereqId || '',
    //                     prereq_code: tr.children[5].textContent.trim()
    //                 });
    //             });

    //             if(y.key === 5 && rows.length === 0) return;
    //             payload.blocks.push({
    //                 year_level: y.key,
    //                 semester: semester,
    //                 subjects: rows
    //             });
    //         });
    //     });

    //     return payload;
    // }

    // function loadingAPIrequest(status){
    //     if(status === true){
    //         swal({
    //             title: "Loading",
    //             icon: 'info',
    //             text: "Please wait",
    //             button: false,
    //             closeOnClickOutside: false,
    //             closeOnEsc: false
    //         });
    //     }
    //     if(status === false){
    //         swal.close();
    //     }

    // }

    // // $('#saveProspectusBtn').on('click', function () {
    // //     const payload = collectProspectusPayload();

    // //     const postData = [
    // //         { name: 'submitProspectus', value: 'createProspectus' },
    // //         { name: 'curriculum_id', value: payload.curriculum_id },
    // //         { name: 'required_units', value: payload.required_units },
    // //         { name: 'prospectus_json', value: JSON.stringify(payload.blocks) }
    // //     ];

    // //     console.log('Payload to submit:', postData);

    // //     document.getElementById('saveDescLabel').textContent = 'Saving Prospectus';
    // //     document.getElementById('saveDesc').textContent = "Are you sure you want to save this prospectus? Once created it cannot be updated or deleted.";
    // //     $('#saveProspectusModal').modal('show');


    // //     $('#confirmSaveProspectusBtn').off('click').on('click', function (e) {
    // //         e.preventDefault();

    // //         $.ajax({
    // //             url: "<?php echo BASE_URL; ?>registrar/actions/prospectus_process.php",
    // //             method: "POST",
    // //             data: postData,
    // //             dataType: "json",
    // //             beforeSend: loadingAPIrequest(true),
    // //             complete: loadingAPIrequest(false),
    // //             success: function (data) {
    // //                 if(data){
    // //                     if(data.code === 200 && data.msg_status === true){
    // //                         swal({
    // //                             title: "Success",
    // //                             icon: "success",
    // //                             text: data.msg_response,
    // //                             button: false,
    // //                             timer:3000,
    // //                         }).then(function () {
    // //                             $('#saveProspectusModal').modal('hide');
    // //                             if(window.opener && !window.opener.closed){
    // //                                 window.opener.location.href = "<?php echo BASE_URL; ?>registrar/curriculum.php";
    // //                             }
    // //                             window.close();
    // //                             setTimeout(function() {
    // //                                 window.location.href = "<?php echo BASE_URL; ?>registrar/curriculum.php";
    // //                             }, 500);
    // //                         })
    // //                     }
    // //                     if(data.code === 501 && data.msg_status === false){
    // //                         $('#saveProspectusModal').modal('hide');
    // //                         swal({
    // //                             title: "Failed to create",
    // //                             icon: "error",
    // //                             text: data.msg_response,
    // //                             button: true,
    // //                         })
    // //                     }
    // //                     if(data.code === 502 && data.msg_status === false){
    // //                         $('#saveProspectusModal').modal('hide');
    // //                         swal({
    // //                             title: "Failed to create",
    // //                             icon: "error",
    // //                             text: data.msg_response,
    // //                             button: true,
    // //                         })
    // //                     }
    // //                     if(data.code === 500 && data.msg_status === false){
    // //                         $('#saveProspectusModal').modal('hide');
    // //                         swal({
    // //                             title: "Failed to create",
    // //                             icon: "error",
    // //                             text: data.msg_response,
    // //                             button: true,
    // //                         })
    // //                     }
    // //                 }
    // //             },
    // //             error: function () {
    // //                 $('#saveProspectusModal').modal('hide');
    // //                 swal({
    // //                     title: "Error",
    // //                     text: "An error occurred while saving the prospectus.",
    // //                     icon: "error"
    // //                 });
    // //             }
    // //         });
    // //     })
    // // });
    function sumUnitsFromTable(table) {
    return table.getData().reduce((sum, r) => {
        return sum + (parseFloat(r.unit) || 0);
    }, 0);
    }
    
    function updateOverallUnits() {
    const total =
        sumUnitsFromTable(firstTable1) +
        sumUnitsFromTable(firstTable2) +
        sumUnitsFromTable(secondTable1) +
        sumUnitsFromTable(secondTable2) +
        sumUnitsFromTable(thirdTable1) +
        sumUnitsFromTable(thirdTable2) +
        sumUnitsFromTable(fourthTable1) +
        sumUnitsFromTable(fourthTable2) +
        sumUnitsFromTable(fifthTable1) +
        sumUnitsFromTable(fifthTable2);

    document.getElementById('units').textContent = `Units to be Earned: ${total}`;
    }

    // 1ST YEAR
    const firstTable1 = new Tabulator("#first_1sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title",
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
            
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });
    const firstTable2 = new Tabulator("#first_2sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });

    // 2ND YEAR
    const secondTable1 = new Tabulator("#second_1sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });
    const secondTable2 = new Tabulator("#second_2sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });

    // 3RD YEAR
    const thirdTable1 = new Tabulator("#third_1sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });
    const thirdTable2 = new Tabulator("#third_2sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });

    // 4th year
    const fourthTable1 = new Tabulator("#fourth_1sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });
    const fourthTable2 = new Tabulator("#fourth_2sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });

    // 5TH YEAR
    const fifthTable1 = new Tabulator("#fifth_1sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });
    const fifthTable2 = new Tabulator("#fifth_2sem", {
        layout: "fitColumns",
        reactiveData: true,
        columns: [
            { 
                title: "Code", 
                field: "code", 
                editor: "input" 
            },
            { 
                title: "Title", 
                field: "title", 
                editor: "input" 
            },
            { 
                title: "Lec", 
                field: "lec", 
                editor: "number" 
            },
            { 
                title: "Lab", 
                field: "lab", 
                editor: "number" 
            },
            { 
                title: "Unit", 
                field: "unit", 
                editor: "number",
                bottomCalc: function(values){
                    return values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                },
                bottomCalcFormatter: function(cell){
                    return `<strong>${cell.getValue()}</strong>`;
                } 
            },
            { 
                title: "Pre-req", 
                field: "prereq", 
                editor: "input" 
            },
            {
                title: "Action",
                hozAlign: "center",
                minWidth: 200,
                width: 200,
                sorter: false,
                formatter: "buttonCross",
                width: 40,
                cellClick: function(e, cell) {
                    cell.getRow().delete();
                }
            },
        ],
        data: [
            { code: "", title: "", lec: 0, lab: 0, unit: 0, prereq: "" }
        ],
        cellEdited: function(){
            updateOverallUnits();
        },
        rowDeleted: function(){
            updateOverallUnits();
        },
        dataChanged: function(){
            updateOverallUnits();
        }
    });

    // updateOverallUnits();

    addListener(btn_add_row1, "click", function() {
        firstTable1.addRow({}, true);
    });

    addListener(btn_add_row2, "click", function() {
        firstTable2.addRow({}, true);
    });

    addListener(scdYr_tb1, "click", function() {
        secondTable1.addRow({}, true);
    });

    addListener(scdYr_tb2, "click", function() {
        secondTable2.addRow({}, true);
    });

    addListener(trdYr_tb1, "click", function() {
        thirdTable1.addRow({}, true);
    });

    addListener(trdYr_tb2, "click", function() {
        thirdTable2.addRow({}, true);
    });

    addListener(frtYr_tb1, "click", function() {
        fourthTable1.addRow({}, true);
    });

    addListener(frtYr_tb2, "click", function() {
        fourthTable2.addRow({}, true);
    });

    addListener(fthYr_tb1, "click", function() {
        fifthTable1.addRow({}, true);
    });

    addListener(fthYr_tb2, "click", function() {
       fifthTable2.addRow({}, true);
    });

    function isRowEmpty(r) {
        return (
            String(r.code || '').trim() === '' &&
            String(r.title || '').trim() === '' &&
            Number(r.lec || 0) === 0 &&
            Number(r.lab || 0) === 0 &&
            Number(r.unit || 0) === 0 &&
            String(r.prereq || '').trim() === ''
        );
    }

    document.getElementById('saveProspectusBtn').addEventListener('click', function (e) {
        e.preventDefault();

        const formData = [
            {
                name: "submitProspectus",
                value: "createProspectus"
            },
            {
                name: "curriculum_id",
                value: Number(curr_id)
            },
            {
                name: "program_id",
                value: Number(program_id)
            },
            {
                name: "curr_title",
                value: currTitle
            }
        ];
        const fsYr_1 = firstTable1.getData()
        const fsYr_2 = firstTable2.getData();

        const scYr_1 = secondTable1.getData();
        const scYr_2 = secondTable2.getData();

        const trYr_1 = thirdTable1.getData();
        const trYr_2 = thirdTable2.getData();

        const frYr_1 = fourthTable1.getData();
        const frYr_2 = fourthTable2.getData();

        const ftYr_1 = fifthTable1.getData();
        const ftYr_2 = fifthTable2.getData();

        const tableMap = [
            { 
                table: JSON.stringify(fsYr_1), 
                year_level: 1, 
                sem: "1st Semester" 
            },
            { 
                table: JSON.stringify(fsYr_2), 
                year_level: 1, 
                sem: "2nd Semester" 
            },
            { 
                table: JSON.stringify(scYr_1), 
                year_level: 2, 
                sem: "1st Semester" 
            },
            { 
                table: JSON.stringify(scYr_2), 
                year_level: 2, 
                sem: "2nd Semester" 
            },
            { 
                table: JSON.stringify(trYr_1), 
                year_level: 3, 
                sem: "1st Semester" 
            },
            { 
                table: JSON.stringify(trYr_2), 
                year_level: 3, 
                sem: "2nd Semester" 
            },
            { 
                table: JSON.stringify(frYr_1 ), 
                year_level: 4, 
                sem: "1st Semester" 
            },
            { 
                table: JSON.stringify(frYr_2), 
                year_level: 4, 
                sem: "2nd Semester" 
            }
        ];

        const ftYr_1_clean = ftYr_1.filter(r => !isRowEmpty(r));
        const ftYr_2_clean = ftYr_2.filter(r => !isRowEmpty(r));



        // const collData = JSON.stringify(fsYr_1) + JSON.stringify(fsYr_2) + JSON.stringify(scYr_1) + JSON.stringify(scYr_2) + JSON.stringify(trYr_1) + JSON.stringify(trYr_2) + JSON.stringify(frYr_1) + JSON.stringify(frYr_2);

        if (ftYr_1_clean.length > 0) {
            tableMap.push({
                year_level: 5,
                semester: "1st Semester",
                table: ftYr_1_clean
            });
        }

        if (ftYr_2_clean.length > 0) {
            tableMap.push({
                year_level: 5,
                semester: "2nd Semester",
                table: ftYr_2_clean
            });
        }

        postData = [
            {
                name: "table_Data",
                value : JSON.stringify(tableMap)
            }
        ]

        const send_data = postData.concat(formData);
        console.log('formData: ', send_data);
        

        $.ajax({
            url: "<?php echo BASE_URL; ?>dean/actions/prospectus_process.php",
            method: "POST",
            data: send_data,
            dataType: "json",
            success: function (data) {
                console.log('Response from server:', data);
                return;
            },
            error: function () {
                swal({ title: "Error", text: "Request failed.", icon: "error" });
            }
        });
    });


});
</script>
</html>
