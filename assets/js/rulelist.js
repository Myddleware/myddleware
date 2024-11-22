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
    
    if (ruleName.length > 0) {
        $.ajax({
            type: "GET",
            url: url,
            data: { rule_name: ruleName },
            success: function(data){
                var html = $(data);
                var tbody_html = html.find('#tbody_rule_list').html();
                
                if ($.trim(tbody_html) === '') {
                    $('#tbody_rule_list').html('<tr><td colspan="100%" class="text-center">No rules found</td></tr>');
                } else {
                    $('#tbody_rule_list').html(tbody_html);
                }
            }
        });
    } else {
        // if the search bar is empty, reset the table
        $('#tbody_rule_list').html(initialTableState);
    }
});

// --For 'rule name' in the list view
$('.edit-button-name-list').on('click', function () {        
    var field = $(this).closest('td'); 
    var editFormContainer = field.find('.edit-form-container'); 
    editFormContainer.show();
});


$('.close-button-name-list').on('click', function () {
    var editFormContainer = $(this).closest('.edit-form-container');
    editFormContainer.css('display', 'none');
});

$('.edit-form-name-list').on('submit', function (event) {
    event.preventDefault();

    var editForm = $(this);
    var displayText = editForm.closest('td').find('.rule-name-display');
    var newValueField = editForm.find('input[name="ruleName"]');
    var ruleId = editForm.find('input[name="ruleId"]').val();
    var newValue = newValueField.val().trim();
    var updateUrl = editForm.attr('action');

    if (newValue === "") {
        alert("Rule name is empty");
        return;
    }

    $.ajax({
        type: 'GET',
        url: checkRuleNameUrlList,
        data: {
            ruleId: ruleId,
            ruleName: newValue
        },
        success: function (response) {
            if (response.exists) {
                alert("This rule name already exists. Please choose a different name.");
            } else {
                $.ajax({
                    type: 'POST',
                    url: updateUrl,
                    data: {
                        ruleId: ruleId,
                        ruleName: newValue
                    },
                    success: function (response) {
                        displayText.text(newValue);
                        editForm.closest('.edit-form-container').css('display', 'none');
                    },
                    error: function (error) {
                        alert("An error occurred while updating the rule name.");
                    }
                });
            }
        },
        error: function (error) {
            alert("An error occurred while checking the rule name.");
        }
    });
});
