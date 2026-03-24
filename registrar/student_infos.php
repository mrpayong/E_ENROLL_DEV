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

$general_page_title = "Student Information";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];

## table
$table_array = array();
$select = "SELECT user_id,general_id,f_name,m_name,l_name,suffix,birth_date,sex,user_role as roles,username,email_address,position,status,locked FROM users WHERE user_id = '".escape($db_connect, $s_user_id)."'";
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
$porgrams = array();
$program_query = "SELECT program_id, program FROM programs";
if($query = call_mysql_query($program_query)){
    if($num = call_mysql_num_rows($query)){
        while($data = call_mysql_fetch_array($query)){
            array_push($porgrams, $data);
        }
    }
}
$encoded_programs = json_encode($porgrams);

$departments = array();
$department_query = "SELECT department_id, department FROM departments";
if($query = call_mysql_query($department_query)){
    if($num = call_mysql_num_rows($query)){
        while($data = call_mysql_fetch_array($query)){
            array_push($departments, $data);
        }
    }
}
$encoded_departments = json_encode($departments);
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

        <div id="main" class="container">
            <div class="page-inner">
                <?php
                include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                ?>
            
                <section class="section">
                    <div class="row justify-content-center m-0">
                        <section class="card shadow-sm p-0" style="margin:auto;">
                            <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                <label class="fw-semibold mb-3 mb-md-0 fs-5 text-white">Student Information Table</label>
                            </header>
                            <div class="p-3" style="min-height: 40rem;">
                                <div id="student-table"></div>
                            </div>
                        </section>
                    </div>
                </section>

                <!-- update modal -->
                <section>
                    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form class="modal-content" id="updateForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <h5 class="modal-title" id="updateModalLabel"></h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="d-flex flex-row gap-3 w-auto">
                                        <div class="mb-3">
                                            <i class="bi bi-person-badge"></i>
                                            <label for="idNumber" class="form-label">Student ID</label>
                                            
                                            <input type="text" class="form-control" id="idNumber" name="idNumber" required readOnly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="f_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="f_name" name="f_name" required readOnly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="m_name" class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" id="m_name" name="m_name" required readOnly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="l_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="l_name" name="l_name" required readOnly>
                                        </div>
                                    </div>


                                    <div class="d-flex flex-row gap-3 w-auto">
                                        <div class="mb-3">
                                            <label for="suffix" class="form-label">Suffix Name</label>
                                            <input type="text" class="form-control" id="suffix" name="suffix" required readOnly>
                                        </div>
                                        <div class="mb-3">
                                            <i class="bi bi-gender-ambiguous"></i>
                                            <label for="gender" class="form-label">Gender</label>
                                            <input class="form-control" id="gender" name="gender" required readOnly>
                                        </div>
                                        <div class="mb-3">
                                            <i class="bi bi-calendar-event"></i>
                                            <label for="birth_date" class="form-label">Date of Birth</label>
                                            <input type="text" class="form-control" id="birth_date" name="birth_date" required readOnly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" required readOnly>
                                        </div>
                                    </div>



                                    <div class="row" >
                                        <div class="col-md-6 mb-3">
                                            <label for="barangay" class="form-label">Barangay</label>
                                            <select class="form-select" id="barangay" name="barangay" required>
                                                <option value="" disabled selected>Select Barangay</option>
                                                <option value="Brgy. Canlubang">Brgy. Canlubang</option>
                                                <option value="Brgy. Mayapa">Brgy. Mayapa</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="addressLong" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="addressLong" name="addressLong" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contact" class="form-label">Contact Number</label>
                                            <input type="text" min="0" max="9" pattern="\d{11}"
                                            inputmode="numeric" maxlength="11" placeholder="0000 000 0000" class="form-control" id="contact" name="contact" 
                                            oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11);" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="ccc_email" class="form-label">CCC Email</label>
                                            <input type="email" class="form-control" id="ccc_email" name="ccc_email" required readOnly>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="year_level" class="form-label">Year Level</label>
                                            <input type="number" class="form-control"
                                                maxlength="1" id="year_level" name="year_level" inputmode="numeric" pattern="\d{11}"
                                                oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11);"
                                                required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="curriculum" class="form-label">Curriculum</label>
                                            <select id="curriculum" name="curriculum" required>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row w-auto">
                                        <div class="col-md-4 mb-3">
                                            <label for="department" class="form-label">Department</label>
                                            <select class="form-control" id="department" name="department" required>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="program" class="form-label">Program</label>
                                            <select class="form-control" id="program" name="program" required>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="major" class="form-label">Major</label>
                                            <input type="text" class="form-control" id="major" name="major">
                                        </div>
                                    </div>
                                    
                                    <div class="row" >
                                        <div class="col-md-6 mb-3">
                                            <label for="emergency" class="form-label">Emergency</label>
                                            <textarea type="text" class="form-control" id="emergency" name="emergency" required>
                                            </textarea>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="additional_data" class="form-label">Additional Info</label>
                                            <textarea type="text" class="form-control" id="additional_data" name="additional_data" required>
                                            </textarea>
                                        </div>
                                    </div>

                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn-confirm btn btn-primary">Update</button>
                                    <button type="button" class="btn-cancel btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- lock modal -->
                <section>
                    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="lockForm" autocomplete="off">
                                <div class="modal-header bg-primary text-white py-2">
                                    <h5 class="modal-title" id="statusModalLabel"></h5>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div>
                                        <p id="lockDesc" class="form-text text-dark fw-semibold"></p>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn-confirm btn btn-primary">Confirm</button>
                                    <button type="button" class="btn-cancel btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <?php include_once FOOTER_PATH; ?>
    </div>
</div>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const programs = <?php echo $encoded_programs; ?>;
    const departments = <?php echo $encoded_departments; ?>;

    function populateDepartment(selector, selectedId = null) {
        const dropdown = $(selector);
        if(dropdown[0].selectize){
            dropdown[0].selectize.destroy();
        }
        dropdown.empty();
        dropdown.append('<option value="" selected disabled>Select Department</option>');
        departments.forEach(function(item){
            dropdown.append(
                $('<option>', {
                    value: item.department_id,
                    text: item.department
                })
            );
        });
        dropdown.selectize({
            allowEmptyOption: true,
            create: false,
            sortField: 'text'
        });
        if(selectedId){
            dropdown[0].selectize.setValue(selectedId);
        }
    };

    function populateCurriculumDropdown(selector, selectedId = null) {
        const dropdown = $(selector);
        // Destroy previous selectize instance if exists
        if(dropdown[0].selectize){
            dropdown[0].selectize.destroy();
        }
        dropdown.empty();
        dropdown.append('<option value="" selected disabled>Select Curriculum</option>');

        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/fetchCurrForPros.php",
            method: "GET",
            dataType: "json",
            success: function(response){
                if(response.code === 200 && response.data){
                    response.data.forEach(function(item){
                        dropdown.append(
                            $('<option>', {
                                value: item.curriculum_id,
                                text: item.header + " (" + item.curriculum_code + ")"
                            })
                        );
                    });
                    dropdown.selectize({
                        allowEmptyOption: true,
                        create: false,
                        sortField: 'text'
                    });
                    if(selectedId){
                        dropdown[0].selectize.setValue(selectedId);
                    }
                }
            }
        });
    }

    function populateProgramDropdown(selector, selectedId = null) {
        const dropdown = $(selector);
        if(dropdown[0].selectize){
            dropdown[0].selectize.destroy();
        }
        dropdown.empty();
        dropdown.append('<option value="" selected disabled>Select Program</option>');
        programs.forEach(function(item){
            dropdown.append(
                $('<option>', {
                    value: item.program_id,
                    text: item.program
                })
            );
        });
        dropdown.selectize({
            allowEmptyOption: true,
            create: false,
            sortField: 'text'
        });
        if(selectedId){
            dropdown[0].selectize.setValue(selectedId);
        }
    };

    function actionsFormatter(cell) {
        const row = cell.getRow().getData();
        const status = Number(row.status);

        let action = ``;
        
        if(status === 0){
            action += `<button data-id="${row.student_id}" class="btn btn-sm btn-primary me-2 edit-btn" title="Update"><i class="text-white fas fa-pencil-alt"></i></button>`
            action += `<button data-id="${row.student_id}" class="btn btn-sm btn-warning unlock-btn" title="Unlock"><i class="text-white fas fa-lock-open"></i></button>`
        }
        if(status === 1){
            action += `<button data-id="${row.student_id}" class="btn btn-sm btn-dark lock-btn" title="Lock"><i class="text-white fas fa-lock"></i></button>`
        }
        return action;
    }

    function valueFormatter(cellVal, rowVal){
        return function (cell){
            const rowData = cell.getRow().getData();
            const valueA = rowData[cellVal];
            const valueB = rowData[rowVal];

            if (!valueA || !valueB) {
                return 'update data';
            }
            if (valueA !== valueB) {
                return `mismatch`;
            }
            return valueA ?? valueB;
        }
    }

    var studentTable = new Tabulator("#student-table", {
        ajaxURL: "<?php echo BASE_URL; ?>registrar/actions/fetchStudentInfo.php",
        ajaxConfig: "GET",
        layout: "fitDataStretch",
        pagination: "remote",
        paginationSize: 10,
        rowHeight:80,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        columns: [
            {
                title: "Actions",
                formatter: actionsFormatter,
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title: "Student ID", 
                field: "student_id_no", 
                headerFilterLiveFilter: true,
                hozAlign: "center",
                headerHozAlign: "center",
                headerFilter: "input",
            },
            {
                title: "Name", 
                field: "name", 
                headerFilterLiveFilter: true,
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title: "Gender", 
                field: "sex", 
                headerFilterLiveFilter: true,
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(data){
                    const sex = data.getValue();
                    return sex.toUpperCase();
                }
            },
            {
                title: "Birth Date", 
                field: "birth_date", 
                headerFilterLiveFilter: true,
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title: "Username", 
                field: "username", 
                headerFilterLiveFilter: true,
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title: "CCC Email", 
                field: "ccc_email", 
                headerFilterLiveFilter: true,
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title: "Barangay", 
                field: "barangay", 
                headerFilterLiveFilter: true,
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const rowBrgy = cell.getRow().getData().barangay;
                    return rowBrgy 
                        ? rowBrgy 
                        : '';
                }
            },
            {
                title: "Address", 
                field: "address", 
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const rowAddress = cell.getRow().getData().address;
                    return rowAddress 
                        ? rowAddress 
                        : '';
                }
            },
            {
                title: "Contact", 
                field: "contact", 
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const rowContact = cell.getRow().getData().contact;
                    return rowContact 
                        ? rowContact 
                        : '';
                }
            }
        ],
    });

    function loadingAPIrequest(status){
        if(status === true){
            swal({
                title: "Loading",
                icon: 'info',
                text: "Please wait",
                button: false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });
        }
        if(status === false){
            swal.close();
        }
    }


    let editId;
    let currStatus;
    let newStatus;
    document.querySelector('#student-table').addEventListener('click', function(e){
        e.preventDefault();
        const editBtn = e.target.closest('.edit-btn');
        const lockBtn = e.target.closest('.unlock-btn');
        const unlockBtn = e.target.closest('.lock-btn');

        if(editBtn){
            const rowId = editBtn.getAttribute('data-id');
            const row = studentTable.getRows().find(r => r.getData().student_id == rowId);

            const rowData = row.getData();
            console.log('updating:', rowData);
            document.getElementById('updateModalLabel').textContent = `Update ${rowData.name}`;
            document.getElementById('idNumber').value = rowData.student_id_no !== null ? rowData.student_id_no : rowData.general_id;
            document.getElementById('f_name').value = rowData.f_name;
            document.getElementById('m_name').value = rowData.m_name;
            document.getElementById('l_name').value = rowData.l_name;
            document.getElementById('suffix').value = rowData.suffix;
            document.getElementById('birth_date').value = rowData.birth_date;
            document.getElementById('username').value = rowData.username;
            document.getElementById('gender').value = rowData.sex
            document.getElementById('username').value = rowData.username;
            document.getElementById('ccc_email').value = rowData.email_address;

            document.getElementById('addressLong').value = rowData.address !== "" ? rowData.address : '';
            document.getElementById('contact').value = rowData.contact !== "" ? rowData.contact : '';
            document.getElementById('year_level').value = Number(rowData.year_level) !== 0 ? rowData.year_level : '';
            document.getElementById('major').value = rowData.major !== "" ? rowData.major : '';
            document.getElementById('barangay').value = rowData.barangay !== "" ? rowData.barangay : '';
            populateProgramDropdown('#program', rowData.program_id);
            populateDepartment('#department', rowData.department_id);
            populateCurriculumDropdown('#curriculum', rowData.curriculum_id);
            document.getElementById('emergency').value = Number(rowData.emergency_data) !== 0 ? rowData.emergency_data : '';
            document.getElementById('additional_data').value = Number(rowData.additional_data) !== "" ? rowData.additional_data : '';

            $('#updateModal').modal('show');
        }
        if(lockBtn || unlockBtn){
            let rowId = null;
            if(lockBtn){
                rowId = lockBtn.getAttribute('data-id');
            }
            if(unlockBtn){
                rowId = unlockBtn.getAttribute('data-id');
            }

            const row = studentTable.getRows().find(r => r.getData().student_id == rowId);
            const rowData = row.getData();
            editId = rowData.student_id_no;
            currStatus = rowData.status;

            if(lockBtn){
                document.getElementById('statusModalLabel').textContent = `Lock ${rowData.name}'s Account`;
                document.getElementById('lockDesc').textContent = `Are you sure you want to lock ${rowData.name}'s account?`;
            }
            if(unlockBtn){
                document.getElementById('statusModalLabel').textContent = `Unlock ${rowData.name}'s Account`;
                document.getElementById('lockDesc').textContent = `Are you sure you want to unlock ${rowData.name}'s account?`;
            }
            $('#statusModal').modal('show');
        }
    });

    document.querySelector('#lockForm').addEventListener('click', function(e){
        const confirmBtn = e.target.closest('.btn-confirm');
        const cancelBtn = e.target.closest('.btn-cancel');

        if(confirmBtn){
            if(currStatus === 0){
                newStatus = 1;
            } 
            if(currStatus === 1){
                newStatus = 0;
            }
        }

        if(cancelBtn){
            $('#statusModal').modal('hide');
            editId = '';
            currStatus = '';
            newStatus = '';
            document.getElementById('statusModalLabel').textContent = ``;
            document.getElementById('statusDesc').textContent = ``;
            return;
        }
    })
    

    // update 
    $('#updateForm').on('submit', function(e){
        e.preventDefault();
        const formData = jQuery('#updateForm').serializeArray();

        const newData = [
            {
            name: "submitStudent",
            value: "updateStudent"
            },
            {
                name: "editId",
                value: editId
            }
        ];

        const postData = formData.concat(newData);
        console.log('post:', postData)

        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/student_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data.msg_status === true && data.code === 200){
                    swal({
                        title: data.msg_title,
                        text: data.msg_response,
                        icon: "success",
                        button:false,
                        timer:3000
                    }).then(function(){
                        $('#updateModal').modal('hide');
                        $('#updateForm')[0].reset();
                        studentTable.setData();
                    })
                }
                if(data.msg_status === false && data.code === 501){
                    swal({
                        title: "Failed to update",
                        icon: "error",
                        text: data.msg_response,
                        button: true,
                    })
                }
                if(data.msg_status === false && data.code === 502){
                    swal({
                        title: "Failed to update",
                        icon: "error",
                        text: data.msg_response,
                        button: true,
                    })
                }
                if(data.msg_status === false && data.code === 503){
                    swal({
                        title: "Failed to update",
                        icon: "error",
                        text: data.msg_response,
                        button: true,
                    })
                }
                if(data.msg_status === false && data.code === 504){
                    swal({
                        title: "Update Notice",
                        icon: "info",
                        text: data.msg_response,
                        button: true,
                    })
                }
                if(data.msg_status === false && data.code === 500){
                    swal({
                        title: "Failed to update",
                        icon: "error",
                        text: data.msg_response,
                        button: true,
                    })
                }
            },
            error: function(xhr, status, error){
                swal({
                    title: "Failed to update",
                    icon: "error",
                    text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                    button: true
                })
            }
        })
    })


    // lock
    $('#lockForm').on('submit', function(e){
        e.preventDefault();

        const postData = [
            {
                name: "submitStudent",
                value: "lockStudent"
            },
            {
                name: "editId",
                value: editId
            },
            {
                name: "newStatus",
                value: newStatus
            }
        ];
        console.log('post data:', postData);


        $.ajax({
            url: "<?php echo BASE_URL; ?>registrar/actions/student_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    if(data.msg_status === true && data.code === 200){
                        swal({
                            title: data.msg_title,
                            text: data.msg_response,
                            icon: "success",
                            buttons: false,
                            timer:3000
                        }).then(function(){
                            $('#statusModal').modal('hide');
                            $('#lockForm')[0].reset();
                            studentTable.setData();
                        })
                    }
                    if(data.msg_status === false && data.code === 501){
                        swal({
                            title: "Failed to lock",
                            icon: "error",
                            text: data.msg_response,
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 502){
                        swal({
                            title: "Failed to lock",
                            icon: "error",
                            text: data.msg_response,
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 503){
                        swal({
                            title: "Failed to lock",
                            icon: "error",
                            text: data.msg_response,
                            button: true,
                        })
                    }
                    if(data.msg_status === false && data.code === 500){
                        swal({
                            title: "Failed to lock",
                            icon: "error",
                            text: data.msg_response,
                            button: true,
                        })
                    }
                }
            },
            error: function(xhr, status, error){
                swal({
                    title: "Failed to lock",
                    icon: "error",
                    text: "You're good, possible network interruption. Check your internet connection. Consult support at MISD is advised.",
                    button: true
                })
            }
        })
    })

});
</script>

</html>