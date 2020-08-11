jQuery(function($) {

    // const { GoogleSpreadsheet } = require('google-spreadsheet');

    $("#wcgs-sync-categories").on('click', function(e) {

        e.preventDefault();
        
        const data = {'action': 'wcgs_sync_categories'};
        $.post(ajaxurl, data, function(response){
            
            console.log(response);
        })

    });
});
