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

$profs = array();
$query_prof = "SELECT user_id, f_name, m_name, l_name, suffix FROM users WHERE JSON_CONTAINS(user_role, '\"4\"')";
if ($prof_query = call_mysql_query($query_prof)) {
    if ($num = mysqli_num_rows($prof_query)) {
        while ($data = call_mysql_fetch_array($prof_query)) {
            $profs[] = [
                'user_id' => $data['user_id'],
                'name' => trim($data['f_name'] . ' ' . $data['m_name'] . ' ' . $data['l_name']. ' ' . $data['suffix'])
            ];
        }
    }
}

$sections = array();
$section_sql = " SELECT class_id, class_name, sem_limit
FROM class_section WHERE status = 0 ORDER BY class_name ASC
";
if ($section_query = call_mysql_query($section_sql)) {
    if ($num = mysqli_num_rows($section_query)) {
        while ($data = call_mysql_fetch_array($section_query)) {
           
            $data['sem_limit'] = $data['sem_limit'] ? json_decode($data['sem_limit'], true) : 'No Limit';
            // echo json_encode($data['sem_limit'], JSON_PRETTY_PRINT);
            $default_limit = reset($data['sem_limit']);
            $sections[] = [
                'class_id' => $data['class_id'],
                'class_name' => $data['class_name'],
                'sem_limit' => $default_limit
            ];
        }
    }
}

$courses = array();
$subject_sql = "SELECT subject_id, subject_code, subject_title FROM subject";
if($course_query = call_mysql_query($subject_sql)){
    if($num = call_mysql_num_rows($course_query)){
        while ($data = call_mysql_fetch_array($course_query)){
            $courses[] = [
                'subject_id' => $data['subject_id'],
                'subject_code' => $data['subject_code'],
                'subject_title' => $data['subject_title'],
            ];
        }
    }
}

$programs = array();
$program_sql = "SELECT program_id, program FROM programs WHERE status = '0' ORDER BY program ASC";
if($program_query = call_mysql_query($program_sql)){
    if($num = call_mysql_num_rows($program_query)){
        while ($data = call_mysql_fetch_array($program_query)){
            $programs[] = [
                'program_id' => $data['program_id'],
                'program' => $data['program']
            ];
        }
    }
}

$sems = array();
$query = "SELECT school_year_id, sem, school_year
    FROM school_year 
    WHERE isDefault = 0
    ORDER BY date_from DESC";
if ($sem_query = call_mysql_query($query)) {
    if ($num = mysqli_num_rows($sem_query)) {
        while ($data = call_mysql_fetch_array($sem_query)) {
            $sems[] = [
                'school_year_id' => $data['school_year_id'],
                'sem' => $data['school_year']. " — ". $data['sem']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
    <style>
        .selectize-control.multi .selectize-input .item {
            background: #e7ed2fe8 !important;   /* Your preferred highlight color */
            color: #1b1616b9 !important;           /* White text for contrast */
            border-radius: 4px;
            border: none;
            font-weight: bold;
        }
        .selectize-control.multi .selectize-input .item.active {
            background: #0088ff !important;   /* Example: orange when active */
            color: #fff !important;
        }
    </style>
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

</head>

<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH.'/global/sidebar.php'; ?>
        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>
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
                        <div class="row justify-content-center  mx-4 m-4">
                            <section class="card shadow-sm  p-0" style="margin:auto;">
                                <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <h1 class="fw-semibold mb-3 mb-md-0 fs-4 text-white">Schedule</h1>
                                    <button class="btn btn-info fw-semibold px-4 py-2 rounded-3" 
                                    id="createButton" style="background:#173ea5;">
                                        <i class="bi bi-plus-lg"></i> Create Schedule
                                    </button>
                                </header>
                                <div class="table-responsive px-3 pb-4 pt-1 mt-3 d-flex flex-column justify-content-between" style="min-height: 40rem;">
                                    <div class="table-bordered" id="tableSched"></div>
                                    <div class="d-flex justify-content-start gap-2">
                                        <button type="button" id="downloadCSV" class="btn btn-outline-success fw-semibold px-4 py-2 rounded-3">
                                            <i class="bi bi-file-earmark-spreadsheet"></i> Download CSV
                                        </button>
                                        <button type="button" id="downloadXLSX" class="btn btn-outline-primary fw-semibold px-4 py-2 rounded-3">
                                            <i class="bi bi-file-earmark-excel"></i> Download XLSX
                                        </button>
                                        <button type="button" id="printTable" class="btn btn-outline-secondary fw-semibold px-4 py-2 rounded-3">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <button type="button" id="showAllBtn"
                                        class="btn btn-primary fw-semibold px-4 py-2 rounded-3">
                                            Show all
                                        </button>



                                    </div>
                                </div>

                            </section>
                        </div>


                    </section>


                <!-- create -->
                <div class="modal fade" id="schedFormModal" tabindex="-1" aria-labelledby="createLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <form class="modal-content" id="schedForm" autocomplete="off">
                            <div class="modal-header bg-primary text-white py-2">
                                <h5 class="modal-title" id="createLabel">Create Schedule</h5>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="prof" class="form-label fw-bold"">Instructor<span class="required-field"></span></label>
                                        <select id="prof" name="prof" required>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="schoolYear" class="form-label fw-bold"">Fiscal Year<span class="required-field"></span></label>
                                        <select id="schoolYear" name="schoolYear" required>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="section" class="form-label fw-bold"">Section<span class="required-field"></span></label>
                                        <select id="section" name="section" required>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="limit" class="form-label fw-bold"">Section Limit<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="limit" name="limit" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="year_level" class="form-label fw-bold"">Year Level<span class="required-field"></span></label>
                                        <select id="year_level" name="year_level" required>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="program" class="form-label fw-bold"">Program<span class="required-field"></span></label>
                                        <select id="program" name="program" required>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="courseCode" class="form-label fw-bold"">Course Code<span class="required-field"></span></label>
                                        <select id="courseCode" name="courseCode" required>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="courseName" class="form-label fw-bold"">Course Title<span class="required-field"></span></label>
                                        <input type="text" id="courseName" name="courseName" class="form-control" readonly required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="lec" class="form-label fw-bold"">Lecture<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="lec" name="lec" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="lab" class="form-label fw-bold"">Laboratory<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="lab" name="lab" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="unit" class="form-label fw-bold"">Unit<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="unit" name="unit" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="hours" class="form-label fw-bold"">Total Hours<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="hours" name="hours" requried>
                                    </div>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-borderless" id="sched_time_table">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Room</th>
                                                <th>Component</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <select name="schedDay" class="form-control select-day">
                                                        <option value="" disabled selected>Select Day</option>
                                                        <option value="Monday">Monday</option>
                                                        <option value="Tuesday">Tuesday</option>
                                                        <option value="Wednesday">Wednesday</option>
                                                        <option value="Thursday">Thursday</option>
                                                        <option value="Friday">Friday</option>
                                                        <option value="Saturday">Saturday</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" id="time_from" name="time_from" class="form-control time-from" />
                                                </td>
                                                <td>
                                                    <input type="text" name="time_to" id="time_to" class="form-control time-to">
                                                </td>
                                                <td>
                                                    <input type="text" name="facility" class="form-control facility-class">
                                                </td>
                                                <td>
                                                    <select name="component" class="form-control select-component">
                                                        <option value="">Select Component</option>
                                                        <option value="lec">Lecture</option>
                                                        <option value="lab">Laboratory</option>
                                                    </select>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button type="button" id="add_sched_row" class="text-nowrap btn btn-outline-success btn-sm">
                                                        <i class="fas fa-plus-circle"></i> Add Schedule
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div id="rendered_sched_rows">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary fs-6">Create</button>
                                <button type="button" class="btn btn-danger fs-6" id="cancelProgramModalBtn">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>


                <!-- edit -->
                <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <form class="modal-content" id="updateForm" autocomplete="off">
                            <div class="modal-header bg-primary text-white py-2">
                                <h5 class="modal-title" id="updateLabel"></h5>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="newProf" class="form-label fw-bold"">Instructor<span class="required-field"></span></label>
                                        <select id="newProf" name="newProf" required>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="fiscalYear" class="form-label fw-bold"">Fiscal Year<span class="required-field"></span></label>
                                        <input type="text" id="fiscalYear" name="fiscalYear" class="form-control" readonly required disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="newSection" class="form-label fw-bold"">Section<span class="required-field"></span></label>
                                        <select id="newSection" name="newSection" required>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="newLimit" class="form-label fw-bold"">Section Limit<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="newLimit" name="newLimit" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="newYear_level" class="form-label fw-bold"">Year Level<span class="required-field"></span></label>
                                        <select id="newYear_level" name="newYear_level" required>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="newProgram" class="form-label fw-bold"">Program<span class="required-field"></span></label>
                                        <select id="newProgram" name="newProgram" required>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="newCourseCode" class="form-label fw-bold"">Course Code<span class="required-field"></span></label>
                                        <select id="newCourseCode" name="newCourseCode" required>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="newCourseName" class="form-label fw-bold"">Course Title<span class="required-field"></span></label>
                                        <input type="text" id="newCourseName" name="newCourseName" class="form-control" readonly required>
                                    </div>


                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="newLec" class="form-label fw-bold"">Lecture<span class="required-field"></span></label>
                                        <input type="number" class="form-control" placeholder="# of units" id="newLec" name="newLec" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="newLab" class="form-label fw-bold"">Laboratory<span class="required-field"></span></label>
                                        <input type="number" class="form-control" placeholder="# of units" id="newLab" name="newLab" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="newUnit" class="form-label fw-bold"">Unit<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="newUnit" name="newUnit" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="newHours" class="form-label fw-bold"">Total Hours<span class="required-field"></span></label>
                                        <input type="number" class="form-control" id="newHours" name="newHours" requried>
                                    </div>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered" id="udpate_schedTable">
                                        <thead>
                                            <tr>
                                                <th>Day<span class="required-field"></span></th>
                                                <th>From<span class="required-field"></span></th>
                                                <th>To<span class="required-field"></span></th>
                                                <th>Room<span class="required-field"></span></th>
                                                <th>Component<span class="required-field"></span></th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <select name="newDay" class="form-control newDay">
                                                        <option value="">Select Day</option>
                                                        <option value="Monday">Monday</option>
                                                        <option value="Tuesday">Tuesday</option>
                                                        <option value="Wednesday">Wednesday</option>
                                                        <option value="Thursday">Thursday</option>
                                                        <option value="Friday">Friday</option>
                                                        <option value="Saturday">Saturday</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" id="newTime_from" name="newTime_from" class="form-control newTime_from" />
                                                </td>
                                                <td>
                                                    <input type="text" name="newTime_to" id="newTime_to" class="form-control newTime_to">
                                                </td>
                                                <td>
                                                    <input type="text" name="newFacility" class="form-control newFacility">
                                                </td>
                                                <td>
                                                    <select name="newComponent" class="form-control newComponent">
                                                        <option value="">Select Component</option>
                                                        <option value="lec">Lecture</option>
                                                        <option value="lab">Laboratory</option>
                                                    </select>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button type="button" id="update_sched_row" class="btn btn-outline-success btn-sm">
                                                        <i class="bi bi-plus-circle"></i> Add Schedule
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div id="update_sched_rows">
                                    <input type="text" id="update_sched_selectize" />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="cancelProgramModalBtn">Cancel</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
(function(){
        let timerVal = 2000;
        let sectionData = <?php echo json_encode($sections); ?>;
        let profs = <?php echo json_encode($profs); ?>;
        let courses = <?php echo json_encode($courses); ?>;
        let programs = <?php echo json_encode($programs); ?>;
        let sems = <?php echo json_encode($sems); ?>;

        function populateCourseCodeDropdown(selector, selectedId = null) {
            const dropdown = $(selector);
            if(dropdown[0].selectize){
                dropdown[0].selectize.destroy();
            }
            dropdown.empty();
            dropdown.append('<option value="" selected disabled>Select Subject Code</option>');
            courses.forEach(function(item){
                dropdown.append(
                    $('<option>', {
                        value: item.subject_id,
                        text: item.subject_code
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
            return {title: dropdown.find('option:selected').text()};
        }

        function populateYearLevelDropdown(selector, selectedId = null) {
            const yearLevels = [
                { value: "1", text: "1st Year" },
                { value: "2", text: "2nd Year" },
                { value: "3", text: "3rd Year" },
                { value: "4", text: "4th Year" }
            ];
            const $dropdown = $(selector);
            $dropdown.empty();
            $dropdown.append('<option value="" selected disabled>Select Year Level</option>');
            yearLevels.forEach(function(item) {
                $dropdown.append(
                    $('<option>', {
                        value: item.value,
                        text: item.text
                    })
                );
            });
            $dropdown.selectize({
                allowEmptyOption: true,
                create: false,
                sortField: 'text'
            });
            if (selectedId) {
                $dropdown[0].selectize.setValue(selectedId);
            }
        }

        function populateSectionDropdown(selector, selectedId = null) {
            const $dropdown = $(selector);
            if ($dropdown[0].selectize) {
                $dropdown[0].selectize.destroy();
            }
            $dropdown.empty();
            $dropdown.append('<option value="" selected disabled>Select Section</option>');
            sectionData.forEach(function(item) {
                $dropdown.append(
                    $('<option>', {
                        value: item.class_id,
                        text: item.class_name,
                        'data-limit': item.sem_limit
                    })
                );
            });
            $dropdown.selectize({
                allowEmptyOption: true,
                create: false,
                sortField: 'text'
            });
            
            if (selectedId) {
                $dropdown[0].selectize.setValue(selectedId);
            }
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
        }

        function populateSchoolYearDropdown(selector, selectedId = null) {
            const dropdown = $(selector);
            if(dropdown[0].selectize){
                dropdown[0].selectize.destroy();
            }
            dropdown.empty();
            dropdown.append('<option value="" selected disabled>Select Fiscal Year</option>');
            sems.forEach(function(item){
                dropdown.append(
                    $('<option>', {
                        value: item.school_year_id,
                        text: item.sem
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

        function populateProfDropdown(selector, selectedId = null) {
            const $dropdown = $(selector);
            if($dropdown[0].selectize){
                $dropdown[0].selectize.destroy();
            }
            $dropdown.empty();
            $dropdown.append('<option value:"" selected disabled>Select Instructor</option>');
            profs.forEach(function(item) {
                $dropdown.append(
                    $('<option>', {
                        value: item.user_id,
                        text: item.name
                    })
                );
            });

            $dropdown.selectize({
                allowEmptyOption: true,
                create:false,
                sortField: 'text'
            })
            if(selectedId){
                $dropdown[0].selectize.setValue(selectedId);
            }
        }

        (function populateSYDropdown() {
            $.ajax({
                url: "<?php echo BASE_URL; ?>registrar/actions/fetchSemesterForForm.php",
                method: "GET",
                dataType: "json",
                success: function(response) {
                    if(response && response.data) {
                        const $syDropdown = $('#syDropdown');
                        $syDropdown.empty();
                        $syDropdown.append('<option value="0" disabled selected>Select School Year/Sem</option>');
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

                        // --- Set to latest fiscal year (first in the list) ---
                        if(response.data.length > 0){
                            // Selectize instance
                            var selectize = $syDropdown[0].selectize;
                            // Set value to the first fiscal year id
                            selectize.setValue(response.data[0].school_year_id);
                            document.getElementById('generateBtn').click();
                        }
                    }
                }
            });
        })();

        document.getElementById('generateBtn').addEventListener('click', function() {
            // Get the selected fiscal year/semester ID
            const syId = $('#syDropdown').val();

            console.log('Selected SY ID: ', syId);
            // If a fiscal year is selected, filter the table
            if (syId && syId !== "0") {
                tableSched.setFilter(
                    "schoolyear_id", "=", syId
                );
            } else {
                // If nothing is selected, clear the filter
                tableSched.clearFilter();
            }
        });

        // Download CSV
        document.getElementById('downloadCSV').addEventListener('click', function() {
            tableSched.download("csv", "schedule-data.csv");
        });

        // Download XLSX
        document.getElementById('downloadXLSX').addEventListener('click', function() {
            tableSched.download("xlsx", "schedule-data.xlsx", {sheetName:"Schedule Data"});
        });

        // Print Table
        document.getElementById('printTable').addEventListener('click', function() {
            tableSched.print(false, true);
        });

        populateSectionDropdown('#section');
        $('#section').trigger('change');
        populateProfDropdown('#prof');
        populateCourseCodeDropdown('#courseCode');
        $('#courseCode')[0].selectize.on('change', function(value) {
            var title = courseTitleMap[value] || '';
            $('#courseName').val(title);
        });
        populateProgramDropdown('#program');
        populateSchoolYearDropdown('#schoolYear');
        populateYearLevelDropdown('#year_level');

        const sectionLimitMap = {};
        sectionData.forEach(function(item) {
            sectionLimitMap[item.class_id] = item.sem_limit;
        });

        // After populating the dropdown and initializing Selectize
        const sectionSelectize = $('#section')[0].selectize;
        sectionSelectize.on('change', function(value) {
            var limit = sectionLimitMap[value] !== undefined ? sectionLimitMap[value] : '';
            $('#limit').val(limit);
        });

        document.querySelector('#createButton').addEventListener('click', function(e) {
            $('#schedFormModal').modal('show');
        });

        document.querySelector('#cancelProgramModalBtn').addEventListener('click', function(){
            $('#schedFormModal').modal('hide');
        })


        let courseTitleMap = {};
        courses.forEach(function(item) {
            courseTitleMap[item.subject_id] = item.subject_title;
        });
        // Get Selectize instances
        const courseCodeSelectize = $('#courseCode')[0].selectize;
        const courseNameSelectize = $('#courseName')[0].selectize;

        // When course code changes, update the course title
        $('#courseCode')[0].selectize.on('change', function(value) {
            var title = courseTitleMap[value] || '';
            console.log('courseTitleMap: ', courseTitleMap)
            if (title) {
                $('#courseName')[0].selectize.setValue(title);
            } else {
                $('#courseName')[0].selectize.clear();
            }
        });

        function loadingAPIrequest(status){
            if(status === true){
                swal({
                    title: "Loading",
                    icon: 'info',
                    text: "Please wait",
                    buttons:false,
                    closeOnClickOutside: false,
                    closeOnEsc: false
                });
            }
            if(status === false){
                swal.close();
            }

        }

        function actionButton(cell){
            const rowData = cell.getRow().getData();
            const studentLimit = Number(rowData?.section_limit);

            let buttons = `
                <button class="btn btn-sm btn-primary edit-sched fs-6" data-id="${rowData.teacher_class_id}">
                    <i class="bi bi-pencil-square"></i> Update
                </button>
            `;
            if (studentLimit === 0) {
                buttons += `
                    <button class="btn btn-sm btn-danger archive-sched" data-id="${rowData.teacher_class_id}">
                       <i class="bi bi-archive"></i> Archive
                    </button>
                `;
            }
            return buttons;
        };


        const tableSched = new Tabulator('#tableSched',{
            ajaxURL: "<?php echo BASE_URL; ?>registrar/actions/fetchSchedule.php",
            ajaxConfig: "GET",
            layout: "fitDataStretch",
            ajaxFiltering: true,
            ajaxSorting: true,
            placeholder: "No Data Available",
            pagination: "remote",
            paginationSize: 10,
            movableColumns: true,
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
            initialSort:[
                {column:"date_added", dir:"desc"},
            ],
            columns: [
                {
                    title: "Actions", 
                    field: "actions", 
                    hozAlign: "center",
                    print: false,
                    download:false,
                    formatter: actionButton
                },
                {
                    title: "Instructor", 
                    field: "instructor_name", 
                    headerFilter: "input"
                },
                {
                    title: "Section", 
                    field: "class_name", 
                    headerFilter: "input"
                },
                {
                    title: "Section Limit", 
                    field: "section_limit", 
                    headerFilter: "input",
                    hozAlign: "center",
                    formatter: function(cell){
                        const studentLimit = Number(cell.getValue());
                        return studentLimit > 1
                            ? `${studentLimit} students` 
                            : studentLimit === 1
                                ? `${studentLimit} student`
                                : '0 student';
                    }
                },
                {
                    title: "Program", 
                    field: "program", 
                    headerFilter: "input",
                    hozAlign: "center"
                },
                {
                    title: "Course", 
                    field: "subject_code", 
                    headerFilter: "input",
                    hozAlign: "center"
                },
                {
                    title: "Course Title", 
                    field: "subject_text",
                    sorter: "string", 
                    hozAlign: "center", 
                    headerFilter: "input"
                },
                {
                    title: "Schedule", 
                    field: "schedule", 
                    headerFilter: "input",
                    formatter: "html" 
                },
                {
                    title: "Total Hours", 
                    field: "hours", 
                    hozAlign: "center", 
                    headerFilter: "input",
                    formatter: function(cell){
                        const hoursData = Number(cell.getValue());
                        return hoursData > 1
                            ? `${hoursData} hours` 
                            : `${hoursData} hour`;
                    }
                },
                {
                    title: "Lecture", 
                    field: "lec", 
                    headerFilter: "input",
                    hozAlign: "center",
                    formatter: function(cell){
                        const limitData = cell.getValue();
                        return limitData > 1
                            ? `${limitData} units` 
                            : limitData === 1
                                ? `${limitData} unit`
                                : '0 unit';
                    }
                },
                {
                    title: "Laboratory", 
                    field: "lab", 
                    headerFilter: "input",
                    hozAlign: "center",
                    formatter: function(cell){
                        const limitData = cell.getValue();
                        return limitData > 1
                            ? `${limitData} units` 
                            : limitData === 1
                                ? `${limitData} unit`
                                : '0 unit';
                    }
                },
                {
                    title: "Units", 
                    field: "unit", 
                    headerFilter: "input",
                    hozAlign: "center",
                    formatter: function(cell){
                        const limitData = Number(cell.getValue());
                        return limitData > 1
                            ? `${limitData} units` 
                            : limitData === 1
                                ? `${limitData} unit`
                                : '0 unit';
                    }
                },
                {
                    title: "School Year", 
                    field: "school_year", 
                    headerFilter: "input",
                    hozAlign: "center"
                },
                {
                    title: "Sem", 
                    field: "sem", 
                    headerFilter: "input",
                    hozAlign: "center"
                },
                {
                    field:"sy_id",
                    visible:false
                }
            ],
        });

        let isShowingAll = false;
        const defaultPageSize = 10; // your default

        document.getElementById('showAllBtn').addEventListener('click', function() {
            if (!isShowingAll) {
                tableSched.clearFilter();
                tableSched.setPageSize(10000); // show all
                tableSched.setPage(1);
                this.textContent = "Hide";
                isShowingAll = true;
            } else {
                tableSched.setPageSize(defaultPageSize); // restore default
                tableSched.setPage(1);
                this.textContent = "Show all";
                isShowingAll = false;
            }
        });

        let timeFromPicker = flatpickr("#time_from", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altInput: true,
            altFormat: "h:i K",
            time_24hr: false,
            onChange: function(selectedDates, dateStr, instance) {
                // When time_from changes, update minTime for time_to
                if (dateStr) {
                    timeToPicker.set('minTime', dateStr);
                } else {
                    timeToPicker.set('minTime', null);
                }
            }
        });
        
        let timeToPicker = flatpickr("#time_to", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altInput: true,
            altFormat: "h:i K",
            time_24hr: false
        });

        let newFromPicker = flatpickr("#newTime_from", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altInput: true,
            altFormat: "h:i K",
            time_24hr: false,
            onChange: function(selectedDates, dateStr, instance) {
                // When time_from changes, update minTime for time_to
                if (dateStr) {
                    newToPicker.set('minTime', dateStr);
                } else {
                    newToPicker.set('minTime', null);
                }
            }
        });
        
        let newToPicker = flatpickr("#newTime_to", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altInput: true,
            altFormat: "h:i K",
            time_24hr: false
        });

        function formatScheduleRow(day, from, to, room, component) {
            return `${day}:: ${from}-${to}:: ${room.toUpperCase()}:: ${component}`;
        }



        function timeToSeconds(timeStr) {
            // timeStr should be in "HH:mm" 24-hour format
            var parts = timeStr.split(':');
            var hours = parseInt(parts[0], 10);
            var minutes = parseInt(parts[1], 10);
            return hours * 3600 + minutes * 60;
        }

        $(document).on('click', '#add_sched_row', function() {
            // Get values from the first row
            var $row = $('#sched_time_table tbody tr:first');
            var day = $row.find('.select-day').val();
            var from = $row.find('.time-from').val();
            var to = $row.find('.time-to').val();
            var room = $row.find('.facility-class').val();
            var component = $row.find('.select-component').val();

            var from24 = timeToSeconds(from);
            var to24 = timeToSeconds(to);
            // class time duraiton validation (From-To)
            if(from24 >= to24) {
                swal({
                    title: "Invalid class duration.",
                    icon: "warning",
                    button: true
                });
                return;
            }
            // Validate (optional)
            if(!day || !from || !to || !room || !component) {
                swal({
                    title: "Incomplete Data",
                    icon: "warning",
                    text: "Please fill out all fields before adding.",
                    button: true
                })
                return;
            }

            if ($('#rendered_sched_selectize').length === 0) {
                $('#rendered_sched_rows').append('<input type="text" id="rendered_sched_selectize" />');
                $('#rendered_sched_selectize').selectize({
                    delimiter: ',',
                    persist: false,
                    create: false,
                    maxItems: null,
                    readOnly: true,
                    onDelete: function(values) {
                        return true;
                    }
                });
                $('#rendered_sched_selectize').next('.selectize-control').find('input').prop('readonly', true);
            }

            // Format the summary string
            var summary = formatScheduleRow(day, from, to, room, component);

            // Render the summary row with a remove button
            var selectizeInput = $('#rendered_sched_selectize')[0].selectize;
            selectizeInput.addOption({value: summary, text: summary});
            selectizeInput.addItem(summary);

        });


        // Remove rendered summary row
        $(document).on('click', '.remove-rendered-row', function() {
            var target = $(this).data('target');
            $(target).remove();
        });
        
        var selectizeInstance = $('#rendered_sched_selectize')[0] && $('#rendered_sched_selectize')[0].selectize;

        $("#schedForm").on('submit', function(e){
            e.preventDefault();

            var selectizeInstance = $('#rendered_sched_selectize')[0] && $('#rendered_sched_selectize')[0].selectize;
            var scheduleSummaries = [];
            if (selectizeInstance) {
                var val = selectizeInstance.getValue();
                scheduleSummaries = val ? val.split(',') : []; // array of summary strings
            }


            const formData = jQuery("#schedForm").serializeArray();
            const newData = [
                {
                    name: "submitSchedule",
                    value: "createSched"
                },
                {
                    name: "schedule_summaries",
                    value: JSON.stringify(scheduleSummaries)
                }
            ];

            const postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>registrar/actions/schedule_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: loadingAPIrequest(true),
                complete: loadingAPIrequest(false),
                success: function(data){
                    if(data){
                        if(data.code === 200 && data.msg_status === true){
                            swal({
                                title: "Created Successfully.",
                                icon: "success",
                                text: data.msg_response,
                                button:false,
                                timer:3000,
                            }).then(function(){
                                $('#schedFormModal').modal('hide');
                                $('#schedForm')[0].reset();
                                $('#rendered_sched_rows').empty();
                                tableSched.setData();
                                populateSectionDropdown('#section');
                                $('#section').trigger('change');
                                $('#section')[0].selectize.on('change', function(value) {
                                    var limit = sectionLimitMap[value] !== undefined ? sectionLimitMap[value] : '';
                                    $('#limit').val(limit);
                                });
                                populateProfDropdown('#prof');
                                populateCourseCodeDropdown('#courseCode');
                                $('#courseCode')[0].selectize.on('change', function(value) {
                                    var title = courseTitleMap[value] || '';
                                    $('#courseName').val(title);
                                });
                                populateProgramDropdown('#program');
                                populateSchoolYearDropdown('#schoolYear');
                                $('#year_level')[0].selectize.clear();
                            })
                        }
                        if(data.code === 404 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 401 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 501 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 501 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 502 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 503 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 504 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 505 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 506 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 507 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 508 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 509 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 510 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 511 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 512 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 513 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 500 && data.msg_status === false){
                            swal({
                                title: "Failed to create schedule.",
                                icon: "error",
                                text: "Failed to execute action.",
                                button:true,
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    swal.close();
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "Network/Server error occured",
                        button:true
                    })
                }
            })
        })


        let editId;
        let newStatus;
        let oldStatus;
        let newSy_id;
        $(document).on('click', '#update_sched_row', function() {
            var $row = $('#udpate_schedTable tbody tr:first');
            var day = $row.find('.newDay').val();
            var from = $row.find('.newTime_from').val();
            var to = $row.find('.newTime_to').val();
            var room = $row.find('.newFacility').val();
            var component = $row.find('.newComponent').val();

            var from24 = timeToSeconds(from);
            var to24 = timeToSeconds(to);
            // class time duraiton validation (From-To)
            if(from24 >= to24) {
                swal({
                    title: "Invalid class duration.",
                    icon: "warning",
                    button: true
                });
                return;
            }
            // Validate (optional)
            if(!day || !from || !to || !room || !component) {
                swal({
                    title: "Incomplete Data",
                    icon: "warning",
                    text: "Please fill out all fields before adding.",
                    button: true
                })
                return;
            }

            // Get existing schedules from selectize
            var selectizeInput = $('#update_sched_selectize')[0] && $('#update_sched_selectize')[0].selectize;
            var existingSchedules = [];
            if (selectizeInput) {
                var val = selectizeInput.getValue();
                existingSchedules = val ? val.split(',') : [];
            }


            var summary = formatScheduleRow(day, from, to, room, component);
            selectizeInput.addOption({value: summary, text: summary});
            selectizeInput.addItem(summary);
        });

        let courseCodeMap = {};
        courses.forEach(function(item) {
            courseCodeMap[item.subject_title] = item.subject_id;
        });
        document.querySelector('#tableSched').addEventListener('click', function(e){
            e.preventDefault();
            const editBtn = e.target.closest('.edit-sched');
            const archiveBtn = e.target.closest('.archive-sched');

            if(editBtn){
                const rowId = editBtn.getAttribute('data-id');
                const row = tableSched.getRows().find(r => r.getData().teacher_class_id == rowId);
                const rowData = row.getData();

                
                $('#updateLabel').text('Update Schedule');
                populateProfDropdown('#newProf', rowData.teacher_id),
                populateSectionDropdown('#newSection', rowData.class_id),
                $('#newSection').trigger('change');
                $('#newSection')[0].selectize.off('change'); // Remove previous handlers to avoid duplicates
                $('#newSection')[0].selectize.on('change', function(value) {
                    var limit = sectionLimitMap[value] !== undefined ? sectionLimitMap[value] : '';
                    $('#newLimit').val(limit);
                });
                $('#newSection').trigger('change');
                populateYearLevelDropdown('#newYear_level', rowData.year_level),
                populateCourseCodeDropdown('#newCourseCode', rowData.subject_id),
                $('#newCourseCode')[0].selectize.on('change', function(value) {
                    var title = courseTitleMap[value] || '';
                    $('#newCourseName').val(title);
                });
                $('#newCourseName').val(rowData.subject_text);
                
                populateProgramDropdown('#newProgram', rowData.program_id),
                document.querySelector('#fiscalYear').value = rowData.school_year + " " + rowData.sem;
                newSy_id = rowData.sy_id;
                $('#newLimit').val(rowData.section_limit);
                $('#newLec').val(rowData.lec);
                $('#newLab').val(rowData.lab);
                $('#newUnit').val(rowData.unit);
                $('#newHours').val(rowData.hours);

                if ($('#update_sched_selectize').length && !$('#update_sched_selectize')[0].selectize) {
                    $('#update_sched_selectize').selectize({
                        delimiter: ',',
                        persist: false,
                        create: false,
                        maxItems: null,
                        readOnly: true,
                        onDelete: function(values) {
                            return true;
                        }
                    });
                    $('#update_sched_selectize').next('.selectize-control').find('input').prop('readonly', true);
                }

                var selectizeInput = $('#update_sched_selectize')[0] && $('#update_sched_selectize')[0].selectize;
                if (selectizeInput) {
                    selectizeInput.clearOptions();
                    selectizeInput.clear();
                    if (Array.isArray(rowData.schedule_array)) {
                        rowData.schedule_array.forEach(function(item){
                            selectizeInput.addOption({value: item, text: item});
                            selectizeInput.addItem(item);
                        });
                    }
                }

                        // Store the editId for use on form submit
                editId = Number(rowData.teacher_class_id);
                $('#updateModal').modal('show');
                return;

            }
            if(archiveBtn){
                const rowId = archiveBtn.getAttribute('data-id');
                const row = tableSched.getRows().find(r => r.getData().teacher_class_id == rowId);
                const rowData = row.getData();

                

            }
        })

        $("#updateForm").on('submit', function(e){
            e.preventDefault();

            var selectizeInstance = $('#update_sched_selectize')[0] && $('#update_sched_selectize')[0].selectize;
            var scheduleSummaries = [];
            if (selectizeInstance) {
                var val = selectizeInstance.getValue();
                scheduleSummaries = val ? val.split(',') : []; // array of summary strings
            }

            const formData = jQuery("#updateForm").serializeArray();
            const newData = [
                {
                    name: "submitSchedule",
                    value: "updateSched"
                },
                {
                    name: "editId",
                    value: editId
                },
                {
                    name: "schedule_summaries",
                    value: JSON.stringify(scheduleSummaries)
                },
                {
                    name: "newSchoolYear",
                    value: newSy_id
                }
            ];
            const postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>registrar/actions/schedule_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: loadingAPIrequest(true),
                complete: loadingAPIrequest(false),
                success: function(data){
                    if(data){
                        if(data.code === 200 && data.msg_status === true){
                            swal({
                                title: "Updated Successfully.",
                                icon: "success",
                                text: data.msg_response,
                                button:false,
                                timer:3000,
                            }).then(function(){
                                $('#updateModal').modal('hide');
                                $('#updateForm')[0].reset();
                                tableSched.setData();
                            })
                        }
                        if(data.code === 501 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true, 
                            })
                        }
                        if(data.code === 502 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 503 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 504 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 505 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 506 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 507 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 508 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 509 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 510 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 511 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 512 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 513 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 514 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 500 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 401 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                        if(data.code === 404 && data.msg_status === false){
                            swal({
                                title: "Failed to update schedule.",
                                icon: "error",
                                text: data.msg_response,
                                button:true,
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    swal.close();
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "Network/Server error occured",
                        button:true
                    })
                }
            })
        });
})();
</script>

</html>