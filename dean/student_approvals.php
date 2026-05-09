<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;
require DOMAIN_PATH . '/dean/process/dean_backlog_helper.php';


$general_page_title = "Course Offering";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];


if ($g_user_role !== "DEAN") {
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include_once DOMAIN_PATH . '/global/meta_data.php';
        include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
</head>
<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>
        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>
            <div class="container">
                <div class="page-inner">
                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                    ?>
                    <div class="card card-round">
                        <div class="card-header d-flex justify-content-between p-2 text-white rounded-top-2 bg-primary align-items-center">
                            <div>
                                <i class="fas fa-clipboard-list fs-2 ms-2"></i><span class="fs-2 ms-2">Offered Courses Table</span>
                            </div>
                            <div class="me-2 gap-2">
                                <button class="btn btn-sm btn-light fs-6" id="Upload_offer" type="button"><i class="fas fa-upload"></i> Upload Courses to Offer</button>
                                <button class="btn btn-sm btn-light fs-6" id="Add_offer" type="button"><i class="fas fa-plus"></i> Add Courses to Offer</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-3">
                                <div class="col-md-6">
                                    <div class="input-group align-items-center">
                                        <label for="syDropdown" class="fw-semibold me-2">School Year / Semester</label>
                                        <select id="syDropdown" style="width:250px;"></select>
                                        <button id="generateBtn" type="button" style="height: 2.1rem; margin-bottom:0.35rem !important;" class="bg-success text-white border-0 rounded-end">
                                            Generate
                                        </button>
                                    </div>
                                </div>
                                <div class="responsive-table col-md-12 w-auto">
                                    <div id="courseOffers"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DOMAIN_PATH . '/global/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="uploadOffer_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <form class="modal-content" id="uploadOffer_form" autocomplete="off">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fw-6">Upload Courses to Offer</h5>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-row align-items-center justify-content-between mb-2">
                        <a href="<?php echo BASE_URL; ?>dean/download_course.php?attach=IMP_OFFERED_COURSE" target="_blank">
                            <i class="fas fa-download"></i>&ensp;Download Offered Courses CSV Template
                        </a>
                    </div>
                    <input type="file" name="import_offered_course_file" id="import_offered_course_file" class="bulk_dropify" data-allowed-file-extensions="csv" accept=".csv" required>
                    <div class="mt-3" id="uploadOfferRowsTable"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="confirmUploadOfferBtn" class="btn btn-primary btn-sm" disabled>Confirm</button>
                    <button type="button" id="hideUploadModal" class="btn btn-danger btn-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="courseList_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <form class="modal-content" id="offerList_form" autocomplete="off">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fw-6" id="courseList_title"></h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning text-black mb-3">
                        <strong><i class="fas fa-exclamation-triangle text-warning"></i> WARNING: Closing this form or reloading the page will remove your progress.</strong>
                    </div>
                    <div class="offer-entry-grid mb-3 align-items-center">
                        <div>
                            <label class="form-label fw-semibold mb-1">Course</label>
                            <select class="form-control" id="offerCourseSelect"></select>
                        </div>
                        <div>
                            <label class="form-label fw-semibold mb-1">Year Level</label>
                            <select class="form-control" id="offerYearLevelSelect">
                                <option value="" disabled selected>Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label fw-semibold mb-1 d-block">&nbsp;</label>
                            <button type="button" class="btn btn-success btn-sm w-100" id="addOfferRowBtn">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div id="offerRowsTable"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                    <button type="button" id="hideCreateModal" class="btn btn-danger btn-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function escapeHtml(value) {
                const temp = document.createElement('div');
                temp.textContent = value == null ? '' : String(value);
                return temp.innerHTML;
            }

            function scheduleFormatter(cell) {
                const value = cell.getValue();
                if (!value) {
                    return '<span class="text-muted">No schedule</span>';
                }

                let parsed = value;
                if (typeof parsed === 'string') {
                    try {
                        parsed = JSON.parse(parsed);
                    } catch (error) {
                        return escapeHtml(parsed);
                    }
                }

                if (!Array.isArray(parsed) || parsed.length === 0) {
                    return '<span class="text-muted">No schedule</span>';
                }

                return parsed.map(function (item) {
                    return escapeHtml(String(item).replaceAll('::', ' | '));
                }).join('<br>');
            }

            function statusFormatter(cell) {
                const status = String(cell.getValue() || '').toUpperCase();
                const badgeMap = {
                    'ACTIVE': 'bg-success',
                    'INACTIVE': 'bg-secondary',
                    'ARCHIVED': 'bg-dark'
                };

                const badgeClass = badgeMap[status] || 'bg-secondary';
                return '<span class="badge ' + badgeClass + '">' + escapeHtml(status || 'UNKNOWN') + '</span>';
            }

            function swalError(title, text) {
                swal({
                    title: title,
                    icon: 'error',
                    text: text,
                    button: true
                });
            }

            function swalSuccess(title, text) {
                swal({
                    title: title,
                    icon: 'success',
                    text: text,
                    button: true
                });
            }

            let currentSchoolYearId = 0;
            let availableScheduledCourses = [];
            let pendingOfferRows = [];
            const courseListModalElement = document.getElementById('courseList_modal');
            const courseListModalInstance = new bootstrap.Modal(courseListModalElement, {
                backdrop: 'static',
                keyboard: false
            });
            const uploadOfferModalElement = document.getElementById('uploadOffer_modal');
            const uploadOfferModalInstance = new bootstrap.Modal(uploadOfferModalElement, {
                backdrop: 'static',
                keyboard: false
            });
            let offerRowsTable = null;
            let uploadOfferRowsTable = null;
            let pendingUploadOfferRows = [];

            const offeringTable = new Tabulator('#courseOffers', {
                ajaxURL: "<?php echo BASE_URL; ?>dean/actions/fetchRequestCourses.php",
                ajaxConfig: "GET",
                pagination: "remote",
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50],
                ajaxFiltering: true,
                ajaxSorting: true,
                movableColumns: true,
                headerFilterPlaceholder: "Search",
                placeholder: "No Data Found",
                layout: "fitDataStretch",
                minHeight: 300,
                height: 420,
                ajaxParams: function () {
                    return {
                        school_year_id: currentSchoolYearId || ''
                    };
                },
                ajaxResponse: function(url, params, response) {
                    if (!response || response.msg_status !== true) {
                        swalError('Load Failed', response && response.msg_response ? response.msg_response : 'Unable to load offered subject list.');
                        return {
                            last_page: 1,
                            data: []
                        };
                    }
                    return {
                        last_page: response.last_page || 1,
                        data: Array.isArray(response.data) ? response.data : []
                    };
                },
                columns: [
                    // { title: 'ID', field: 'offered_subject_id', hozAlign: 'center', width: 90, headerFilter: 'input' },
                    { title: 'Program', field: 'program_short_name', headerFilter: 'input' },
                    { title: 'Section', field: 'class_name', headerFilter: 'input' },
                    { title: 'Course Code', field: 'course_code', headerFilter: 'input' },
                    { title: 'Course Title', field: 'course_title', headerFilter: 'input', minWidth: 220 },
                    { title: 'Year Level', field: 'year_level', hozAlign: 'center', headerFilter: 'input', width: 110 },
                    { title: 'Semester', field: 'sem', hozAlign: "center", headerFilter: 'input', minWidth: 140 },
                    // { title: 'Status', field: 'status', formatter: statusFormatter, hozAlign: 'center', headerFilter: 'input', width: 120 },
                    // { title: 'Section Limit', field: 'section_limit', hozAlign: 'center', width: 120 },
                    // { title: 'Course Limit', field: 'course_limit', hozAlign: 'center', width: 120 },
                    // { title: 'Schedule', field: 'schedule', formatter: scheduleFormatter, minWidth: 260 },
                    // { title: 'Created By', field: 'dean_name', headerFilter: 'input', minWidth: 180 },
                    { title: 'Offered On', field: 'createdAt', hozAlign: "center", headerFilter: 'input', minWidth: 180 }
                ]
            });

            function removeOfferRowByIndex(rowIndex) {
                if (Number.isNaN(rowIndex) || rowIndex < 0 || rowIndex >= pendingOfferRows.length) {
                    return;
                }

                pendingOfferRows.splice(rowIndex, 1);
                refreshOfferRowsTable();
            }

            function ensureOfferRowsTable() {
                if (offerRowsTable) {
                    return offerRowsTable;
                }

                offerRowsTable = new Tabulator('#offerRowsTable', {
                    data: [],
                    layout: 'fitColumns',
                    height: 280,
                    placeholder: 'No course added yet.',
                    headerVisible: true,
                    movableColumns: false,
                    reactiveData: false,
                    columns: [
                        {
                            title: 'Course',
                            field: 'course_label',
                            minWidth: 320,
                            formatter: function(cell) {
                                return escapeHtml(cell.getValue() || '');
                            }
                        },
                        {
                            title: 'Year Level',
                            field: 'year_level',
                            width: 150,
                            hozAlign: 'center'
                        },
                        {
                            title: 'Action',
                            field: 'actions',
                            width: 110,
                            hozAlign: 'center',
                            headerSort: false,
                            formatter: function(cell) {
                                const rowData = cell.getRow().getData();
                                return '<button type="button" class="btn btn-outline-danger btn-sm js-remove-offer-row" data-row-index="' + rowData.row_index + '"><i class="fas fa-times"></i></button>';
                            },
                            cellClick: function(e, cell) {
                                const rowData = cell.getRow().getData();
                                removeOfferRowByIndex(parseInt(rowData.row_index, 10));
                            }
                        }
                    ]
                });

                return offerRowsTable;
            }

            function buildOfferPreviewColumns(withAction) {
                const columns = [
                    {
                        title: 'Course',
                        field: 'course_label',
                        minWidth: 320,
                        formatter: function(cell) {
                            return escapeHtml(cell.getValue() || '');
                        }
                    },
                    {
                        title: 'Program Code',
                        field: 'program_short_name',
                        width: 130,
                        hozAlign: 'center'
                    },
                    {
                        title: 'Year Level',
                        field: 'year_level',
                        width: 140,
                        hozAlign: 'center'
                    }
                ];

                if (withAction) {
                    columns.push({
                        title: 'Action',
                        field: 'actions',
                        width: 110,
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function(cell) {
                            const rowData = cell.getRow().getData();
                            return '<button type="button" class="btn btn-outline-danger btn-sm" data-row-index="' + rowData.row_index + '"><i class="fas fa-times"></i></button>';
                        },
                        cellClick: function(e, cell) {
                            const rowIndex = parseInt(cell.getRow().getData().row_index, 10);
                            if (Number.isNaN(rowIndex)) return;
                            pendingUploadOfferRows.splice(rowIndex, 1);
                            refreshUploadOfferRowsTable();
                        }
                    });
                }

                return columns;
            }

            function ensureUploadOfferRowsTable() {
                if (uploadOfferRowsTable) {
                    return uploadOfferRowsTable;
                }

                uploadOfferRowsTable = new Tabulator('#uploadOfferRowsTable', {
                    data: [],
                    layout: 'fitColumns',
                    height: 280,
                    placeholder: 'Upload a CSV file to preview courses.',
                    columns: buildOfferPreviewColumns(true)
                });

                return uploadOfferRowsTable;
            }

            function refreshUploadOfferRowsTable() {
                const table = ensureUploadOfferRowsTable();
                const tableData = pendingUploadOfferRows.map(function(row, index) {
                    return {
                        row_index: index,
                        teacher_class_id: row.teacher_class_id,
                        year_level: row.year_level,
                        course_label: row.course_label || '',
                        program_short_name: row.program_short_name || ''
                    };
                });

                table.setData(tableData);
                document.getElementById('confirmUploadOfferBtn').disabled = tableData.length === 0;
            }

            function resetUploadOfferForm() {
                pendingUploadOfferRows = [];
                refreshUploadOfferRowsTable();

                const form = document.getElementById('uploadOffer_form');
                if (form) form.reset();

                const dropify = $('#import_offered_course_file').data('dropify');
                if (dropify) {
                    dropify.resetPreview();
                    dropify.clearElement();
                }
            }

            function populateSYDropdown() {
                $.ajax({
                    url: "<?php echo BASE_URL; ?>dean/actions/fetchSemesterForForm.php",
                    method: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (!response || response.status !== true || !Array.isArray(response.data)) {
                            return;
                        }

                        const $syDropdown = $('#syDropdown');
                        if ($syDropdown[0] && $syDropdown[0].selectize) {
                            $syDropdown[0].selectize.destroy();
                        }

                        $syDropdown.empty();
                        $syDropdown.append('<option value="" disabled selected>Select School Year/Sem</option>');

                        response.data.forEach(function(item) {
                            $syDropdown.append(
                                $('<option>', {
                                    value: item.school_year_id,
                                    text: item.school_year + ' - ' + item.sem
                                })
                            );
                        });

                        $syDropdown.selectize({
                            allowEmptyOption: true,
                            create: false,
                            sortField: 'text',
                            onChange: function(value) {
                                currentSchoolYearId = parseInt(value, 10) || 0;
                                if (currentSchoolYearId) {
                                    offeringTable.setData("<?php echo BASE_URL; ?>dean/actions/fetchRequestCourses.php", {
                                        school_year_id: currentSchoolYearId
                                    });
                                } else {
                                    offeringTable.clearData();
                                }
                            }
                        });

                        const selectize = $syDropdown[0].selectize;
                        const defaultItem = response.data.find(function(item) {
                            return parseInt(item.isDefault || 0, 10) === 1;
                        }) || response.data[0];

                        if (defaultItem) {
                            selectize.setValue(String(defaultItem.school_year_id), true);
                            currentSchoolYearId = parseInt(defaultItem.school_year_id, 10) || 0;
                            offeringTable.setData("<?php echo BASE_URL; ?>dean/actions/fetchRequestCourses.php", {
                                school_year_id: currentSchoolYearId
                            });
                        }
                    }
                });
            }

            function fetchScheduledCoursesForSelectedTerm(callback) {
                if (!currentSchoolYearId) {
                    swalError('School Year / Semester Required', 'Please select a school year / semester first.');
                    return;
                }

                $.ajax({
                    url: "<?php echo BASE_URL; ?>dean/actions/fetchOfferedCourse.php",
                    method: "GET",
                    data: {
                        school_year_id: currentSchoolYearId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (!response || response.msg_status !== true) {
                            swalError('Load Failed', response && response.msg_response ? response.msg_response : 'Unable to load scheduled courses.');
                            return;
                        }

                        availableScheduledCourses = Array.isArray(response.data) ? response.data : [];
                        if (typeof callback === 'function') {
                            callback();
                        }
                    },
                    error: function() {
                        swalError('Error', 'Network/Server error occurred.');
                    }
                });
            }

            function buildScheduledCourseOptions($select) {
                if (!$select || !$select.length) return;

                if ($select[0].selectize) {
                    $select[0].selectize.destroy();
                }

                $select.empty();
                $select.append('<option value="" disabled selected>Select Scheduled Course</option>');

                availableScheduledCourses.forEach(function(item) {
                    $select.append(
                        $('<option>', {
                            value: item.teacher_class_id,
                            text: item.display_text
                        })
                    );
                });

                $select.selectize({
                    allowEmptyOption: true,
                    create: false,
                    sortField: 'text'
                });
            }

            function getScheduledCourseByTeacherClassId(teacherClassId) {
                return availableScheduledCourses.find(function(item) {
                    return parseInt(item.teacher_class_id, 10) === parseInt(teacherClassId, 10);
                }) || null;
            }

            function refreshOfferRowsTable() {
                const table = ensureOfferRowsTable();
                const tableData = [];

                pendingOfferRows.forEach(function(row, index) {
                    const course = getScheduledCourseByTeacherClassId(row.teacher_class_id);
                    tableData.push({
                        row_index: index,
                        teacher_class_id: row.teacher_class_id,
                        year_level: row.year_level,
                        course_label: course ? String(course.display_text || '') : 'Unknown course'
                    });
                });

                table.setData(tableData);
            }

            function resetOfferRows() {
                pendingOfferRows = [];
                refreshOfferRowsTable();
            }

            function resetOfferEntryInputs() {
                const courseSelect = document.getElementById('offerCourseSelect');

                if (courseSelect && courseSelect.selectize) {
                    courseSelect.selectize.clear(true);
                    // courseSelect.selectize.focus();
                }
            }

            function setupOfferEntryInputs() {
                buildScheduledCourseOptions($('#offerCourseSelect'));

                const $yearLevelSelect = $('#offerYearLevelSelect');
                if ($yearLevelSelect[0] && $yearLevelSelect[0].selectize) {
                    $yearLevelSelect[0].selectize.destroy();
                }

                $yearLevelSelect.selectize({
                    allowEmptyOption: true,
                    create: false,
                    sortField: 'text'
                });
            }

            function addPendingOfferRow() {
                const courseSelect = document.getElementById('offerCourseSelect');
                const yearLevelSelect = document.getElementById('offerYearLevelSelect');

                const teacherClassId = courseSelect && courseSelect.selectize ? courseSelect.selectize.getValue() : '';
                const yearLevel = yearLevelSelect && yearLevelSelect.selectize ? yearLevelSelect.selectize.getValue() : '';

                if (!teacherClassId || !yearLevel) {
                    swalError('Incomplete Data', 'Please select both course and year level before adding to the list.');
                    return;
                }

                const duplicateExists = pendingOfferRows.some(function(row) {
                    return parseInt(row.teacher_class_id, 10) === parseInt(teacherClassId, 10)
                        && parseInt(row.year_level, 10) === parseInt(yearLevel, 10);
                });

                if (duplicateExists) {
                    swalError('Duplicate Entry', 'This course and year level combination is already in the list.');
                    return;
                }

                pendingOfferRows.push({
                    teacher_class_id: parseInt(teacherClassId, 10),
                    year_level: parseInt(yearLevel, 10)
                });

                refreshOfferRowsTable();
                resetOfferEntryInputs();
            }

            document.getElementById('generateBtn').addEventListener('click', function() {
                const syValue = $('#syDropdown').val();
                currentSchoolYearId = parseInt(syValue, 10) || 0;

                if (!currentSchoolYearId) {
                    swalError('School Year / Semester Required', 'Please select a school year / semester first.');
                    return;
                }

                offeringTable.setData("<?php echo BASE_URL; ?>dean/actions/fetchRequestCourses.php", {
                    school_year_id: currentSchoolYearId
                });
            });

            document.getElementById('Add_offer').addEventListener('click', function() {
                const syValue = $('#syDropdown').val();
                currentSchoolYearId = parseInt(syValue, 10) || 0;

                fetchScheduledCoursesForSelectedTerm(function() {
                    courseListModalInstance.show();
                    document.getElementById('courseList_title').textContent = "Create Course Offers";
                    resetOfferRows();
                    setupOfferEntryInputs();
                    setTimeout(function() {
                        ensureOfferRowsTable().redraw(true);
                    }, 150);
                });
            });

            document.getElementById('Upload_offer').addEventListener('click', function() {
                const syValue = $('#syDropdown').val();
                currentSchoolYearId = parseInt(syValue, 10) || 0;

                if (!currentSchoolYearId) {
                    swalError('School Year / Semester Required', 'Please select a school year / semester first.');
                    return;
                }

                resetUploadOfferForm();
                uploadOfferModalInstance.show();
                setTimeout(function() {
                    ensureUploadOfferRowsTable().redraw(true);
                }, 150);
            });

            document.getElementById('hideCreateModal').addEventListener('click', function(){
                courseListModalInstance.hide();
            });

            document.getElementById('hideUploadModal').addEventListener('click', function(){
                uploadOfferModalInstance.hide();
                resetUploadOfferForm();
            });

            document.getElementById('addOfferRowBtn').addEventListener('click', function() {
                if (!currentSchoolYearId) {
                    swalError('School Year / Semester Required', 'Please select a school year / semester first.');
                    return;
                }

                if (!Array.isArray(availableScheduledCourses) || availableScheduledCourses.length === 0) {
                    fetchScheduledCoursesForSelectedTerm(function() {
                        setupOfferEntryInputs();
                    });
                    return;
                }

                addPendingOfferRow();
            });

            $('#offerList_form').on('submit', function(e) {
                e.preventDefault();

                if (!Array.isArray(pendingOfferRows) || pendingOfferRows.length === 0) {
                    swalError('No Data', 'Please add at least one course offer to the list before submitting.');
                    return;
                }

                $.ajax({
                    url: "<?php echo BASE_URL; ?>dean/actions/fetchOfferedCourse.php",
                    method: "POST",
                    dataType: "json",
                    data: [
                        { name: 'submitOfferedCourseList', value: 'createOfferedCourseList' },
                        { name: 'school_year_id', value: currentSchoolYearId },
                        { name: 'offered_rows', value: JSON.stringify(pendingOfferRows) }
                    ],
                    success: function(response) {
                        if (response && response.code === 200 && response.msg_status === true) {
                            swalSuccess('Saved Successfully', response.msg_response || 'Offered subject list saved successfully.');
                            courseListModalInstance.hide();
                            resetOfferRows();
                            resetOfferEntryInputs();
                            offeringTable.setData("<?php echo BASE_URL; ?>dean/actions/fetchRequestCourses.php", {
                                school_year_id: currentSchoolYearId
                            });
                            return;
                        }

                        swalError('Save Failed', response && response.msg_response ? response.msg_response : 'Unable to save offered subject list.');
                    },
                    error: function() {
                        swalError('Error', 'Network/Server error occurred.');
                    }
                });
            });

            $('#import_offered_course_file').on('change', function() {
                if (!this.files || this.files.length === 0) {
                    pendingUploadOfferRows = [];
                    refreshUploadOfferRowsTable();
                    return;
                }

                if (!currentSchoolYearId) {
                    swalError('School Year / Semester Required', 'Please select a school year / semester first.');
                    this.value = '';
                    return;
                }

                const formData = new FormData();
                formData.append('school_year_id', currentSchoolYearId);
                formData.append('import_offered_course_file', this.files[0]);

                $.ajax({
                    url: "<?php echo BASE_URL; ?>dean/actions/importOfferedCourse_process.php",
                    method: "POST",
                    data: formData,
                    dataType: "json",
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        swal({
                            title: "Uploading",
                            icon: "info",
                            text: "Please wait while the CSV is validated.",
                            buttons: false,
                            closeOnClickOutside: false,
                            closeOnEsc: false
                        });
                    },
                    complete: function() {
                        swal.close();
                    },
                    success: function(response) {
                        if (!response || response.msg_status !== true) {
                            pendingUploadOfferRows = [];
                            refreshUploadOfferRowsTable();
                            swalError('Upload Failed', response && response.msg_response ? response.msg_response : 'Unable to parse uploaded CSV file.');
                            return;
                        }

                        pendingUploadOfferRows = (response.data || []).map(function(row) {
                            return {
                                teacher_class_id: parseInt(row.teacher_class_id, 10),
                                year_level: parseInt(row.year_level, 10),
                                course_label: row.course_label || '',
                                program_short_name: row.program_short_name || ''
                            };
                        }).filter(function(row) {
                            return row.teacher_class_id > 0 && row.year_level > 0;
                        });

                        refreshUploadOfferRowsTable();
                    },
                    error: function() {
                        pendingUploadOfferRows = [];
                        refreshUploadOfferRowsTable();
                        swalError('Error', 'Network/Server error occurred.');
                    }
                });
            });

            $('#uploadOffer_form').on('submit', function(e) {
                e.preventDefault();

                if (!Array.isArray(pendingUploadOfferRows) || pendingUploadOfferRows.length === 0) {
                    swalError('No Data', 'Please upload a valid CSV file before confirming.');
                    return;
                }

                const offeredRows = pendingUploadOfferRows.map(function(row) {
                    return {
                        teacher_class_id: parseInt(row.teacher_class_id, 10),
                        year_level: parseInt(row.year_level, 10)
                    };
                });

                $.ajax({
                    url: "<?php echo BASE_URL; ?>dean/actions/fetchOfferedCourse.php",
                    method: "POST",
                    dataType: "json",
                    data: [
                        { name: 'submitOfferedCourseList', value: 'createOfferedCourseList' },
                        { name: 'school_year_id', value: currentSchoolYearId },
                        { name: 'offered_rows', value: JSON.stringify(offeredRows) }
                    ],
                    success: function(response) {
                        if (response && response.code === 200 && response.msg_status === true) {
                            swalSuccess('Saved Successfully', response.msg_response || 'Offered subject list saved successfully.');
                            uploadOfferModalInstance.hide();
                            resetUploadOfferForm();
                            offeringTable.setData("<?php echo BASE_URL; ?>dean/actions/fetchRequestCourses.php", {
                                school_year_id: currentSchoolYearId
                            });
                            return;
                        }

                        swalError('Save Failed', response && response.msg_response ? response.msg_response : 'Unable to save offered subject list.');
                    },
                    error: function() {
                        swalError('Error', 'Network/Server error occurred.');
                    }
                });
            });

            populateSYDropdown();
            ensureOfferRowsTable();
            ensureUploadOfferRowsTable();
            $('.bulk_dropify').dropify({
                messages: {
                    'default': 'Drag and drop your CSV file here.',
                    'replace': 'Drag and drop, or click to replace.',
                    'remove': 'Remove',
                    'error': 'Ooops, something wrong happened.'
                }
            });
        });
    </script>
</body>
</html>
