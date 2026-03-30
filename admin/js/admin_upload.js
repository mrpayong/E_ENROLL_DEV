document.addEventListener('DOMContentLoaded', function () {
    if (!window.adminUploadsConfig || !Array.isArray(window.adminUploadsConfig.tableData)) {
        return;
    }

    const tableElement = document.getElementById('admin-uploads-table');
    if (!tableElement || typeof Tabulator === 'undefined') {
        return;
    }

    const tableData = window.adminUploadsConfig.tableData;

    new Tabulator(tableElement, {
        data: tableData,
        layout: 'fitDataStretch',
        height: '700px',
        pagination: 'local',
        paginationSize: 25,
        paginationSizeSelector: [25, 50, 100],
        placeholder: 'No admin uploads found.',
        headerHozAlign: 'center',
        columns: [
            {
                title: 'File Name',
                field: 'original_filename',
                headerHozAlign: 'left',
                hozAlign: 'left',
                minWidth: 250,
                headerFilter: 'input',
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    const name = cell.getValue() || data.filename || '';
                    const filePath = data.file_path || '';

                    // escape using Tabulator's built-in helper if available
                    const esc = Tabulator.prototype && Tabulator.prototype.sanitizeHTML
                        ? Tabulator.prototype.sanitizeHTML
                        : function (v) { return String(v); };

                    return (
                        '<div class="d-flex align-items-center">' +
                        '  <i class="bi bi-file-earmark-text file-icon text-primary me-2"></i>' +
                        '  <div>' +
                        '    <div class="fw-semibold">' + esc(name) + '</div>' +
                        '    <div class="text-muted small">' + esc(filePath) + '</div>' +
                        '  </div>' +
                        '</div>'
                    );
                },
            },
            {
                title: 'Size',
                field: 'file_size_human',
                hozAlign: 'left',
                width: 120,
                headerFilter: 'input',
            },
            {
                title: 'Uploaded',
                field: 'upload_date',
                hozAlign: 'left',
                minWidth: 150,
                headerFilter: 'input',
            },
            {
                title: 'Downloads',
                field: 'download_count',
                hozAlign: 'center',
                width: 110,
                headerFilter: 'input',
            },
            {
                title: 'Actions',
                hozAlign: 'center',
                width: 120,
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    const url = data.download_url || '#';
                    const disabled = !data.download_url;

                    const esc = Tabulator.prototype && Tabulator.prototype.sanitizeHTML
                        ? Tabulator.prototype.sanitizeHTML
                        : function (v) { return String(v); };

                    if (disabled) {
                        return (
                            '<button type="button" class="btn btn-sm btn-outline-secondary" disabled>' +
                            '  <i class="fas fa-download"></i>' +
                            '</button>'
                        );
                    }

                    return (
                        '<a href="' + esc(url) + '" ' +
                        '   class="btn btn-sm btn-outline-primary" ' +
                        '   title="Download" target="_blank">' +
                        '   <i class="fas fa-download"></i>' +
                        '</a>'
                    );
                },
                headerSort: false,
            },
        ],
    });
});
