<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Enrollment";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];

if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}

$defaultFy = "";
$fy_id = '';
$sql = "SELECT school_year, sem, school_year_id FROM school_year WHERE isDefault = 1 LIMIT 1";
if($fetchSql = call_mysql_query($sql)){
    if($data = call_mysql_fetch_array($fetchSql)){
        $defaultFy = $data['school_year']." ".$data['sem'];
        $fy_id = $data['school_year_id'];
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

                <section class="card m-0">
                    <header class="card-header bg-primary text-white rounded-2 rounded-bottom-0" 
                        style="padding:0.75rem; padding-left:1.25em; padding-bottom:0.5rem;">
                        <label class="fs-5 text-white fw-bolder">Enrollment Table</label>
                    </header>
                    <div class="card-body pt-1" style="min-height: 40rem;">
                        <div class="row">
                            <div class="d-flex flex-md-row flex-column justify-content-start align-items-center col-md-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-primary-subtle fw-bold" id="inputGroup-sizing-default">Fiscal Year</span>
                                    <input type="text" id="fyInput" class="form-control fw-bolder" aria-label="Sizing example input" readonly aria-describedby="inputGroup-sizing-default">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <div class="table-bordered" id="enrollTable"></div>

                        </div>

                    </div>
                </section>

                <div class="modal fade" id="viewStudentInfo" tabindex="-1" aria-labelledby="viewLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-primary">
                                <h5 class="modal-title" id="viewLabel">Student Academic Information</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <h1>CONTENTS HERE</h1>
                            </div>
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
(function(){
    const actionButtons = function(cell, formatterParams){
        const student_id = cell.getRow().getData().student_id;
        const viewBtn = `<button class="btn btn-sm btn-info view-btn fs-6" style="color:black !important;" data-id="${student_id}"><i class="fas fa-eye"></i> View</button>`;
        return viewBtn;
    };
    
    const enrollTable = new Tabulator("#enrollTable", {
        ajaxURL: "<?php echo BASE_URL; ?>registrar/actions/fetchEnrollees.php",
        ajaxConfig: "GET",
        ajaxFiltering: true,
        layout:"fitDataStretch",
        ajaxSorting: true,
        placeholder: "No Data Available",
        pagination: "remote",
        paginationSize: 10,
        columns:[
            {
                title:"Student ID", 
                field:"student_id_no", 
                headerFilter:"input",
                hozAlign: "center",
            },
            {
                title:"Name", 
                field:"name", 
                headerFilter:"input",
                hozAlign: "center",
            },
            {
                title:"Section", 
                field:"section", 
                headerFilter:"input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title:"Program", 
                field:"program", 
                headerFilter:"input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title:"Curriculum", 
                field:"curriculum_code", 
                headerFilter:"input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title:"Year Level", 
                field:"year_level", 
                headerFilter:"input",
                hozAlign: "center",
                formatter: function(cell){
                    console.log(typeof cell.getData().year_level)
                    const yr_lvl = cell.getData().year_level;
                    switch(yr_lvl){
                        case 1:
                            return "1st Year";
                            break;
                        case 2:
                            return "2nd Year";
                            break;
                        case 3:
                            return "3rd Year";
                            break;
                        case 4:
                            return "4th Year";
                            break;
                        default:
                            return "No year level";
                    }
                }
            },
            {
                title:"Student Classification", 
                field: "WAIT",
                headerFilter:"input",
                hozAlign: "center",
                formatter: () => "wait for Final Grade Module"
            },
            {
                title: "Actions",
                formatter:actionButtons,
                hozAlign: "center",
                headerHozAlign: "center",
            }
        ],
    })

    const FY = <?php echo json_encode($defaultFy); ?>;
    (function FyInput(){
        document.getElementById('fyInput').value = FY ?? "No Fiscal Year";
    })();

    document.querySelector('#enrollTable').addEventListener('click', function(e){
        e.preventDefault();
        const view = e.target.closest('.view-btn');
        if(view){
            const btn_id = view.getAttribute('data-id');
            $('#viewStudentInfo').modal('show');
        }
    })
})();
</script>
</html>
