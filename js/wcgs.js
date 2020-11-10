jQuery(function($) {

    // const { GoogleSpreadsheet } = require('google-spreadsheet');

    const work_div = $("#wcgs_working");

    $("#wcgs-sync-btn").on('click', function(e) {

        e.preventDefault();

        work_div.html('Please wait ...');

        const sheet_name = $("#sheet_name").val();
        const data = { 'action': 'wcgs_sync_data', 'sheet': sheet_name };
        $.post(ajaxurl, data, function(response) {

            work_div.html('');
            const { status, message, raw } = response;
            if (status === 'error') {
                const error_div = $("<div/>").addClass('error updated notice').appendTo(work_div);
                const message_div = $("<div/>").html(message).appendTo(error_div);
            }
            else {
                const error_div = $("<div/>").addClass('info updated notice').appendTo(work_div);
                const message_div = $("<div/>").html(message).appendTo(error_div);
            }

            // document.getElementById("wcgs_working").textContent = JSON.stringify(raw, undefined, 2);
        }, 'json')

    });
});
