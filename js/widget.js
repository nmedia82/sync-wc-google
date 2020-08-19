jQuery(function($) {

    // const { GoogleSpreadsheet } = require('google-spreadsheet');

    $("#wcgs-sync-categories").on('click', function(e) {

        e.preventDefault();

        const sheet_name = $("#sheet_name").val();
        const data = { 'action': 'wcgs_sync_categories', 'sheet': sheet_name };
        $.post(ajaxurl, data, function(response) {

            console.log(response);
            document.getElementById("wcgs_log_data").textContent = JSON.stringify(response, undefined, 2);
        }, 'json')

    });
});
