document.addEventListener('DOMContentLoaded', function () {
    if (!window.registrarLogsConfig) {
        return;
    }

    const tableElement = document.getElementById('registrar-log-table');
    if (!tableElement || typeof Tabulator === 'undefined') {
        return;
    }

    const baseUrl = window.registrarLogsConfig.baseUrl || '';
    const summaryElement = document.getElementById('registrar-log-summary');
    const dateRangeInput = document.getElementById('registrar-date-range');
    const clearBtn = document.getElementById('registrar-clear-date');

    function updateSummary(response) {
        if (!summaryElement) {
            return;
        }

        if (!response || !Array.isArray(response.data) || response.data.length === 0) {
            summaryElement.textContent = 'No activity logs found.';
            return;
        }

        const rows = response.data;
        const totalPage = rows.length;
        const totalRecords = typeof response.total_records === 'number' ? response.total_records : totalPage;
        const actionCounts = {};

        rows.forEach(function (row) {
            const action = (row.action || '').trim();
            if (!action) {
                return;
            }
            actionCounts[action] = (actionCounts[action] || 0) + 1;
        });

        const sortedActions = Object.entries(actionCounts).sort(function (a, b) {
            return b[1] - a[1];
        });

        // Clean UI: no summary text shown
        summaryElement.textContent = '';
    }

    const table = new Tabulator(tableElement, {
        ajaxURL: baseUrl + 'backend/users/user_logs_process.php',
        ajaxParams: { table: 'activity_log', role: 'registrar' },
        layout: 'fitDataStretch',
        height: '700px',
        pagination: 'remote',
        paginationSize: 25,
        paginationSizeSelector: [25, 50, 100],
        placeholder: 'No activity logs found.',
        headerHozAlign: 'center',
        ajaxLoader: true,
        ajaxLoaderLoading: 'Loading registrar activity logs...',
        initialSort: [{ column: 'date_log', dir: 'desc' }],
        ajaxResponse: function (url, params, response) {
            updateSummary(response);
            return response;
        },
        columns: [
            {
                title: 'Date & Time',
                field: 'date_log',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                minWidth: 170,
                formatter: function (cell) {
                    const v = cell.getValue();
                    return v && window.moment ? window.moment(v).format('MMM DD, YYYY hh:mm A') : (v || '');
                },
            },
            {
                title: 'Name',
                field: 'full_name',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                minWidth: 160,
            },
            {
                title: 'Action',
                field: 'action',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                formatter: function (cell) {
                    let string = cell.getValue() || '';
                    let result = string;

                    if (string) {
                        const lower = string.toLowerCase();
                        const pos = lower.indexOf('upload_');

                        if (pos !== -1) {
                            const first = string.substring(0, pos);
                            const second = string.substring(pos);
                            const trimmed = second.slice(0, -1).trim();
                            const href = baseUrl + 'upload/logs/' + trimmed + '.txt';

                            result =
                                first +
                                '<a href="' + href + '" target="_blank" download title="DOWNLOAD">' +
                                '<i class="fas fa-download"></i> ' + second +
                                '</a>';
                        }

                        cell.getElement().style.whiteSpace = 'pre-wrap';
                    }

                    return result;
                },
            },
        ],
    });

    function reloadWithDateFilter(range) {
        const params = { table: 'activity_log', role: 'registrar' };
        if (range && range.startDate && range.endDate && window.moment) {
            params.start_date = range.startDate.format('YYYY-MM-DD');
            params.end_date = range.endDate.format('YYYY-MM-DD');
        }
        table.setData(table.getAjaxUrl(), params);
    }

    if (dateRangeInput && window.jQuery && typeof window.jQuery.fn.daterangepicker === 'function') {
        const $range = window.jQuery(dateRangeInput);
        $range.on('apply.daterangepicker', function (ev, picker) {
            reloadWithDateFilter(picker);
        });
        $range.on('cancel.daterangepicker', function () {
            $range.val('');
            reloadWithDateFilter(null);
        });
    }

    if (clearBtn && dateRangeInput) {
        clearBtn.addEventListener('click', function () {
            dateRangeInput.value = '';
            reloadWithDateFilter(null);
        });
    }
});
