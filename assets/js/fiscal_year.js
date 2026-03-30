(function ($) {
    'use strict';

    var baseUrl = (window.appConfig && window.appConfig.baseUrl) || '/';
    if (baseUrl.slice(-1) !== '/') {
        baseUrl += '/';
    }
    var endpointUrl = baseUrl + 'backend/fiscal_year.php';

    var optionsLoaded = false;
    var isLoading = false;

    function loadFiscalYearOptions(callback) {
        if (optionsLoaded || isLoading) {
            if (typeof callback === 'function') callback();
            return;
        }
        isLoading = true;

        $.ajax({
            url: endpointUrl,
            method: 'GET',
            dataType: 'json',
            data: { action: 'list' }
        }).done(function (res) {
            var $sel = $('#fiscal_year_select');
            $sel.empty();

            if (!res || !res.success || !Array.isArray(res.items) || !res.items.length) {
                $sel.append('<option value="">No fiscal years found</option>');
                return;
            }

            res.items.forEach(function (item) {
                var opt = $('<option>', {
                    value: item.school_year_id,
                    text: item.label
                });
                if (item.is_current) {
                    opt.attr('selected', 'selected');
                }
                $sel.append(opt);
            });

            optionsLoaded = true;
        }).fail(function () {
            var $sel = $('#fiscal_year_select');
            $sel.empty().append('<option value="">Unable to load fiscal years</option>');
        }).always(function () {
            isLoading = false;
            if (typeof callback === 'function') callback();
        });
    }

    function openFiscalYearModal() {
        loadFiscalYearOptions(function () {
            $('#fiscalYearModal').modal('show');
        });
    }

    // Open modal when sidebar Fiscal Year links are clicked
    $(document).on('click', '#student_fiscal_year_link, #dean_fiscal_year_link', function (e) {
        e.preventDefault();
        openFiscalYearModal();
    });

    // Set button: persist selected fiscal year in session and reload
    $(document).on('click', '#btn_set_fiscal_year', function () {
        var $sel = $('#fiscal_year_select');
        var id = $sel.val();
        if (!id) {
            if (typeof swal === 'function') {
                swal({
                    title: 'Select Fiscal Year',
                    text: 'Please select a fiscal year and semester.',
                    icon: 'info'
                });
            } else {
                alert('Please select a fiscal year and semester.');
            }
            return;
        }

        $.ajax({
            url: endpointUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'set',
                school_year_id: id
            }
        }).done(function (res) {
            if (!res || !res.success) {
                var msg = (res && res.message) || 'Unable to update fiscal year.';
                if (typeof swal === 'function') {
                    swal({ title: 'Error', text: msg, icon: 'error' });
                } else {
                    alert(msg);
                }
                return;
            }

            try {
                var detail = {
                    school_year_id: res.school_year_id,
                    school_year: res.school_year,
                    sem: res.sem,
                    label: res.label
                };
                window.dispatchEvent(new CustomEvent('fiscalYearChanged', { detail: detail }));
            } catch (e) {
                // Older browsers can ignore this.
            }

            // Show success on the current page, then reload.
            if (typeof swal === 'function') {
                swal({
                    title: 'Success',
                    text: 'Fiscal Year has been set.',
                    icon: 'success',
                    button: false,
                    timer:2000
                }).then(function () {
                    $('#fiscalYearModal').modal('hide');
                    window.location.reload();
                });
            } else {
                alert('Fiscal Year has been set.');
                $('#fiscalYearModal').modal('hide');
                window.location.reload();
            }
        }).fail(function () {
            if (typeof swal === 'function') {
                swal({
                    title: 'Error',
                    text: 'Unable to update fiscal year. Please try again.',
                    icon: 'error'
                });
            } else {
                alert('Unable to update fiscal year. Please try again.');
            }
        });
    });

})(jQuery);
