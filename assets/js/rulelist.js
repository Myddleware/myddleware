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
$("#rulenamesearchbar").on("submit", function(event) {
    event.preventDefault();
});

// save the initial table state somewhere accessible
var initialTableState = $('#tbody_rule_list').html();

$("#rule_name").on("keyup input", function() {
    var ruleName = $(this).val();
    var url = $(this).data('url');

    if (ruleName.length >= 3) {
        $.ajax({
            type: "GET",
            url: url,
            data: { rule_name: ruleName },
            success: function(data){
                // parse the returned data and load it into a jQuery object
                var html = $(data);
                // find the tbody element
                var tbody_html = html.find('#tbody_rule_list').html();
                // replace the tbody in the current page with the tbody from the returned data
                $('#tbody_rule_list').html(tbody_html);
            }
        });
    } else if (ruleName.length == 0) {
        // if the search bar is empty, reset the table
        $('#tbody_rule_list').html(initialTableState);
    }
});
