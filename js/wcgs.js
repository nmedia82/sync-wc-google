/* global jQuery ajaxurl wcgs_vars */

const work_div = jQuery("#wcgs_working");

jQuery(function($) {

    // const { GoogleSpreadsheet } = require('google-spreadsheet');

    
    $(document).on('click', '#wcgs-disconnect', function(e) {
        e.preventDefault();
        const a = confirm('Are you sure to disconnect current connection?');
        if( !a ) return;
        
        const data = { 'action': `wcgs_disconnect` };
        $.post(ajaxurl, data, function(response) {
            window.location.reload();
        });
    })

    // Hide sync-back by default
    $(".wcgs-sync-back-btn").hide();

    $("#wcgs-sync-btn").on('click', function(e) {

        e.preventDefault();

        work_div.html('Please wait ...');
        
        $("#wcgs_progressbar").css('visibility','hidden');

        const sheet_name = $("#sheet_name").val();
        /**
         * 1- get total products via Google API
         * */
        const data = { 'action': `wcgs_get_total_${sheet_name}`, 'sheet': sheet_name };
        $.post(ajaxurl, data, function(response) {

            console.log(response);
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
                    var chunk_count = 0;
                    $("#wcgs_progressbar").css('visibility','visible');
                    $(`#pb-run`).css('width', '10%');
                    // $(`#pb-run`).html('');
                    // for (var c = 0; c < chunks; c++) {

                        wcgs_sync_data_in_chunks(chunk_count, chunks, wcgs_products_sync_callback);
                    // }
                    break;

                default:
                    // code
            }

            // document.getElementById("wcgs_working").textContent = JSON.stringify(raw, undefined, 2);
        }, 'json');

    });
    
    
    $(document).on('click', '.wcgs-sync-btn', function(e){
        e.preventDefault();
        work_div.html('Please wait ...');
        
        const data = {action: 'wcgs_check_service_connect'};
        $.post(ajaxurl, data, function(response) {
            const {data:message, success} = response;
            const div_class = success ? 'info' : 'error';
            work_div.html('');
            $("<div/>").addClass(`${div_class} updated notice`).appendTo(work_div)
            .html(message);
            if(success){
                window.location.reload();
            }
        });
        
    })

});

// chunk syncing callback funciton
function wcgs_products_sync_callback(chunk, chunks, sync_result) {

    var chunk_count = chunk+1;
    const {message, status, updatble_ranges} = sync_result;
    const msg_class = status === 'success' ? 'wcgs-green' : 'wcgs-red';
    var resp_msg = `<span class="${msg_class}">Chunk# ${chunk_count}/${chunks}: ${message}</span>`;
    
    // now updating id, sync, images, image cols after product synced
    jQuery.ajax({
    url: wcgs_vars.gas_url,
    type: "POST",
    data: JSON.stringify(updatble_ranges),
    success: function(data, status, xhr) {
        const {status:s, message} = data;
        if(s === 'success'){
            jQuery(`#pb-run`).append(':: Sheet Updated ::');      
        }else{
            jQuery(`#pb-run`).html(message).css('width', `100%`);
        }
      
    },
    error: function(data, textStatus, errorThrown) {
        alert(errorThrown);
    }
    });
    
    console.log(chunk_count, chunks)
    
    var run = (chunk_count / chunks) * 100;
    jQuery(`#pb-run`).css('width', `${run}%`);
    jQuery(`#pb-run`).html(resp_msg);
    jQuery("<div/>").html(resp_msg).appendTo(work_div);
    
    if( chunk_count < chunks ) {
        wcgs_sync_data_in_chunks(chunk_count, chunks, wcgs_products_sync_callback);
    } else {
        jQuery(`#pb-run`).html('SYNC OPERATION COMPLETED SUCCESSFULLY !!');
        jQuery(`#pb-run`).css('background-color', '#6ea820');
    }
}

// start syncing data into chunks
function wcgs_sync_data_in_chunks(chunk, chunks, callback) {

    const sheet_name = jQuery("#sheet_name").val();
    const data = { 'action': `wcgs_sync_chunk_${sheet_name}`, 'sheet': sheet_name, 'chunk': chunk };
    jQuery.post(ajaxurl, data, function(response) {
        callback(chunk, chunks, response);
    }, 'json');

}


function wcgs_post_to_GAS() {
    
    jQuery.ajax({
    url: wcgs_vars.gas_url,
    type: "POST",
    data: JSON.stringify({"hello":"man"}),
    success: function(data, status, xhr) {
      console.log("success");
      console.log(JSON.parse(data.postdata));
    }
    });
}
