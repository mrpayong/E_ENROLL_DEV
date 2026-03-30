<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Fiscal Year";
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
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>

        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>
            
            <div class="container">
                <div class="page-inner">
                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                    ?>

                    <main id="main" class="main">
                        <section class="section">
                            <div class="row justify-content-center m-0">
                                <section class="card shadow-sm  p-0">
                                    <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                        <label class="fw-semibold mb-3 mb-md-0 fs-5 text-white">Fiscal Year Table</label>
                                        <button class="btn btn-info fw-semibold px-4 py-2 rounded-3" id="createFiscalYearBtn" style="background:#173ea5;">
                                            <i class="bi bi-plus-lg"></i> Create Fiscal Year
                                        </button>
                                    </header>
                                    <div class="table-responsive px-3 pb-4 pt-1 mt-3">
                                        <div class="rounded-3" style="min-height: 40rem;">

                                            <div id="fiscal-year-table" class="table-bordered  rounded"></div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </section>
                    </main>

                    <!-- Modal for Create Fiscal Year -->
                    <div class="modal fade" id="fiscalYearModal" tabindex="-1" aria-labelledby="fiscalYearModalLabel" aria-modal="true" role="dialog">
                        <div class="modal-dialog" role="document">
                            <form class="modal-content" id="fiscalYearForm" autocomplete="off">
                            <header class="modal-header bg-primary py-2">
                                <h2 class="modal-title fs-5 text-light" id="fiscalYearModalLabel">Create Fiscal Year</h2>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </header>
                            <section class="modal-body">
                                <div class="mb-3">
                                    <label for="schoolYear" class="form-label">School Year</label>
                                    <input type="text" class="form-control" id="schoolYear" name="schoolYear" placeholder="e.g. 2025-2026" required>
                                </div>
                                <div class="mb-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select class="form-select" id="semester" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="1st Semester">1st Semester</option>
                                        <option value="2nd Semester">2nd Semester</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" required>
                                </div>
                                <div class="mb-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate" required>
                                </div>
                            </section>
                            <footer class="modal-footer">
                                <button type="submit" class="btn btn-primary">Create</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            </footer>
                            </form>
                        </div>
                    </div>

                    <!-- Edit Fiscal Year Modal -->
                    <div class="modal fade" id="EditFiscalYearModal" tabindex="-1" aria-labelledby="EditFiscalYearModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="EditFiscalYearForm" autocomplete="off">
                                <header class="modal-header bg-primary py-2">
                                <h2 class="modal-title bg-primary fs-5 text-light" id="editModalTitle"></h2>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </header>
                            <section class="modal-body">
                                <div class="mb-3">
                                    <label for="editSchoolYear" class="form-label">School Year</label>
                                    <input type="text" class="form-control" id="editSchoolYear" name="schoolYear" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editSemester" class="form-label">Semester</label>
                                    <select class="form-select" id="editSemester" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="1st Semester">1st Semester</option>
                                        <option value="2nd Semester">2nd Semester</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="editStartDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="editStartDate" name="startDate" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editEndDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="editEndDate" name="endDate" required>
                                </div>
                            </section>
                            <footer class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            </footer>
                            </form>
                        </div>
                    </div>

                    <!-- lock school year -->
                    <div class="modal fade" id="LockUnlockFiscalYearModal" tabindex="-1" aria-labelledby="LockUnlockFiscalYearModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="LockUnlockFiscalYear" autocomplete="off">
                                <header class="modal-header bg-primary py-2">
                                <h2 class="modal-title bg-primary fs-5 text-light" id="fyFlagLabel"></h2>
                                <button type="button" class="btn-close text-white cancelAction" data-bs-dismiss="modal" aria-label="Close"></button>
                                </header>
                            <section class="modal-body">
                                <input type="hidden" name="school_year_id" id="lockUnlockSchoolYearId">
                                <input type="hidden" name="flag_used" id="flagSchoolYear">
                                <div class="mb-3">
                                <label for="confirmLockLabel" id="confirmFlagDescription" class="form-label"></label>
                                </div>
                            </section>
                            <footer class="modal-footer">
                                <button type="submit" class="btn btn-primary confirmAction">Yes</button>
                                <button type="button" class="btn btn-danger cancelAction" data-bs-dismiss="modal">No</button>
                            </footer>
                            </form>
                        </div>
                    </div>

                    <!-- set default fiscal year -->
                    <div class="modal fade" id="defaultFiscalyear" tabindex="-1" aria-labelledby="defaulFiscalyearLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" id="defaultFY" autocomplete="off">
                                <header class="modal-header bg-primary py-2">
                                <h2 class="modal-title bg-primary fs-5 text-light" id="defaultFyLabel"></h2>
                                <button type="button" class="btn-close text-white closeDef" data-bs-dismiss="modal" aria-label="Close"></button>
                                </header>
                            <section class="modal-body">
                                <div class="mb-3">
                                <span id="confirmDefaultDesc"></span>
                                </div>
                            </section>
                            <footer class="modal-footer">
                                <button type="submit" class="btn btn-primary confirmDef">Yes</button>
                                <button type="button" class="btn btn-danger closeDef" data-bs-dismiss="modal">No</button>
                            </footer>
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




















<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Status badge formatter
        function statusFormatter(cell) {
            const value = cell.getValue();
            if (value === "Active") {
                return '<label class="badge" style="font-size:1rem;background:#d1fae5;color:#059669;font-weight:500;">Active</label>';
            } else if (value === "Locked") {
                return '<label class="badge" style="font-size:1rem;background:#fee2e2;color:#dc2626;font-weight:500;">Locked</label>';
            }
            return value;
        }

    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            endDateInput.value = ""; // Clear previous end date
            endDateInput.min = startDateInput.value; // Set min to selected start date
        });
    }

    // For Edit Fiscal Year Modal
    const editStartDateInput = document.getElementById('editStartDate');
    const editEndDateInput = document.getElementById('editEndDate');

    if (editStartDateInput && editEndDateInput) {
        editStartDateInput.addEventListener('change', function() {
            editEndDateInput.value = ""; // Clear previous end date
            editEndDateInput.min = editStartDateInput.value; // Set min to selected start date
        });
    }

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

        // Per-row Action buttons logic
        function actionsFormatter(cell, formatterParams, onRendered) {
            const row = cell.getRow().getData();
            
            let actions = ``;
            if (row.flag_used === "Locked") {
                actions += `
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <button data-id="${row.school_year_id}" class="unlockFiscalYear border border-dark btn btn-sm" style="font-size:0.90rem;color:black; background:#88857D;">
                            <i class="fas fa-lock"></i> Unock
                        </button>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input fiscal-year-default-switch" type="checkbox" role="switch"
                                data-id="${row.school_year_id}" ${row.isDefault == 1 ? "checked" : ""} title="Set as default fiscal year">
                        </div>
                    </div>
                `;
            } 
            if (row.flag_used === "Active") {
                actions += `
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <button class="border border-dark btn btn-sm btn-warning edit-fiscal-year-btn" data-id="${row.school_year_id}" style="font-size:0.90rem;color:black;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button data-id="${row.school_year_id}" class="lockFiscalYear border border-dark btn btn-sm btn-primary" style="font-size:0.90rem;color:black;">
                            <i class="fas fa-lock-open"></i> Lock
                        </button>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input fiscal-year-default-switch" type="checkbox" role="switch"
                                data-id="${row.school_year_id}" ${row.isDefault == 1 ? "checked" : ""} title="Set as default fiscal year">
                        </div>
                    </div>
                `;
            }
            return actions;
        }

        // Tabulator Table
        var fiscalYearTable = new Tabulator("#fiscal-year-table", {
            ajaxURL: "<?php echo BASE_URL; ?>registrar/actions/fetchFiscalYear.php",
            ajaxConfig: "GET",
            ajaxContentType: "form",
            layout: "fitDataStretch",
            resizableColumns:true,
            movableColumns:true,
            pagination: "remote",
            paginationSize: 10,
            paginationDataSent: {
                "page": "page",
                "size": "size"
            },
            paginationDataReceived: {
                "last_page": "last_page",
                "data": "data",
                "total_record": "total_record"
            },
            columns: [
                {
                    title: "Fiscal Year",
                    field: "school_year",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerSort: true,
                    hozAlign: "center",
                    headerHozAlign: "center",
                },
                {
                    title: "Semester",
                    field: "sem",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerSort: true,
                    hozAlign: "center",
                    headerHozAlign: "center",
                },
                {
                    title: "Start Date",
                    field: "date_from",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerSort: true,
                    hozAlign: "center",
                    headerHozAlign: "center",
                },
                {
                    title: "End Date",
                    field: "date_to",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerSort: true,
                    hozAlign: "center",
                    headerHozAlign: "center",
                },
                {
                    title: "Status",
                    field: "flag_used",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    headerFilterLiveFilter: true,
                    headerSort: true,
                    hozAlign: "center",
                    headerHozAlign: "center",
                    formatter: function(cell) {
                        // Convert flag_used to status string
                        const value = cell.getValue();
                        if (value == "Active") {
                            return '<span class="badge" style="background:#d1fae5;color:#059669;font-weight:500;">Active</span>';
                        } else {
                            return '<span class="badge" style="background:#fee2e2;color:#dc2626;font-weight:500;">Locked</span>';
                        }
                    }
                },
                {
                    title: "Actions",
                    field: "actions",
                    hozAlign: "center",
                    headerHozAlign: "center",
                    formatter: actionsFormatter,
                }
            ],
            // autoResize: true,
            height: "auto",
            // resizableColumns: true,
            headerFilterPlaceholder: "Search",
            tooltips: false,
            movableColumns:true,
            headerHozAlign: "center",
            placeholder: "No Data Found",
        });

        const editFiscalYearModal = document.getElementById('EditFiscalYearModal');

        let newFlag;
        let currFlag;
        let currDef;
        let newDef;
        let editId;

        function toInputDateFormat(dateString) {
            // Try to parse common formats like "January 15, 2026"
            const date = new Date(dateString);
            if (isNaN(date)) return ""; // fallback if invalid
            // Format as YYYY-MM-DD for input[type="date"]


            console.log('date: ', date)
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        }

        document.querySelector("#fiscal-year-table").addEventListener("click", function(e) {
            const editBtn = e.target.closest(".edit-fiscal-year-btn");
            const lockBtn = e.target.closest(".lockFiscalYear");
            const unlockBtn = e.target.closest(".unlockFiscalYear");
            const switchDef = e.target.closest(".fiscal-year-default-switch");


            if (editBtn) {
                const rowId = editBtn.getAttribute("data-id");
                const row = fiscalYearTable.getRows().find(r => r.getData().school_year_id == rowId);
                if (!row) return;

                const rowData = row.getData();
                // Populate modal fields safely
                editId = rowData.school_year_id;
                document.getElementById('editSchoolYear').value = rowData.school_year;
                document.getElementById('editModalTitle').textContent = `Edit F.Y. ${rowData.school_year}`;
                document.getElementById('editSemester').value = rowData.sem;
                document.getElementById('editStartDate').value = toInputDateFormat(rowData.date_from);
                document.getElementById('editEndDate').value = toInputDateFormat(rowData.date_to);
                currDef = parseInt(rowData.isDefault);
                $("#EditFiscalYearModal").modal("show");
            }
            if (unlockBtn) {
                const rowId = parseInt((unlockBtn).getAttribute("data-id"));
                const row = fiscalYearTable.getRows().find(r => r.getData().school_year_id === rowId);
                if (!row) return;

                // Set the hidden input value for the modal
                const rowData = row.getData();
                document.getElementById('fyFlagLabel').textContent = `Unlock F.Y. ${rowData.school_year}`;
                document.getElementById('confirmFlagDescription').textContent = `Are you sure you want to unlock ${rowData.school_year} fiscal year?`;
                document.getElementById('lockUnlockSchoolYearId').value = rowData.school_year_id;
                const fyFlag = document.getElementById('flagSchoolYear').value = rowData.flag_used;
                currDef = parseInt(rowData.isDefault);
                $("#LockUnlockFiscalYearModal").modal("show");

                if(fyFlag !== null){
                    currFlag = fyFlag;
                    return parseInt(currFlag);
                }
            }
            if (lockBtn) {
                const rowId = parseInt((lockBtn).getAttribute("data-id"));
                const row = fiscalYearTable.getRows().find(r => r.getData().school_year_id === rowId);
                if (!row) return;

                // Set the hidden input value for the modal
                const rowData = row.getData();
                
                
                document.getElementById('fyFlagLabel').textContent = `Lock F.Y. ${rowData.school_year}`;
                document.getElementById('confirmFlagDescription').textContent = `Are you sure you want to lock ${rowData.school_year} fiscal year?`;
                document.getElementById('lockUnlockSchoolYearId').value = rowData.school_year_id;
                const fyFlag = document.getElementById('flagSchoolYear').value = rowData.flag_used;
                currDef = parseInt(rowData.isDefault);
                $("#LockUnlockFiscalYearModal").modal("show");


                if(fyFlag !== null){
                    currFlag = rowData.flag_status;
                    return parseInt(currFlag);
                }
            }
            if (switchDef) {
                const rowId = parseInt((switchDef).getAttribute("data-id"));
                const row = fiscalYearTable.getRows().find(r => r.getData().school_year_id === rowId);
                if (!row) return;

                // Set the hidden input value for the modal
                const rowData = row.getData();
                let hasOtherDefault = false;
                fiscalYearTable.getRows().forEach(r => {
                    const data = r.getData();
                    if (parseInt(data.school_year_id) !== parseInt(rowData.school_year_id) && parseInt(data.isDefault) === 1) {
                        hasOtherDefault = true;
                    }
                });

                if (hasOtherDefault === false) {
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "There must be a default Fiscal Year.",
                        button: true
                    });
                    fiscalYearTable.setData();
                    return;
                }
                if(hasOtherDefault === true){
                    currDef = parseInt(rowData.isDefault);
                    document.getElementById('defaultFyLabel').textContent = `Set default F.Y. ${rowData.sem} ${rowData.school_year}`;
                    document.getElementById('confirmDefaultDesc').textContent = `Are you sure you want to set F.Y. ${rowData.sem} ${rowData.school_year} as default?`;
                    editId = rowData.school_year_id;
                    
                    $("#defaultFiscalyear").modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    $("#defaultFiscalyear").modal("show");
                }
            }

        });
        

        document.querySelector("#LockUnlockFiscalYearModal").addEventListener("click", function(e) {
            const confirmFlag = e.target.closest(".confirmAction");
            const cancelFlag = e.target.closest(".cancelAction");

            if(confirmFlag) {
                if(confirmFlag !== null && currFlag !== null && isNaN(currFlag) === false){
                    if(currFlag === 1){
                        return newFlag = 0;
                    }
                    if(currFlag === 0){
                        return newFlag = 1;
                    }
                } else {
                    swal.fire({
                        title: 'Error',
                        text: "Error identifying fiscal year flag status. Try again.",
                        icon: 'error',
                        button: true,
                    });
                }
            }
            if (cancelFlag) {
                // Reset form fields\
                // Clear dynamic modal labels/descriptions
                document.getElementById('flagSchoolYear').value = "";
                document.getElementById('lockUnlockSchoolYearId').value = "";
                document.getElementById('fyFlagLabel').textContent = "";
                document.getElementById('confirmFlagDescription').textContent = "";
                // Reset JS variables
                currFlag = null;
                newFlag = null;
                if (document.activeElement) document.activeElement.blur();
                return;
            }
        });

        document.querySelector("#defaultFiscalyear").addEventListener("click", function(e) {
            const confirmFlag = e.target.closest(".confirmDef");
            const cancelFlag = e.target.closest(".closeDef");

            if(confirmFlag) {
                if(currDef === 1){
                    newDef = 0;
                }
                if(currDef === 0){
                    newDef = 1;
                }
            }
            if (cancelFlag) {
                // Reset form fields\
                // Clear dynamic modal labels/descriptions
                document.getElementById('defaultFyLabel').textContent = "";
                document.getElementById('confirmDefaultDesc').textContent = "";
                // Reset JS variables
                currDef = null;
                newDef = null;
                editId = null;
                if (document.activeElement) document.activeElement.blur();
                fiscalYearTable.setData();
                return;
            }
        });
        
        // Modal logic
        const fiscalYearModal = new bootstrap.Modal(document.getElementById('fiscalYearModal'));

        document.getElementById('createFiscalYearBtn').addEventListener('click', function() {
            $('#fiscalYearModal').modal('show');
        });

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
        // create fiscal year error and success handling
        $(function() {
            /*** submit the fiscal year form ***/
            $("#fiscalYearForm").on('submit', function(e) {
                e.preventDefault();

                // Remove previous error highlights/messages if you have them

                // Gather form data
                let formData = jQuery("#fiscalYearForm").serializeArray();
                
                let newData = [{
                    name: "actionSubmitFiscalYear",
                    value: "submitFiscalYear"
                }];
                let postData = formData.concat(newData);
                console.log("post data: ", postData)

                $.ajax({
                    url: "<?php echo BASE_URL; ?>registrar/actions/fiscalYear_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: function() {
                        $('#fiscalYearForm :submit').html('<span class="spinner-border spinner-border-sm"></span>Loading..');
                        $("#fiscalYearForm :input").prop("disabled", true);
                        $("#fiscalYearForm :button").prop("disabled", true);
                    },
                    complete: function() {
                        $('#fiscalYearForm :submit').html('Created');
                        $("#fiscalYearForm :input").prop("disabled", false);
                        $("#fiscalYearForm :button").prop("disabled", false);
                    },
                    success: function(data) {
                        if (data.msg_status !== null) {
                            if(data.code === 200 && data.msg_status === true){
                                swal({
                                    title: "Fiscal Year created.",
                                    icon: 'success',
                                    text: data.msg_response,
                                    button: false,
                                    timer: 2000
                                }).then(function() {
                                    // Optionally reload Tabulator data here
                                    $('#fiscalYearModal').modal('hide');
                                    $("#fiscalYearForm")[0].reset();
                                    fiscalYearTable.setData();
                                });
                            }
                            if (data.code === 400 && data.msg_status === false){
                                swal({
                                    title: "Failed to create fiscal year.",
                                    icon: 'error',
                                    text: data.msg_response,
                                    button: true,
                                })
                            }
                            if(data.code === 405 && data.msg_status === false){
                                swal({
                                    title: "Failed to create fiscal year.",
                                    icon: 'error',
                                    text: data.msg_response,
                                    button: true,
                                })
                            }
                            if (data.code === 500 && data.msg_status === false){
                                swal({
                                    title: "Failed to create fiscal year.",
                                    icon: 'error',
                                    text: data.msg_response,
                                    button: true,
                                })
                            }
                        } else {
                            swal({
                                title: 'Error',
                                text: "Error identifying server response." + data.code,
                                icon: 'error',
                                button: true,
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        swal({
                            title: 'Error',
                            html: "Error creating fiscal year",
                            icon: 'error',
                            button: true,
                        });
                    },
                    async: false
                });
            });
        });

        // edit fiscal year error and success handling
        $(function() {
            $("#EditFiscalYearForm").on('submit', function(e) {
                e.preventDefault();
                let formData = jQuery('#EditFiscalYearForm').serializeArray();
                
                const newData = [
                    {
                        name: "school_year_id",
                        value: editId
                    },
                    {
                        name: "updateFiscalYear", 
                        value: "updateNewData" 
                    },
                    {
                        name: "isDefault",
                        value: currDef
                    }
                ]

                const postData = formData.concat(newData);
                
                $.ajax({
                    url: "<?php echo BASE_URL; ?>registrar/actions/fiscalYear_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: loadingAPIrequest(true),
                    complete: loadingAPIrequest(false),
                    success: function(data) {
                        console.log("response data: ", data);
                            if (data.code === 200 && data.msg_status === true) {
                                swal({
                                    title: "Success updating!",
                                    icon: 'success',
                                    button: false,
                                    timer: 2000,
                                    text: data.msg_response
                                }).then(function() {
                                    console.log("success")
                                    $('#EditFiscalYearModal').modal('hide');
                                    $("#EditFiscalYearForm")[0].reset();
                                    fiscalYearTable.setData(); // reload table
                                });
                            }
                            if (data.code !== 200 && data.msg_status === false) {
                                swal({
                                    title: "Error!",
                                    icon: 'error',
                                    button: true,
                                    text: data.msg_response
                                })
                            }
                    },
                    error: function(xhr, status, error) {
                        swal({
                            title: 'Error',
                            html: "Error updating fiscal year.",
                            icon: 'error'
                        });
                    }
                });
            });
        });

        // lock
        $(function() {
            $("#LockUnlockFiscalYear").on('submit', function(e) {
                e.preventDefault();
                // when debugging here always use json.stringify then json.parse
                
                // let postData = [];
                let newData = [
                    {
                        name: "updateFiscalYear",
                        value: "LockUnlockFiscalYear"
                    },
                    {
                        name: "isDefault",
                        value: currDef
                    },
                    {
                        name: "flag_status",
                        value: newFlag
                    }
                ];
                let formData = jQuery("#LockUnlockFiscalYear").serializeArray();

                // console.log("form data: ", formData)
                // return;
                const postData = formData.concat(newData);
                // if(formData.length > 0){
                //     // basic array list insertion algo
                //     const flagStatus = formData.find(i => i.name === "flag_used");
                //     formData.pop();
                //     postData.push(formData[0])
                    
                //     if(flagStatus !== null && flagStatus !== undefined && flagStatus.name ==="flag_used"){
                //         const updatedFlag = {
                //             ...flagStatus,
                //             value: typeof newFlag === 'string'
                //                 ? Number(newFlag) || parseInt(newFlag)
                //                 : parseInt(newFlag) || Number(newFlag)
                //         }
                //         postData.push(updatedFlag)
                //         postData.push(newData);
                //     }
                // }


                console.log("post data: ", postData);
                // return;


                $.ajax({
                    url: "<?php echo BASE_URL; ?>registrar/actions/fiscalYear_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: function() {
                        $('#LockUnlockFiscalYear :submit').html('<span class="spinner-border spinner-border-sm"></span>Processing');
                        $("#LockUnlockFiscalYear :input").prop("disabled", true);
                    },
                    complete: function() {
                        $('#LockUnlockFiscalYear :submit').html('Yes');
                        $("#LockUnlockFiscalYear :input").prop("disabled", false);
                    },
                    success: function(data) {
                        if (data.msg_status === true && data.code === 200) {
                            swal({
                                title: "Success!",
                                icon: 'success',
                                text: data.msg_response,
                                button: false,
                                timer: 2000
                            }).then(function() {
                                $('#LockUnlockFiscalYearModal').modal('hide');
                                $("#LockUnlockFiscalYear")[0].reset();
                                fiscalYearTable.setData();
                            });
                        } else {
                            swal({
                                title: "Failed!",
                                icon: 'error',
                                text: data.msg_response,
                                button: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log("error xhr: ", xhr);
                        console.log("error status: ", status);
                        console.log("error error: ", error);
                        swal({
                            title: 'Error',
                            text: "Error locking/unlocking fiscal year.",
                            icon: 'error',
                            button: true
                        });
                    }
                });
            });
        });


        // set default
        $(function() {
            $("#defaultFY").on('submit', function(e) {
                e.preventDefault();
                // when debugging here always use json.stringify then json.parse
                
                let postData = [
                    {
                        name: "school_year_id",
                        value: editId
                    },
                    {
                        name: "isDefault",
                        value: newDef
                    },
                    {
                        name: "updateFiscalYear",
                        value: "defaultFiscalYear"
                    }
                ];



                $.ajax({
                    url: "<?php echo BASE_URL; ?>registrar/actions/fiscalYear_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    beforeSend: loadingAPIrequest(true),
                    complete: loadingAPIrequest(false),
                    success: function(data) {
                        if (data.msg_status === true && data.code === 200) {
                            swal({
                                title: "Success!",
                                icon: 'success',
                                text: data.msg_response,
                                button: false,
                                timer: 3000
                            }).then(function() {
                                $('#defaultFiscalyear').modal('hide');
                                $("#defaultFY")[0].reset();
                                fiscalYearTable.setData();
                            });
                        } 
                        if (data.msg_status === false && data.code === 501) {
                            swal({
                                title: "Failed to set",
                                icon: 'error',
                                text: data.msg_response,
                                button: true
                            })
                        } 
                        if (data.msg_status === false && data.code === 502) {
                            swal({
                                title: "Failed to set",
                                icon: 'error',
                                text: data.msg_response,
                                button: true
                            })
                        } 
                        if (data.msg_status === false && data.code === 503) {
                            swal({
                                title: "Failed to set",
                                icon: 'error',
                                text: data.msg_response,
                                button: true
                            })
                        }
                        if (data.msg_status === false && data.code === 404) {
                            swal({
                                title: "Failed to set",
                                icon: 'error',
                                text: data.msg_response,
                                button: true
                            })
                        } 
                        if (data.msg_status === false && data.code === 500) {
                            swal({
                                title: "Failed to set",
                                icon: 'error',
                                text: data.msg_response,
                                button: true
                            })
                        } 
                    },
                    error: function(xhr, status, error) {
                        console.log("error xhr: ", xhr);
                        console.log("error status: ", status);
                        console.log("error error: ", error);
                        swal({
                            title: 'Error',
                            text: "Error locking/unlocking fiscal year.",
                            icon: 'error',
                            button: true
                        });
                    }
                });
            });
        });

    });
</script>

</html>