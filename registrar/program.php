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

## table
$table_array = array();
$select = "SELECT user_id,general_id,f_name,m_name,l_name,suffix,birth_date,sex,user_role as roles,username,email_address,position,status,locked FROM users ORDER BY user_id DESC";
if ($query = call_mysql_query($select)) {
    if ($num = mysqli_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data['name'] = get_full_name($data['f_name'],$data['m_name'],$data['l_name'],$data['suffix']);

            $user_roles = [];
            foreach (json_decode($data['roles']) as $role) {
                if (isset(SYSTEM_ACCESS['E-ENROLL']['role'][$role])) {
                    $user_roles[] = SYSTEM_ACCESS['E-ENROLL']['role'][$role];
                }
            }
            $data['user_role'] = !empty($user_roles) ? implode(', ', $user_roles) : '';

            if ($data['status'] == 1) {
                $data['account_status'] = 'Deactivated';
            } elseif ($data['locked'] == 1) {
                $data['account_status'] = 'Locked';
            } elseif ($data['status'] == 0 && $data['locked'] == 0) {
                $data['account_status'] = 'Active';
            }
            array_push($table_array, $data);
        }
    }
}

function departmentMini($dept) {
    switch ($dept) {
        case 'Department of Computing and Informatics':
            return 'DCI';
        case 'Department of Education':
            return 'DTE';
        case 'Department of Business and Accounting':
        case 'Department of Business Administration':
            return 'DBA';
        default:
            return 'Unknown Dept.';
    }
}

$program = [
    [
        'code' => 'BSCS',
        'name' => 'Bachelor of Science in Computer Science',
        'department' => 'Department of Computing and Informatics',
        'duration' => '4 Years',
        'students' => 120,
        'status' => 'Active',
        'action' => "actions"
    ],
    [
        'code' => 'BSIT',
        'name' => 'Bachelor of Science in Information Technology',
        'department' => 'Department of Computing and Informatics',
        'duration' => '4 Years',
        'students' => 220,
        'status' => 'Active',
        'action' => "actions"
    ],
]
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>


</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>


<main id="main" class="main">
    <section class="section">
        
            <div class="row justify-content-center">
                
                    <section class="card shadow-sm  p-0" style="margin:auto;">
                        <header class="d-flex bg-eclearance flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                            <h1 class="fw-semibold mb-3 mb-md-0 fs-4 text-white">Program Management</h1>
                            <button class="btn btn-primary fw-semibold px-4 py-2 rounded-3" id="createProgramBtn" style="background:#173ea5;">
                                <i class="bi bi-plus-lg"></i> Create Program
                            </button>
                        </header>
                        <div class="table-responsive px-3 pb-4 pt-1 mt-3">
                            <div id="programTable" class="table-bordered">
                            </div>
                        </div>
                    </section>
                
            </div>
        
    </section>
</main>

<section>
    <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="programForm" autocomplete="off">
                <div class="modal-header bg-eclearance text-white py-2">
                    <h5 class="modal-title" id="programModalLabel">Create Program</h5>
                    <button type="button" class="btn-close text-white" id="cancelProgramModalBtn" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="programName" class="form-label">Program Name</label>
                        <input type="text" class="form-control" id="programName" name="programName" required>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="major" class="form-label">Major</label>
                        <select class="form-select" id="major" name="major">
                            <option value="">Select Major</option>
                            <option value="MATH">Major in Math</option>
                            <option value="ELEMENTARY">Major in Elementary</option>
                            <option value="SCIENCE">Major in Science</option>
                            <option value="SECONDARY_HIGHSCHOOL">Major in Secondary Highschool</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex flex-row gap-2 w-100">
                        <div class="d-flex flex-column gap-2 w-50">
                            <label for="duration" class="form-label">Duration</label>
                            <input type="number" class="form-control" id="duration" name="duration" placeholder="0" required>
                        </div>
                        <div class="d-flex flex-column gap-2 w-50">
                            <label for="programCode" class="form-label">Program Code</label>
                            <input type="text" class="form-control" id="programCode" name="programCode" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <button type="button" class="btn btn-danger" id="cancelProgramModalBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section>
    <div class="modal fade" id="editProgramModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="editProgramModalForm" autocomplete="off">
                <div class="modal-header bg-eclearance text-white py-2">
                    <h5 class="modal-title" id="editProgramModalLabel"></h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editProgramName" class="form-label">Program Name</label>
                        <input type="text" class="form-control" id="editProgramName" name="newProgramName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDepartment" class="form-label">Department</label>
                        <select class="form-select" id="editDepartment" name="newDepartment" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editMajor" class="form-label">Major</label>
                        <select class="form-select" id="editMajor" name="newMajor">
                            <option value="">Select Major</option>
                            <option value="MATH">Major in Math</option>
                            <option value="ELEMENTARY">Major in Elementary</option>
                            <option value="SCIENCE">Major in Science</option>
                            <option value="SECONDARY_HIGHSCHOOL">Major in Secondary Highschool</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex flex-row gap-2 w-100">
                        <div class="d-flex flex-column gap-2 w-50">
                            <label for="editDuration" class="form-label">Duration</label>
                            <input type="number" class="form-control" id="editDuration" name="newDuration" placeholder="0" required>
                        </div>
                        <div class="d-flex flex-column gap-2 w-50">
                            <label for="editProgramCode" class="form-label">Program Code</label>
                            <input type="text" class="form-control" id="editProgramCode" name="newProgramCode" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>


    <div class="modal fade" id="archiveProgramModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="archiveProgramForm" autocomplete="off">
                <div class="modal-header bg-eclearance text-white py-2">
                    <h5 class="modal-title" id="archiveProgramLabel"></h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p name="archiveDesc" id="archiveDesc" class="form-text text-dark fw-semibold">
                    </div>
                </div>
                <footer class="modal-footer py-1">
                    <button type="submit" class="btn btn-primary confirm-archive-btn">Confirm</button>
                    <button type="button" class="btn btn-danger cancel-archive-btn">Cancel</button>
                </footer>
            </form>
        </div>
    </div>

<?php include_once FOOTER_PATH; ?>


</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var programModal = new bootstrap.Modal(document.getElementById('programModal'));
    var openBtn = document.getElementById('createProgramBtn');
    var cancelBtn = document.getElementById('cancelProgramModalBtn');
    // var form = document.getElementById('programForm');

    openBtn.addEventListener('click', function () {
        programModal.show();
    });
    cancelBtn.addEventListener('click', function () {
        programModal.hide();
        form.reset();
    });

    function actionsFormatter(cell) {
        const row = cell.getRow().getData();

        return `
            <button data-id="${row.program_id}" class="btn btn-sm btn-primary me-2 edit-prog-btn" title="Edit"><i class="bi bi-pencil"></i></button>
            <button data-id="${row.program_id}" class="btn btn-sm btn-danger archive-prog-btn" title="Archive"><i class="bi bi-archive"></i></button>
        `;
    }

    const programTable = new Tabulator("#programTable", {
        ajaxURL: "<?php echo BASE_URL;?>/registrar/actions/fetchProgam.php",
        ajaxConfig:"GET",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: "remote",
        paginationSize: 10,
        movableColumns: true,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        initialSort: [
            { column: "short_name", dir: "asc" },
        ],
        columns: [
            { 
                title: "Program Code", 
                field: "short_name", 
                headerFilter: "input",
                hozAlign: "center", 
                headerHozAlign: "center", 
                width: 150,
            },
            { 
                title: "Program Name", 
                field: "program", 
                headerFilter: "input",
                headerHozAlign: "center", 
                width: 330,
            },
            { 
                title: "Department", 
                field: "department", 
                headerFilter: "input",
                headerHozAlign: "center", 
                width: 305, 
            },
            { 
                title: "Duration", 
                field: "duration", 
                hozAlign: "center", 
                headerFilter: "input",
                headerHozAlign: "center",
                width: 125, 
                formatter: function(cell){
                    return cell.getValue() + " Years";
                }
            },
            { 
                title:"Status", 
                field: "status", 
                hozAlign: "center", 
                headerFilter: "input",
                headerHozAlign: "center", 
                width: 100,
                formatter: function(cell){
                    const status = cell.getValue();
                    if(status === 1){
                        return '<label class="badge" style="font-size:1rem;background:#d1fae5;color:#059669;font-weight:500;">Active</label>';
                    }
                    if(status === 0){
                        return '<label class="badge" style="font-size:1rem;background:#fee2e2;color:#dc2626;font-weight:500;">Locked</label>';
                    }
                }
            },
            {
                title: "Actions",
                field: "actions",
                headerSort: false,
                headerFilter: false,
                headerHozAlign: "center",
                hozAlign: "center",
                formatter: actionsFormatter,
                width: 110,
                maxWidth: 120,
                minWidth: 90
            }
        ],
    });

    let editId;
    let archiveId;
    let currArchiveStatus;
    let newArchiveStatus;
    document.querySelector('#programTable').addEventListener('click', function(e){
        e.preventDefault();
        const editBtn = e.target.closest('.edit-prog-btn');
        const archiveBtn = e.target.closest('.archive-prog-btn');

        if(editBtn){
            const rowId = editBtn.getAttribute("data-id");
            
            const row = programTable.getRows().find(r => r.getData().program_id == rowId);
            const rowData = row.getData();

            document.getElementById('editProgramName').value = rowData.program;
            document.getElementById('editProgramCode').value = rowData.short_name;
            // document.getElementById('editDepartment').value = rowData.department;
            editId = rowData.program_id;
            document.getElementById('editDuration').value = rowData.duration;
            document.getElementById('editMajor').value = rowData.major;
            populateDepartmentDropdown('#editDepartment', rowData.department_id);
            document.getElementById('editProgramModalLabel').textContent = `Edit ${rowData.program}`;
            $('#editProgramModal').modal('show');
        }
        if(archiveBtn){
            const rowId = archiveBtn.getAttribute("data-id");
            const row = programTable.getRows().find(r => r.getData().program_id == rowId);
            const rowData = row.getData();

            archiveId = rowData.program_id;
            currArchiveStatus = rowData.archiveStatus;
            document.getElementById('archiveDesc').textContent = `Are you sure you want to archive ${rowData.program}?`;
            document.getElementById('archiveProgramLabel').textContent = `Archive ${rowData.program}`;
            $('#archiveProgramModal').modal('show');
        }
    })


    document.querySelector('#archiveProgramForm').addEventListener("click", function(e){
        const confirmBtn = e.target.closest('.confirm-archive-btn');
        const cancelBtn = e.target.closest('.cancel-archive-btn')

        if(confirmBtn){
            if(currArchiveStatus === 1){
                newArchiveStatus = 0;
            }
        }
        if(cancelBtn){
            document.getElementById('archiveProgramLabel').textContent = ``; //modal title
            document.getElementById('archiveDesc').textContent = ``; //modal description
            currArchiveStatus = null;
            newArchiveStatus = null;
            if (document.activeElement) document.activeElement.blur();
            return;
        }
    })

    function loadingAPIrequest(status){
        console.log("stat: ", status)
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

    // create program
    populateDepartmentDropdown('#department');
    $("#programForm").on('submit', function (e){
        e.preventDefault();

        const formData = jQuery('#programForm').serializeArray();
        const newData = [{
            name: "submitProgram",
            value: "createProgram"
        }]

        const postData = formData.concat(newData);

        console.log("post: ", postData)

        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/program_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    swal.close();
                    if(data.status === true && data.code === 200){
                        swal({
                            title: "Program created!",
                            text: "Program has been created successfully!",
                            icon: "success",
                            timer: 3000,
                            button: false,
                        }).then(function(){
                            $('#programForm')[0].reset();
                            programModal.hide();
                            programTable.setData();
                        })
                    }
                    if(data.status === false && data.code === 501){
                        swal({
                            title: "Failed to create program.",
                            text: "All fields are required.",
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.status === false && data.code === 502){
                        swal({
                            title: "Failed to create program.",
                            text: "Program name or code already exist.",
                            icon: "error",
                            button: true,
                        }).then(function(){
                            document.getElementById('programCode').value = "";
                            document.getElementById('programName').value = "";
                        })
                    }
                    if(data.status === false && data.code === 500){
                        swal({
                            title: "An error occured.",
                            text: "You're good, unkown error that needs consulting has occured. Consult support at MISD is advised.",
                            icon: "error",
                            button: true,
                        })
                    }
                }
            },
            error: function(xhr, status, error){
                swal({
                    title: "Error",
                    icon: "error",
                    text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                    button: true
                });
            }
        })
    })

    function populateDepartmentDropdown(selected, selectedId = null) {
        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/fetchDeptForProgram.php",
            method: "GET",
            dataType: "json",
            success: function(data) {
                if(data.status === false && data.code === 400){
                    swal({
                        title: "Error!",
                        icon: "error",
                        text: "Unavailable.",
                        button: true,
                        timer: 5000
                    });
                    return;
                }
                if(data.status === false && data.code === 401){
                    swal({
                        title: "Error!",
                        icon: "error",
                        text: "Unavailable.",
                        button: true,
                        timer: 5000
                    });
                    return;
                }
                if(data.status === false && data.code === 500){
                    swal({
                        title: "Error!",
                        icon: "error",
                        text: "Something went wrong.",
                        button: true,
                        timer: 5000
                    });
                    return;
                }
                if(data.status === true && data.code === 200){
                    var $programSelect = $(selected);
                    $programSelect.empty();
                    $programSelect.append('<option value="" disabled selected>Select Department</option>');
                    data.data.forEach(function(departments) {
                        $programSelect.append(
                            $('<option>', {
                                value: departments.department_id,
                                text: departments.department,
                                selected: departments.department_id == selectedId
                            })
                        );
                    });
                    if(selectedId){
                        $programSelect.val(selectedId)
                    }
                }
            },
            error: function() {
                swal({
                    title: "Error",
                    icon: "error",
                    text: "Failed to load departments.",
                    button: true
                });
            }
        });
    }

    $('#programModal').on('show.bs.modal', function () {
        populateDepartmentDropdown();
    });

    // update program
    $("#editProgramModalForm").on('submit', function (e){
        e.preventDefault();

        const formData = jQuery('#editProgramModalForm').serializeArray();
        const newData = [
            {
                name: "submitProgram",
                value: "editProgram"
            },
            {
                name: "programId",
                value:editId
            }
        ]

        const postData = formData.concat(newData);

        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/program_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    if(data.msg_status === true && data.code === 200){
                        swal({
                            title: "Program udpated!",
                            text: "Program has been udpated successfully!",
                            icon: "success",
                            timer: 3000,
                            button:false
                        }).then(function(){
                            $('#editProgramModalForm')[0].reset();
                            $('#editProgramModal').modal('hide');
                            programTable.setData();
                        })
                    }
                    if(data.msg_status === false && data.code === 501){
                        swal({
                            title: "Failed to edit program.",
                            text: "All fields are required.",
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 502){
                        swal({
                            title: "Failed to edit program.",
                            text: "It seems the information you are trying to edit does not exist or you have unstable network.",
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 503){
                        swal({
                            title: "Failed to edit program.",
                            text: "Connection failed",
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 504){
                        swal({
                            title: "Failed to edit program.",
                            text: "You did not make any changes.",
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 500){
                        swal({
                            title: "An error occured.",
                            text: "You're good, unkown error that needs consulting has occured. Consult support at MISD is advised.",
                            icon: "error",
                            button: true,
                        })
                    }
                }
            },
            error: function(xhr, status, error){
                swal({
                    title: "Error",
                    icon: "error",
                    text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                    button: true
                });
            }
        })
    })
    
    $("#archiveProgramForm").on('submit', function (e){
        e.preventDefault();

        const postData = [
            {
                name: "submitProgram",
                value: "archiveProgram"
            },
            {
                name: "programId",
                value:archiveId
            },
            {
                name: "newArchiveStatus",
                value: newArchiveStatus
            }
        ]

        console.log('post: ', postData)


        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/program_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    if(data.msg_status === true && data.code === 200){
                        swal({
                            title: "Program archived!",
                            text: "Program has been archived successfully.",
                            icon: "success",
                            timer: 3000,
                            button: false
                        }).then(function(){
                            $('#archiveProgramForm')[0].reset();
                            $('#archiveProgramModal').modal('hide');
                            programTable.setData();
                        })
                    }
                    if(data.msg_status === false && data.code === 502){
                        swal({
                            title: "Failed to archive program.",
                            text: "It seems the information you are trying to archive does not exist or you have unstable network.",
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 503){
                        swal({
                            title: "Failed to archive program.",
                            text: data.msg_response,
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 501){
                        swal({
                            title: "Failed to archive program.",
                            text: data.msg_response,
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 504){
                        swal({
                            title: "Failed to archive program.",
                            text: data.msg_response,
                            icon: "error",
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 500){
                        swal({
                            title: "An error occured.",
                            text: "You're good, unkown error that needs consulting has occured. Consult support at MISD is advised.",
                            icon: "error",
                            button: true,
                        })
                    }
                }
            },
            error: function(xhr, status, error){
                swal({
                    title: "Error",
                    icon: "error",
                    text: "You're good, possibly just a network interruption, check your internet connection. Consult support at MISD is advised.",
                    button: true
                });
            }
        })
    })


});
</script>

</html>