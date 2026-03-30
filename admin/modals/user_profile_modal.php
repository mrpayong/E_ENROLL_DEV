<!-- Update Profile Modal -->
<div class="modal fade" id="profile_modal" aria-hidden="true" role="dialog" aria-labelledby="profileModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header color-accent-blue-bg text-white">
                <h5 class="modal-title" id="profileModalLabel"><i class="bi bi-pencil-square"></i> Update Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userupdate" method="post">
                <div class="modal-body">
                    <h5 class="mb-3">
                        <span id="err_msg" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></span>
                    </h5>
                    <input type="hidden" name="general_id" value="<?php echo $g_general_id; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><b>Username</b></label>
                            <input type="text" name="username" id="username" value="<?php echo $db_username; ?>" class="form-control" required>
                            <span id="err_username" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><b>Recovery Email Address</b></label>
                            <input type="email" name="recovery_email" id="recovery_email" value="<?php echo $db_recovery_email; ?>" class="form-control" required>
                            <span id="err_recovery_email" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-blue" id="update_user" name="update_user" value="update_user">Update Information</button>
                </div>
            </form>
        </div>
    </div>
</div>
