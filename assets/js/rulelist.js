const $ = require('jquery');

$(function(){
    $( ".onoffswitch" ).on('change', function(e) { 
        path_fiche_update = $(this).children('input').attr('title');
        $.ajax({
            type: "POST",
            url: path_fiche_update,						
                success: function(data){                 
            }			
        });	
    });
});

$('#rule_name').on('keyup', function() {
    var ruleName = $(this).val();
    var url = $(this).data('url');

    if (ruleName.length >= 3) {
        $.get(url, { rule_name: ruleName }, function(data) {
            // update the rules table with the new data
        });
    }
});


