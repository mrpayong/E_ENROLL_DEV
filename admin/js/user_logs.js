document.addEventListener('DOMContentLoaded', function () {
    if (!window.userLogsConfig || !Array.isArray(window.userLogsConfig.tableData)) {
        return;
    }

    const tableElement = document.getElementById('user-log-table');
    if (!tableElement || typeof Tabulator === 'undefined') {
        return;
    }

    const tableData = window.userLogsConfig.tableData;

    const table = new Tabulator(tableElement, {
        data: tableData,
        layout: 'fitDataStretch',
        height: '700px',
        pagination: 'local',
        paginationSize: 25,
        paginationSizeSelector: [25, 50, 100],
        placeholder: 'No user logs found.',
        headerHozAlign: 'center',
        initialSort: [{ column: 'login_date', dir: 'desc' }],
        columns: [
            {
                title: 'Login Date',
                field: 'login_date',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                minWidth: 170,
                formatter: function (cell) {
                    const v = cell.getValue();
                    return v && window.moment ? window.moment(v).format('MMM DD, YYYY hh:mm A') : (v || '');
                },
            },
            {
                title: 'Logout Date',
                field: 'logout_date',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                minWidth: 170,
                formatter: function (cell) {
                    const v = cell.getValue();
                    return v && window.moment ? window.moment(v).format('MMM DD, YYYY hh:mm A') : (v || '');
                },
            },
            {
                title: 'Complete Name',
                field: 'name',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                minWidth: 160,
            },
            {
                title: 'IP Address',
                field: 'ip_address',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                minWidth: 140,
            },
            {
                title: 'Device',
                field: 'device',
                headerFilter: 'input',
                headerFilterFunc: 'like',
                formatter: 'textarea',
                minWidth: 200,
            },
        ],
    });

    function safeFileSuffix() {
        if (typeof window.getFormattedTime === 'function') {
            return window.getFormattedTime();
        }
        const d = new Date();
        return (
            d.getFullYear().toString() +
            ('0' + (d.getMonth() + 1)).slice(-2) +
            ('0' + d.getDate()).slice(-2) + '_' +
            ('0' + d.getHours()).slice(-2) +
            ('0' + d.getMinutes()).slice(-2) +
            ('0' + d.getSeconds()).slice(-2)
        );
    }

    const csvBtn = document.getElementById('download-csv');
    const jsonBtn = document.getElementById('download-json');
    const xlsxBtn = document.getElementById('download-xlsx');
    const printBtn = document.getElementById('print-table');

    if (csvBtn) {
        csvBtn.addEventListener('click', function () {
            table.download('csv', 'user_log_' + safeFileSuffix() + '.csv', { bom: true });
        });
    }

    if (jsonBtn) {
        jsonBtn.addEventListener('click', function () {
            table.download('json', 'user_log_' + safeFileSuffix() + '.json');
        });
    }

    if (xlsxBtn) {
        xlsxBtn.addEventListener('click', function () {
            table.download('xlsx', 'user_log_' + safeFileSuffix() + '.xlsx');
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', function () {
            table.print(false, true);
        });
    }
});
