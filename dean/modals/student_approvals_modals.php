<!-- Subject Details & Decision Modal -->
<div class="modal fade" id="subjectDetailsModal" tabindex="-1" role="dialog" aria-labelledby="subjectDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="subjectDetailsLabel">Enrollment Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="subject_request_id">

                <div class="mb-3">
                    <div><strong>Student ID:</strong> <span id="subjectStudentId"></span></div>
                    <div><strong>Name:</strong> <span id="subjectStudentName"></span></div>
                    <div><strong>Program:</strong> <span id="subjectProgram"></span></div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;" class="text-center">#</th>
                                <th style="width: 15%;">Subject Code</th>
                                <th>Subject Title</th>
                                <th style="width: 15%;">Pre-Req</th>
                                <th style="width: 10%;" class="text-center">Units</th>
                            </tr>
                        </thead>
                        <tbody id="subjectDetailsBody">
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>

                <div class="form-group p-0 mb-3" id="subject_remarks_group" style="display:none;">
                    <label for="subject_remarks">Dean's Remarks / Evaluation Notes:</label>
                    <textarea id="subject_remarks" class="form-control" rows="3" placeholder="Enter reason for decision or special instructions..."></textarea>
                </div>

                <div class="form-group p-0" id="subject_recommend_group" style="display:none;">
                    <label class="mb-1">Recommended Subjects from Prospectus (optional):</label>
                    <div id="subject_recommend_list" class="border rounded p-2" style="max-height: 200px; overflow-y: auto; background-color: #f8fafc; font-size: 0.9rem;">
                        <!-- Checkboxes loaded via JS from student prospectus -->
                    </div>
                    <small class="form-text text-muted">Pick subject(s) from the student's prospectus to recommend for a future term.</small>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="btnSubjectReject" class="btn btn-danger">Disapprove</button>
                <button type="button" id="btnSubjectApprove" class="btn btn-success">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Prospectus Modal -->
<div class="modal fade" id="prospectusModal" tabindex="-1" role="dialog" aria-labelledby="prospectusLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="prospectusLabel">Student Prospectus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="prospectus-wrapper">
                    <div class="prospectus-sheet">
                        <div class="prospectus-header text-center mb-3">
                            <div class="school-name">CITY COLLEGE OF CALAMBA</div>
                            <div class="school-office">OFFICE OF THE COLLEGE REGISTRAR</div>
                            <div class="school-address">Calamba City</div>
                            <div class="prospectus-title">Bachelor of Science in Information Technology</div>
                            <div class="prospectus-revision">Rv. 2020</div>
                        </div>

                        <div class="prospectus-meta mb-2">
                            <div>
                                <span class="meta-label">NAME:</span>
                                <span id="prospectusStudentName"></span>
                                <span class="meta-spacer"></span>
                                <span class="meta-label">Units Earned:</span>
                                <span>0</span>
                            </div>
                            <div>
                                <span class="meta-label">STUDENT NO.:</span>
                                <span id="prospectusStudentId"></span>
                                <span class="meta-spacer"></span>
                                <span class="meta-label">Units to be Earned:</span>
                                <span>0</span>
                            </div>
                        </div>

                        <div class="prospectus-body" id="prospectusBody">
                            <!-- Dynamic content injected via dean/js/student_approvals.js from final_grade data -->
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
