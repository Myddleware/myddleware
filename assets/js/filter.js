$(document).ready(function() {
    $('#item_filter_filter').on('change', function() {
        
        var selectedValue = $(this).val();
        $('#combined_filter_document_' + selectedValue).removeAttr('hidden');
        $('#combined_filter_rule_' + selectedValue).removeAttr('hidden');
        
        // Get the label element
        var labelFor = $('label[for="combined_filter_document_' + selectedValue + '"]');
        var labelForValue = labelFor.attr('for');
        // Get the label element again
        var labelForRule = $('label[for="combined_filter_rule_' + selectedValue + '"]');
        var labelForValue = labelFor.attr('for');
        labelFor.removeAttr('hidden');
        labelForRule.removeAttr('hidden');
    });
});







