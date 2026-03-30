<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Course";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];

if (!($g_user_role == "DEAN")) {
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
                <div class="page-inner">

                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                    ?>

                    <section class="section">
                            <div class="row justify-content-center m-0">
                                
                                    <section class="card shadow-sm  p-0" style="margin:auto;">
                                        <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                            <h1 class="fw-semibold mb-3 mb-md-0 fs-4 text-white">Course Table</h1>

                                            <div class="d-flex flex-row gap-1 align-items-center">
                                                <button class="btn btn-info fw-semibold px-4 py-2 rounded-3" id="createCourseBtn" style="background:#173ea5;">
                                                    <i class="fas fa-plus"></i> Create course
                                                </button>

                                                <button class="btn btn-light fw-semibold px-4 py-2 rounded-3" id="bulkAdd" style="background:#173ea5;">
                                                    <i class="fas fa-cloud-upload-alt"></i> Add Bulk Course
                                                </button>
                                            </div>

                                        </header>
                                        <div class="table-responsive px-3 pb-4 pt-1 mt-3 d-flex flex-column justify-content-between" style="min-height: 40rem;">
                                            <div class="table-bordered" id="courseTable"></div>
                                        
                                            <!-- <div id="footer-total" style="text-align:right; padding: 10px; font-weight:bold;"></div> -->
                                            <div>
                                                <button type="button" class="btn btn-primary btn-sm fs-6" id="course-download-csv">Download as CSV</button>
                                                <button type="button" class="btn btn-primary btn-sm fs-6" id="course-download-xlsx">Download as XLSX</button>
                                                <button type="button" class="btn btn-primary btn-sm fs-6" id="user-print-table">Print</button>
                                            </div>
                                        </div>
                                    </section>
                                

                            </div>
                        
                    </section>
                

                    <!-- create course -->
                    <div class="modal fade" id="courseFormModal" tabindex="-1" aria-labelledby="courseFormLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="courseForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <h5 class="modal-title" id="courseFormLabel">Create course</h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div>
                                            <label for="courseCode" class="form-label">Course Code</label>
                                            <input type="text" class="form-control" id="courseCode" name="courseCode" required>
                                        </div>
                                        <div>
                                            <label for="manual" class="form-label">Set as Manual Enroll</label>
                                            <input type="checkbox" class="form-check-input" id="manual" name="manual">
                                        </div>                        
                                    </div>

                                    <div class="mb-3" id="courseNameContainer">
                                        <label for="courseName" class="form-label">Course title</label>
                                        <input type="text" class="form-control" id="courseName" name="courseName[]" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="course_limit" class="form-label">Course Limit</label>
                                        <input type="number" class="form-control" id="course_limit" name="course_limit" required>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="lec_units" class="form-label">Lecture</label>
                                            <input type="number" class="form-control" id="lec_units" placeholder="No. of units" name="lec_units" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lab_units" class="form-label">Laboratory</label>
                                            <input type="number" class="form-control" id="lab_units" placeholder="No. of units" name="lab_units" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="unit" class="form-label">Unit</label>
                                        <input type="number" class="form-control" id="unit" name="unit" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Create</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- void course -->
                    <div class="modal fade" id="arcModal" tabindex="-1" aria-labelledby="arcModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="arcForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <label class="modal-title fs-5 fw-bolder" id="arcModalLabel"></label>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="form-text fs-6 fw-bold" id="arcDesc" ></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-confirm btn-sm fs-6 btn-success">Confirm</button>
                                    <button type="button" class="btn btn-cancel btn-sm fs-6 btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="modal fade" id="bulkModal" tabindex="-1" aria-labelledby="bulkLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <label id="bulkLabel" class="modal-title">
                                        Bulk Upload
                                    </label>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="file" name="" id="file_courses" class="bulk_dropify" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                    <button type="button" class="btn btn-cancel btn-sm btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>


                </div>
            </div>
            <?php include_once FOOTER_PATH; ?>
        </div>
    </div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const openModalBtn = document.getElementById('createCourseBtn');

    openModalBtn.addEventListener('click', function(){
        $('#courseFormModal').modal('show')
    })

    function actionsFormatter(cell) {
        const row = cell.getRow().getData();

        return `
            <button data-id="${row.subject_id}" class="btn btn-sm btn-secondary me-2 arc-course" title="Edit"><i class="fas fa-archive"></i> Void</button>
        `;
    }

    const highlightColors = [
        "#FFF3CD", // yellow
        "#D1ECF1", // blue
    ];
    let duplicateCodes = {};
    let codeColorMap = {};

    function findDuplicateCodes(data) {
        const codeCount = {};
        data.forEach(row => {
            codeCount[row.subject_code] = (codeCount[row.subject_code] || 0) + 1;
        });
        duplicateCodes = {};
        codeColorMap = {};
        let colorIndex = 0;
        Object.keys(codeCount).forEach(code => {
            if(codeCount[code] > 1) {
                duplicateCodes[code] = true;
                codeColorMap[code] = highlightColors[colorIndex % highlightColors.length];
                colorIndex++;
            }
        });
    }
    
    const courseTable = new Tabulator("#courseTable", {
        ajaxURL: "<?php echo BASE_URL; ?>dean/actions/fetchCourse.php",
        ajaxConfig: "GET",
        layout: "fitDataStretch",
        responsiveLayout: "collapse",
        pagination: "remote",
        paginationSize: 10,
        movableColumns: true,
        ajaxFiltering: true,
        ajaxSorting: true,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        ajaxResponse: function(url, params, response){
            findDuplicateCodes(response.data);
            return response;
        },
        rowFormatter: function(row){
            const data = row.getData();
            if(duplicateCodes[data.subject_code]){
                row.getElement().style.backgroundColor = codeColorMap[data.subject_code];
            }
        },
        columns: [
            {
                title: "Course code",
                field: "subject_code",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center"
            },
            {
                title: "Course name",
                field: "subject_title",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center"
            },
            {
                title: "Lecture",
                field: "lec",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const limitData = cell.getValue();
                    return limitData > 1
                        ? `${limitData} Units` 
                        : limitData === 1
                            ? `${limitData} Unit`
                            : 'No unit';
                }
            },
            {
                title: "Laboratory",
                field: "lab",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const limitData = cell.getValue();
                    return limitData > 1
                        ? `${limitData} Units` 
                        : limitData === 1
                            ? `${limitData} Unit`
                            : 'No unit';
                }
            },
            {
                title: "Units",
                field: "unit",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const limitData = cell.getValue();
                    return limitData > 1
                        ? `${limitData} Units` 
                        : limitData === 1
                            ? `${limitData} Unit`
                            : 'No unit';
                }
            },
            {
                title: "Date modified",
                field: "date_modified",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center"
            },
            {
                title: "Actions",
                field: "actions",
                headerSort: false,
                print: false,
                download:false,
                headerFilter: false,
                headerHozAlign: "center",
                hozAlign: "center",
                formatter: actionsFormatter,
            }
        ]
    }) 

    function loadingAPIrequest(status){
        if(status === true){
            swal({
                title: "Loading",
                icon: 'info',
                text: "Please wait",
                button:false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });
        }
        if(status === false){
            swal.close();
        }
    }

    let subjectCodeDataList = []; // [{subject_code, lec, lab, unit}]

    async function fetchSubjectCode_units(){
        try {
            const response = await $.ajax({
                url: "<?php echo BASE_URL; ?>dean/actions/subjectFormAutomate.php",
                method: "GET",
                dataType: "json",
            })
            subjectCodeDataList = response.data;
        } catch (error) {
            swal({
                icon: "error",
                title: "Error",
                text: "Code detection and Auto-fill will not commence.",
                button: true
            })
        }
    }

    fetchSubjectCode_units();

    document.getElementById('course-download-csv').addEventListener('click', function() {
        courseTable.download("csv", "course_" + new Date().toISOString().slice(0,10) + ".csv", {
            bom: true
        });
    });
    
    document.getElementById('course-download-xlsx').addEventListener('click', function() {
        courseTable.download("xlsx", "course_" + new Date().toISOString().slice(0,10) + ".xlsx");
    });

    document.getElementById('user-print-table').addEventListener('click', function() {
        courseTable.print(false, true);
    });

    let newManual;
    let isCourseNameMultiple = $('#manual').is(':checked');
    let checkState = false;



    function renderCourseNameFields(isManual) {
        if (isCourseNameMultiple === isManual) return;
        isCourseNameMultiple = isManual;
        const container = $('#courseNameContainer');
        container.empty();

        container.append('<label class="form-label">Course name</label>');
        if (isManual) {
            // Multiple input fields with Add/Remove
            container.append(`
                <div id="courseNameList">
                    <div class="input-group mb-2 course-name-row">
                        <input type="text" class="form-control" name="courseName[]" required>
                        <button type="button" class="btn btn-success add-course-name">+</button>
                    </div>
                </div>
            `);
        } else {
            // Single input field
            container.append('<input type="text" class="form-control" id="courseName" name="courseName[]" required>');
        }
    }
    
    
    function tryAutoFillCourseFields() {
        const courseCode = $('#courseCode').val().trim();

        let match = null
        if (courseCode) {
            // Format subject_code
            const subject_code = courseCode.toUpperCase();

            // Find matching subject_code in the array
            match = subjectCodeDataList.find(item => item.subject_code === subject_code) || null;

            if (match) {
                $('#lec_units').val(match.lec);
                $('#lab_units').val(match.lab);
                $('#unit').val(match.unit);
                $('#lec_units').prop('readonly', true);
                $('#lab_units').prop('readonly', true);
                $('#unit').prop('readonly', true);

                if (!$('#manual').is(':checked')) {
                    $('#manual').prop('checked', true);
                }
                $('#manual').prop('disabled', true);
                renderCourseNameFields(true);
                newManual = match.manualEnroll;
                
            } else {
                $('#lec_units').val('').prop('readonly', false);
                $('#lab_units').val('').prop('readonly', false);
                $('#unit').val('').prop('readonly', false);

                // If user checked, keep checked and enable
                if (checkState) {
                    $('#manual').prop('checked', true);
                    $('#manual').prop('disabled', false);
                    renderCourseNameFields(true);
                } else {
                    $('#manual').prop('checked', false);
                    $('#manual').prop('disabled', false);
                    renderCourseNameFields(false);
                }
                newManual = match !== null ? 1 : 0;
           
                
            }
        } else {
            $('#lec_units').val('').prop('readonly', false);
            $('#lab_units').val('').prop('readonly', false);
            $('#unit').val('').prop('readonly', false);

            if (checkState) {
                $('#manual').prop('checked', true);
                $('#manual').prop('disabled', false);
                renderCourseNameFields(true);
            } else {
                $('#manual').prop('checked', false);
                $('#manual').prop('disabled', false);
                renderCourseNameFields(false);
            }
            newManual = match !== null ? 1 : 0;
        }
    }

    // Trigger when either field changes
    $('#acronym, #courseCode').on('input', tryAutoFillCourseFields);
    


    // Initial render (unchecked by default)
    renderCourseNameFields($('#manual').is(':checked'));

    // Toggle on checkbox change
    $('#manual').on('change', function() {
        checkState = this.checked;
        renderCourseNameFields(this.checked);
    });

    // Add/Remove logic for dynamic fields
    $(document).on('click', '.add-course-name', function() {
        $('#courseNameList').append(`
            <div class="input-group mb-2 course-name-row">
                <input type="text" class="form-control" name="courseName[]" required>
                <button type="button" class="btn btn-danger remove-course-name">-</button>
            </div>
        `);
    });
    $(document).on('click', '.remove-course-name', function() {
        $(this).closest('.course-name-row').remove();
    });

    // create course
    $("#courseForm").on('submit', function(e){
        e.preventDefault();

        
        const formData = jQuery('#courseForm').serializeArray();

        const newData = [
            {
                name: "submitCourse",
                value: "createCourse"
            },
            {
                name: "newManual",
                value: newManual
            }
        ]

        const postData = formData.concat(newData);
        console.log('post: ', postData)
        
        $.ajax({
            url: "<?php echo BASE_URL; ?>dean/actions/course_process_dean.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    if(data.status === true && data.code === 200){
                        swal({
                            icon: "success",
                            title: "Course created!",
                            text: "Course has been created.",
                            timer: 3000,
                            button:false,
                        }).then(function(){
                            $('#courseFormModal').modal('hide');
                            $('#courseForm')[0].reset();
                            courseTable.setData();
                            fetchSubjectCode_units();
                            renderCourseNameFields(false); 
                        })
                    }
                    if(data.status === false && data.code === 502){
                        swal({
                            icon: "error",
                            title: "Failed to create section.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.status === false && data.code === 501){
                        swal({
                            icon: "error",
                            title: "Failed to create section.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.status === false && data.code === 500){
                        swal({
                            icon: "error",
                            title: "Failed to create section.",
                            text: "You're good, unkown error that needs consulting has occured. Consult support at MISD is advised.",
                            button: true
                        })
                    }
                }
            },
            error: function(){
                swal({
                    icon: "error",
                    title: "Error",
                    text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                    button: true
                })
            }
        })
    })

    document.getElementById('bulkAdd').addEventListener('click', function(e){
        e.preventDefault();

        $('#bulkModal').modal('show');
    })

    $('.bulk_dropify').dropify({
        messages: {
            'default': 'Drag and drop your CSV file here.',
            'replace': 'Drag and drop, or click to replace.',
            'remove': 'Remove',
            'error': 'Ooops, something wrong happended.'
        }
    });

    document.querySelector('#courseTable').addEventListener('click', function(e){
        e.preventDefault();
        const arcButton = e.target.closest('.arc-course');

        if(arcButton){
            const courId = Number(arcButton.getAttribute('data-id'));
            const row = courseTable.getRows().find(r => r.getData().subject_id == courId);

            const rowData = row.getData();
            console.log('row data: ', rowData);
            document.getElementById('arcDesc').textContent = `Are you sure you want to void ${rowData.subject_title}?`;
            document.getElementById('arcModalLabel').textContent = `${rowData.subject_code} — ${rowData.subject_title}`;
            $('#arcModal').modal('show');

        }
    })


    
    // update course
    // $("#editCourseForm").on('submit', function(e){
    //     e.preventDefault();

    //     const formData = jQuery('#editCourseForm').serializeArray();

    //     const newData = [
    //         {
    //             name: "submitCourse",
    //             value: "editCourse"
    //         },
    //         {
    //             name: "editId",
    //             value: editId
    //         },
    //         {
    //             name: "newManual",
    //             value: newManual
    //         }
    //     ]

    //     const postData = formData.concat(newData);

    //     $.ajax({
    //         url: "<?php echo BASE_URL; ?>registrar/actions/course_process.php",
    //         method: "POST",
    //         data: postData,
    //         dataType: "json",
    //         beforeSend: loadingAPIrequest(true),
    //         complete: loadingAPIrequest(false),
    //         success: function(data){
    //             if(data){
    //                 if(data.msg_status === true && data.code === 200){
    //                     swal({
    //                         icon: "success",
    //                         title: "Course updated!",
    //                         text: "Course has been updated.",
    //                         timer: 3000,
    //                         button: false
    //                     }).then(function(){
    //                         $('#editCourseFormModal').modal('hide');
    //                         $('#editCourseForm')[0].reset();
    //                         courseTable.setData();
    //                         fetchSubjectCode_units();
    //                     })
    //                 }
    //                 if(data.msg_status === false && data.code === 502){
    //                     swal({
    //                         icon: "error",
    //                         title: "Failed to update course.",
    //                         text: data.msg_response,
    //                         button: true
    //                     })
    //                 }
    //                 if(data.msg_status === false && data.code === 504){
    //                     swal({
    //                         icon: "error",
    //                         title: "Failed to update course.",
    //                         text: data.msg_response,
    //                         button: true
    //                     })
    //                 }
    //                 if(data.msg_status === false && data.code === 501){
    //                     swal({
    //                         icon: "error",
    //                         title: "Failed to update course.",
    //                         text: data.msg_response,
    //                         button: true
    //                     })
    //                 }
    //                 if(data.msg_status === false && data.code === 505){
    //                     swal({
    //                         icon: "error",
    //                         title: "Failed to update course.",
    //                         text: data.msg_response,
    //                         button: true
    //                     })
    //                 }
    //                 if(data.msg_status === false && data.code === 500){
    //                     swal({
    //                         icon: "error",
    //                         title: "Failed to update course.",
    //                         text: data.msg_response,
    //                         button: true
    //                     })
    //                 }
    //             }
    //         },
    //         error: function(){
    //             swal({
    //                 icon: "error",
    //                 title: "Error",
    //                 text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
    //                 button: true
    //             })
    //         }
    //     })
    // })


})


</script>

</html>


