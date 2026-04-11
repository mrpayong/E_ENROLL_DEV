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
    let currTitle = '';
    function loadCurriculumOptions(selector = '', selectedId = null) {
        const component = $(selector);
        if(component.is("#curriculum")){
            $.ajax({
                url: '<?php echo BASE_URL; ?>dean/actions/fetchCurrForPros.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if(response.code === 200 && response.msg_status === true){
                        const data = response.data
                        const currData = data.find(d => d.curriculum_id === Number(selectedId));
                        console.log("curr data: ",selectedId)
                        if(!currData){
                            document.getElementById('curriculum').textContent = "Curriculum Title Unavailable";
                        }
                        if(currData){
                            currTitle = currData.header; // <--- for saving prospectus process
                            document.getElementById('curriculum').textContent = currData.header;
                        }
                    }
                }
            });
        }

        if(component.is("#program")){
            $.ajax({
                url: '<?php echo BASE_URL; ?>dean/actions/fetchProgForSection.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if(response.code === 200 && response.status === true){
                        const data = response.data
                        const currData = data.find(d => Number(d.program_id) === Number(selectedId));
                        if(!currData){
                            document.getElementById('program').textContent = "Program not found";
                        }
                        document.getElementById('program').textContent = currData.program;

                        
                    }
                }
            });
        }
    }

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
        layout: "fitDataStretch",
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
                editor: "number",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
        layout: "fitDataStretch",
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
    // Map year+semester to the correct table
    const tablesByKey = {
        "1|1st Semester": firstTable1,
        "1|2nd Semester": firstTable2,
        "2|1st Semester": secondTable1,
        "2|2nd Semester": secondTable2,
        "3|1st Semester": thirdTable1,
        "3|2nd Semester": thirdTable2,
        "4|1st Semester": fourthTable1,
        "4|2nd Semester": fourthTable2,
        "5|1st Semester": fifthTable1,
        "5|2nd Semester": fifthTable2,
    };

    function normalizeCourse(c) {
        return {
            subject_id: c.subject_id || "",
            code: c.subject_code || "",
            title: c.subject_title || "",
            lec: Number(c.lec || 0),
            lab: Number(c.lab || 0),
            unit: Number(c.unit || 0),
            prereq: c.pre_req || ""
        };
    }

    function populateTablesFromPayload(payload) {
        if (!payload || !Array.isArray(payload)) return;

        const grouped = {};
        payload.forEach((c) => {
            const year = Number(c.year_level);
            const key = `${year}|${c.semester}`;
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(normalizeCourse(c));
        });

        Object.keys(tablesByKey).forEach((key) => {
            const table = tablesByKey[key];
            const rows = grouped[key] || [];
            if (table) {
                table.setData(rows.length ? rows : [{ code:"", title:"", lec:0, lab:0, unit:0, prereq:"" }]);
            }
        });

        if (typeof updateOverallUnits === "function") updateOverallUnits();
    }

    let curr_id = "";
    let program_id = "";
    const allTables = [
    firstTable1, firstTable2,
    secondTable1, secondTable2,
    thirdTable1, thirdTable2,
    fourthTable1, fourthTable2,
    fifthTable1, fifthTable2
    ];

    const addButtons = [
    btn_add_row1, btn_add_row2,
    scdYr_tb1, scdYr_tb2,
    trdYr_tb1, trdYr_tb2,
    frtYr_tb1, frtYr_tb2,
    fthYr_tb1, fthYr_tb2
    ];
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
                        curr_id = data.curriculum_id;
                        program_id = data.program_id;
                        loadCurriculumOptions('#curriculum', data.curriculum_id);
                        loadCurriculumOptions('#program', data.program_id);
                        if(data.update_status === 1){
                            addButtons.forEach(btn => {
                                if(btn){
                                    btn.hidden = true;
                                }
                            });

                            allTables.forEach(table => {
                                if(!table) return;

                                table.getColumns().forEach(col => {
                                    const def = col.getDefinition();
                                    const field = col.getField();


                                    if(field === "code" || field === "title" || field === "lec" || field === "lab" || field === "unit" || field === "prereq"){
                                        col.updateDefinition({
                                            editor: false
                                        });
                                    }

                                    if(def.title === "Action"){
                                        col.hide();
                                    }
                                });
                            });
                        }
                        populateTablesFromPayload(data.courses);
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
