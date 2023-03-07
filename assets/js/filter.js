$(document).ready(function() {
    // This function is executed when the document has loaded completely

    $('#item_filter_filter').on('change', function() {
        // This function is executed when the value of the 'item_filter_filter' select element changes

        var selectedValue = $(this).val();
        // Get the selected value of the 'item_filter_filter' select element

        $('#combined_filter_document_' + selectedValue).removeAttr('hidden');
        $('#combined_filter_rule_' + selectedValue).removeAttr('hidden');

        var labelFor = $('label[for="combined_filter_document_' + selectedValue + '"]');
        var labelForRule = $('label[for="combined_filter_rule_' + selectedValue + '"]');
        labelFor.removeAttr('hidden');
        labelForRule.removeAttr('hidden');
        // Show the labels associated with the above elements

        $('.' + selectedValue).removeAttr('hidden');
        // Show all elements with the class name equal to the selected value
    });

    $('.removeFilter').on('click', function() {
        // This function is executed when an element with the 'removeFilter' class is clicked

        var clickedClass = $(this).attr('class');
        var classes = clickedClass.split(' ');
        var lastClass = classes[classes.length - 1];
        // Get the last class name of the clicked element

        $('#combined_filter_document_' + lastClass).attr('hidden', true);
        // Remove value
        $('#combined_filter_document_' + lastClass).val('')
        $('#combined_filter_rule_' + lastClass).attr('hidden', true);
        $('#combined_filter_rule_' + lastClass).val('');
        // Hide the elements with IDs 'combined_filter_document_{lastClass}' and 'combined_filter_rule_{lastClass}'

        var labelFor = $('label[for="combined_filter_document_' + lastClass + '"]');
        var labelForRule = $('label[for="combined_filter_rule_' + lastClass + '"]');
        labelFor.attr('hidden', true);
        labelForRule.attr('hidden', true);
        // Hide the labels associated with the above elements

        $('.' + lastClass).attr('hidden', true);
        // Hide all elements with the class name equal to the last class name of the clicked element
    });
});
