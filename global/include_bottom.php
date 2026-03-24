<?php

/** 
==================================================================
 File name   : include_bottom.php
 Version     : 1.0.0
 Begin       : 2026-02-26
 Last Update :
 Author      : 
 Description : include all JS and OTHER SCRIPTS (FOR ADMINS UI).
 =================================================================
 **/
?>
<!-- FONTS AND ICON JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/webfont/webfont.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script>
    WebFont.load({
        google: {
            families: ["Public Sans:300,400,500,600,700"]
        },
        custom: {
            families: [
                "Font Awesome 5 Solid",
                "Font Awesome 5 Regular",
                "Font Awesome 5 Brands",
                "simple-line-icons",
            ],
            urls: ["<?php echo BASE_URL; ?>assets/dashboard/css/fonts.min.css"],
        },
        active: function() {
            sessionStorage.fonts = true;
        },
    });
</script>

<!-- BOOTSRAP JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/bootstrap.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- MAIN JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/jquery-3.7.1.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/main.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/app.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/moment.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/moment-timezone-with-data.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/tabulator.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/bootstrap-datetimepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/selectize.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/jquery.timepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/dropify.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/daterangepicker.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/sweetalert2.js?v=<?php echo FILE_VERSION; ?>"></script>


<!-- DASHBOARD JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/custom.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- [DASHBOARD] CORE JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/core/popper.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- [DASHBOARD] PLUGIN JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/chart.js/chart.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jquery.sparkline/jquery.sparkline.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/chart-circle/circles.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/bootstrap-notify/bootstrap-notify.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jsvectormap/jsvectormap.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jsvectormap/world.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/sweetalert/sweetalert.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- SCRIPTS -->
<script>
    /** for popup */
    var windowObjectReference = null;

    function openRequestedPopup({
        url,
        title,
        w,
        h,
        position = 'center'
    }) {
        if (windowObjectReference == null || windowObjectReference.closed) {

        } else {
            windowObjectReference.close();
            windowObjectReference = null
        };

        // Fixes dual-screen position                             Most browsers      Firefox
        const dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
        const dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;

        const width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
        const height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

        const systemZoom = width / window.screen.availWidth;

        var left = 0;
        var top = 0;
        if (position == "top_left") {
            left = 0;
            top = 0;
        } else if (position == "top_right") {
            left = width;
            top = 0;
        } else {
            left = (width - w) / 2 / systemZoom + dualScreenLeft;
            top = (height - h) / 2 / systemZoom + dualScreenTop;
        }

        windowObjectReference = window.open(url, title,
            `scrollbars=yes,width=${w / systemZoom},height=${h / systemZoom},top=${top},left=${left}`
        )

        if (window.focus) windowObjectReference.focus();
    }

    function alert_notif(message, state = "success") {
        /** state = default, primary, secondary, info, success, warning, danger/error */
        const icons = {
            success: "fas fa-check-circle",
            info: "fas fa-info-circle",
            warning: "fas fa-exclamation-circle",
            danger: "fas fa-times-circle",
        };
        
        if (state == "error") state = "danger";

        var content = {};
        content.title = "";
        content.message = message;
        content.icon = icons[state] ?? "none";
        content.url = "#";
        content.target = "";

        $.notify(content, {
            type: state,
            placement: {
                from: "top",
                align: "right",
            },
            time: 100,
            delay: 0,
        });
    }

    (function() {
        /** date and time */
        var set_server_time = <?php echo "'" . DATE_TIME . "';\r\n"; ?>
        var serverOffset = moment(set_server_time).diff(new Date());
        var clock_id = datetime();

        function datetime() {
            setInterval(function() {
                if (document.getElementById('now')) {
                    var now_server = moment();
                    now_server.add(serverOffset, 'milliseconds');
                    var timeNow = now_server.format('ddd | MMMM DD, YYYY h:mm:ss A');
                    $('#now').html(timeNow);
                    $('#printTime').html('Date Printed : ' + timeNow);
                } else {
                    clearInterval(clock_id);
                }
            }, 1000);
        }
    })();
</script>