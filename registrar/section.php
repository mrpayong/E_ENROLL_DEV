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
$select = "SELECT user_id,general_id,f_name,m_name,l_name,suffix,birth_date,sex,user_role as roles,username,email_address,position,status,locked 
FROM users WHERE user_id = '".      escape($db_connect, $s_user_id)     ."'";
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

?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>


</head>
<style>
.selectize-control.single .selectize-input {
    border-radius: 0 !important;
}
</style>
<body>
<div class="wrapper">
    <?php include_once DOMAIN_PATH . '/global/sidebar.php';?>
    <div class="main-panel">
        <?php include_once DOMAIN_PATH . '/global/header.php';?>
        <div class="container">

                <section class="section">
                    <div class="row mx-2 mb-3 mt-3">
                        <div class="d-flex flex-md-row flex-column align-items-center col-md-12">
                            <div class="input-group align-items-center">
                                <label for="syDropdown" class="fw-semibold me-2">School Year / Semester</label>
                                <select id="syDropdown" style="width:250px;">
                                </select>
                                <button id="generateBtn" style="height: 2.1rem; margin-bottom:0.35rem !important;" 
                                class="bg-success text-white border-0 rounded-end">
                                    Generate</button>
                            </div>
                        </div>
                    </div>

                    <div class="row justify-content-center mx-4 m-4">
                        <section class="card shadow-sm  p-0">
                            <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                <h1 class="fw-semibold mb-3 mb-md-0 fs-4 text-white">Section</h1>
                                <button class="btn btn-info fw-semibold px-4 py-2 rounded-3" 
                                id="createSectionBtn" style="background:#173ea5;" disabled>
                                    <i class="bi bi-plus-lg"></i> Create Section
                                </button>
                            </header>
                            <div class="table-responsive p-2" style="min-height: 40rem;">
                                <div class="table-bordered" id="sectionTable"></div>
                            </div>
                        </section>
                    </div>


                </section>


                <!-- create -->
                <div class="modal fade" id="sectionFormModal" tabindex="-1" aria-labelledby="sectionLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form class="modal-content" id="sectionForm" autocomplete="off">
                            <div class="modal-header bg-primary text-white py-2">
                                <h5 class="modal-title" id="sectionFormLabel">Create Section</h5>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="sectionName" class="form-label">Section Name</label>
                                    <input type="text" class="form-control" id="sectionName" name="sectionName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="program" class="form-label">Program</label>
                                    <select class="form-select" id="program" name="program" required>
                                        <option value="">Select Program</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="section_limit" class="form-label">Section Limit</label>
                                    <input type="number" class="form-control" id="section_limit" name="section_limit" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Create</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="cancelProgramModalBtn">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- edit -->
                <div class="modal fade" id="editSectionFormModal" tabindex="-1" aria-labelledby="sectionLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form class="modal-content" id="editSectionForm" autocomplete="off">
                            <div class="modal-header bg-primary text-white py-2">
                                <h5 class="modal-title" id="editSectionFormLabel"></h5>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="newSectionName" class="form-label">Section Name</label>
                                    <input type="text" class="form-control" id="newSectionName" name="newSectionName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newProgram" class="form-label">Program</label>
                                    <select class="form-select" id="newProgram" name="newProgram" required>
                                        <option value="">Select Program</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="newLimit" class="form-label">Section Limit (for this section only)</label>
                                    <input type="number" class="form-control" id="newLimit" name="newLimit" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>


        </div>
        <?php include_once FOOTER_PATH; ?>
    </div>
</div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openModalBtn = document.getElementById('createSectionBtn');

    openModalBtn.addEventListener('click', function(){
        $('#sectionFormModal').modal('show')
    })

    function formatDate(dateCreated){
        if (!dateCreated) return "";
        // Parse the date string
        const date = new Date(dateCreated);
        if (isNaN(date)) return dateCreated; // fallback if invalid
        // Format as YYYY-MM-DD or any format you like
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: '2-digit'
        });
    }
    

    function actionsFormatter(cell) {
        const row = cell.getRow().getData();

        return `
            <button data-id="${row.class_id}" class="btn btn-sm btn-primary me-2 edit-section-btn fs-6" title="Edit"><i class="bi bi-pencil"></i> Update Section</button>
        `;
    }

    (function populateSYDropdown() {
        $.ajax({
            url: "<?php echo BASE_URL; ?>/registrar/actions/fetchSemesterForForm.php",
            method: "GET",
            dataType: "json",
            success: function(response) {
                if(response && response.data) {
                    const $syDropdown = $('#syDropdown');
                    $syDropdown.empty();
                    $syDropdown.append('<option value="0">Default School Year/Sem</option>');
                    response.data.forEach(function(item) {
                        $syDropdown.append(
                            $('<option>', {
                                value: item.school_year_id,
                                text: `SY. ${item.school_year}` + " " + item.sem // e.g., "SY. 2024-2025 1st Sem"
                            })
                        );
                    });
                    $syDropdown.selectize({
                        allowEmptyOption: true,
                        create: false,
                        sortField: 'text'
                    });
                }
            }
        });
    })();

    let sy_id;
    document.getElementById('generateBtn').addEventListener('click', function() {
        // Get Selectize value
        const selectize = $('#syDropdown')[0].selectize;
        const schoolYearId = selectize.getValue();

        sy_id = schoolYearId;
        if(schoolYearId !== undefined || schoolYearId !== null){
            $('#createSectionBtn').prop('disabled', false);
        }

        // Reload the Tabulator table with the selected school_year_id as a parameter
        sectionTable.setData("<?php echo BASE_URL; ?>/registrar/actions/fetchSection.php", {
            school_year_id: schoolYearId
        });
    });


    const sectionTable = new Tabulator("#sectionTable", {
        ajaxURL: "<?php echo BASE_URL; ?>/registrar/actions/fetchSection.php",
        ajaxConfig: "GET",
        layout: "fitDataStretch",
        responsiveLayout: "collapse",
        pagination: "remote",
        paginationSize: 10,
        movableColumns: true,
        ajaxFiltering:true,
        ajaxSorting:true,
        rowHeight:80,
        height: "auto",
        headerFilterPlaceholder: "Search",
        ajaxResponse: function(url, params, response){
            // Check if there is data
            if(response && response.data && response.data.length > 0){
                this.setHeight("auto"); // Set height to auto if data exists
            }else{
                this.setHeight("170px"); // Fixed height if no data
            }
            return response;
        },
        placeholder: "Select semester then click &quot;Generate&quot; to load data.",
        columns: [
            {
                title: "Actions",
                field: "actions",
                headerSort: false,
                headerFilter: false,
                headerHozAlign: "center",
                print:false,
                downaload:false,
                hozAlign: "center",
                formatter: actionsFormatter,
            },
            {
                title: "Section Name",
                field: "class_name",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title: "Section Limit per Sem",
                field: "sem_limit",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell){
                    const rowData = cell.getRow().getData();
                    const row = cell.getValue();

                    return rowData.is_default === true
                        ? `${row} Students <span class="badge bg-warning">Default</span>` 
                        : `${row} Students`;
                }
            },
            {
                title: "Program Code",
                field: "short_name",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center"
            },
            {
                title: "Last Updated",
                field: "date_modified",
                headerFilter: "input",
                hozAlign: "center",
                headerHozAlign: "center",
            }
        ],
    });

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

    function populateProgramDropdown(selected, selectedId = null) {
        $.ajax({
            url: "<?php echo BASE_URL; ?>/registrar/actions/fetchProgForSection.php",
            method: "GET",
            dataType: "json",
            success: function(response) {
                if(response.status && response.data) {
                    var $programSelect = $(selected);
                    $programSelect.empty();
                    $programSelect.append('<option value="">Select Program</option>');
                    response.data.forEach(function(prog) {
                        $programSelect.append(
                            $('<option>', {
                                value: prog.program_id,
                                text: prog.program
                            })
                        );
                    });
                    if(selectedId !== null){
                        $programSelect.val(selectedId)
                    }
                }
            },
            error: function() {
                swal({
                    title: "Error",
                    icon: "error",
                    text: "Failed to load programs.",
                    button: true
                });
            }
        });
    }


    // Call this when the modal is shown
    $('#sectionFormModal').on('show.bs.modal', function () {
        populateProgramDropdown();
    });

    // create section
    populateProgramDropdown("#program");
    $("#sectionForm").on('submit', function(e){
        e.preventDefault();

        const formData = jQuery('#sectionForm').serializeArray();
        console.log("form data: ", formData)

        const newData = [
            {
                name: "submitSection",
                value: "createSection"
            },
            {
                name: "school_year_id",
                value: sy_id
            },
        ];

        const postData = formData.concat(newData);
        console.log("post data: ", postData)

        $.ajax({
            url: "<?php echo BASE_URL; ?>/registrar/actions/section_process.php",
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
                            title: "Section created!",
                            text: "Section has been created.",
                            timer: 3000,
                            button: false
                        }).then(function(){
                            $('#sectionFormModal').modal('hide');
                            $('#sectionForm')[0].reset();
                            sectionTable.setData();
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


    let editId;
    document.querySelector('#sectionTable').addEventListener('click', function(e){
        e.preventDefault();
        const editBtn = e.target.closest('.edit-section-btn');

        if(editBtn){
            const rowId = editBtn.getAttribute('data-id');
            const row = sectionTable.getRows().find(r => r.getData().class_id == rowId);
            const rowData = row.getData();

            console.log('row data: ', rowData)

            editId = rowData.class_id;
            document.getElementById('newSectionName').value = rowData.class_name;
            document.getElementById('newLimit').value = rowData.sem_limit;
            populateProgramDropdown("#newProgram", rowData.program_id);
            document.getElementById('editSectionFormLabel').textContent = `Edit ${rowData.class_name}`
            $("#editSectionFormModal").modal('show');
        }
    })

    // edit section
    $("#editSectionForm").on('submit', function(e){
        e.preventDefault();

        const formData = jQuery('#editSectionForm').serializeArray();
        console.log("form data: ", formData)

        const newData = [
            {
                name: "submitSection",
                value: "editSection"
            },
            {
                name: "editId",
                value: editId
            },
            {
                name: "school_year_id",
                value: sy_id
            }
        ]

        const postData = formData.concat(newData);
        console.log("post data: ", postData)

        $.ajax({
            url: "<?php echo BASE_URL; ?>/registrar/actions/section_process.php",
            method: "POST",
            data: postData,
            dataType: "json",
            beforeSend: loadingAPIrequest(true),
            complete: loadingAPIrequest(false),
            success: function(data){
                if(data){
                    console.log("data:", data)
                    if(data.msg_status === true && data.code === 200){
                        swal({
                            icon: "success",
                            title: "Section updated!",
                            text: "Section has been updated.",
                            timer: 3000,
                            button: false
                        }).then(function(){
                            $('#editSectionFormModal').modal('hide');
                            $('#editSectionForm')[0].reset();
                            sectionTable.setData();
                        })
                    }
                    if(data.msg_status === false && data.code === 502){
                        swal({
                            icon: "error",
                            title: "Failed to update section.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 504){
                        swal({
                            icon: "error",
                            title: "Failed to update section.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 501){
                        swal({
                            icon: "error",
                            title: "Failed to update section.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 505){
                        swal({
                            icon: "error",
                            title: "Failed to update section.",
                            text: data.msg_response,
                            button: true
                        })
                    }
                    if(data.msg_status === false && data.code === 500){
                        swal({
                            icon: "error",
                            title: "Failed to update section.",
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
})


</script>

</html>