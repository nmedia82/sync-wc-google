jQuery(function($) {

    // const { GoogleSpreadsheet } = require('google-spreadsheet');

    const work_div = $("#wcgs_working");

    $("#wcgs-sync-btn-zz").on('click', function(e) {
        
        e.preventDefault();
        
        const sheet_name = $("#sheet_name").val();
        const data = { 'action': `wcgs_sync_data_${sheet_name}`, 'sheet': sheet_name };
        let params = new URLSearchParams(data).toString();
        
        var xhr = new XMLHttpRequest()
        xhr.open("POST", ajaxurl, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onprogress = function () {
          console.log("PROGRESS:", xhr.responseText)
        }
        xhr.send(params);
    });
    
    $("#wcgs-sync-btn").on('click', function(e) {

        e.preventDefault();

        work_div.html('Please wait ...');

        const sheet_name = $("#sheet_name").val();
        const data = { 'action': `wcgs_sync_data_${sheet_name}`, 'sheet': sheet_name };
        $.post(ajaxurl, data, function(response) {

            work_div.html('');
            const { status, message, raw } = response;
            switch (status) {
                case 'error':
                    const error_div = $("<div/>").addClass('error updated notice').appendTo(work_div);
                    const message_1 = $("<div/>").html(message).appendTo(error_div);
                    break;
                case 'success':
                    const success_div = $("<div/>").addClass('info updated notice').appendTo(work_div);
                    const message_2 = $("<div/>").html(message).appendTo(success_div);
                    break;
                case 'message_response':
                    const message_3 = $("<div/>").html(message).appendTo(work_div);
                    break;
                
                default:
                    // code
            }

            // document.getElementById("wcgs_working").textContent = JSON.stringify(raw, undefined, 2);
        }, 'json')

    });
});
