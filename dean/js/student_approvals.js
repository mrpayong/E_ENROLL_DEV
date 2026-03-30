(function ($) {

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDateSubmitted(raw) {
        if (!raw) return "";

        if (window.luxon && window.luxon.DateTime) {
            var dt = window.luxon.DateTime.fromSQL(raw);
            if (dt.isValid) {
                return dt.toFormat("MMM dd, yyyy hh:mm a");
            }
        }

        var normalized = raw.replace(" ", "T");
        var d = new Date(normalized);

        if (!isNaN(d.getTime())) {
            return d.toLocaleString();
        }

        return raw;
    }

    $(document).ready(function () {

        var cfg = window.deanApprovalsConfig || {};
        var allData = Array.isArray(cfg.tableData) ? cfg.tableData : [];
        var deanId = cfg.deanId || "";
        var baseUrl = cfg.baseUrl || "";

        // Debug: verify what the front-end actually received
        console.log('[DeanApprovals] raw cfg.tableData type:', Object.prototype.toString.call(cfg.tableData));
        console.log('[DeanApprovals] allData length after normalization:', allData.length);

        // Debug: inspect each row's status and subject counts
        allData.forEach(function (row) {
            console.log('[DeanApprovals] row', row.request_id,
                'status=', row.status,
                'status_normalized=', row.status_normalized,
                'subject_count=', row.subject_count,
                'requested_subjects type=', Object.prototype.toString.call(row.requested_subjects));
        });

        // Track current status filter label: 'PENDING' or 'DONE'
        var currentStatusFilter = 'PENDING';

        /* ===============================
           CONFIRM WRAPPER
        =============================== */

        function confirmAndSubmit(status) {
            var actionText =
                status === 'APPROVED'
                    ? 'approve this enrollment request'
                    : 'disapprove this enrollment request';

            // Require remarks only when disapproving
            if (status === 'REJECTED') {
                var remarksVal = $('#subject_remarks').val();
                if (!remarksVal || !remarksVal.trim()) {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Remarks required',
                            text: 'Please enter a reason for disapproval before proceeding.'
                        });
                    } else if (typeof window.swal === 'function') {
                        window.swal('Remarks required', 'Please enter a reason for disapproval before proceeding.', 'warning');
                    } else {
                        alert('Please enter a reason for disapproval before proceeding.');
                    }
                    return;
                }
            }

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Are you sure?',
                    text: 'You are about to ' + actionText + '.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: status === 'APPROVED' ? '#16a34a' : '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText:
                        status === 'APPROVED'
                            ? 'Yes, approve'
                            : 'Yes, disapprove'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        submitDecision(status);
                    }
                });
            } else {
                // Fallback, but still SweetAlert style if global swal() exists
                if (typeof window.swal === 'function') {
                    window.swal({
                        title: 'Are you sure?',
                        text: 'You are about to ' + actionText + '.',
                        icon: 'warning',
                        buttons: true,
                        dangerMode: (status !== 'APPROVED')
                    }).then(function (willDo) {
                        if (willDo) {
                            submitDecision(status);
                        }
                    });
                } else if (confirm('Are you sure you want to ' + actionText + '?')) {
                    submitDecision(status);
                }
            }
        }

        /* ===============================
           TABULATOR
        =============================== */

        var table = new Tabulator("#approval-table", {
            // Load any data we already have instead of starting from an empty array
            data: allData,
            layout: "fitColumns",
            height: "700px",
            pagination: "local",
            paginationSize: 25,
            paginationSizeSelector: [25, 50, 100],
            placeholder: "No pending enrollment requests found.",
            headerHozAlign: "center",
            columns: [
                {
                    title: "Date Submitted",
                    field: "created_at",
                    sorter: "string",
                    headerFilter: "input",
                    formatter: function (cell) {
                        return formatDateSubmitted(cell.getValue());
                    }
                },
                {
                    title: "Status",
                    field: "status",
                    // Use a simple text filter to avoid Tabulator's deprecated select/list editor warnings
                    headerFilter: "input",
                    formatter: function (cell) {
                        var val = (cell.getValue() || '').toUpperCase();
                        if (val === 'APPROVED') return '<span class="badge bg-success">Approved</span>';
                        if (val === 'REJECTED') return '<span class="badge bg-danger">Rejected</span>';
                        if (val === 'PENDING') return '<span class="badge bg-warning text-dark">Pending</span>';
                        return escapeHtml(val);
                    }
                },
                {
                    title: "Name",
                    field: "fullname",
                    headerFilter: "input"
                },
                {
                    title: "Program",
                    field: "program",
                    headerFilter: "input"
                },
                {
                    title: "Subjects",
                    field: "requested_subjects",
                    formatter: function (cell) {

                        var subjects = cell.getValue();

                        if (Array.isArray(subjects) && subjects.length > 0) {

                            var codes = subjects
                                .map(function (sub) {
                                    return sub.subject_code || "";
                                })
                                .filter(function (code) {
                                    return code !== "";
                                });

                            var title = codes.join(', ');
                            var count = subjects.length;
                            var label = count + " subject" + (count > 1 ? "s" : "");

                            return "<span class='subject-tag' title='" +
                                title.replace(/'/g, "&#39;") +
                                "'>" + label + "</span>";
                        }

                        return "";
                    }
                },
                {
                    title: "Remarks",
                    field: "remarks",
                    headerFilter: "input",
                    minWidth: 220,
                    widthGrow: 3,
                    formatter: function (cell) {
                        return escapeHtml(cell.getValue() || "");
                    }
                },
                {
                    title: "Dean Recommended Subjects",
                    field: "recommended_subjects",
                    minWidth: 180,
                    widthGrow: 1,
                    formatter: function (cell) {
                        var raw = cell.getValue();
                        if (!raw) return "";

                        var arr = [];
                        if (Array.isArray(raw)) {
                            arr = raw;
                        } else if (typeof raw === 'string') {
                            try {
                                var tmp = JSON.parse(raw);
                                if (Array.isArray(tmp)) arr = tmp;
                            } catch (e) {
                                // not JSON, treat as plain string
                                arr = [raw];
                            }
                        }

                        if (!arr.length) return "";
                        return escapeHtml(arr.join(', '));
                    }
                },
                {
                    title: "Action",
                    field: "request_id",
                    hozAlign: "center",
                    width: 140,
                    headerSort: false,
                    formatter: function (cell) {

                        var id = cell.getValue();
                        var rowData = cell.getRow().getData();
                        var studentId = rowData.student_id;
                        var studentName = rowData.fullname || "";
                        var statusNorm = (rowData.status_normalized || rowData.status || "").toString().toUpperCase();

                        var canEdit = (statusNorm === 'PENDING');

                        var detailTitle = canEdit
                            ? "View and decide on this request"
                            : "View details (already processed)";

                        return "<div class='form-button-action'>" +
                            "<button type='button' class='btn btn-link btn-action' title='" + detailTitle + "' onclick='openSubjectDetails(" + id + ")'>" +
                                "<i class='fa fa-list text-success'></i>" +
                            "</button>" +
                            "<button type='button' class='btn btn-link btn-action' title='View full prospectus' onclick=\"openProspectusModal('" +
                                studentId + "', '" +
                                studentName.replace(/'/g, "&#39;") +
                            "')\">" +
                                "<i class='fa fa-book text-primary'></i>" +
                            "</button>" +
                        "</div>";
                    }
                }
            ]
        });

        // Apply initial Pending filter only after the table is fully built
        if (allData.length) {
            table.on("tableBuilt", function () {
                applyPendingFilter();
            });
        }

        /* ===============================
           FIND REQUEST
        =============================== */

        function findRequestById(id) {
            return allData.find(function (r) {
                return String(r.request_id) === String(id);
            }) || null;
        }

        /* ===============================
           OPEN MODAL
        =============================== */

        window.openSubjectDetails = function (requestId) {

            var req = findRequestById(requestId);
            if (!req) return;

            $('#subject_request_id').val(req.request_id);
            $('#subjectStudentId').text(req.student_id || '');
            $('#subjectStudentName').text(req.fullname || '');
            $('#subjectProgram').text(req.program || '');

            var tbody = $('#subjectDetailsBody');
            tbody.empty();

            var subjects = req.requested_subjects;

            // Normalize in case the field was encoded as JSON string
            if (typeof subjects === 'string') {
                try {
                    var parsed = JSON.parse(subjects);
                    if (Array.isArray(parsed)) {
                        subjects = parsed;
                    }
                } catch (e) {
                    subjects = [];
                }
            }

            if (!Array.isArray(subjects)) {
                subjects = [];
            }

            console.log('[DeanApprovals] openSubjectDetails request', req);
            console.log('[DeanApprovals] subject count for request', requestId, ':', subjects.length);

            if (!subjects.length) {
                tbody.append(
                    '<tr><td colspan="5" class="text-center text-muted">No subjects found.</td></tr>'
                );
            } else {
                var regular = [];
                var backlog = [];
                var higherYear = [];

                subjects.forEach(function (sub) {
                    var isBacklog = sub && (sub.is_backlog || sub.is_backlog === 1 || sub.is_backlog === '1');
                    var isHigher = sub && (sub.is_higher_year || sub.is_higher_year === 1 || sub.is_higher_year === '1');

                    if (isBacklog) {
                        backlog.push(sub);
                    } else if (isHigher) {
                        higherYear.push(sub);
                    } else {
                        regular.push(sub);
                    }
                });

                var rowIndex = 1;

                if (regular.length) {
                    tbody.append('<tr class="table-success"><td colspan="5"><strong>Regular subjects in this term</strong></td></tr>');
                    regular.forEach(function (sub) {
                        tbody.append(
                            '<tr>' +
                            '<td class="text-center">' + (rowIndex++) + '</td>' +
                            '<td>' + escapeHtml(sub.subject_code || '') + '</td>' +
                            '<td>' + escapeHtml(sub.subject_title || '') + '</td>' +
                            '<td>' + escapeHtml(sub.pre_req || '') + '</td>' +
                            '<td class="text-center">' + escapeHtml(sub.unit || sub.units || '') + '</td>' +
                            '</tr>'
                        );
                    });
                }

                if (backlog.length) {
                    tbody.append('<tr class="table-danger"><td colspan="5"><strong>Backlog subjects</strong></td></tr>');
                    backlog.forEach(function (sub) {
                        tbody.append(
                            '<tr class="text-danger fw-semibold">' +
                            '<td class="text-center">' + (rowIndex++) + '</td>' +
                            '<td>' + escapeHtml(sub.subject_code || '') + '</td>' +
                            '<td>' + escapeHtml(sub.subject_title || '') + '</td>' +
                            '<td>' + escapeHtml(sub.pre_req || '') + '</td>' +
                            '<td class="text-center">' + escapeHtml(sub.unit || sub.units || '') + '</td>' +
                            '</tr>'
                        );
                    });
                }

                if (higherYear.length) {
                    tbody.append('<tr class="table-primary"><td colspan="5"><strong>Higher-year subjects</strong></td></tr>');
                    higherYear.forEach(function (sub) {
                        tbody.append(
                            '<tr class="text-primary fw-semibold">' +
                            '<td class="text-center">' + (rowIndex++) + '</td>' +
                            '<td>' + escapeHtml(sub.subject_code || '') + '</td>' +
                            '<td>' + escapeHtml(sub.subject_title || '') + '</td>' +
                            '<td>' + escapeHtml(sub.pre_req || '') + '</td>' +
                            '<td class="text-center">' + escapeHtml(sub.unit || sub.units || '') + '</td>' +
                            '</tr>'
                        );
                    });
                }
            }

            $('#subject_remarks').val('');
            $('#subject_recommend_list').empty();

            // Preload recommended-subject options from the student's prospectus
            var sid = req.student_id || '';
            if (sid) {
                loadRecommendedSubjectsFromProspectus(sid);
            }

            // Show or hide decision controls depending on status
            var stNorm = getNormalizedStatus(req);
            if (stNorm === 'PENDING') {
                $('#subject_remarks_group').show();
                $('#subject_recommend_group').show();
                $('#btnSubjectApprove').prop('disabled', false).show();
                $('#btnSubjectReject').prop('disabled', false).show();
            } else {
                // Already processed: view-only modal
                $('#subject_remarks_group').hide();
                $('#subject_recommend_group').hide();
                $('#btnSubjectApprove').prop('disabled', true).hide();
                $('#btnSubjectReject').prop('disabled', true).hide();
            }

            $('#subjectDetailsModal').modal('show');
        };

        function loadRecommendedSubjectsFromProspectus(studentId) {
            var container = $('#subject_recommend_list');
            if (!container.length) return;

            container.html('<div class="text-muted small">Loading subjects from prospectus...</div>');

            var apiUrl = baseUrl && baseUrl.endsWith('/') ? baseUrl : (baseUrl + '/');
            apiUrl += 'dean/process/student_prospectus_data.php';

            $.ajax({
                url: apiUrl,
                type: 'POST',
                dataType: 'json',
                data: { student_id: studentId },
                success: function (res) {
                    container.empty();

                    if (!res || !res.success || !res.prospectus) {
                        container.html('<div class="text-muted small">No prospectus subjects found.</div>');
                        return;
                    }

                    var added = {};
                    Object.keys(res.prospectus).forEach(function (yearKey) {
                        var semesters = res.prospectus[yearKey] || {};
                        Object.keys(semesters).forEach(function (semKey) {
                            var semData = semesters[semKey] || {};
                            (semData.subjects || []).forEach(function (sub) {
                                var code = (sub.subject_code || '').trim();
                                if (!code || added[code]) return;

                                // Skip subjects already passed by the student
                                var isPassed = sub.is_passed === 1 || sub.is_passed === '1' || sub.is_passed === true;
                                if (isPassed) return;

                                added[code] = true;

                                var title = sub.course_desc || '';
                                var label = code + (title ? ' - ' + title : '');
                                var checkboxId = 'rec_' + code.replace(/[^A-Za-z0-9_:-]/g, '_');
                                var html = '' +
                                    '<div class="form-check mb-1">' +
                                        '<input class="form-check-input" type="checkbox" id="' + escapeHtml(checkboxId) + '" value="' + escapeHtml(code) + '">' +
                                        '<label class="form-check-label" for="' + escapeHtml(checkboxId) + '">' + escapeHtml(label) + '</label>' +
                                    '</div>';
                                container.append(html);
                            });
                        });
                    });

                    if (!Object.keys(added).length) {
                        container.html('<div class="text-muted small">No remaining subjects to recommend.</div>');
                    }
                },
                error: function () {
                    container.html('<div class="text-muted small">Unable to load prospectus subjects.</div>');
                }
            });
        }

        /* ===============================
           SUBMIT
        =============================== */

        function submitDecision(status) {

            var requestId = $('#subject_request_id').val();
            if (!requestId) return;

            var btnApprove = $('#btnSubjectApprove');
            var btnReject = $('#btnSubjectReject');

            btnApprove.prop('disabled', true);
            btnReject.prop('disabled', true);

            var apiUrl = baseUrl.endsWith('/')
                ? baseUrl
                : baseUrl + '/';

            apiUrl += 'dean/process/student_approvals_process.php';

            // Collect any recommended subjects selected by the Dean (array of codes)
            var recommended = [];
            $('#subject_recommend_list input.form-check-input:checked').each(function () {
                var code = ($(this).val() || '').trim();
                if (code) {
                    recommended.push(code);
                }
            });

            $.ajax({
                url: apiUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    request_id: requestId,
                    status: status,
                    remarks: $('#subject_remarks').val(),
                    dean_id: deanId,
                    recommended_subjects: JSON.stringify(recommended)
                },
                success: function (res) {

                    if (res.success) {
                        var successTitle = status === 'APPROVED'
                            ? 'Enrollment Approved'
                            : 'Enrollment Disapproved';
                        var successText = status === 'APPROVED'
                            ? 'The enrollment request has been approved successfully.'
                            : 'The enrollment request has been disapproved successfully.';

                        if (window.Swal && typeof window.Swal.fire === 'function') {
                            window.Swal.fire({
                                icon: 'success',
                                title: successTitle,
                                text: successText,
                                confirmButtonColor: '#2563EB'
                            }).then(function () {
                                location.reload();
                            });
                        } else if (typeof window.swal === 'function') {
                            window.swal({
                                icon: 'success',
                                title: successTitle,
                                text: successText
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            alert(successTitle + ': ' + successText);
                            location.reload();
                        }
                    } else {
                        if (window.Swal && typeof window.Swal.fire === 'function') {
                            window.Swal.fire({
                                icon: 'error',
                                title: 'System Error',
                                text: res.message || 'Unable to save decision.'
                            });
                        } else {
                            alert('System Error: ' + res.message);
                        }
                        btnApprove.prop('disabled', false);
                        btnReject.prop('disabled', false);
                    }
                },
                error: function () {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Connection Error',
                            text: 'Please check your network or try again.'
                        });
                    } else {
                        alert('Connection error.');
                    }
                    btnApprove.prop('disabled', false);
                    btnReject.prop('disabled', false);
                }
            });
        }

        $('#btnSubjectApprove').on('click', function () {
            confirmAndSubmit('APPROVED');
        });

        $('#btnSubjectReject').on('click', function () {
            // First click: reveal remarks + recommended-subject picker.
            if (!$('#subject_remarks_group').is(':visible')) {
                $('#subject_remarks_group').slideDown('fast');
                $('#subject_recommend_group').slideDown('fast');
                return;
            }
            // Second click: proceed with confirmation and submission.
            confirmAndSubmit('REJECTED');
        });

        // Helper to normalize status via PHP-provided status_normalized field
        function getNormalizedStatus(rowData) {
            var raw = '';
            if (rowData && rowData.status_normalized != null) {
                raw = rowData.status_normalized;
            } else if (rowData && rowData.status != null) {
                raw = rowData.status;
            }
            return String(raw).trim().toUpperCase();
        }

        function applyPendingFilter() {
            currentStatusFilter = 'PENDING';
            table.clearFilter(true);
            table.setFilter(function (rowData) {
                // In Tabulator 5, the filter function receives the row data object
                return getNormalizedStatus(rowData) === 'PENDING';
            });
        }

        function applyDoneFilter() {
            currentStatusFilter = 'DONE';
            table.clearFilter(true);
            table.setFilter(function (rowData) {
                var st = getNormalizedStatus(rowData);
                return st === 'APPROVED' || st === 'REJECTED';
            });
        }

        // Wire up the Pending / Done filter buttons
        $('#filter_status_pending').on('click', function () {
            $('#filter_status_pending').addClass('btn-primary active').removeClass('btn-outline-secondary');
            $('#filter_status_done').addClass('btn-outline-secondary').removeClass('btn-primary active');
            applyPendingFilter();
        });

        $('#filter_status_done').on('click', function () {
            $('#filter_status_done').addClass('btn-primary active').removeClass('btn-outline-secondary');
            $('#filter_status_pending').addClass('btn-outline-secondary').removeClass('btn-primary active');
            applyDoneFilter();
        });

        window.openProspectusModal = function (studentId, studentName) {
            if (!studentId) return;

            $('#prospectusStudentId').text(studentId);
            $('#prospectusStudentName').text(studentName || '');

            // Clear any previous dynamic content
            $('#prospectusBody').empty();

            // Default Units Earned to 0 until data is loaded
            var unitsEarnedSpan = $('#prospectusModal .prospectus-meta .meta-label').filter(function () {
                return $(this).text().trim() === 'Units Earned:';
            }).first().next('span');
            if (unitsEarnedSpan.length) {
                unitsEarnedSpan.text('0');
            }

            // Default Units to be Earned to 0/blank until data is loaded
            var unitsToEarnSpan = $('#prospectusModal .prospectus-meta .meta-label').filter(function () {
                return $(this).text().trim() === 'Units to be Earned:';
            }).first().next('span');
            if (unitsToEarnSpan.length) {
                unitsToEarnSpan.text('0');
            }

            $('#prospectusModal').modal('show');

            // Load real grades/prospectus data from backend
            var apiUrl = baseUrl && baseUrl.endsWith('/') ? baseUrl : (baseUrl + '/');
            apiUrl += 'dean/process/student_prospectus_data.php';

            $.ajax({
                url: apiUrl,
                type: 'POST',
                dataType: 'json',
                data: { student_id: studentId },
                success: function (res) {
                    if (!res || !res.success) {
                        if (window.Swal && typeof window.Swal.fire === 'function') {
                            window.Swal.fire({
                                icon: 'error',
                                title: 'Unable to load grades',
                                text: (res && res.message) || 'No grade data found for this student.'
                            });
                        }
                        return;
                    }

                    var prospectus = res.prospectus || {};

                    // If there is no curriculum/prospectus data, show a friendly message
                    if (!prospectus || Object.keys(prospectus).length === 0) {
                        $('#prospectusBody').html('<div class="p-3 text-center text-muted">No curriculum data found for this student.</div>');
                    } else {
                        var html = '';

                        Object.keys(prospectus).forEach(function (yearKey) {
                            var semesters = prospectus[yearKey] || {};
                            html += '<div class="year-block mb-4">';
                            html += '<div class="year-heading">' + escapeHtml(yearKey) + '</div>';
                            html += '<div class="row g-2">';

                            Object.keys(semesters).forEach(function (semKey) {
                                var semData = semesters[semKey] || { subjects: [], total_units: 0 };
                                html += '<div class="col-md-6 mb-3">';
                                html += '<div class="sem-heading">' + escapeHtml(semKey) + '</div>';
                                html += '<table class="prospectus-table">';
                                html += '<thead><tr>' +
                                    '<th>Grade</th>' +
                                    '<th>Code</th>' +
                                    '<th>Course Title</th>' +
                                    '<th>Lec</th>' +
                                    '<th>Lab</th>' +
                                    '<th>Units</th>' +
                                    '<th>Pre-Req</th>' +
                                    '</tr></thead><tbody>';

                                (semData.subjects || []).forEach(function (sub) {
                                    var code = escapeHtml(sub.subject_code || '');
                                    var title = escapeHtml(sub.course_desc || '');
                                    var units = escapeHtml(sub.units || '');
                                    var grade = escapeHtml(sub.converted_grade || '');

                                    html += '<tr>' +
                                        '<td>' + grade + '</td>' +
                                        '<td>' + code + '</td>' +
                                        '<td>' + title + '</td>' +
                                        '<td></td>' +
                                        '<td></td>' +
                                        '<td>' + units + '</td>' +
                                        '<td></td>' +
                                        '</tr>';
                                });

                                html += '<tr class="total-row">' +
                                        '<td colspan="5">&nbsp;</td>' +
                                        '<td>' + (semData.total_units || 0) + '</td>' +
                                        '<td class="text-center">Total Units</td>' +
                                        '</tr>';

                                html += '</tbody></table></div>';
                            });

                            html += '</div></div>';
                        });

                        $('#prospectusBody').html(html);
                    }

                    // Update Units Earned meta value
                    var totalUnits = (typeof res.total_units_earned !== 'undefined')
                        ? res.total_units_earned
                        : 0;

                    var unitsSpan = $('#prospectusModal .prospectus-meta .meta-label').filter(function () {
                        return $(this).text().trim() === 'Units Earned:';
                    }).first().next('span');
                    if (unitsSpan.length) {
                        unitsSpan.text(totalUnits);
                    }

                    // Update Units to be Earned meta value (total curriculum units)
                    var unitsToEarn = (typeof res.units_to_be_earned !== 'undefined' && res.units_to_be_earned !== null)
                        ? res.units_to_be_earned
                        : '';

                    var unitsToEarnSpan2 = $('#prospectusModal .prospectus-meta .meta-label').filter(function () {
                        return $(this).text().trim() === 'Units to be Earned:';
                    }).first().next('span');
                    if (unitsToEarnSpan2.length) {
                        unitsToEarnSpan2.text(unitsToEarn);
                    }
                },
                error: function () {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Connection Error',
                            text: 'Unable to load prospectus data. Please try again later.'
                        });
                    }
                }
            });
        };

    });

})(jQuery);