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
                    <label class="fs-2 text-white fw-bolder">Enrollment</label>
                </header>
                <div class="card-body pt-1" style="min-height: 40rem;">
                    <div class="table-responsive">
                        <div class="table-bordered" id="enrollTable"></div>

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
(function(){
    
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
                field:"student_id", 
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
                title:"Program", 
                field:"program", 
                headerFilter:"input",
                hozAlign: "center",
                headerHozAlign: "center",
            },
            {
                title:"Year Level", 
                field:"year_level", 
                headerFilter:"input",
                hozAlign: "center",
            },
            {
                title:"Status", 
                field:"status", 
                headerFilter:"input",
                hozAlign: "center",
            }
        ],
    })
})();
</script>
</html>
