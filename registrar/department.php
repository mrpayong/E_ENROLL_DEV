
<?php
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Use only for purple badge, as NiceAdmin/Bootstrap doesn't have this by default */
        .badge-purple {
            background: #f3e8ff !important;
            color: #a259e6 !important;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include_once DOMAIN_PATH . '/global/sidebar.php';?>
    <div class="main-panel">
        <?php include_once DOMAIN_PATH . '/global/header.php';?>
        <div class="container">
                <main id="main">
                    <section class="section">

                        <div class="row justify-content-center mx-4 m-4">
                            <section class="card shadow-sm  p-0" style="margin:auto;">
                                <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <h1 class="fw-semibold text-white mb-3 mb-md-0 fs-3">Department</h1>
                                    <button
                                        id="addDepartmentBtn"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#departmentModal"
                                        class="btn btn-info fs-6 fw-semibold rounded-3 d-flex align-items-center">
                                        <i class="bi bi-building-add me-2"></i> Add Department
                                    </button>
                                </header>
                                <div class="table-responsive px-3 pb-4 pt-1 mt-3">
                                    <div id="department-table" class="table-bordered border rounded"></div>
                                </div>

                            </section>
                        </div>
                    </section>
                </main>

                <!-- Modal for Add Department -->
                <section>
                <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-modal="true" role="dialog">
                    <div class="modal-dialog" role="document">
                    <form class="modal-content" id="departmentForm" autocomplete="off">
                        <header class="modal-header py-2 bg-eclearance text-white">
                        <h2 class="modal-title fs-5" id="departmentModalLabel">Add Department</h2>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </header>
                        <section class="modal-body">
                        <div class="mb-3">
                            <label for="departmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="departmentName" name="departmentName" required>
                        </div>
                        <div class="mb-3">
                            <label for="deptHead" class="form-label">Dean</label>
                            <select class="form-select" id="deptHead" name="deptHead" required>
                                <option value="">Choose a Dean</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="codeName" class="form-label">Department Code</label>
                            <input type="text" class="form-control" id="codeName" name="codeName" required>
                        </div>
                        </section>
                        <footer class="modal-footer py-1">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="cancelDepartmentBtn">Cancel</button>
                        </footer>
                    </form>
                    </div>
                </div>
                </section>

                <!-- modal for edit department -->
                <section>
                <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-modal="true" role="dialog">
                    <div class="modal-dialog" role="document">
                    <form class="modal-content" id="editDepartmentForm" autocomplete="off">
                        <header class="modal-header py-2 bg-eclearance text-white">
                        <h2 class="modal-title bg-eclearance text-light fs-5" id="editDepartmentModalTitle"></h2>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </header>
                        <section class="modal-body">
                        <div class="mb-3">
                            <label for="departmentNewName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="departmentNewName" name="departmentNewName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDeptHead" class="form-label">Dean</label>
                            <select class="form-select" id="editDeptHead" name="editDeptHead" required>
                                <option id="editDeptHeadId" value="">Choose a Dean</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newCodeName" class="form-label">Department Code</label>
                            <input type="text" class="form-control" id="newCodeName" name="newCodeName" required>
                        </div>
                        </section>
                        <footer class="modal-footer py-1">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="cancelDepartmentBtn">Cancel</button>
                        </footer>
                    </form>
                    </div>
                </div>
                </section>

                <!-- add program  -->
                <section>
                    <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="programForm" autocomplete="off">
                                <div class="modal-header bg-eclearance text-white py-2">
                                    <h5 class="modal-title" id="programModalLabel">Add Program</h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control bg-secondary text-white" id="department" name="department" readOnly required>
                                    </div>

                                    <div class="mb-3 d-flex flex-row gap-2 w-100">
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programName" class="form-label">Program Name</label>
                                            <input type="text" class="form-control" id="programName" name="programName" required>
                                        </div>
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programCode" class="form-label">Program Code</label>
                                            <input type="text" class="form-control" id="programCode" name="programCode" required>
                                        </div>
                                    </div>



                                    <div class="mb-3">
                                        <label for="major" class="form-label">Major</label>
                                        <input type="text" class="form-control" id="major" name="major">
                                    </div>

                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration</label>
                                        <input type="number" class="form-control" id="duration" name="duration" placeholder="0" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- add major -->
                <section>
                    <div class="modal fade" id="majorModal" tabindex="-1" aria-labelledby="majorModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form class="modal-content" id="majorForm" autocomplete="off">
                                <div class="modal-header bg-eclearance text-white py-2">
                                    <h5 class="modal-title" id="majorModalLabel">Add major</h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">

                                    <div class="mb-3 d-flex flex-row gap-2 w-100">
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programName-major" class="form-label">Program Name</label>
                                            <input type="text" class="form-control bg-secondary text-white" id="programName-major" name="programName-major" readOnly required>
                                        </div>
                                        <div class="d-flex flex-column gap-2 w-50">
                                            <label for="programCode-major" class="form-label">Program Name</label>
                                            <input type="text" class="form-control bg-secondary text-white" id="programCode-major" name="programCode-major" readOnly required>
                                        </div>
                                    </div>



                                    <div class="mb-3">
                                        <label for="major" class="form-label">Major</label>
                                        <input type="text" placeholder="e.g. Mathematics, English, Filipino"class="form-control" id="major" name="major" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            
                <!-- update program -->
                <section>
                    <div class="modal fade" id="editProgramModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form class="modal-content" id="editProgramModalForm" autocomplete="off">
                                <div class="modal-header bg-eclearance text-white py-2">
                                    <h5 class="modal-title" id="editProgramModalLabel"></h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>


                                <div class="modal-body">

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="newCode" class="form-label">Program Code</label>
                                            <input type="text" class="form-control" id="newCode" name="newCode" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="newProgram" class="form-label">Program Name</label>
                                            <input type="text" class="form-control" id="newProgram" name="newProgram" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="newMajor" class="form-label">Major</label>
                                        <input type="text" class="form-control" id="newMajor" name="newMajor" required>
                                    </div>
                                </div>



                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
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
        // Modal logic
        var departmentModal = new bootstrap.Modal(document.getElementById('departmentModal'));
        var cancelBtn = document.getElementById('cancelDepartmentBtn');
        var form = document.getElementById('departmentForm');

        if(cancelBtn) cancelBtn.addEventListener('click', function () {
            departmentModal.hide();
        });
        // Tabulator logic
        


        function actionsFormatter(cell) {
            const row = cell.getRow().getData();

            if(row.major === "") {
                return `
                <button data-id="${row.program_id}" class="btn btn-sm btn-primary me-2 btn-major" title="Add Major"><i class="bi bi-plus-circle"></i> Add Major</button>
            `;
            }
            return `
                <button data-id="${row.program_id}" class="btn btn-sm btn-primary me-2 btn-major" title="Add Major"><i class="bi bi-plus-circle"></i> Add Major</button>
                <button data-id="${row.program_id}" class="btn btn-sm btn-warning update-prog-btn" title="Update Program"><i class="bi bi-pencil-square"></i> Update Program</button>
            `;
        }

        const departmentTable = new Tabulator("#department-table", {
            ajaxURL: "<?php echo BASE_URL; ?>/registrar/actions/fetchDepartment.php",
            ajaxConfig: "GET",            
            layout: "fitDataStretch",
            movableColumns: true,
            headerFilterPlaceholder: "Search",
            placeholder: "No Data Found",
            pagination: "remote",
            paginationSize: 10,
            groupStartOpen: false,
            groupBy: function(data){
                return data.department
            },
            groupHeader: function(value, count, data, group) {
                const dept = data[0];
                return `
                    <span class="text-black fw-bold">${dept.department} (${dept.code_name})</span>
                    <span class="ms-2">Dean: ${dept.dean}</span>
                    <button data-id="${dept.department_id}" class="btn btn-sm btn-success ms-2 add-btn"><i class="bi bi-plus-circle-dotted"></i> Add Program</button>
                    <button data-id="${dept.department_id}" class="btn btn-sm btn-primary ms-2 edit-dept-btn"><i class="bi bi-pencil-square"></i> Update Department</button>
                `;
            },
            columns: [
                {
                    title: "Actions",
                    field: "actions",
                    headerSort: false,
                    headerFilter: false,
                    headerHozAlign: "center",
                    hozAlign: "center",
                    formatter: actionsFormatter,
                },
                {
                    title: "Program Code",
                    field: "short_name",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
                {
                    title: "Program Name",
                    field: "program",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    hozAlign: "left",
                },
                {
                    title: "Major",
                    field: "major",
                    headerFilter: "input",
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
                {
                    title: "Department",
                    field: "department",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
                {
                    title: "Department Code",
                    field: "code_name",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerHozAlign: "center",
                    headerHozAlign: "center",
                    hozAlign: "center",
                },
            ]
        });


 
        let department_id = '';
        let departmentNewName = '';
        let program_id = '';
        // set up data for Edit form
        document.querySelector("#department-table").addEventListener("click", function(e){
            const editBtn = e.target.closest(".edit-dept-btn")
            const addProgram = e.target.closest(".add-btn")
            const addMajor = e.target.closest(".btn-major")
            const updateProg = e.target.closest('.update-prog-btn');
            if(editBtn){
                const rowId = editBtn.getAttribute('data-id');

                const row = departmentTable.getRows().find(r => r.getData().department_id == rowId);
                if(!row) {
                    Swal.fire({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this department. Possible network disruption.",
                        timer: 5000,
                        showConfirmButton: true
                    })
                    return;
                }

                const rowData = row.getData();
                const newName = document.getElementById('departmentNewName').value = rowData.department;
                document.getElementById('newCodeName').value = rowData.code_name;
                DeanData('#editDeptHead', rowData.user_id);
                document.getElementById('editDepartmentModalTitle').textContent = `Update ${rowData.department}`; 
                department_id = rowId;
                departmentNewName = newName;
                $('#editDepartmentModal').modal('show');

            }
            if(addProgram){
                const rowId = addProgram.getAttribute('data-id');

                const row = departmentTable.getRows().find(r => r.getData().department_id == rowId);
                if(!row) {
                    Swal.fire({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this department",
                        timer: 5000,
                        showConfirmButton: true
                    })
                    return;
                }

                const rowData = row.getData();

                document.getElementById('department').value = rowData.department;
                department_id = rowData.department_id;
                console.log('row data: ', row.getData());
                // $('#archiveDepartmentModal').modal('show');
                $('#programModal').modal('show');
                return;
            }
            if(addMajor){
                const rowId = addMajor.getAttribute('data-id');

                const row = departmentTable.getRows().find(r => r.getData().program_id == rowId);
                if(!row) {
                    Swal.fire({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this department",
                        timer: 5000,
                        showConfirmButton: true
                    })
                    return;
                }

                const rowData = row.getData();

                document.getElementById('programName-major').value = rowData.program;
                document.getElementById('programCode-major').value = rowData.short_name;
                program_id = rowData.program_id
                console.log('row data: ', row.getData(), "ROW ID: ", rowId);
                $('#majorModal').modal('show');
                return;
            }
            if(updateProg){
                const rowId = updateProg.getAttribute('data-id');

                const row = departmentTable.getRows().find(r => r.getData().program_id == rowId);
                if(!row) {
                    Swal.fire({
                        title: "Something went wrong.",
                        icon: "error",
                        text: "Can't find this program. Possible network disruption.",
                        showConfirmButton: true
                    })
                    return;
                }

                const rowData = row.getData();

                console.log('row data: ', rowData);
                document.getElementById('newProgram').value = rowData.program;
                document.getElementById('newCode').value = rowData.short_name;
                document.getElementById('newMajor').value = rowData.major;
                document.getElementById('editProgramModalLabel').textContent = `Update ${rowData.program}`; 
                program_id = rowData.program_id;
                $('#editProgramModal').modal('show');

            }
        })


        function loadingAPIrequest(status){
            console.log("stat: ", status)
            if(status === true){
                Swal.fire({
                    title: "Loading",
                    icon: 'info',
                    text: "Please wait"
                });
                Swal.showLoading();
            }
            if(status === false){
                Swal.close();
            }

        }

        // create
        $('#programForm').on('submit', function(e){
            e.preventDefault();
            const formData = jQuery('#programForm').serializeArray();

            const newData = [
                {
                    name: "submitProgram",
                    value: "createProgram"
                },
                {
                    name: "department_id",
                    value: department_id
                }
            ]

            const postData = formData.concat(newData);
            console.log('postData: ', postData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>/registrar/actions/program_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: loadingAPIrequest(true),
                complete: loadingAPIrequest(false),
                success: function(data){
                    if(data){
                        Swal.close();
                        if(data.status === true && data.code === 200){
                            Swal.fire({
                                title: "Program created!",
                                text: "Program has been created successfully!",
                                icon: "success",
                                timer: 3000,
                                showConfirmButton: false,
                            }).then(function(){
                                $('#programForm')[0].reset();
                                $('#programModal').modal('hide');
                                departmentTable.setData();
                            })
                        }
                        if(data.status === false && data.code === 501){
                            Swal.fire({
                                title: "Failed to create program.",
                                text: data.msg_response,
                                icon: "error",
                                showConfirmButton: true,
                            })
                        }
                        if(data.status === false && data.code === 502){
                            Swal.fire({
                                title: "Failed to create program.",
                                text: "Program name or code already exist.",
                                icon: "error",
                                showConfirmButton: true,
                            }).then(function(){
                                document.getElementById('programCode').value = "";
                                document.getElementById('programName').value = "";
                            })
                        }
                        if(data.status === false && data.code === 500){
                            Swal.fire({
                                title: "An error occured.",
                                text: "You're good, unkown error that needs consulting has occured. Consult support at MISD is advised.",
                                icon: "error",
                                showConfirmButton: true,
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    Swal.fire({
                        title: "Error",
                        icon: "error",
                        text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                        showConfirmButton: true
                    });
                }
            })
        })

        // add major
        $('#majorForm').on('submit', function(e){
            e.preventDefault();
            const formData = jQuery('#majorForm').serializeArray();

            const newData = [
                {
                    name: "submitProgram",
                    value: "addMajor"
                },
                {
                    name: "programId",
                    value: program_id
                }
            ]

            const postData = formData.concat(newData);
            console.log('postData: ', postData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>/registrar/actions/program_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: loadingAPIrequest(true),
                complete: loadingAPIrequest(false),
                success: function(data){
                    if(data){
                        Swal.close();
                        if(data.msg_status === true && data.code === 200){
                            Swal.fire({
                                title: "Major added!",
                                text: data.msg_response,
                                icon: "success",
                                timer: 3000,
                                showConfirmButton: false,
                            }).then(function(){
                                $('#majorForm')[0].reset();
                                $('#majorModal').modal('hide');
                                departmentTable.setData();
                            })
                        }
                        if(data.msg_status === false && data.code === 505){
                            Swal.fire({
                                title: "Failed to add",
                                text: data.msg_response,
                                icon: "error",
                                showConfirmButton: true,
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    Swal.fire({
                        title: "Error",
                        icon: "error",
                        text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                        showConfirmButton: true
                    });
                }
            })
        })

        //  create department
        DeanData('#deptHead'); 
        $(function(){
            $('#departmentForm').on('submit', function(e){
                e.preventDefault();

                const formData = jQuery('#departmentForm').serializeArray();


                const newData = [{
                    name: 'departmentSubmit',
                    value: 'createDepartment'
                }];

                
                const postData = formData.concat(newData);

                $.ajax({
                    url: "<?php echo BASE_URL ?>/registrar/actions/department_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: function(){
                        Swal.fire({
                            title: "Creating",
                            allowOutsideClick:false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(data){
                        Swal.close(); //close loading
                        if(data){
                            if(data.code === 200 && data.msg_status === true){
                                Swal.fire({
                                    title: "Department Created",
                                    icon: "success",
                                    text: data.msg_response,
                                    showConfirmButton: false,
                                    timer: 3000,
                                }).then(function(){
                                    $('#departmentModal').modal('hide');
                                    $('#departmentForm')[0].reset();
                                    departmentTable.setData();
                                });
                            };
                            if(data.code === 501 && data.msg_status === false){
                                // Require fields error hanlder
                                Swal.fire({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 502 && data.msg_status === false){
                                // Department duplicate error handler
                                Swal.fire({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 404 && data.msg_status === false){
                                //  caught error in try-catch handler
                                Swal.fire({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: "Failed to execute action.",
                                    showConfirmButton: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 503 && data.msg_status === false){
                                // Failed query handler
                                Swal.fire({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: "Action was unsuccessful",
                                    showConfirmButton: true,
                                    timer: 5000,
                                })
                                console.error(data.msg_response);
                            };
                            if(data.code === 504 && data.msg_status === false){
                                // dean already assigned
                                Swal.fire({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 5000,
                                })
                            };
                            if(data.code === 500 && data.msg_status === false){
                                // POST method and requeste condition error handler
                                Swal.fire({
                                    title: "Failed creating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 5000,
                                })
                            };
                        } else {
                            Swal.fire({
                                title: "Failed creating department",
                                icon: "error",
                                text: "Network/System disruption occured",
                                showConfirmButton: true,
                            })
                        }
                    },
                    error: function(xhr, status, error){
                        Swal.close();
                        Swal.fire({
                            title: "Error",
                            icon: "error",
                            text: "Network/Server error occured",
                            showConfirmButton:true
                        })
                    }
                    
                });

            })
        });

        // edit department
        $(function(){
            $('#editDepartmentForm').on('submit', function(e){
                e.preventDefault();
                let formData = jQuery('#editDepartmentForm').serializeArray();
                

                newData = [
                    {
                        name: "departmentSubmit",
                        value: "updateDepartment"
                    },
                    {
                        name: 'department_id',
                        value: department_id
                    },
                ];

                const postData = formData.concat(newData);

                
                $.ajax({
                    url: "<?php echo BASE_URL; ?>/registrar/actions/department_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: function(){
                        $("#editDepartmentForm :submit").html('<span class="spinner-border spinner-border-sm"></span>');
                        $("#editDepartmentForm :input").prop('disabled', true);
                    },
                    complete: function(){
                        $('#editDepartmentForm :submit').html('Edit');
                        $("#editDepartmentForm :input").prop("disabled", false);
                        $("#editDepartmentForm :button").prop("disabled", false);
                    },
                    success: function(data){
                        if(data){
                            if(data.code === 200 && data.msg_status === true){
                                Swal.fire({
                                    title: "Department Updated!",
                                    icon: "success",
                                    text: data.msg_response,
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(function(){
                                    $('#editDepartmentModal').modal('hide');
                                    $('#editDepartmentForm')[0].reset();
                                    departmentTable.setData();
                                });
                            };
                            if(data.code === 501 && data.msg_status === false){
                                // Require fields error hanlder
                                Swal.fire({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 500 && data.msg_status === false){
                                // Require fields error hanlder
                                Swal.fire({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 503 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 502 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 404 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 400 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 2000,
                                })
                            };
                            if(data.code === 504 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed updating department",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton: true,
                                    timer: 2000,
                                })
                            };
                        } else {
                            Swal.fire({
                                title: "Failed updating department",
                                icon: "error",
                                text: "Network/System disruption occured",
                                showConfirmButton: true,
                            })
                        }
                    },
                    error: function(xhr, status, error){
                        Swal.close();
                        Swal.fire({
                            title: "Error",
                            icon: "error",
                            text: "Network/Server error occured",
                            showConfirmButton:true
                        })
                    }
                })
            })
        })

        // archive department
        $(function(){
            $('#archiveDepartmentForm').on('submit', function(e){
                e.preventDefault();

                newData = [
                    {
                        name: "departmentSubmit",
                        value: "archiveDepartment"
                    },
                    {
                        name: "newArchiveStatus",
                        value: Number(newDeptStatus)
                    }
                ];
                const postData = archiveForm.concat(newData);

                
                $.ajax({
                    url: "<?php echo BASE_URL; ?>/registrar/actions/department_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: function(){
                    },
                    complete: function(){
                    },
                    success: function(data){
                        if(data){
                            if(data.code === 200 && data.msg_status === true){
                                Swal.fire({
                                    title: "Department Archived Successfully.",
                                    icon: "success",
                                    text: data.msg_response,
                                    showConfirmButton:false,
                                    timer:2000,
                                }).then(function(){
                                    $('#archiveDepartmentModal').modal('hide');
                                    $('#archiveDepartmentForm')[0].reset();
                                    departmentTable.setData();
                                    archiveForm = [];
                                    currDeptStatus = null;
                                    newDeptStatus = null;
                                })
                            }
                            if(data.code === 501 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed to archive department.",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton:true,
                                    timer:5000,
                                })
                            }
                            if(data.code === 500 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed to archive department.",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton:true,
                                    timer:5000,
                                })
                            }
                            if(data.code === 503 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed to archive department.",
                                    icon: "error",
                                    text: data.msg_response,
                                    showConfirmButton:true,
                                    timer:5000,
                                })
                            }
                            if(data.code === 404 && data.msg_status === false){
                                Swal.fire({
                                    title: "Failed to archive department.",
                                    icon: "error",
                                    text: "Failed to execute action.",
                                    showConfirmButton:true,
                                    timer:5000,
                                })
                                console.error(data.msg_response);
                            }
                        }
                    },
                    error: function(xhr, status, error){
                        Swal.close();
                        Swal.fire({
                            title: "Error",
                            icon: "error",
                            text: "Network/Server error occured",
                            showConfirmButton:true
                        })
                    }
                })
            })
        })

        function DeanData(selectSelector, selectedDeanId = null){
            $.ajax({
                url: "<?php echo BASE_URL;?>/registrar/actions/fetchDean.php",
                method: "GET",
                dataType: "json",
                success: function(data){
                    var $deanSelect = $(selectSelector);
                    $deanSelect.empty();
                    $deanSelect.append('<option value="">Choose a Dean</option>');
                    data.forEach(function(dean){
                        $deanSelect.append(
                            $('<option>', {
                                value: dean.user_id,
                                text: dean.name,
                                selected: dean.user_id == selectedDeanId
                            })
                        );
                    });

                    if(selectedDeanId){
                        $deanSelect.val(selectedDeanId)
                    }
                },
                error: function(){
                    Swal.fire({
                        title: "Error",
                        icon: "error",
                        text: "Failed to load deans",
                        showConfirmButton:true
                    });
                },
            })
        }

        $('#departmentModal').on('show.bs.modal', function(){
            DeanData();
        })
});
</script>
</html>