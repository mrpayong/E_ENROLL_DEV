(function($){
    $(document).ready(function() {
        var cfg = window.deanStudentInfoConfig || {};
        var tableData = Array.isArray(cfg.tableData) ? cfg.tableData : [];

        var table = new Tabulator("#student-info-table", {
            data: tableData,
            layout: "fitDataStretch",
            height: "700px",
            pagination: "local",
            paginationSize: 25,
            paginationSizeSelector: [25, 50, 100],
            placeholder: "No students found for this department.",
            headerHozAlign: "center",
            columns: [
                { title: "Student ID", field: "student_id", headerFilter: "input", width: 140 },
                { title: "Name", field: "fullname", headerFilter: "input", width: 260 },
                { title: "Program", field: "program", headerFilter: "input", width: 260 },
                { title: "Year", field: "year_level", hozAlign: "center", width: 80 },
                {
                    title: "Status",
                    field: "academic_status",
                    width: 120,
                    formatter: function(cell) {
                        var raw = (cell.getValue() || '').toString().trim();
                        var value = raw.toUpperCase();
                        var isIrregular = (value === 'IRREG' || value === 'IRREGULAR');

                        var label = isIrregular ? 'IRREGULAR' : 'REGULAR';
                        var klass = isIrregular ? 'bg-warning text-dark' : 'bg-success';

                        return '<span class="badge student-status-badge ' + klass + '\">' + label + '</span>';
                    }
                },
                { title: "Department", field: "department", headerFilter: "input", width: 260 }
            ]
        });
    });
})(jQuery);
