(function ($) {

    var cfg = window.enrollmentPageConfig || {};
    var allSections = cfg.sections || [];
    var studentYearLevel = cfg.studentYearLevel || null;
    var studentAcademicStatus = cfg.studentAcademicStatus || '';
    var baseProcessUrl = cfg.baseProcessUrl || cfg.baseUrl || '';
    var isTermOpen = (typeof cfg.isTermOpen === 'boolean') ? cfg.isTermOpen : true;
    if (baseProcessUrl && baseProcessUrl.slice(-1) !== '/') {
        baseProcessUrl += '/';
    }
    var processUrl = baseProcessUrl + 'student/process/unified_enrollment_process.php';

    var cartSubjects = [];
    var currentSubjects = [];
    var backlogSubjects = [];
    var backlogSubjectsMode = 'backlog'; // 'backlog' | 'advanced' | 'none'
    var requiredUnits = null; // from prospectus (server-side)
    var requiredBacklogSubjectCodes = []; // from server meta
    var irregularPhase = 1; // 1 = block subjects view, 2 = backlog selection view
    var allowAdvancedFutureSubjects = false; // special-case flag from server meta
    // When true, the next call to updateCart() will NOT show a
    // cart-level schedule conflict alert even if conflicts exist.
    // This is used for initial auto-fill on page load so the
    // student is not greeted with a warning dialog immediately
    // after refreshing the page.
    var suppressNextCartConflictAlert = false;

    // Map class_id -> section metadata (including program_id) based on
    // the server-provided sections list. Used to know which program a
    // section belongs to when filtering dropdowns.
    var sectionByClassId = {};
    (allSections || []).forEach(function (sec) {
        var cid = parseInt(sec.class_id, 10);
        if (!cid) return;
        sectionByClassId[cid] = sec;
    });

    function isRegularStudent() {
        return (studentAcademicStatus || '').toUpperCase() === 'REGULAR';
    }

    function isReadOnlyTerm() {
        return !isTermOpen;
    }

    function showReadOnlyAlert() {
        var msg = 'Enrollment is closed for this school year and semester.\n\n' +
                  'You can review your information for this term, but new enrollment changes are not allowed.';

        if (typeof swal === 'function') {
            swal({
                title: 'Enrollment Closed',
                text: msg,
                icon: 'info'
            });
        } else {
            alert(msg);
        }
    }

    /* ===============================
       GENERIC HELPERS
    =============================== */

    function escapeHtml(str) {
        if (str === undefined || str === null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ===============================
       TIME UTILITIES
    =============================== */

    function toMinutes(t) {
        var parts = t.split(':');
        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    function toHHMM(minutes) {
        var h = Math.floor(minutes / 60);
        var m = minutes % 60;
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }

    function getHourlyRowsBetween(start, end) {
        var startMin = toMinutes(start);
        var endMin = toMinutes(end);

        var rows = [];

        // Snap DOWN to nearest hour
        var current = Math.floor(startMin / 60) * 60;

        while (current < endMin) {
            rows.push(toHHMM(current));
            current += 60;
        }

        return rows;
    }

    function normalizeTimeString(str) {
        if (!str) return '';
        var parts = str.split(':');
        if (parts.length < 2) return '';
        return String(parts[0]).padStart(2, '0') + ':' + String(parts[1]).padStart(2, '0');
    }

    // Convert "HH:MM" (24h) to "h:MM AM/PM" for display
    function formatTime12(t) {
        if (!t) return '';
        var parts = t.split(':');
        if (parts.length < 2) return t;
        var h = parseInt(parts[0], 10);
        var m = parts[1];
        if (isNaN(h)) return t;
        var suffix = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        if (h === 0) h = 12;
        return h + ':' + m + ' ' + suffix;
    }

    /* ===============================
       SCHEDULE PARSERS
    =============================== */

    function parseScheduleToSlots(raw) {
        var slots = [];
        if (!raw) return slots;

        var entries = [];

        try {
            var parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                entries = parsed;
            } else if (typeof parsed === 'string') {
                entries = [parsed];
            }
        } catch (e) {
            entries = [raw];
        }

        entries.forEach(function (entry) {
            if (typeof entry !== 'string') return;

            var parts = entry.split('::');
            if (parts.length < 2) return;

            var dayName = parts[0].trim().toLowerCase();
            var timeStr = parts[1].trim();
            var roomName = (parts[2] || '').trim();

            var dayShortMap = {
                sunday: 'Sun',
                monday: 'Mon',
                tuesday: 'Tue',
                wednesday: 'Wed',
                thursday: 'Thu',
                friday: 'Fri',
                saturday: 'Sat'
            };

            var dayShort = dayShortMap[dayName];
            if (!dayShort) return;

            var rangeParts = timeStr.split('-');
            if (rangeParts.length < 2) return;

            var start = normalizeTimeString(rangeParts[0].trim());
            var end = normalizeTimeString(rangeParts[1].trim());

            if (!start || !end) return;

            slots.push({
                day: dayShort,
                room: roomName,
                start: start,
                end: end,
                rows: getHourlyRowsBetween(start, end)
            });
        });

        return slots;
    }

    /* ===============================
       SUBJECTS & CART RENDERING
    =============================== */

    function formatScheduleShort(raw) {
        if (!raw) return '';

        var entries = [];
        try {
            var parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                entries = parsed;
            } else if (typeof parsed === 'string') {
                entries = [parsed];
            }
        } catch (e) {
            entries = [raw];
        }

        var parts = [];
        entries.forEach(function (entry) {
            if (typeof entry !== 'string') return;
            var p = entry.split('::');
            if (p.length < 2) return;
            var day = p[0].trim();
            var time = p[1].trim();
            if (day && time) {
                var rangeParts = time.split('-');
                if (rangeParts.length === 2) {
                    var start = normalizeTimeString(rangeParts[0].trim());
                    var end = normalizeTimeString(rangeParts[1].trim());
                    if (start && end) {
                        parts.push(day + ' ' + formatTime12(start) + ' - ' + formatTime12(end));
                    } else {
                        parts.push(day + ' ' + time);
                    }
                } else {
                    parts.push(day + ' ' + time);
                }
            }
        });

        return parts.join(', ');
    }

    function formatRoomShort(raw) {
        if (!raw) return '';
        try {
            var obj = JSON.parse(raw);
            var keys = Object.keys(obj || {});
            if (!keys.length) return '';
            var key = keys[0];
            return key.split('_')[0] || '';
        } catch (e) {
            return raw;
        }
    }

    // Determine section candidates and the best-matching section for
    // the current cart.
    //
    // Rules for IRREGULAR students:
    // - Work only with non-backlog subjects currently in the cart.
    // - A section is a valid OPTION only if it can offer **all** of
    //   those non-backlog subjects (based on currentSubjects).
    // - If multiple sections can offer all subjects, list all of them
    //   and let the student choose; the "best" is just the first by id.
    // - If no section offers 100% of the subjects, fall back to
    //   sections with the highest coverage count (max number of
    //   cart subjects they can offer).
    function computeSuggestedSectionInfo() {
        // Collect unique non-backlog subject codes from the cart.
        var blockCodes = [];
        var blockMap = {};
        (cartSubjects || []).forEach(function (s) {
            if (!s) return;
            if (s.is_backlog || 0) return;
            var code = String(s.subject_code || '').trim();
            if (!code || blockMap[code]) return;
            blockMap[code] = true;
            blockCodes.push(code);
        });

        // If there are no non-backlog subjects, we cannot suggest
        // any concrete section yet.
        if (!blockCodes.length) {
            return {
                bestId: null,
                bestName: 'Not yet determined',
                candidates: {}
            };
        }

        // Build a map of which non-backlog subjects are offered by
        // each section, based on the full currentSubjects list.
        var offerings = {}; // offerings[classId][subject_code] = true
        (currentSubjects || []).forEach(function (s) {
            if (!s) return;
            if (s.is_backlog || 0) return;
            var cid = parseInt(s.class_id || 0, 10);
            if (!cid) return;
            var code = String(s.subject_code || '').trim();
            if (!code) return;
            if (!offerings[cid]) {
                offerings[cid] = {};
            }
            offerings[cid][code] = true;
        });

        var fullSections = [];     // sections that can offer ALL cart subjects
        var coverageCounts = {};   // how many cart subjects each section can offer

        Object.keys(offerings).forEach(function (key) {
            var cid = parseInt(key, 10);
            var codesForSection = offerings[key] || {};
            var coverage = 0;
            blockCodes.forEach(function (code) {
                if (codesForSection[code]) {
                    coverage++;
                }
            });
            if (coverage > 0) {
                coverageCounts[cid] = coverage;
            }
            if (coverage === blockCodes.length) {
                fullSections.push(cid);
            }
        });

        var candidateIds = [];
        if (fullSections.length) {
            // Preferred case: only sections that can offer all
            // non-backlog subjects in the cart.
            candidateIds = fullSections.slice();
        } else {
            // Fallback: no section can offer all subjects. In this
            // edge case, keep only those with maximum coverage.
            var max = 0;
            Object.keys(coverageCounts).forEach(function (key) {
                var cnt = coverageCounts[key];
                if (cnt > max) max = cnt;
            });
            if (max > 0) {
                Object.keys(coverageCounts).forEach(function (key) {
                    if (coverageCounts[key] === max) {
                        candidateIds.push(parseInt(key, 10));
                    }
                });
            }
        }

        if (!candidateIds.length) {
            return {
                bestId: null,
                bestName: 'Not yet determined',
                candidates: {}
            };
        }

        // Build display names for candidate sections.
        var candidates = {};
        candidateIds.forEach(function (cid) {
            var sec = sectionByClassId[cid] || {};
            var nm = String(sec.class_name || '').trim();

            // Fallback: if section metadata is missing, try to get
            // the class_name from any subject that belongs to it.
            if (!nm && currentSubjects && currentSubjects.length) {
                for (var i = 0; i < currentSubjects.length; i++) {
                    var s = currentSubjects[i];
                    if (!s) continue;
                    if (parseInt(s.class_id || 0, 10) === cid && s.class_name) {
                        nm = String(s.class_name).trim();
                        break;
                    }
                }
            }

            candidates[cid] = nm || ('Class ' + cid);
        });

        // Pick a "best" section as the smallest id among candidates.
        var bestId = null;
        candidateIds.forEach(function (cid) {
            if (bestId === null || cid < bestId) {
                bestId = cid;
            }
        });

        var bestName = candidates[bestId] || 'Not yet determined';

        return {
            bestId: bestId,
            bestName: bestName,
            candidates: candidates
        };
    }

    // Detect schedule conflicts on the client side using the
    // normalized slots produced by parseScheduleToSlots. This is a
    // convenience check only; the server also enforces conflicts.
    function findScheduleConflicts(subjects) {
        var byDay = {}; // day => [{ startMin, endMin, label }]

        (subjects || []).forEach(function (s) {
            if (!s || !s.schedule) return;

            var label = String(s.subject_code || '').trim();
            if (s.subject_title) {
                label += (label ? ' - ' : '') + String(s.subject_title);
            }
            if (!label) {
                label = 'Subject';
            }

            var slots = parseScheduleToSlots(s.schedule);
            slots.forEach(function (slot) {
                if (!slot || !slot.day || !slot.start || !slot.end) return;

                var day = slot.day; // e.g. 'Mon'
                var startMin = toMinutes(slot.start);
                var endMin = toMinutes(slot.end);
                if (!isFinite(startMin) || !isFinite(endMin) || endMin <= startMin) return;

                if (!byDay[day]) byDay[day] = [];
                byDay[day].push({
                    startMin: startMin,
                    endMin: endMin,
                    label: label
                });
            });
        });

        var conflicts = [];

        Object.keys(byDay).forEach(function (day) {
            var list = byDay[day];
            if (!list || list.length < 2) return;

            list.sort(function (a, b) { return a.startMin - b.startMin; });

            for (var i = 1; i < list.length; i++) {
                var prev = list[i - 1];
                var curr = list[i];
                if (curr.startMin < prev.endMin) {
                    conflicts.push({
                        day: day,
                        first: prev.label,
                        second: curr.label
                    });
                    // We can break after the first conflict per day
                    // to keep the message concise.
                    break;
                }
            }
        });

        return conflicts;
    }

    // Print helpers for enrolled schedule card
    window.printEnrolledSchedule = function () {
        window.print();
    };

    window.printEnrolledSchedulePdf = function () {
        var url = (baseProcessUrl || '/') + 'student/process/print_enrolled_schedule_pdf.php';
        window.open(url, '_blank');
    };

    // Render a simple schedule preview in the weekly grid for
    // REGULAR students only. Irregular students still rely on the
    // backend for final schedule generation, so we keep their
    // preview empty to avoid confusion.
    function updateSchedulePreview(subjects) {
        var $card = $('#schedule_preview_card');
        var $tbody = $('#schedule_table_body');
        if (!$card.length || !$tbody.length) return;

        // Clear all cells first
        $tbody.find('td[data-day]').each(function () {
            $(this).removeClass('schedule-has-class');
            $(this).find('.schedule-block').remove();
            $(this).html('');
        });

        // Only show preview for regular students.
        if (!isRegularStudent()) {
            return;
        }

        (subjects || []).forEach(function (s) {
            if (!s || !s.schedule) return;
            var label = String(s.subject_code || '').trim();
            if (s.subject_title) {
                label += (label ? ' - ' : '') + String(s.subject_title);
            }
            if (!label) {
                return;
            }

            var slots = parseScheduleToSlots(s.schedule);
            slots.forEach(function (slot) {
                var day = slot.day;
                var rows = slot.rows || [];
                if (!rows.length) return;

                rows.forEach(function (rowTime) {
                    var $row = $tbody.find('tr[data-time="' + rowTime + '"]');
                    if (!$row.length) return;
                    var $cell = $row.find('td[data-day="' + day + '"]');
                    if (!$cell.length) return;
                    $cell.addClass('schedule-has-class');
                });

                // Place a single schedule-block in the first time
                // row cell and stretch it vertically across the
                // entire block span so the label appears centered
                // inside the full blue box, without overflowing
                // beyond the last row.
                var firstTime = rows[0];
                var lastTime = rows[rows.length - 1];
                var $firstRow = $tbody.find('tr[data-time="' + firstTime + '"]');
                var $lastRow = $tbody.find('tr[data-time="' + lastTime + '"]');
                if ($firstRow.length && $lastRow.length) {
                    var $firstCell = $firstRow.find('td[data-day="' + day + '"]');
                    var $lastCell = $lastRow.find('td[data-day="' + day + '"]');
                    if ($firstCell.length && $lastCell.length) {
                        var topPx = $firstCell.offset().top;
                        var bottomPx = $lastCell.offset().top + $lastCell.outerHeight();
                        var blockHeight = bottomPx - topPx;
                        if (blockHeight > 0) {
                            var blockHtml = '<div class="schedule-block" style="top:0;height:' + blockHeight + 'px;">' +
                                '<b>' + escapeHtml(String(s.subject_code || '')) + '</b>' +
                                (s.subject_title ? '<span>' + escapeHtml(String(s.subject_title)) + '</span>' : '') +
                                '</div>';
                            $firstCell.append(blockHtml);
                        }
                    }
                }
            });
        });
    }

    /* ===============================
       CART
    =============================== */
    // subject_code, keeping the first occurrence. Used to avoid
    // showing the same subject multiple times for irregular
    // students when it is offered in several sections.
    function dedupeBySubjectCode(list) {
        if (!Array.isArray(list)) return [];
        var seen = {};
        var out = [];
        list.forEach(function (s) {
            if (!s) return;
            var code = String(s.subject_code || '').trim();
            if (!code) return;
            if (seen[code]) return;
            seen[code] = true;
            out.push(s);
        });
        return out;
    }

    function applySubjectsHeaderFilters(list) {
        var codeFilter = ($('#filter_subject_code').val() || '').toString().toLowerCase().trim();
        var titleFilter = ($('#filter_subject_title').val() || '').toString().toLowerCase().trim();
        var sectionFilter = ($('#filter_section').val() || '').toString().toLowerCase().trim();
        var unitsFilter = ($('#filter_units').val() || '').toString().toLowerCase().trim();
        var scheduleFilter = ($('#filter_schedule').val() || '').toString().toLowerCase().trim();

        if (!codeFilter && !titleFilter && !sectionFilter && !unitsFilter && !scheduleFilter) {
            return list;
        }

        return (list || []).filter(function (s) {
            if (!s) return false;

            if (codeFilter) {
                var code = (s.subject_code || '').toString().toLowerCase();
                if (code.indexOf(codeFilter) === -1) return false;
            }

            if (titleFilter) {
                var title = (s.subject_title || '').toString().toLowerCase();
                if (title.indexOf(titleFilter) === -1) return false;
            }

            if (sectionFilter) {
                var section = (s.class_name || '').toString().toLowerCase();
                if (section.indexOf(sectionFilter) === -1) return false;
            }

            if (unitsFilter) {
                var units = (s.unit != null ? String(s.unit) : '').toLowerCase();
                if (units.indexOf(unitsFilter) === -1) return false;
            }

            if (scheduleFilter) {
                var schedText = (formatScheduleShort(s.schedule) || '').toString().toLowerCase();
                if (schedText.indexOf(scheduleFilter) === -1) return false;
            }

            return true;
        });
    }

    function renderSubjectsTable(subjects) {
        var $tbody = $('#subjects_tbody');
        $tbody.empty();

        if (!subjects || !subjects.length) {
            $tbody.append(
                '<tr><td colspan="6" class="text-center text-muted">No subjects available for the selected filters.</td></tr>'
            );
            $('#available_subjects_count').text('0');
            return;
        }

        var isIrregular = !isRegularStudent();

        // For irregular students, the main table should list only
        // regular (current-term) subjects; backlogs are rendered in
        // a separate Backlog Subjects card.
        var toRender = (subjects || []).filter(function (s) {
            if (!s) return false;
            if (isIrregular && (s.is_backlog || 0)) return false;
            return true;
        });

        // Apply column filters from the header inputs (Tabulator-style)
        toRender = applySubjectsHeaderFilters(toRender);

        if (!toRender.length) {
            $tbody.append(
                '<tr><td colspan="6" class="text-center text-muted">No subjects available for the selected filters.</td></tr>'
            );
            $('#available_subjects_count').text('0');
            return;
        }

        toRender.forEach(function (s) {
            var scheduleText = formatScheduleShort(s.schedule);
            var roomText = formatRoomShort(s.room);

            // Always display the schedule in this column for both
            // regular and irregular students.
            var columnText = scheduleText;

            var isBacklog = !!(s.is_backlog || 0);
            var titleCell = escapeHtml(s.subject_title);
            if (!isIrregular && isBacklog) {
                titleCell += ' <small class="text-warning">(Backlog)</small>';
            }

            var sectionCell = escapeHtml(s.class_name || '');

            var rowClasses = [];
            if (!isIrregular && isBacklog) {
                rowClasses.push('backlog-row');
            }
            var classAttr = rowClasses.length ? ' class="' + rowClasses.join(' ') + '"' : '';
            var actionCellHtml = '<td></td>';

            // For IRREGULAR students, show an explicit Add/Remove
            // button in the Action column so they manually build
            // their cart. Regular students still rely on auto-fill.
            if (isIrregular) {
                var inCart = false;
                (cartSubjects || []).some(function (cs) {
                    if (parseInt(cs.teacher_class_id, 10) === parseInt(s.teacher_class_id, 10)) {
                        inCart = true;
                        return true;
                    }
                    return false;
                });

                var btnLabel = inCart ? 'Remove' : 'Add';
                var btnClass = inCart ? 'btn-outline-danger' : 'btn-outline-primary';

                actionCellHtml = '<td><button type="button" class="btn btn-sm ' + btnClass + ' btn-toggle-cart" data-teacher-class-id="' +
                    escapeHtml(s.teacher_class_id) + '">' + btnLabel + '</button></td>';
            }

            var rowHtml = '' +
                '<tr data-teacher-class-id="' + escapeHtml(s.teacher_class_id) + '"' + classAttr + '>' +
                '<td>' + escapeHtml(s.subject_code) + '</td>' +
                '<td>' + titleCell + '</td>' +
                '<td>' + sectionCell + '</td>' +
                '<td>' + escapeHtml(s.unit) + '</td>' +
                '<td>' + escapeHtml(columnText) + '</td>' +
                actionCellHtml +
                '</tr>';

            $tbody.append(rowHtml);
        });

        $('#available_subjects_count').text(String(toRender.length));
    }

    function renderBacklogSubjectsTable(subjects) {
        var $card = $('#backlog_subjects_card');
        var $container = $('#backlog_container_card');
        var $tbody = $('#backlog_subjects_tbody');

        // Backlog card is only relevant for irregular students.
        if (isRegularStudent()) {
            $card.hide();
            if ($container.length) {
                $container.hide();
            }
            return;
        }

        if ($container.length) {
            $container.show();
        } else {
            $card.show();
        }

        $tbody.empty();

        var list = Array.isArray(subjects) ? subjects.slice() : [];

        // Apply column header filters for backlog table
        var codeFilter = ($('#filter_backlog_code').val() || '').toString().toLowerCase().trim();
        var titleFilter = ($('#filter_backlog_title').val() || '').toString().toLowerCase().trim();
        var sectionFilter = ($('#filter_backlog_section').val() || '').toString().toLowerCase().trim();
        var unitsFilter = ($('#filter_backlog_units').val() || '').toString().toLowerCase().trim();
        var schedFilter = ($('#filter_backlog_schedule').val() || '').toString().toLowerCase().trim();

        if (codeFilter || titleFilter || sectionFilter || unitsFilter || schedFilter) {
            list = list.filter(function (s) {
                if (!s) return false;

                if (codeFilter) {
                    var code = (s.subject_code || '').toString().toLowerCase();
                    if (code.indexOf(codeFilter) === -1) return false;
                }

                if (titleFilter) {
                    var title = (s.subject_title || '').toString().toLowerCase();
                    if (title.indexOf(titleFilter) === -1) return false;
                }

                if (sectionFilter) {
                    var section = (s.class_name || '').toString().toLowerCase();
                    if (section.indexOf(sectionFilter) === -1) return false;
                }

                if (unitsFilter) {
                    var units = (s.unit != null ? String(s.unit) : '').toLowerCase();
                    if (units.indexOf(unitsFilter) === -1) return false;
                }

                if (schedFilter) {
                    var schedText = (formatScheduleShort(s.schedule) || '').toString().toLowerCase();
                    if (schedText.indexOf(schedFilter) === -1) return false;
                }

                return true;
            });
        }

        if (!list.length) {
            $tbody.append('<tr><td colspan="6" class="text-center text-muted">No backlog subjects available for this term.</td></tr>');
            return;
        }

        list.forEach(function (s) {
            var scheduleText = formatScheduleShort(s.schedule);
            var titleCell = escapeHtml(s.subject_title || '');
            var unitsText = escapeHtml(s.unit);
            var columnText = scheduleText;

            var sectionCell = escapeHtml(s.class_name || '');

            var isBacklog = !!(s.is_backlog || 0);
            if (isBacklog) {
                titleCell += ' <small class="text-danger">(Backlog)</small>';
            }

            var inCart = false;
            (cartSubjects || []).some(function (cs) {
                if (parseInt(cs.teacher_class_id, 10) === parseInt(s.teacher_class_id, 10)) {
                    inCart = true;
                    return true;
                }
                return false;
            });

            var btnLabel = inCart ? 'Remove' : 'Add';
            var btnClass = inCart ? 'btn-outline-danger' : 'btn-outline-primary';

            var rowHtml = '' +
                '<tr data-teacher-class-id="' + escapeHtml(s.teacher_class_id) + '">' +
                '<td>' + escapeHtml(s.subject_code) + '</td>' +
                '<td>' + titleCell + '</td>' +
                '<td>' + sectionCell + '</td>' +
                '<td>' + unitsText + '</td>' +
                '<td>' + escapeHtml(columnText) + '</td>' +
                '<td><button type="button" class="btn btn-sm ' + btnClass + ' btn-toggle-cart" data-teacher-class-id="' +
                    escapeHtml(s.teacher_class_id) + '">' + btnLabel + '</button></td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });

        // Note: badge counters for Backlog / Higher-Year sections are
        // maintained by the higher-level refreshBacklogHigherYearUI
        // helper, not here.
    }

    // Split the combined backlog/higher-year offerings into
    // separate buckets so we can render them in distinct tabs.
    function getBacklogAndHigherYearBuckets() {
        var combined = getCombinedBacklogAndHigherYearSubjects();
        var backlogOnly = [];
        var higherOnly = [];

        (combined || []).forEach(function (s) {
            if (!s) return;
            var isBacklog = !!(s.is_backlog || 0);
            var isHigherYear = !!(s.is_higher_year || 0);

            if (isHigherYear) {
                higherOnly.push(s);
            } else if (isBacklog) {
                backlogOnly.push(s);
            }
        });

        return {
            combined: combined,
            backlog: backlogOnly,
            higher: higherOnly
        };
    }

    // Render Backlog and Higher-Year subjects into separate tables,
    // shown at the same time for irregular students.
    function renderHigherYearSubjectsTable(subjects) {
        var $card = $('#higher_year_subjects_card');
        var $container = $('#higher_year_container_card');
        var $tbody = $('#higher_year_subjects_tbody');

        if (isRegularStudent()) {
            $card.hide();
            if ($container.length) {
                $container.hide();
            }
            return;
        }

        if ($container.length) {
            $container.show();
        } else {
            $card.show();
        }

        $tbody.empty();

        var list = Array.isArray(subjects) ? subjects.slice() : [];

        // Apply column header filters for higher-year table
        var codeFilter = ($('#filter_higher_code').val() || '').toString().toLowerCase().trim();
        var titleFilter = ($('#filter_higher_title').val() || '').toString().toLowerCase().trim();
        var sectionFilter = ($('#filter_higher_section').val() || '').toString().toLowerCase().trim();
        var unitsFilter = ($('#filter_higher_units').val() || '').toString().toLowerCase().trim();
        var schedFilter = ($('#filter_higher_schedule').val() || '').toString().toLowerCase().trim();

        if (codeFilter || titleFilter || sectionFilter || unitsFilter || schedFilter) {
            list = list.filter(function (s) {
                if (!s) return false;

                if (codeFilter) {
                    var code = (s.subject_code || '').toString().toLowerCase();
                    if (code.indexOf(codeFilter) === -1) return false;
                }

                if (titleFilter) {
                    var title = (s.subject_title || '').toString().toLowerCase();
                    if (title.indexOf(titleFilter) === -1) return false;
                }

                if (sectionFilter) {
                    var section = (s.class_name || '').toString().toLowerCase();
                    if (section.indexOf(sectionFilter) === -1) return false;
                }

                if (unitsFilter) {
                    var units = (s.unit != null ? String(s.unit) : '').toLowerCase();
                    if (units.indexOf(unitsFilter) === -1) return false;
                }

                if (schedFilter) {
                    var schedText = (formatScheduleShort(s.schedule) || '').toString().toLowerCase();
                    if (schedText.indexOf(schedFilter) === -1) return false;
                }

                return true;
            });
        }

        if (!list.length) {
            $tbody.append('<tr><td colspan="6" class="text-center text-muted">No higher-year subjects available for this term.</td></tr>');
            return;
        }

        list.forEach(function (s) {
            var scheduleText = formatScheduleShort(s.schedule);
            var titleCell = escapeHtml(s.subject_title || '');
            var unitsText = escapeHtml(s.unit);
            var columnText = scheduleText;

            var sectionCell = escapeHtml(s.class_name || '');

            if (s.is_higher_year || 0) {
                titleCell += ' <small class="text-info">(Higher-Year)</small>';
            }

            var inCart = false;
            (cartSubjects || []).some(function (cs) {
                if (parseInt(cs.teacher_class_id, 10) === parseInt(s.teacher_class_id, 10)) {
                    inCart = true;
                    return true;
                }
                return false;
            });

            var btnLabel = inCart ? 'Remove' : 'Add';
            var btnClass = inCart ? 'btn-outline-danger' : 'btn-outline-primary';

            var rowHtml = '' +
                '<tr data-teacher-class-id="' + escapeHtml(s.teacher_class_id) + '">' +
                '<td>' + escapeHtml(s.subject_code) + '</td>' +
                '<td>' + titleCell + '</td>' +
                '<td>' + sectionCell + '</td>' +
                '<td>' + unitsText + '</td>' +
                '<td>' + escapeHtml(columnText) + '</td>' +
                '<td><button type="button" class="btn btn-sm ' + btnClass + ' btn-toggle-cart" data-teacher-class-id="' +
                    escapeHtml(s.teacher_class_id) + '">' + btnLabel + '</button></td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });
    }

    // Update the Backlog and Higher-Year sections based on the
    // currently loaded offerings; both cards are visible together.
    function refreshBacklogHigherYearUI() {
        if (isRegularStudent()) {
            $('#backlog_container_card').hide();
            $('#higher_year_container_card').hide();
            $('#backlog_subjects_tbody').empty();
            $('#higher_year_subjects_tbody').empty();
            return;
        }

        var buckets = getBacklogAndHigherYearBuckets();
        var backlogList = buckets.backlog || [];
        var higherList = buckets.higher || [];

        $('#backlog_subjects_count').text(String(backlogList.length));
        $('#higher_year_subjects_count').text(String(higherList.length));

        renderBacklogSubjectsTable(backlogList);
        renderHigherYearSubjectsTable(higherList);
    }

    function renderCart() {
        var $empty = $('#cart_empty_state');
        var $list = $('#cart_list');
        var $items = $('#cart_items');
        var $count = $('#cart_count');
        var $totalUnits = $('#cart_total_units');
        var $requiredUnits = $('#required_units_label');
        var $sectionRow = $('#cart_section_row');
        var $sectionLabel = $('#cart_section_label');

        $items.empty();

        if (!cartSubjects || !cartSubjects.length) {
            $empty.show();
            $list.hide();
            $count.text('0');
            $totalUnits.text('0.0');
            if ($requiredUnits.length) {
                if (requiredUnits === null) {
                    $requiredUnits.text('N/A');
                } else {
                    $requiredUnits.text(parseFloat(requiredUnits).toFixed(1));
                }
            }
            if ($sectionRow.length) {
                // When the cart is empty, reset the section label.
                if (!isRegularStudent()) {
                    $sectionRow.show();
                    if ($sectionLabel.length) {
                        $sectionLabel.text('Not yet determined');
                        $sectionLabel.removeAttr('data-class-id');
                    }
                } else {
                    $sectionRow.hide();
                }
            }
            return;
        }

        var total = 0;

        cartSubjects.forEach(function (s, idx) {
            var units = parseFloat(s.unit || 0) || 0;
            total += units;

            var titleLine = escapeHtml(s.subject_code || '');
            if (s.subject_title) {
                titleLine += ' - ' + escapeHtml(s.subject_title);
            }

            var isBacklog = !!(s.is_backlog || 0);
            var isHigherYear = !!(s.is_higher_year || 0);

            // Prefer Higher-Year label when both flags are set so
            // advanced subjects are not shown as simple backlogs in
            // the cart (e.g., IT 301, CC 301 for 3rd year).
            if (isHigherYear) {
                titleLine += ' (Higher-Year)';
            } else if (isBacklog) {
                titleLine += ' (Backlog)';
            }

            var schedShort = formatScheduleShort(s.schedule || '');
            if (schedShort.indexOf(',') !== -1) {
                schedShort = schedShort.split(',')[0].trim();
            }

            // Allow removing any subject from the cart via a
            // close button on each card (not just backlogs).
            var removeHtml = '<button type="button" class="cart-remove-btn" data-index="' + idx + '">&#10005;</button>';

            // Show section and schedule in the cart for both
            // regular and irregular students so irregulars can also
            // see which section each subject belongs to.
            var bodyHtml = '<div class="cart-item-body">' +
                '<div class="subject-name">' + escapeHtml(s.class_name || '') + '</div>' +
                (schedShort ? '<div class="cart-item-schedule">' + escapeHtml(schedShort) + '</div>' : '') +
            '</div>';

            var itemHtml = '' +
                '<div class="cart-item" data-index="' + idx + '">' +
                    '<div class="cart-item-header">' +
                        '<div class="cart-item-title">' + titleLine + '</div>' +
                        '<div class="cart-item-units">' + units.toFixed(1) + ' Units</div>' +
                        removeHtml +
                    '</div>' +
                    bodyHtml +
                '</div>';

            $items.append(itemHtml);
        });

        $empty.hide();
        $list.show();
        $count.text(cartSubjects.length);

        var totalFixed = total.toFixed(1);
        $totalUnits
            .removeClass('text-primary text-success text-danger')
            .text(totalFixed);

        if ($requiredUnits.length) {
            if (requiredUnits === null) {
                $requiredUnits.text('N/A');
                $totalUnits.addClass('text-primary');
            } else {
                var req = parseFloat(requiredUnits) || 0;
                $requiredUnits.text(req.toFixed(1));

                // For both regular and irregular students, highlight
                // overload/underload in red and an exact match in
                // green so the student can see immediately when
                // their total units do not align with the required
                // load.
                var eps = 0.0001;
                if (Math.abs(total - req) < eps) {
                    $totalUnits.addClass('text-success');
                } else if (total > req + eps) {
                    $totalUnits.addClass('text-danger');
                } else {
                    // total < required
                    $totalUnits.addClass('text-danger');
                }
            }
        }

        // Update section label in the cart based on the current
        // cart contents. For regular students we hide this row and
        // rely on the main Section dropdown above. For irregular
        // students we show a read-only label reflecting the section
        // that best matches their chosen non-backlog subjects.
        if ($sectionRow.length && $sectionLabel.length) {
            if (isRegularStudent()) {
                $sectionRow.hide();
            } else {
                $sectionRow.show();

                var info = computeSuggestedSectionInfo();
                if (info && info.bestId !== null && info.bestName) {
                    $sectionLabel.text(info.bestName);
                    $sectionLabel.attr('data-class-id', String(info.bestId));
                } else {
                    $sectionLabel.text('Not yet determined');
                    $sectionLabel.removeAttr('data-class-id');
                }
            }
        }
    }

    /* ===============================
       CART
    =============================== */

    function updateCart(subjects) {
        cartSubjects = subjects || [];

        var conflicts = findScheduleConflicts(cartSubjects);

        updateSchedulePreview(cartSubjects);
        renderCart();

        // Keep the Available Subjects table's Add/Remove buttons in
        // sync with the current cart contents for irregular students.
        if (!isRegularStudent()) {
            renderSubjectsTable(currentSubjects);
            // For irregular students, refresh the Backlog and
            // Higher-Year tabs so their Add/Remove buttons and
            // badges stay in sync with the cart contents.
            refreshBacklogHigherYearUI();
        }

        if (conflicts.length) {
            if (suppressNextCartConflictAlert) {
                // Skip showing the alert once (used for initial
                // auto-fill on page load), then reset the flag.
                suppressNextCartConflictAlert = false;
                return;
            }
            var msg = 'Schedule conflict detected in your cart.\n\n';
            conflicts.forEach(function (c) {
                if (!c) return;
                // c comes from findScheduleConflicts and has the
                // shape { day, first, second }.
                if (c.day && c.first && c.second) {
                    msg += c.day + ' - ' + c.first + ' vs ' + c.second + '\n';
                }
            });

            if (typeof swal === 'function') {
                swal({
                    title: 'Schedule Conflict',
                    text: msg,
                    icon: 'warning'
                });
            } else {
                alert(msg);
            }
        }
    }

    /* ===============================
       DATA LOADING & ACTIONS
    =============================== */

    function loadBacklogSubjects(callback) {
        backlogSubjects = [];
        backlogSubjectsMode = 'none';

        $.ajax({
            url: processUrl,
            method: 'GET',
            dataType: 'json',
            data: {
                action: 'fetch_backlog_subjects'
            }
        }).done(function (res) {
            backlogSubjects = [];
            backlogSubjectsMode = 'none';

            var mode = 'backlog';
            if (res && res.meta && typeof res.meta.mode === 'string') {
                mode = res.meta.mode;
            }
            backlogSubjectsMode = mode;

            if (res && res.success && Array.isArray(res.subjects)) {
                backlogSubjects = res.subjects.map(function (s) {
                    // When the server is in advanced mode, the
                    // returned subjects are higher-year offerings
                    // (not classic backlogs). Mark them with a
                    // dedicated flag so the UI and cart can label
                    // them correctly.
                    if (mode === 'advanced') {
                        s.is_backlog = s.is_backlog || 0;
                        s.is_higher_year = 1;
                    } else {
                        s.is_backlog = 1;
                        s.is_higher_year = s.is_higher_year || 0;
                    }
                    return s;
                });
            }

            if (typeof callback === 'function') callback(res);
        }).fail(function () {
            backlogSubjects = [];
            backlogSubjectsMode = 'none';
            if (typeof callback === 'function') callback(null);
        });
    }

    // Helper: get combined list of real backlog subjects (from the
    // main currentSubjects list) plus any higher-year offerings that
    // were loaded via fetch_backlog_subjects(). This is what we show
    // in the Backlog / Higher-Year tab.
    function getCombinedBacklogAndHigherYearSubjects() {
        var combined = [];

        // 1) Real backlog subjects from the main fetch_subjects
        // response.
        (currentSubjects || []).forEach(function (s) {
            if (!s) return;
            if (!(s.is_backlog || 0)) return;
            combined.push(s);
        });

        // 2) Extra backlog / higher-year offerings from the
        // dedicated fetch_backlog_subjects endpoint.
        if (backlogSubjects && backlogSubjects.length && backlogSubjectsMode !== 'none') {
            backlogSubjects.forEach(function (s) {
                if (!s) return;
                var tid = parseInt(s.teacher_class_id || 0, 10);
                if (!tid) {
                    combined.push(s);
                    return;
                }
                var exists = combined.some(function (x) {
                    return parseInt(x.teacher_class_id || 0, 10) === tid;
                });
                if (!exists) {
                    combined.push(s);
                }
            });
        }

        return combined;
    }

    function applyBacklogOptionsFilter() {
        if (!backlogSubjects || !backlogSubjects.length) {
            return;
        }

        var allowedPrograms = {};
        var allowedCombos = {};

        (backlogSubjects || []).forEach(function (s) {
            var pid = parseInt(s.program_id || 0, 10);
            var cid = parseInt(s.class_id || 0, 10);
            if (!pid || !cid) return;
            allowedPrograms[pid] = true;
            allowedCombos[pid + '-' + cid] = true;
        });

        // Restrict Program options to only those that offer backlog
        // subjects this term.
        $('#program_id option').each(function () {
            var val = parseInt($(this).val(), 10);
            if (!val) return;
            if (!allowedPrograms[val]) {
                // Hide programs that do not offer any backlog
                // subjects this term to avoid a very long list.
                $(this)
                    .prop('disabled', true)
                    .attr('data-has-backlog', '0')
                    .hide();
            } else {
                $(this)
                    .prop('disabled', false)
                    .attr('data-has-backlog', '1')
                    .show();
            }
        });

        var currentProgram = parseInt($('#program_id').val() || 0, 10);
        if (!allowedPrograms[currentProgram]) {
            var firstAllowedProgram = null;
            $('#program_id option').each(function () {
                var val = parseInt($(this).val(), 10);
                if (!val) return;
                if (allowedPrograms[val]) {
                    firstAllowedProgram = val;
                    return false;
                }
            });
            if (firstAllowedProgram !== null) {
                $('#program_id').val(String(firstAllowedProgram));
                currentProgram = firstAllowedProgram;
            }
        }

        // Restrict Section options to only those (program, class)
        // combinations that actually offer backlog subjects.
        $('#class_id option').each(function () {
            var cid = parseInt($(this).val(), 10);
            if (!cid) return;
            var sec = sectionByClassId[cid];
            var pid = sec ? parseInt(sec.program_id || 0, 10) : 0;
            var key = pid + '-' + cid;
            if (!allowedCombos[key]) {
                // Hide sections that do not actually offer any
                // backlog subjects to keep the dropdown compact.
                $(this)
                    .prop('disabled', true)
                    .attr('data-has-backlog', '0')
                    .hide();
            } else {
                $(this)
                    .prop('disabled', false)
                    .attr('data-has-backlog', '1')
                    .show();
            }
        });

        var currentClass = parseInt($('#class_id').val() || 0, 10);
        var currentKey = currentProgram + '-' + currentClass;
        if (!allowedCombos[currentKey]) {
            var firstAllowedClass = null;
            $('#class_id option').each(function () {
                var cid = parseInt($(this).val(), 10);
                if (!cid) return;
                var sec = sectionByClassId[cid];
                var pid = sec ? parseInt(sec.program_id || 0, 10) : 0;
                var key = pid + '-' + cid;
                if (pid === currentProgram && allowedCombos[key]) {
                    firstAllowedClass = cid;
                    return false;
                }
            });

            if (firstAllowedClass === null) {
                $('#class_id option').each(function () {
                    var cid = parseInt($(this).val(), 10);
                    if (!cid) return;
                    var sec = sectionByClassId[cid];
                    var pid = sec ? parseInt(sec.program_id || 0, 10) : 0;
                    var key = pid + '-' + cid;
                    if (allowedCombos[key]) {
                        firstAllowedClass = cid;
                        return false;
                    }
                });
            }

            if (firstAllowedClass !== null) {
                $('#class_id').val(String(firstAllowedClass));
            }
        }
    }

    function renderBacklogSubjectsForStep2() {
        var selectedProgramId = parseInt($('#program_id').val() || 0, 10);
        var selectedClassId = parseInt($('#class_id').val() || 0, 10);
        var backlogList;

        if (backlogSubjectsMode === 'advanced') {
            // Advanced mode: show all higher-year, no-pre-req subjects
            // that match the current Program/Section filters.
            backlogList = (backlogSubjects || []).filter(function (s) {
                var pid = parseInt(s.program_id || 0, 10);
                var cid = parseInt(s.class_id || 0, 10);

                if (selectedProgramId && pid && pid !== selectedProgramId) return false;
                if (selectedClassId && cid && cid !== selectedClassId) return false;
                return true;
            });
        } else {
            // Normal backlog mode: only subjects whose codes are in
            // the required backlog list for this term.
            var codesMap = {};
            (requiredBacklogSubjectCodes || []).forEach(function (c) {
                c = String(c || '').trim();
                if (!c) return;
                codesMap[c] = true;
            });

            backlogList = (backlogSubjects || []).filter(function (s) {
                var code = String(s.subject_code || '').trim();
                if (!codesMap[code]) return false;

                var pid = parseInt(s.program_id || 0, 10);
                var cid = parseInt(s.class_id || 0, 10);

                if (selectedProgramId && pid && pid !== selectedProgramId) return false;
                if (selectedClassId && cid && cid !== selectedClassId) return false;
                return true;
            });
        }

        currentSubjects = backlogList;
        setBacklogSubjectsMode(backlogSubjectsMode);
        renderSubjectsTable(currentSubjects);
    }

    function loadSubjects() {
        var $tbody = $('#subjects_tbody');

        $tbody.html('<tr><td colspan="5" class="text-center text-muted">Loading subjects...</td></tr>');

        var programId = $('#program_id').val() || '';
        var classId = $('#class_id').val() || '';

        // Whenever we reload subjects for a new program/section, reset
        // irregular flow back to step 1 (block subjects view).
        setBlockSubjectsMode();

        // SUBJECT-FIRST FLOW FOR IRREGULAR STUDENTS
        // ----------------------------------------
        // For irregular students, we no longer filter by a specific
        // section (class_id) when fetching subjects. Instead, we load
        // all current-term offerings for the student's program &
        // year level, and let the backend choose the best-fitting
        // section at save time based on the selected subjects.
        var ajaxData = {
            action: 'fetch_subjects',
            program_id: programId
        };
        if (isRegularStudent()) {
            ajaxData.class_id = classId;
        }

        $.ajax({
            url: processUrl,
            method: 'GET',
            dataType: 'json',
            data: ajaxData
        }).done(function (res) {
            if (!res || !res.success) {
                $tbody.html('<tr><td colspan="5" class="text-center text-danger">' +
                    escapeHtml((res && res.message) || 'Failed to load subjects.') +
                    '</td></tr>');
                currentSubjects = [];
                if ($('#autoFill').is(':checked')) {
                    updateCart([]);
                }
                return;
            }

            currentSubjects = res.subjects || [];

            // Ensure regular current-term subjects are listed first,
            // then backlog subjects, in case the server did not
            // already sort them (defensive sorting).
            currentSubjects.sort(function (a, b) {
                var ab = a.is_backlog ? 1 : 0;
                var bb = b.is_backlog ? 1 : 0;
                if (ab !== bb) return ab - bb;
                var ac = String(a.subject_code || '');
                var bc = String(b.subject_code || '');
                return ac.localeCompare(bc);
            });
            // Capture required units meta, if provided by the server
            if (res.meta && typeof res.meta.required_units !== 'undefined' && res.meta.required_units !== null) {
                var ru = parseFloat(res.meta.required_units);
                requiredUnits = isNaN(ru) ? null : ru;
            } else {
                requiredUnits = null;
            }

            // Capture backlog subject codes required for this term (if any)
            if (res.meta && Array.isArray(res.meta.backlog_subject_codes)) {
                var seenCodes = {};
                requiredBacklogSubjectCodes = res.meta.backlog_subject_codes.filter(function (code) {
                    if (!code) return false;
                    code = String(code).trim();
                    if (!code) return false;
                    if (seenCodes[code]) return false;
                    seenCodes[code] = true;
                    return true;
                });
            } else {
                requiredBacklogSubjectCodes = [];
            }

            // Special-case flag: the server can indicate that the
            // student has backlog subjects that are not offered this
            // term but is allowed to take higher-year subjects with no
            // pre-requisites.
            allowAdvancedFutureSubjects = !!(res.meta && res.meta.has_unoffered_backlogs);

            // Render main Available Subjects list. For irregular
            // students this includes only regular subjects; backlog
            // subjects are shown in a separate card.
            var visibleSubjects = currentSubjects;
            renderSubjectsTable(visibleSubjects);
            if (!isRegularStudent()) {
                // Initialize Backlog / Higher-Year sections based on
                // the currently available backlog offerings.
                refreshBacklogHigherYearUI();
            } else {
                $('#backlog_subjects_card').hide();
            }

            // If the server reports that the student has backlog
            // subjects that are not offered this term, load the
            // special higher-year, no-pre-req offerings and show
            // them in the Backlog/Higher-Year card.
            if (!isRegularStudent() && allowAdvancedFutureSubjects) {
                loadBacklogSubjects(function () {
                    if (!backlogSubjects || !backlogSubjects.length) {
                        return;
                    }

                    // After loading higher-year offerings, refresh
                    // both Backlog and Higher-Year sections.
                    refreshBacklogHigherYearUI();
                });
            }

            // Auto-fill applies only for regular students. Irregular
            // students always build their cart manually via the
            // Action column. When we auto-fill right after loading
            // subjects (e.g., on first page load or when changing
            // sections), we suppress the first cart-level conflict
            // alert so the student is not greeted with a warning
            // immediately on refresh. Conflicts are still checked
            // when they add/remove subjects or submit.
            if (isRegularStudent() && $('#autoFill').is(':checked')) {
                suppressNextCartConflictAlert = true;
                updateCart(currentSubjects);
            }
        }).fail(function () {
            $tbody.html('<tr><td colspan="5" class="text-center text-danger">Unable to load subjects. Please try again.</td></tr>');
            currentSubjects = [];
            if ($('#autoFill').is(':checked')) {
                updateCart([]);
            }
            $('#backlog_subjects_card').hide();
        });
    }

    // Determine which required backlog subject codes (if any) are still
    // missing from the current cart. This is used on the client side to
    // guide irregular students before submitting to the server.
    function getMissingBacklogCodesInCart() {
        if (!requiredBacklogSubjectCodes || !requiredBacklogSubjectCodes.length) {
            return [];
        }

        // Only remind students about backlog subjects that are
        // actually OFFERED this term. When the system is in
        // advanced/higher-year mode (backlogSubjectsMode ===
        // 'advanced'), the dedicated backlogSubjects list contains
        // higher-year, no-pre-req offerings, not true backlog
        // classes, so we should not nag them about codes that are
        // unavailable this term.

        var offeredBacklog = {};

        // Backlog subjects coming from the main fetch_subjects
        // response.
        (currentSubjects || []).forEach(function (s) {
            if (!s) return;
            if (!(s.is_backlog || 0)) return;
            // Skip subjects that are explicitly tagged as
            // higher-year; they are handled separately.
            if (s.is_higher_year || 0) return;
            var code = String(s.subject_code || '').trim();
            if (!code) return;
            offeredBacklog[code] = true;
        });

        // Additional backlog offerings loaded via
        // fetch_backlog_subjects, but only when we are in the normal
        // backlog mode (not advanced/higher-year).
        if (backlogSubjects && backlogSubjects.length && backlogSubjectsMode === 'backlog') {
            (backlogSubjects || []).forEach(function (s) {
                if (!s) return;
                if (!(s.is_backlog || 0)) return;
                if (s.is_higher_year || 0) return;
                var code = String(s.subject_code || '').trim();
                if (!code) return;
                offeredBacklog[code] = true;
            });
        }

        var selected = {};
        (cartSubjects || []).forEach(function (s) {
            if (!s) return;
            if (!(s.is_backlog || 0)) return;
            var code = String(s.subject_code || '').trim();
            if (!code) return;
            selected[code] = true;
        });

        var missing = [];
        requiredBacklogSubjectCodes.forEach(function (code) {
            code = String(code || '').trim();
            if (!code) return;
            // Only consider backlog codes that are actually
            // offered this term. If a required backlog subject is
            // not in offeredBacklog (e.g., IT 200 when only higher-
            // year IT 301/CC 301 are offered), we skip it so that
            // irregular students are not reminded about subjects
            // that they cannot enroll in this term.
            if (!offeredBacklog[code]) {
                return;
            }

            if (!selected[code]) {
                missing.push(code);
            }
        });

        return missing;
    }

    // Helper to find a friendly label for a backlog subject code based on
    // whatever backlog offerings we have loaded in memory.
    function describeBacklogCode(code) {
        var display = '';
        var target = String(code || '').trim();
        if (!target) return '';

        (currentSubjects || []).some(function (s) {
            if (!s) return false;
            var sc = String(s.subject_code || '').trim();
            if (sc === target && (s.is_backlog || 0)) {
                display = sc;
                if (s.subject_title) {
                    display += ' - ' + s.subject_title;
                }
                return true;
            }
            return false;
        });

        if (!display && backlogSubjects && backlogSubjects.length) {
            (backlogSubjects || []).some(function (s) {
                if (!s) return false;
                var sc = String(s.subject_code || '').trim();
                if (sc === target) {
                    display = sc;
                    if (s.subject_title) {
                        display += ' - ' + s.subject_title;
                    }
                    return true;
                }
                return false;
            });
        }

        if (!display) {
            display = target;
        }
        return display;
    }

    function scrollToSubjectsTable() {
        var $target = $('#subjects_tbody');
        if (!$target.length) return;
        var top = $target.offset().top - 80;
        $('html, body').animate({ scrollTop: top }, 500);
    }

    // Switch Available Subjects card to "block" (normal) mode.
    function setBlockSubjectsMode() {
        irregularPhase = 1;
        $('#available_subjects_title').text('Available Subjects for Your Program');
        $('#available_subjects_hint').text('Choose your current-term subjects. The system will assign the best section automatically based on your chosen subjects.');
        // Show auto-fill only for regular students; irregular
        // students always build their cart manually.
        if (isRegularStudent()) {
            $('#autoFillRow').show();
        } else {
            $('#autoFillRow').hide();
            $('#autoFill').prop('checked', false);
        }
        // Program value is fixed to the student's program.
        $('#program_id').prop('disabled', true);

        // Column label: always show Schedule for the fourth column
        // in the Available Subjects table.
        $('#subjects_prereq_header').text('Schedule');

        // In Step 1, only regular students can choose a section.
        if (isRegularStudent()) {
            $('#section_select_group').show();
            $('#class_id').prop('disabled', false);
            $('#subjects_table').addClass('no-action');
        } else {
            $('#section_select_group').hide();
            $('#class_id').prop('disabled', true);
            $('#subjects_table').removeClass('no-action');
        }

        // Hide Schedule Preview for irregular students while they are
        // in Step 1 choosing subjects. Regular students keep it.
        if (!isRegularStudent()) {
            $('#schedule_preview_card').hide();
        } else {
            $('#schedule_preview_card').show();
        }
    }

    // Switch Available Subjects card to "backlog" or "advanced" mode
    // for irregular students in step 2.
    function setBacklogSubjectsMode(mode) {
        irregularPhase = 2;
        if (mode === 'advanced') {
            $('#available_subjects_title').text('Higher-Year Subjects Offered This Term');
            $('#available_subjects_hint').text('Step 2: You may add higher-year subject(s) without pre-requisites from the list, then click ENROLL again. The system will still assign the best section automatically.');
        } else {
            $('#available_subjects_title').text('Backlog Subjects Offered This Term');
            $('#available_subjects_hint').text('Step 2: Choose your backlog subject(s) from the list, then click ENROLL again. The system will still assign the best section automatically.');
        }
        // In Step 2, the Program dropdown remains fixed; for
        // irregular students we hide the Section dropdown entirely
        // from the Available Subjects card.
        $('#autoFillRow').hide();
        $('#autoFill').prop('checked', false);
        $('#program_id').prop('disabled', true);
        if (isRegularStudent()) {
            $('#section_select_group').show();
            $('#class_id').prop('disabled', false);
        } else {
            $('#section_select_group').hide();
            $('#class_id').prop('disabled', true);
        }
        // Show Action column in Step 2
        $('#subjects_table').removeClass('no-action');

        // In backlog/advanced mode (used by irregulars), keep the
        // Schedule Preview hidden as the final section is still
        // determined automatically by the backend.
        if (!isRegularStudent()) {
            $('#schedule_preview_card').hide();
        }
    }

    // Show a guidance dialog for irregular students when they still
    // have backlog subjects available, but allow them to proceed
    // with only regular subjects if they choose.
    function showBacklogGuidance(missingCodes) {
        if (!missingCodes || !missingCodes.length) return;

        var listText = missingCodes.map(function (code) {
            return '- ' + describeBacklogCode(code);
        }).join('\n');

        var msg = "📝 Note: We noticed some backlog subjects are available this term:\n\n" +
          listText + "\n\n" +
          "While you can proceed with your current selection, we recommend including these to stay on track with your curriculum. Would you like to continue anyway?";

        if (typeof swal === 'function') {
            swal({
                title: 'Backlog Subjects Reminder',
                text: msg,
                icon: 'info',
                buttons: ['Review Subjects', 'Proceed Anyway']
            }).then(function (proceedAnyway) {
                if (proceedAnyway) {
                    submitEnrollment();
                } else {
                    scrollToSubjectsTable();
                }
            });
        } else {
            var proceed = confirm(msg + '\n\nPress OK to proceed anyway, or Cancel to review your subjects.');
            if (proceed) {
                submitEnrollment();
            } else {
                scrollToSubjectsTable();
            }
        }
    }

    function submitEnrollment() {
        if (!cartSubjects || !cartSubjects.length) {
            if (typeof swal === 'function') {
                swal({
                    title: 'No Subjects in Cart',
                    text: 'Please add at least one subject to your cart before proceeding.',
                    icon: 'warning'
                });
            } else {
                alert('Please add at least one subject to your cart before proceeding.');
            }
            return;
        }

        // Prevent submitting when there is any schedule conflict in
        // the current cart. The server performs the same validation
        // for safety, but doing it here gives immediate feedback.
        var conflicts = findScheduleConflicts(cartSubjects);
        if (conflicts && conflicts.length) {
            var first = conflicts[0];
            var msg = 'Your selected subjects have a schedule conflict';
            if (first && first.day && first.first && first.second) {
                msg += '\n\nDay: ' + first.day + '\nBetween: ' + first.first + ' and ' + first.second;
            }
            msg += '\n\nPlease adjust your subjects before proceeding.';

            if (typeof swal === 'function') {
                swal({
                    title: 'Schedule Conflict',
                    text: msg,
                    icon: 'warning'
                });
            } else {
                alert(msg);
            }
            return;
        }

        // For REGULAR students, we still require an explicit
        // Program/Section combination using the main Section
        // dropdown. For IRREGULAR students, the section is
        // determined automatically from the subjects in the cart
        // (majority/coverage of non-backlog subjects) using the same
        // logic as computeSuggestedSectionInfo().
        var programId = $('#program_id').val();
        var classId;
        if (isRegularStudent()) {
            classId = $('#class_id').val();
        } else {
            var infoForSubmit = computeSuggestedSectionInfo();
            classId = (infoForSubmit && infoForSubmit.bestId !== null)
                ? String(infoForSubmit.bestId)
                : '';
        }

        if (!programId || (isRegularStudent() && !classId)) {
            if (typeof swal === 'function') {
                swal({
                    title: 'Program and Section Required',
                    text: isRegularStudent()
                        ? 'Please select a Program and Section before proceeding.'
                        : 'Please ensure your Program is selected before proceeding.',
                    icon: 'warning'
                });
            } else {
                alert(isRegularStudent()
                    ? 'Please select a Program and Section before proceeding.'
                    : 'Please ensure your Program is selected before proceeding.');
            }
            return;
        }

        // For irregular students, make sure we are not submitting an
        // enrollment that only contains backlog subjects. At least
        // one block subject (non-backlog) must be present.
        if (!isRegularStudent()) {
            var hasBlockSubject = false;
            (cartSubjects || []).some(function (s) {
                if (!s) return false;
                if (!(s.is_backlog || 0)) {
                    hasBlockSubject = true;
                    return true;
                }
                return false;
            });

            if (!hasBlockSubject) {
                if (typeof swal === 'function') {
                    swal({
                        title: 'Block Subjects Missing',
                        text: 'Your cart currently contains only backlog subjects. Please include your block section subjects before submitting.',
                        icon: 'warning'
                    });
                } else {
                    alert('Your cart currently contains only backlog subjects. Please include your block section subjects before submitting.');
                }
                return;
            }
        }

        var totalUnits = 0;
        (cartSubjects || []).forEach(function (s) {
            var u = parseFloat(s.unit || 0) || 0;
            totalUnits += u;
        });

        // Client-side check against prospectus required units (if available)
        // Enforced only for regular students; irregulars are allowed to
        // exceed the required units due to backlog subjects.
        if (requiredUnits !== null && isRegularStudent()) {
            var req = parseFloat(requiredUnits) || 0;
            if (Math.abs(totalUnits - req) >= 0.0001) {
                var msg = 'Your selected total units (' + totalUnits.toFixed(1) + ') do not match the required total (' +
                          req.toFixed(1) + ') defined in your prospectus for this term.\n\nPlease adjust your subjects before proceeding.';
                if (typeof swal === 'function') {
                    swal({
                        title: 'Units Mismatch',
                        text: msg,
                        icon: 'warning'
                    });
                } else {
                    alert(msg);
                }
                return;
            }
        }

        var sectionText;
        if (isRegularStudent()) {
            sectionText = $('#class_id option:selected').text() || '';
        } else {
            var infoForText = computeSuggestedSectionInfo();
            sectionText = (infoForText && infoForText.bestName) ? infoForText.bestName : 'Not yet determined';
        }
        var confirmText;
        if (isRegularStudent()) {
            confirmText =
                'You are about to submit ' + cartSubjects.length + ' subject(s) ' +
                '(' + totalUnits.toFixed(1) + ' unit(s)) for section ' + sectionText + '.\n\n' +
                'Are you sure you want to proceed?';
        } else {
            confirmText =
                'You are about to submit ' + cartSubjects.length + ' subject(s) ' +
                '(' + totalUnits.toFixed(1) + ' unit(s)).\n\n' +
                'Section (based on most of your chosen subjects): ' + sectionText + '.\n\n' +
                'The system will save this as your class section for this enrollment.\n\n' +
                'Are you sure you want to proceed?';
        }

        if (typeof swal === 'function') {
            swal({
                title: 'Confirm Enrollment',
                text: confirmText,
                icon: 'warning',
                buttons: {
                    cancel: { text: 'Review Subjects', visible: true, className: 'btn btn-secondary' },
                    confirm: { text: 'Yes, Submit', className: 'btn btn-success' }
                },
                dangerMode: true
            }).then(function (willSubmit) {
                if (!willSubmit) return;

                var $btn = $('.btn-proceed');
                $btn.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: processUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'save_enrollment',
                        program_id: programId,
                        class_id: classId,
                        academic_status: studentAcademicStatus,
                        subjects: JSON.stringify(cartSubjects)
                    }
                }).done(function (res) {
                    var isSuccess = !!(res && res.success);
                    var title = isSuccess ? 'Request Submitted' : 'Enrollment Failed';
                    var icon = isSuccess ? 'success' : 'error';
                    var text = (res && res.message) || 'Request completed.';

                    // If the backend auto-dropped any current-term
                    // subjects to prioritize backlogs and keep within
                    // the allowed units, surface that information to
                    // the student.
                    if (isSuccess && res && Array.isArray(res.auto_dropped_subject_codes) && res.auto_dropped_subject_codes.length) {
                        text += '\n\nNote: The system automatically removed the following current-term subject(s) to prioritize your backlog subjects and fit the allowed unit load:';
                        res.auto_dropped_subject_codes.forEach(function (code) {
                            if (!code) return;
                            text += '\n- ' + code;
                        });
                    }

                    swal({
                        title: title,
                        text: text,
                        icon: icon,
                        button:false,
                        timer:2000
                    }).then(function () {
                        if (isSuccess && res && (res.mode === 'irregular' || res.mode === 'regular')) {
                            window.location.reload();
                        }
                    });
                }).fail(function (jqXHR) {
                    var msg = 'Unable to save enrollment at this time.';
                    if (jqXHR && jqXHR.responseText) {
                        try {
                            var parsed = JSON.parse(jqXHR.responseText);
                            if (parsed && typeof parsed.message === 'string' && parsed.message.trim() !== '') {
                                msg = parsed.message;
                            }
                        } catch (e) {
                            // ignore JSON parse errors and keep default msg
                        }
                    }

                    swal({
                        title: 'Error',
                        text: msg,
                        icon: 'error'
                    });
                }).always(function () {
                    $btn.prop('disabled', false).text('ENROLL IN THIS CLASS SECTION');
                });
            });
        } else {
            var proceed = confirm('Are you sure you want to submit your enrollment request?');
            if (!proceed) return;

            var $btn = $('.btn-proceed');
            $btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: processUrl,
                method: 'POST',
                dataType: 'json',
                    data: {
                        action: 'save_enrollment',
                        program_id: programId,
                        class_id: classId,
                        academic_status: studentAcademicStatus,
                        subjects: JSON.stringify(cartSubjects)
                    }
            }).done(function (res) {
                var msg = (res && res.message) || 'Request completed.';
                if (res && res.success && Array.isArray(res.auto_dropped_subject_codes) && res.auto_dropped_subject_codes.length) {
                    msg += '\n\nNote: The system automatically removed the following current-term subject(s) to prioritize your backlog subjects and fit the allowed unit load:';
                    res.auto_dropped_subject_codes.forEach(function (code) {
                        if (!code) return;
                        msg += '\n- ' + code;
                    });
                }
                alert(msg);
                if (res && res.success && (res.mode === 'irregular' || res.mode === 'regular')) {
                    window.location.reload();
                }
            }).fail(function (jqXHR) {
                var msg = 'Unable to save enrollment at this time.';
                if (jqXHR && jqXHR.responseText) {
                    try {
                        var parsed = JSON.parse(jqXHR.responseText);
                        if (parsed && typeof parsed.message === 'string' && parsed.message.trim() !== '') {
                            msg = parsed.message;
                        }
                    } catch (e) {
                        // ignore
                    }
                }
                alert(msg);
            }).always(function () {
                $btn.prop('disabled', false).text('ENROLL IN THIS CLASS SECTION');
            });
        }
    }

    $(document).ready(function () {

        // When the selected term is closed (past/future fiscal year
        // with flag_used = 0), keep this page strictly read-only by
        // hiding the interactive enrollment widgets. Existing
        // enrollment status and schedule cards remain visible.
        if (isReadOnlyTerm()) {
            $('.subjects-container-scroll').hide();
            $('#backlog_container_card').hide();
            $('#higher_year_container_card').hide();
            $('#schedule_preview_card').hide();
            $('.btn-proceed')
                .prop('disabled', true)
                .addClass('disabled')
                .text('Enrollment Closed for This Term');
            return;
        }

        // Highlight the current day column (Mon-Sat) in the schedule preview.
        (function highlightTodayColumn() {
            var $table = $('.schedule-table');
            if (!$table.length) return;

            var dayShortMap = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            var todayIndex = (new Date()).getDay();
            var todayShort = dayShortMap[todayIndex] || '';

            // We only render Mon-Sat in the preview; skip Sunday.
            if (!todayShort || todayShort === 'Sun') return;

            $table.find('th[data-day], td[data-day]').each(function () {
                var d = ($(this).attr('data-day') || '').trim();
                if (d === todayShort) {
                    $(this).addClass('schedule-today');
                }
            });
        })();

        // Initialize subjects tabs: start on Available tab only.
        (function initSubjectsTabs() {
            // Keep Available panel marked active; Backlog and
            // Higher-Year are shown in their own stacked cards, so
            // we no longer use tab clicks here.
            $('#available_subjects_panel').addClass('active');
        })();

        // Initial load of subjects for the currently selected section
        loadSubjects();

        // When Program or Section changes, always reload subjects from
        // the server. For irregular students, the section dropdown is
        // hidden/disabled, so this effectively reacts only to Program
        // changes.
        $('#program_id, #class_id').on('change', function () {
            loadSubjects();
        });

        // Auto-fill cart toggle (Regular students only; checkbox
        // is not rendered for irregular students in the PHP view.)
        $('#autoFill').on('change', function () {
            if (!$(this).is(':checked')) {
                return;
            }

            if (!currentSubjects || !currentSubjects.length) {
                updateCart([]);
                return;
            }

            var subjectsToAdd = currentSubjects.slice();

            // Regular: include all subjects returned for the section.
            updateCart(subjectsToAdd);
        });

        // Column header filters for Available Subjects table
        $(document).on('input', '#filter_subject_code, #filter_subject_title, #filter_section, #filter_units, #filter_schedule', function () {
            // Re-render using currentSubjects so filters are applied
            // on the full dataset on every change.
            renderSubjectsTable(currentSubjects);
        });

        // Column header filters for Backlog and Higher-Year tables
        $(document).on('input', '#filter_backlog_code, #filter_backlog_title, #filter_backlog_section, #filter_backlog_units, #filter_backlog_schedule', function () {
            var buckets = getBacklogAndHigherYearBuckets();
            renderBacklogSubjectsTable(buckets.backlog || []);
        });

        $(document).on('input', '#filter_higher_code, #filter_higher_title, #filter_higher_section, #filter_higher_units, #filter_higher_schedule', function () {
            var buckets = getBacklogAndHigherYearBuckets();
            renderHigherYearSubjectsTable(buckets.higher || []);
        });

        // Enroll in this Section button
        $(document).on('click', '.btn-enroll-section', function () {
            if (!currentSubjects || !currentSubjects.length) {
                if (typeof swal === 'function') {
                    swal({
                        title: 'No Subjects Available',
                        text: 'No subjects available for this section.',
                        icon: 'info'
                    });
                } else {
                    alert('No subjects available for this section.');
                }
                return;
            }

            // REGULAR: keep existing behavior (one-step: block subjects only).
            if (isRegularStudent()) {
                setBlockSubjectsMode();
                updateCart(currentSubjects);
                return;
            }

            // IRREGULAR: step 1 = choose block section subjects only.
            setBlockSubjectsMode();
            var blockOnly = (currentSubjects || []).filter(function (s) {
                return !s.is_backlog;
            });
            if (!blockOnly.length) {
                blockOnly = currentSubjects.slice();
            }
            updateCart(blockOnly);
        });

        function toggleCartByTeacherClassId(tcid) {
            tcid = parseInt(tcid, 10);
            if (!tcid) return;

            var existingIndex = -1;
            (cartSubjects || []).forEach(function (s, idx) {
                if (parseInt(s.teacher_class_id, 10) === tcid) {
                    existingIndex = idx;
                }
            });

            var newCart = (cartSubjects || []).slice();

            if (existingIndex >= 0) {
                // Remove from cart
                newCart.splice(existingIndex, 1);
                updateCart(newCart);
                return;
            }

            var subject = null;
            (currentSubjects || []).some(function (s) {
                if (parseInt(s.teacher_class_id, 10) === tcid) {
                    subject = s;
                    return true;
                }
                return false;
            });

            // If not found in the main currentSubjects list (e.g. a
            // higher-year/backlog subject loaded via the separate
            // backlogSubjects endpoint), look it up there as a
            // fallback so the Backlog/Higher-Year tab's Add button
            // still works.
            if (!subject && backlogSubjects && backlogSubjects.length) {
                (backlogSubjects || []).some(function (s) {
                    if (parseInt(s.teacher_class_id, 10) === tcid) {
                        subject = s;
                        return true;
                    }
                    return false;
                });
            }

            if (!subject) return;
            // Before adding a new subject, warn the user if this
            // will introduce a schedule conflict with subjects that
            // are already in the cart.
            var tentativeCart = newCart.slice();
            tentativeCart.push(subject);

            var conflicts = findScheduleConflicts(tentativeCart);
            if (conflicts && conflicts.length) {
                var first = conflicts[0];
                var msg = 'Adding this subject would create a schedule conflict';
                if (first && first.day && first.first && first.second) {
                    msg += '\n\nDay: ' + first.day + '\nBetween: ' + first.first + ' and ' + first.second;
                }
                msg += '\n\nPlease choose another schedule or adjust your cart.';

                if (typeof swal === 'function') {
                    swal({
                        title: 'Schedule Conflict',
                        text: msg,
                        icon: 'warning'
                    });
                } else {
                    alert(msg);
                }
                return; // do not add the conflicting subject
            }

            newCart.push(subject);
            updateCart(newCart);
        }

        // We no longer toggle cart items by clicking entire rows to
        // avoid accidentally removing required block subjects. Use
        // the explicit Add/Remove buttons instead.

        // Explicit add/remove button for each subject row
        $(document).on('click', '.btn-toggle-cart', function (e) {
            e.stopPropagation();
            var tcid = parseInt($(this).attr('data-teacher-class-id'), 10);
            toggleCartByTeacherClassId(tcid);
        });

        // Proceed to enrollment.
        // REGULAR: submit directly.
        // IRREGULAR: single-step flow with an optional guidance
        // check to ensure required backlog subjects are present.
        $(document).on('click', '.btn-proceed', function () {
            if (isRegularStudent()) {
                submitEnrollment();
                return;
            }

            // IRREGULAR FLOW (no separate Step 2 screen): if there are
            // required backlog subject codes and some of them are not
            // yet in the cart, show a friendly reminder instead of
            // immediately submitting.
            var missing = getMissingBacklogCodesInCart();
            if (missing.length > 0) {
                showBacklogGuidance(missing);
                return;
            }

            submitEnrollment();
        });

        // Remove single subject from cart
        $(document).on('click', '.cart-remove-btn', function (e) {
            e.preventDefault();
            var idx = parseInt($(this).attr('data-index'), 10);
            if (isNaN(idx)) return;
            if (!cartSubjects || !cartSubjects.length) return;

            cartSubjects.splice(idx, 1);
            updateCart(cartSubjects.slice());
        });

    });

})(jQuery);