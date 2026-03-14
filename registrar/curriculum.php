<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$prospectus_data = array();
$fetch_pros = "SELECT DISTINCT curriculum_id FROM curriculum";
  if($sql = call_mysql_query($fetch_pros)){
    while($data = call_mysql_fetch_array($sql)){
      array_push($prospectus_data, $data);
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
      <div id="main" class="container">
                <section class="section">
                        <div class="row justify-content-center mx-4 m-4">
                            <section class="card shadow-sm p-0" style="margin:auto;">
                                <header class="d-flex bg-primary flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <h1 class="fw-semibold mb-3 mb-md-0 fs-4 text-white">Curriculum</h1>
                                    <button class="btn btn-info fw-semibold px-4 py-2 rounded-3" style="background:#173ea5;" data-bs-toggle="modal" data-bs-target="#createModal">
                                        <i class="bi bi-plus-lg text-white"></i> Create Curriculum
                                    </button>
                                </header>
                                <div class="p-3" style="min-height: 40rem;">
                                  <div class="mb-3">
                                    <label class='fw-bold text-black'>Assign Status Legend: </label>
                                    <span class="fs-6 badge bg-success text-dark ms-2">Allowed to be assigned to students</span>
                                    <span class="fs-6 badge bg-danger text-dark ms-1">Not Allowed to be assigned to students</span>
                                  </div>  
                                  <div id="curriculum-table"></div>
                                </div>
                            </section>
                        </div>
                </section>

            <!-- Create Curriculum Modal -->
            <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form class="modal-content" id="createForm">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createModalLabel">Create Curriculum</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">

                    <div class="mb-3">
                      <label for="currTitle" class="form-label">Curriculum Title</label>
                      <input type="text" class="form-control" id="currTitle" name="currTitle" required/>
                    </div>

                    <div class="mb-3">
                      <label for="currCode" class="form-label">Curriculum Code</label>
                      <input type="text" class="form-control" id="currCode" name="currCode" required/>
                    </div>

                    <div class="mb-3">
                      <label for="program" class="form-label">Program</label>
                      <select class="form-select" id="program" name="program" required>
                      </select>
                    </div>
                  </div>


                  <div class="modal-footer">
                    <button type="submit" class="fs-6 btn btn-primary">Create</button>
                    <button type="button" class="fs-6 btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                  </div>

                </form>
              </div>
            </div>

            <!-- edit -->
            <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form class="modal-content" id="updateForm">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="updateModalLabel"></h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">

                    <div class="mb-3">
                      <label for="newCurrTitle" class="form-label">Curriculum Title</label>
                      <input  type="text" class="form-control" id="newCurrTitle" name="newCurrTitle" required/>
                    </div>

                    <div class="mb-3">
                      <label for="newCurrCode" class="form-label">Curriculum Code</label>
                      <input  type="text" class="form-control" id="newCurrCode" name="newCurrCode" required/>
                    </div>

                    <div class="mb-3">
                      <label for="newProgram" class="form-label">Program</label>
                      <select class="form-select" id="newProgram" name="newProgram" required>
                      </select>
                    </div>
                  </div>


                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                  </div>

                </form>
              </div>
            </div>

            <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form class="modal-content" id="statusForm">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="statusModalLabel"></h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <p id="statusDesc"></p>
                  </div>


                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-confirm">Confirm</button>
                    <button type="button" class="btn btn-danger btn-cancel" data-bs-dismiss="modal">Cancel</button>
                  </div>

                </form>
              </div>
            </div>

            <div class="modal fade" id="viewCurr" tabindex="-1" aria-labelledby="" aria-hidden="true">
              <div class="modal-dialog modal-xl view-curriculum-modal">
                <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Curriculum</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="alert alert-info py-1 mb-2 d-block d-md-none">
                      You may scroll sideways to see other data.
                    </div>
                    <div id="curriculum-contents"></div>
                  </div>


                  <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-cancel" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>

            
      </div>
  </div>
</div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const proscData = <?php echo json_encode($prospectus_data); ?>;

    function loadingAPIrequest(status){
        if(status === true){
            swal({
                title: "Loading",
                icon: 'info',
                text: "Please wait",
                button: false
            });
        }
        if(status === false){
            swal.close();
        }
    }

    function actionsFormatter(cell) {
        const row = cell.getRow().getData();
        const statusAllowable = Number(row.status_allowable);
        const hasProspectus = proscData.some(item => Number(item.curriculum_id) === Number(row.curriculum_id));

        let action = ``;

        if(statusAllowable === 1){
          action += `<button data-id="${row.curriculum_id}" class="fs-6 btn btn-sm btn-success me-2 text-black disallow-btn" title="Disallow"><i class="far fa-check-circle"></i> Allow</button>`;
          if(hasProspectus === false){
            action += `<button data-id="${row.curriculum_id}" class="fs-6 btn btn-sm btn-warning me-2 text-black edit-btn" title="Edit"><i class="fas fa-pencil-alt"></i> Update</button>`;
          }
          if(hasProspectus === true){
            action += `<button data-id="${row.curriculum_id}" class="fs-6 btn btn-sm btn-info me-2 view-btn" style="color:black !important;"  title="view curriculum"><i class="fas fa-eye"></i> View</button>`;
          }
        }
        if(statusAllowable === 0){
          action += `<button data-id="${row.curriculum_id}" class="fs-6 btn btn-sm btn-primary me-2 text-black allow-btn" title="Allow"><i class="far fa-times-circle"></i> Disallow</button>`;
          if(hasProspectus === true){
            action += `<button data-id="${row.curriculum_id}" class="fs-6 btn btn-sm btn-info me-2 view-btn" style="color:black !important;"  title="view curriculum"><i class="fas fa-eye"></i> View</button>`;
          }
          if(hasProspectus === false){
            action += `<button data-id="${row.curriculum_id}" class="fs-6 btn btn-sm btn-warning me-2 text-black edit-btn" title="Edit"><i class="fas fa-pencil-alt"></i> Update</button>`;
          }
        }
        if(hasProspectus === false && statusAllowable === 0){
          action += `<button data-id="${row.curriculum_id}" class="fs-6 btn btn-sm btn-info me-2 create-prospectus-btn" style="color:black !important;" title="create prospectus"><i class="fas fa-plus-circle"></i> Add Prospectus</button>`;
        }
        return action;
    }

  const curriculumTable = new Tabulator("#curriculum-table", {
    ajaxURL: "<?php echo BASE_URL;  ?>/registrar/actions/fetchCurriculum.php",
    ajaxConfig: "GET",
    layout: "fitDataStretch",
    responsiveLayout: "collapse",
    pagination: "remote",
    paginationSize: 10,
    movableColumns: true,
    headerFilterPlaceholder: "Search",
    placeholder: "No Data Found",
    columns: [
        {
            title: "Curriculum Title",
            field: "header",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center"
        },
        {
            title: "Curriculum Code",
            field: "curriculum_code",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center",
            formatter: function(cell){
              const value = cell.getValue();
              const row = cell.getRow().getData();
              const status = Number(row.status_allowable);

              let badgeClass = "";

              if(status === 1){
                badgeClass = "bg-danger";
              }
              if(status === 0){
                badgeClass = "bg-success";
              }
              return `<span class="badge ${badgeClass} fs-6">${value}</span>`;
            }
        },
        {
            title: "Required Units",
            field: "units",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center",
            formatter: function(cell) {
                const value = cell.getValue();
                return value > 1 
                  ? `${value} Units`
                  : value === 1
                    ? `${value} Unit`
                    : 'No Units';
            }
        },
        {
            title: "Program",
            field: "program_name",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center"
        },
        {
            title: "Created On",
            field: "date_created",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center"
        },
        {
            title: "Actions",
            field: "actions",
            hozAlign: "center",
            headerHozAlign: "center",
            formatter: actionsFormatter
        }
    ]
  })


  function populateProgramDropdown(selected, selectedId = null) {
      $.ajax({
          url: "<?php echo BASE_URL; ?>/registrar/actions/fetchProgForSection.php",
          method: "GET",
          dataType: "json",
          success: function(data) {
              if(data.status === false && data.code === 400){
                  swal({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      button: true,
                  });
                  return;
              }
              if(data.status === false && data.code === 401){
                  swal({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      button: true,
                  });
                  return;
              }
              if(data.status === false && data.code === 500){
                  swal({
                      title: "Error!",
                      icon: "error",
                      text: "Something went wrong.",
                      button: true,
                  });
                  return;
              }
              if(data.status === true && data.code === 200){
                  var $programSelect = $(selected);
                  $programSelect.empty();
                  $programSelect.append('<option value="" disabled selected>Select Program</option>');
                  data.data.forEach(function(departments) {
                      $programSelect.append(
                          $('<option>', {
                              value: departments.program_id,
                              text: departments.program,
                              selected: departments.program_id == selectedId
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
                  text: "Failed to load programs.",
                  button: true
              });
          }
      });
  }

  let editId;
  let currStatus;
  let newStatus;

  document.querySelector('#curriculum-table').addEventListener('click', function(e){
    e.preventDefault();
    const editBtn = e.target.closest('.edit-btn');
    const allow = e.target.closest('.allow-btn');
    const disallow = e.target.closest('.disallow-btn');
    const view = e.target.closest('.view-btn');
    const createProspectusBtn = e.target.closest('.create-prospectus-btn');
    let viewCurrOpen = false;

    document.addEventListener('keydown', function (e) {
      if (!viewCurrOpen) return;
      const isReload = (e.key === 'F5') || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'r');
      if (isReload) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);

    if(editBtn){
        const curriculumId = editBtn.getAttribute('data-id');
        const row = curriculumTable.getRows().find(r => r.getData().curriculum_id == curriculumId);
        const rowData = row.getData();

        editId = rowData.curriculum_id;
        console.log("rowData", rowData);
        document.getElementById('newCurrTitle').value = rowData.header;
        document.getElementById('newCurrCode').value = rowData.curriculum_code;
        populateProgramDropdown('#newProgram', Number(rowData.program_id));
        document.getElementById('updateModalLabel').textContent = `Update ${rowData.header}`;
        $('#updateModal').modal('show');
    }
    if(allow || disallow){
        const curriculumId = allow ? allow.getAttribute('data-id') : disallow.getAttribute('data-id');
        const row = curriculumTable.getRows().find(r => r.getData().curriculum_id == curriculumId);
        const rowData = row.getData();

        editId = rowData.curriculum_id;
        currStatus = rowData.status_allowable;
        document.getElementById('statusModalLabel').textContent = `${currStatus === 1 ? 'Allow' : 'Disallow'} ${rowData.header}`;
        document.getElementById('statusDesc').textContent = `Are you sure you want to ${currStatus === 1 ? 'allow' : 'disallow'} ${rowData.header}?`;
        $('#statusModal').modal('show');
    }
    if(view){
      // I AM TRYING TO APPEND NEW CONTENT HERE
      const curriculumId = view.getAttribute('data-id');
      const rowData = curriculumTable.getRows().find(r => r.getData().curriculum_id == curriculumId).getData();
      $.ajax({
          url: "<?php echo BASE_URL; ?>registrar/actions/fetchProspectus.php",
          method: "GET",
          data: { curriculum_id: curriculumId },
          dataType: "json",
          beforeSend: loadingAPIrequest(true),
          success: function (data) {
            loadingAPIrequest(false);
            if(data){
              if(data.msg_status === true && data.code === 200){
                const rows = Array.isArray(data.data) ? data.data : [];
                const totalUnits = rows.reduce((sum, r) => sum + Number(r.unit || 0), 0);
                const $container = $('#curriculum-contents');
                $container.empty();

                if (rows.length === 0) {
                    $container.html('<div class="text-muted">No prospectus data found.</div>');
                    return;
                }

                // Helper: escape HTML
                const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({
                    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
                }[m]));

                // Header info from first row
                const headerTitle = esc(rows[0].curriculum_title || '');
                const headerCode  = esc(rows[0].curriculum_code || '');

                // Normalize semester to 1 or 2 and label
                function semIndex(semStr) {
                    const s = (semStr || '').toString().toLowerCase();
                    if (s.includes('1')) return 1;
                    if (s.includes('2')) return 2;
                    if (s.includes('first')) return 1;
                    if (s.includes('second')) return 2;
                    return 0;
                }
                function semLabel(idx) {
                    return idx === 1 ? 'FIRST SEMESTER' : 'SECOND SEMESTER';
                }

                // Group rows by year -> semester
                const grouped = {};
                rows.forEach(r => {
                    const yr_lvl = parseInt(r.year_level, 10) || 0;
                    const sIdx = semIndex(r.semester);
                    if (!grouped[yr_lvl]) grouped[yr_lvl] = {1: [], 2: []};
                    if (sIdx === 1 || sIdx === 2) grouped[yr_lvl][sIdx].push(r);
                });

                // console.log('group keys: ',Object.keys(grouped) ,typeof Object.keys(grouped));
                const hasIncomplete = Object.keys(grouped).some(yr_lvl => {
                  return grouped[yr_lvl][1].length === 0 || grouped[yr_lvl][2].length === 0;
                });

                if (hasIncomplete) {
                  swal({
                    title: "Failed",
                    icon: "error",
                    text: "Cannot view prospectus. Incomplete data.",
                    button: true
                  });
                  return;
                }

                function renderSemesterTable(semRows, semIdx) {
                    let totalUnits = 0;
                    if(semRows.length === 0) return;
                    const body = semRows.map(r => {
                        const units = Number(r.unit || 0);
                        totalUnits += units;
                        return `
                            <tr>
                              <td>${esc(r.subject_code)}</td>
                              <td>${esc(r.subject_title)}</td>
                              <td class="text-center">${esc(r.lec)}</td>
                              <td class="text-center">${esc(r.lab)}</td>
                              <td class="text-center">${esc(units)}</td>
                              <td>${esc(r.pre_req || '')}</td>
                            </tr>
                        `;
                    }).join('');

                    const padStyle = semRows[0].semester === '1st Semester' ? 'pe-xl-0 border-end border-black' : 'ps-xl-0';
                    return `
                        <div class="col-12 col-xl-6 ${padStyle}">
                            <div class="semester-title align-items-center text-center fw-bold rounded-0">
                                ${semLabel(semIdx)}
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm semester-table mb-2">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Course Title</th>
                                            <th class="text-center">Lec</th>
                                            <th class="text-center">Lab</th>
                                            <th class="text-center">Units</th>
                                            <th>Pre-Req</th>
                                        </tr>
                                    </thead>
                                    <tbody>${body}</tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Total Units</th>
                                            <th class="text-center">${totalUnits.toFixed(2)}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    `;
                }

                // Build final HTML
                let html = `
                  <div class="mb-3 d-flex flex-column flex-md-row justify-content-between gap-2">
                      <div>
                          <h4 class="mb-1">${headerTitle}</h4>
                          <div class="text-muted">Curriculum Code: ${headerCode}</div>
                      </div>
                      <div class="text-md-end d-flex flex-column align-items-md-end">
                          <span class="fw-semibold fs-5">Total Units Required: ${rowData.units.toFixed(2)}</span>
                          <span class="fw-semibold fs-5">Total Units Shown: ${totalUnits.toFixed(2)}</span>
                      </div>
                  </div>
                `;


                Object.keys(grouped).sort((a,b)=>a-b).forEach(yr_lvl => {
                    html += `
                        <section class="prospectus-block-card mb-4 border border-1 border-secondary-subtle">
                            <div class="prospectus-block-header px-3 py-2 d-flex justify-content-center align-items-center">
                                <h3 class="h5 mb-0 fw-bolder">${esc(['','FIRST','SECOND','THIRD','FOURTH','FIFTH'][yr_lvl])} YEAR</h3>
                            </div>
                            <div>
                                <div class="row">
                                    ${renderSemesterTable(grouped[yr_lvl][1], 1)}
                                    ${renderSemesterTable(grouped[yr_lvl][2], 2)}
                                </div>
                            </div>
                        </section>
                    `;
                });

                $('#viewCurr').modal('show');
                $container.html(html);
              }
              if(data.msg_status === false && data.code === 500){
                swal({
                  title: "Falied to load curriculum",
                  text: data.msg_response,
                  icon: "error",
                  button: true
                })
              }
              if(data.msg_status === false && data.code === 404){
                swal({
                  title: "Falied to load curriculum",
                  text: data.msg_response,
                  icon: "error",
                  button: true
                })
              }
            }
          },
          error: function (xhr, status, error) {
              swal({
                  title: "Error",
                  icon: "error",
                  text: "Failed to load curriculum details.",
                  button: true
              });
          }
      });

    }
    if (createProspectusBtn) {
      const curriculumId = createProspectusBtn.getAttribute('data-id');
      $.ajax({
        url: "<?php echo BASE_URL.URL_Prospectus; ?>",
        method:"POST",
        dataType: "json",
        data: { curriculum_id: curriculumId},
        beforeSend: loadingAPIrequest(true),
        success: function(data){
          loadingAPIrequest(false);
          if(data){
            if(data.code === 200 && data.msg_status === true){
              const url = "<?php echo BASE_URL; ?>registrar/prospectus.php?coin=" + encodeURIComponent(data.token);
              window.open(url, "_blank");
            }
          }
        },
        error: function () {
          swal({ 
            title: "Error", 
            text: "Failed to load page.", 
            icon: "error" 
          });
        }
      })
      return;
    }
  })

  populateProgramDropdown('#program');

  $('#createForm').on('submit', function(e){
    e.preventDefault();

    const formData = jQuery('#createForm').serializeArray();
    const newData = [{
      name: 'submitCurriculum',
      value: "createCurriculum"
    }];

    const postData = formData.concat(newData);

    $.ajax({
      url: "<?php echo BASE_URL; ?>/registrar/actions/curriculum_process.php",
      method: "POST",
      data: postData,
      dataType: "json",
      beforeSend: loadingAPIrequest(true),
      complete: loadingAPIrequest(false),
      success: function(data){
        if(data){
          if(data.msg_status === true && data.code === 200){
            swal({
                title: "Successfully created!",
                text: data.msg_response,
                icon: 'success',
                button: false,
                timer: 3000
            }).then(function(){
              $('#createModal').modal('hide');
              $('#createForm')[0].reset();
              curriculumTable.setData();
            });
          } 
          if(data.msg_status === false && data.code === 501){
            swal({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          }
          if(data.msg_status === false && data.code === 502){
            swal({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          }
          if(data.msg_status === false && data.code === 504){
            swal({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          } 
          if(data.msg_status === false && data.code === 500){
            swal({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          } 
        }
      },
      error: function(error, xhr, status){
        swal({
            title: "Error!",
            text: "Something went wrong, please try again later.",
            icon: 'error',
            button: true,
        })
      }
    })    
  })

  $('#updateForm').on('submit', function(e){
    e.preventDefault();

    const formData = jQuery('#updateForm').serializeArray();
    const newData = [
        {
        name: 'submitCurriculum',
        value: "updateCurriculum"
        },
        {
            name: 'editId',
            value: editId
        }
    ];

    const postData = formData.concat(newData);

    $.ajax({
      url: "<?php echo BASE_URL; ?>/registrar/actions/curriculum_process.php",
      method: "POST",
      data: postData,
      dataType: "json",
      beforeSend: loadingAPIrequest(true),
      complete: loadingAPIrequest(false),
      success: function(data){
        console.log("response", data);
        if(data){
          if(data.msg_status === true && data.code === 200){
            swal({
                title: "Successfully update!",
                text: data.msg_response,
                icon: 'success',
                button: false,
                timer: 3000
            }).then(function(){
              $('#updateModal').modal('hide');
              $('#updateForm')[0].reset();
              curriculumTable.setData();
            });
          } 
          if(data.msg_status === false && data.code === 501){
            swal({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          }
          if(data.msg_status === false && data.code === 502){
            swal({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          } 
          if(data.msg_status === false && data.code === 503){
            swal({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          }
          if(data.msg_status === false && data.code === 504){
            swal({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          } 
          if(data.msg_status === false && data.code === 500){
            swal({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          } 
        }
      },
      error: function(error, xhr, status){
        swal({
            title: "Error!",
            text: "Something went wrong, please try again later.",
            icon: 'error',
            button: true,
        })
      }
    })    
  })

  document.querySelector('#statusForm').addEventListener('click', function(e){
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

  $('#statusForm').on('submit', function(e){
    e.preventDefault();
    const postData = [
      {
        name: "submitCurriculum",
        value: "updateStatus"
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

    $.ajax({
      url: "<?php echo BASE_URL; ?>registrar/actions/curriculum_process.php",
      method: "POST",
      data: postData,
      dataType: "json",
      beforeSend: loadingAPIrequest(true),
      complete: loadingAPIrequest(false),
      success: function(data){
        if(data){
          if(data.msg_status === true && data.code === 200){
            swal({
                title: "Successfully updated status!",
                text: data.msg_response,
                icon: 'success',
                button: false,
                timer: 3000
            }).then(function(){
              $('#statusModal').modal('hide');
              curriculumTable.setData();
            });
          } 
          if(data.msg_status === false && data.code === 501){
            swal({
                title: "Failed to update status.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          }
          if(data.msg_status === false && data.code === 502){
            swal({
                title: "Failed to update status.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          }
          if(data.msg_status === false && data.code === 500){
            swal({
                title: "Failed to update status.",
                text: data.msg_response,
                icon: 'error',
                button: true,
            })
          } 
        }
      },
      error: function(error, xhr, status){
        swal({
            title: "Error!",
            text: "Something went wrong, please try again later.",
            icon: 'error',
            button: true,
        })
      }
    })
  })

});
</script>
</html>