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

// $('#rule_name').on('keyup', function() {
//     var ruleName = $(this).val();
//     console.log('the rule name', ruleName);
//     var url = $(this).data('url');
//     console.log('the url', url);
//     console.log('the url of the function', regle_list);

//     regle_list = 'http://localhost/myddleware_NORMAL/public/rule/list?rule_name=sui';

//     if (ruleName.length >= 3) {
//         // $.get(url, { rule_name: ruleName }, function(data) {
//             // console.log(data);
//             // update the rules table with the new data
//             console.log('id', ruleName);
//             $.ajax({
//                 type: "POST",
//                 url: regle_list,
//                 // id: ruleName,						
//                     success: function(data){
//                         // location.reload();                 
//                 }			
//             });	
//         // });
//     }
// });


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
