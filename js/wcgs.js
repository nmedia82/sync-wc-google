jQuery(function($) {

    // const { GoogleSpreadsheet } = require('google-spreadsheet');

    const work_div = $("#wcgs_working");
    
    // Hide sync-back by default
    $(".wcgs-sync-back-btn").hide();

    $("#wcgs-sync-btn").on('click', function(e) {

        e.preventDefault();

        work_div.html('Please wait ...');

        const sheet_name = $("#sheet_name").val();
        const data = { 'action': `wcgs_sync_data_${sheet_name}`, 'sheet': sheet_name };
        $.post(ajaxurl, data, function(response) {

            // console.log(response);
            work_div.html('');
            const { status, message, raw, chunks } = response;
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
                    
                case 'chunked':
                    const message_4 = $("<div/>").html(message).appendTo(work_div);
                    for(var c=0; c<chunks.chunks; c++){
                        
                        wcgs_sync_data_in_chunks(c, function(chunk, sync_result){
                            console.log(sync_result);
                            $("<div/>").html(`Chunk# ${chunk}: ${sync_result.message}`).appendTo(work_div);
                        });
                    }
                    break;
                
                default:
                    // code
            }

            // document.getElementById("wcgs_working").textContent = JSON.stringify(raw, undefined, 2);
        }, 'json');

    });
    
});

// start syncing data into chunks
function wcgs_sync_data_in_chunks(chunk, callback){
    
    const sheet_name = jQuery("#sheet_name").val();
    const data = { 'action': `wcgs_sync_chunk_${sheet_name}`, 'sheet': sheet_name, 'chunk':chunk };
    jQuery.post(ajaxurl, data, function(response) {
        callback(chunk, response);
    },'json');
    
}
