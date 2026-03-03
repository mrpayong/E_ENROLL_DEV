<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;


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
                                <header class="d-flex bg-eclearance flex-column py-2 px-3 rounded-top flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <h1 class="fw-semibold mb-3 mb-md-0 fs-4 text-white">Curriculum</h1>
                                    <button class="btn btn-primary fw-semibold px-4 py-2 rounded-3" style="background:#173ea5;" data-bs-toggle="modal" data-bs-target="#createModal">
                                        <i class="bi bi-plus-lg text-white"></i> Create Curriculum
                                    </button>
                                </header>
                                <div class="p-3">
                                    <div id="curriculum-table"></div>
                                </div>
                            </section>
                        </div>
                </section>

            <!-- Create Curriculum Modal -->
            <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form class="modal-content" id="createForm">
                  <div class="modal-header bg-eclearance text-white">
                    <h5 class="modal-title" id="createModalLabel">Create Curriculum</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">

                    <div class="mb-3">
                      <label for="sem" class="form-label">Semester</label>
                      <select class="form-select" id="sem" name="sem" required>
                        <option value="">Select sem</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="department" class="form-label">Department</label>
                      <select class="form-select" id="department" name="department" required>
                        <option value="">Select Department</option>
                        <option value="1">DBA</option>
                        <option value="2">DCI</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="program" class="form-label">Program</label>
                      <select class="form-select" id="program" name="program" required>
                        <option value="">Select Program</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="curriculumName" class="form-label">Curriculum Title</label>
                      <input  type="text" class="form-control" id="curriculumName" name="curriculumName" required/>
                    </div>
                  </div>


                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                  </div>

                </form>
              </div>
            </div>

            <!-- edit -->
            <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form class="modal-content" id="updateForm">
                  <div class="modal-header bg-eclearance text-white">
                    <h5 class="modal-title" id="updateModalLabel"></h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">

                    <div class="mb-3">
                      <label for="newSem" class="form-label">Semester</label>
                      <select class="form-select" id="newSem" name="newSem" required>
                        <option value="">Select sem</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="newDepartment" class="form-label">Department</label>
                      <select class="form-select" id="newDepartment" name="newDepartment" required>
                        <option value="">Select Department</option>
                        <option value="1">DBA</option>
                        <option value="2">DCI</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="newProgram" class="form-label">Program</label>
                      <select class="form-select" id="newProgram" name="newProgram" required>
                        <option value="">Select Program</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="newCurriculumName" class="form-label">Curriculum Title</label>
                      <input  type="text" class="form-control" id="newCurriculumName" name="newCurriculumName" required/>
                    </div>
                  </div>


                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                  </div>

                </form>
              </div>
            </div>

            <div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form class="modal-content" id="archiveForm">
                  <div class="modal-header bg-eclearance text-white">
                    <h5 class="modal-title" id="archiveModalLabel"></h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">

                    <div class="mb-3">
                      <p  class="form-text text-dark fw-semibold" id="archiveDesc" name="archiveDesc">
                    </div>
                  </div>


                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary confirm-btn">Archive</button>
                    <button type="button" class="btn btn-danger cancel-btn" data-bs-dismiss="modal">Cancel</button>
                  </div>

                </form>
              </div>
            </div>
      </div>
  </div>
</div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {

    function loadingAPIrequest(status){
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

    function actionsFormatter(cell) {
        const row = cell.getRow().getData();

        return `
            <button data-id="${row.curriculum_id}" class="btn btn-sm btn-primary me-2 edit-btn" title="Edit"><i class="bi bi-pencil"></i></button>
            <button data-id="${row.curriculum_id}" class="btn btn-sm btn-danger archive-btn" title="Archive"><i class="bi bi-archive"></i></button>
        `;
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
            title: "Curriculum",
            field: "curriculum_title",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center"
        },
        {
            title: "Department",
            field: "department_name",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center"
        },
        {
            title: "Semester",
            field: "sem_name",
            headerFilterLiveFilter: true,
            headerFilter: "input",
            hozAlign: "center",
            headerHozAlign: "center"
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
            field: "createdAt",
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

  function populateDepartmentDropdown(selected, selectedId = null) {
      $.ajax({
          url: "<?php echo BASE_URL; ?>/registrar/actions/fetchDeptForProgram.php",
          method: "GET",
          dataType: "json",
          success: function(data) {
              if(data.status === false && data.code === 400){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      showConfirmButton: true,
                  });
                  return;
              }
              if(data.status === false && data.code === 401){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      showConfirmButton: true,
                  });
                  return;
              }
              if(data.status === false && data.code === 500){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Something went wrong.",
                      showConfirmButton: true,
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
              Swal.fire({
                  title: "Error",
                  icon: "error",
                  text: "Failed to load departments.",
                  showConfirmButton: true
              });
          }
      });
  }

  function populateSemDropdown(selected, selectedId = null) {
      $.ajax({
          url: "<?php echo BASE_URL; ?>/registrar/actions/fetchSemesterForForm.php",
          method: "GET",
          dataType: "json",
          success: function(data) {
              if(data.status === false && data.code === 400){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      showConfirmButton: true,
                      timer: 5000
                  });
                  return;
              }
              if(data.status === false && data.code === 401){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      showConfirmButton: true,
                      timer: 5000
                  });
                  return;
              }
              if(data.status === false && data.code === 500){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Something went wrong.",
                      showConfirmButton: true,
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
                              value: departments.school_year_id,
                              text: departments.sem,
                              selected: departments.school_year_id == selectedId
                          })
                      );
                  });
                  if(selectedId){
                      $programSelect.val(selectedId)
                  }
              }
          },
          error: function() {
              Swal.fire({
                  title: "Error",
                  icon: "error",
                  text: "Failed to load Semester.",
                  showConfirmButton: true
              });
          }
      });
  }

  function populateProgramDropdown(selected, selectedId = null) {
      $.ajax({
          url: "<?php echo BASE_URL; ?>/registrar/actions/fetchProgForSection.php",
          method: "GET",
          dataType: "json",
          success: function(data) {
              if(data.status === false && data.code === 400){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      showConfirmButton: true,
                  });
                  return;
              }
              if(data.status === false && data.code === 401){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Unavailable.",
                      showConfirmButton: true,
                  });
                  return;
              }
              if(data.status === false && data.code === 500){
                  Swal.fire({
                      title: "Error!",
                      icon: "error",
                      text: "Something went wrong.",
                      showConfirmButton: true,
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
              Swal.fire({
                  title: "Error",
                  icon: "error",
                  text: "Failed to load programs.",
                  showConfirmButton: true
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
    const archiveBtn = e.target.closest('.archive-btn');

    if(editBtn){
        const curriculumId = editBtn.getAttribute('data-id');
        const row = curriculumTable.getRows().find(r => r.getData().curriculum_id == curriculumId);
        const rowData = row.getData();

        editId = rowData.curriculum_id;
        document.getElementById('newCurriculumName').value = rowData.curriculum_title;
        populateDepartmentDropdown('#newDepartment', Number(rowData.department_id));
        populateSemDropdown('#newSem', Number(rowData.school_year_id));
        populateProgramDropdown('#newProgram', Number(rowData.program_id));
        document.getElementById('updateModalLabel').textContent = `Update ${rowData.curriculum_title}`;
        $('#updateModal').modal('show');
    }
    if(archiveBtn){
        const curriculumId = archiveBtn.getAttribute('data-id');
        const row = curriculumTable.getRows().find(r => r.getData().curriculum_id == curriculumId);
        const rowData = row.getData();

        editId = rowData.curriculum_id;
        currStatus = rowData.status;
        document.getElementById('archiveModalLabel').textContent = `Archive ${rowData.curriculum_title}`;
        document.getElementById('archiveDesc').textContent = `Are you sure you want to archive ${rowData.curriculum_title}?`;
        $('#archiveModal').modal('show');
    }
  })

    document.querySelector('#archiveForm').addEventListener('click', function(e){
        const confirmBtn = e.target.closest('.confirm-btn');
        const cancelBtn = e.target.closest('.cancel-btn')

        if(confirmBtn){
            if(currStatus === 0){
                return newStatus = 1;
            }
        }
        if(cancelBtn){
            document.getElementById('archiveModalLabel').textContent = ``; //modal title
            document.getElementById('archiveDesc').textContent = ``; //modal description
            currStatus = null;
            newStatus = null;
            if (document.activeElement) document.activeElement.blur();
            return;
        }
    })

  populateDepartmentDropdown('#department');
  populateSemDropdown('#sem');
  populateProgramDropdown('#program');

  $('#createForm').on('submit', function(e){
    e.preventDefault();

    const formData = jQuery('#createForm').serializeArray();
    const newData = [{
      name: 'submitCurriculum',
      value: "createCurriculum"
    }];

    console.log("postdata", postData)
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
            Swal.fire({
                title: "Successfully created!",
                text: data.msg_response,
                icon: 'success',
                showConfirmButton: false,
                timer: 3000
            }).then(function(){
              $('#createModal').modal('hide');
              $('#createForm')[0].reset();
              curriculumTable.setData();
            });
          } 
          if(data.msg_status === false && data.code === 501){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          }
          if(data.msg_status === false && data.code === 502){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
          if(data.msg_status === false && data.code === 503){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          }
          if(data.msg_status === false && data.code === 504){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
          if(data.msg_status === false && data.code === 500){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
        }
      },
      error: function(error, xhr, status){
        console.log("error", error);
        console.log("xhr", xhr);
        console.log("status", status);
        Swal.fire({
            title: "Error!",
            text: "Something went wrong, please try again later.",
            icon: 'error',
            showConfirmButton: true,
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
            Swal.fire({
                title: "Successfully update!",
                text: data.msg_response,
                icon: 'success',
                showConfirmButton: false,
                timer: 3000
            }).then(function(){
              $('#updateModal').modal('hide');
              $('#updateForm')[0].reset();
              curriculumTable.setData();
            });
          } 
          if(data.msg_status === false && data.code === 501){
            Swal.fire({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          }
          if(data.msg_status === false && data.code === 502){
            Swal.fire({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
          if(data.msg_status === false && data.code === 503){
            Swal.fire({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          }
          if(data.msg_status === false && data.code === 504){
            Swal.fire({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
          if(data.msg_status === false && data.code === 500){
            Swal.fire({
                title: "Failed to update.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
        }
      },
      error: function(error, xhr, status){
        console.log("error", error);
        console.log("xhr", xhr);
        console.log("status", status);
        Swal.fire({
            title: "Error!",
            text: "Something went wrong, please try again later.",
            icon: 'error',
            showConfirmButton: true,
        })
      }
    })    
  })

  $('#archiveForm').on('submit', function(e){
    e.preventDefault();

    const postData = [
        {
            name: 'submitCurriculum',
            value: "archiveCurriculum"
        },
        {
            name: 'editId',
            value: editId
        },
        {
            name: 'newStatus',
            value: newStatus
        }
    ];

    console.log('post: ', postData)

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
            Swal.fire({
                title: "Successfully archived!",
                text: data.msg_response,
                icon: 'success',
                showConfirmButton: false,
                timer: 3000
            }).then(function(){
              $('#archiveModal').modal('hide');
              $('#archiveForm')[0].reset();
              curriculumTable.setData();
            });
          } 
          if(data.msg_status === false && data.code === 501){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          }
          if(data.msg_status === false && data.code === 502){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
          if(data.msg_status === false && data.code === 504){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
          if(data.msg_status === false && data.code === 500){
            Swal.fire({
                title: "Failed to create.",
                text: data.msg_response,
                icon: 'error',
                showConfirmButton: true,
            })
          } 
        }
      },
      error: function(error, xhr, status){
        console.log("error", error);
        console.log("xhr", xhr);
        console.log("status", status);
        Swal.fire({
            title: "Error!",
            text: "Something went wrong, please try again later.",
            icon: 'error',
            showConfirmButton: true,
        })
      }
    })    
  })
});
</script>
</html>