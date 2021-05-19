jQuery(function($) {

    // const { GoogleSpreadsheet } = require('google-spreadsheet');
    $("#google-rest").on('click', function(e) {
       e.preventDefault();
       
    //   const range = 'a2:o97';
    //   const url = `https://script.google.com/macros/s/AKfycbxaTmmKvLLKcTYpNmzbXX8NNKoy2mqJVYtKAGY-6L1pqZ4PDc2tuNzE29m1qabEm09C/exec?q=priceList/${range}`;
    
        const action='fetch-categories';
        const args = JSON.stringify({'ids':[136], 'columns':['id','description']});
        var url = `https://script.google.com/macros/s/AKfycbxA_1-nFgTpbcYejpC_8A1hztZLypW9x-luKgWAIQIPCWjnxW_zgd3L37ojwwOJpBbf/exec?`;
        url += `action=${action}&args=${args}`;
        var data = {};
        $.get(url, data, function(response) {
          
            console.log(response); 
        });
    });

    const work_div = $("#wcgs_working");

    // Hide sync-back by default
    $(".wcgs-sync-back-btn").hide();

    $("#wcgs-sync-btn").on('click', function(e) {

        e.preventDefault();

        work_div.html('Please wait ...');

        $("#wcgs_progressbar").hide();

        const sheet_name = $("#sheet_name").val();
        const data = { 'action': `wcgs_sync_data_${sheet_name}`, 'sheet': sheet_name };
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
                    $("#wcgs_progressbar").show();
                    $(`.pb-run`).css('width', '10%');
                    // $(`.pb-run`).html('');
                    for (var c = 0; c < chunks.chunks; c++) {

                        wcgs_sync_data_in_chunks(c, function(chunk, sync_result) {

                            chunk_count++;
                            const resp_msg = `Chunk# ${chunk_count}/${chunks.chunks}: ${sync_result.message}`;
                            var run = (chunk_count / chunks.chunks) * 100;
                            $(`.pb-run`).css('width', `${run}%`);

                            $(`.pb-run`).html(resp_msg);

                            if (chunk_count == chunks.chunks) {
                                $(`.pb-run`).html('SYNC OPERATION COMPLETED SUCCESSFULLY !!');
                                $(`.pb-run`).css('background-color', '#6ea820');
                            }

                            $("<div/>").html(resp_msg).appendTo(work_div);
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
function wcgs_sync_data_in_chunks(chunk, callback) {

    const sheet_name = jQuery("#sheet_name").val();
    const data = { 'action': `wcgs_sync_chunk_${sheet_name}`, 'sheet': sheet_name, 'chunk': chunk };
    jQuery.post(ajaxurl, data, function(response) {
        callback(chunk, response);
    }, 'json');

}
