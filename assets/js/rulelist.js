const $ = require('jquery');

$(function(){
    $( ".onoffswitch-label" ).on('click', function(e) {
        path_fiche_update = $(this).parent().children('input').attr('title');
        $.ajax({
            type: "POST",
            url: path_fiche_update,						
                success: function(data){		
            }			
        });	
    });
});
