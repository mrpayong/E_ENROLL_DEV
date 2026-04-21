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
                <div class="page-inner">

                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                    ?>

                    <section class="section">
                            <div class="row justify-content-center m-0">
                                
                                    <section class="card shadow-sm  p-0" style="margin:auto;">
                                        <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                            <h1 class="fw-semibold mb-3 mb-md-0 fs-4 text-white">Course Table</h1>
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
                
                    <div class="modal fade" id="updateCourse" tabindex="-1" aria-labelledby="updateLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="updateForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <h5 class="modal-title fw-6 fw-bolder" id="updateLabel" autocomplete="off"></h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <label for="limit_course" class="form-label fw-bold">Course Limit</label>
                                    <input type="number" name="limit_course" id="limit_course" class="form-control">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>



            </div>
            <?php include_once FOOTER_PATH; ?>
        </div>
    </div>
</body>s
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    function actionsFormatter(cell) {
        const row = cell.getRow().getData();

        return `
            <button data-id="${row.subject_id}" class="btn btn-sm btn-warning me-2 update-course" title="Update"><i class="fas fa-edit"></i> Update</button>
        `;
    }

    const courseTable = new Tabulator("#courseTable", {
        ajaxURL: "<?php echo BASE_URL; ?>registrar/actions/fetchCourse.php",
        ajaxConfig: "GET",
        layout: "fitDataStretch",
        // responsiveLayout: "collapse",
        pagination: "remote",
        paginationSize: 10,
        movableColumns: true,
        ajaxFiltering: true,
        ajaxSorting: true,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        columns: [
            {
                title: "Course code",
                field: "subject_code",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                // frozen: !isMobile(),
            },
            {
                title: "Course name",
                field: "subject_title",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                // frozen: !isMobile(),
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
                title: "Course limit",
                field: "limit",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const limit = cell.getValue();
                    return `${limit} Students`;
                }
            },
            {
                title: "Program Code",
                field: "short_name",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title: "Curriculum",
                field: "header",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
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

    let subjectCodeDataList = [];
    
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

    // create course
    $("#courseForm").on('submit', function(e){
        e.preventDefault();

        
        const formData = jQuery('#courseForm').serializeArray();

        const newData = [
            {
                name: "submitCourse",
                value: "createCourse"
            }
        ]

        const postData = formData.concat(newData);

        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/course_process.php",
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
                            // fetchSubjectCode_units();
                            // renderCourseNameFields(false); 
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

    let courseId = "";
    document.querySelector('#courseTable').addEventListener('click', function(e){
        e.preventDefault();
        const update = e.target.closest('.update-course');
        if(update){
            const rowId = Number(update.getAttribute('data-id'));
            const row = courseTable.getRows().find(r => r.getData().subject_id == rowId);
            
            const rowData = row.getData();
            console.log('row: ', rowData);

            document.getElementById('updateLabel').textContent = `${rowData.subject_code} — ${rowData.subject_title}`
            courseId = rowData.subject_id;
            $('#updateCourse').modal('show');
        }
    })

    $('#updateForm').on('submit', function(e){
        e.preventDefault();

        const formData = jQuery('#updateForm').serializeArray();
        const newData = [
            {
                name: "submitCourse",
                value: "updateCourse"
            },
            {
                name:"editId",
                value: courseId
            }
        ]

        const postData = formData.concat(newData);

        console.log('form: ', postData)
        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/course_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    if(data.msg_status === true && data.code === 200){
                        swal({
                            icon: "success",
                            title: "Course updated!",
                            text: "Course has been updated.",
                            timer: 3000,
                            button: false
                        }).then(function(){
                            $('#updateCourse').modal('hide');
                            $('#updateForm')[0].reset();
                            courseTable.setData();
                        })
                    }
                    if(data.msg_status === false && data.code === 501){
                        swal({
                            icon: "error",
                            title: "Failed to update course.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 502){
                        swal({
                            icon: "error",
                            title: "Failed to update course.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 500){
                        swal({
                            icon: "error",
                            title: "Failed to update course.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 404){
                        swal({
                            icon: "error",
                            title: "Failed to update course.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 400){
                        swal({
                            icon: "error",
                            title: "Failed to update course.",
                            text: data.msg_response,
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
    });

})


</script>

</html>


