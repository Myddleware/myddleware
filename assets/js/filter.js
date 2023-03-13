$(function() {
    // This function is executed when the value of the 'item_filter_filter' select element changes
    $('#item_filter_filter').on('change', function() {

        var selectedValue = $(this).val();
        $('#combined_filter_document_' + selectedValue + ', #combined_filter_rule_' + selectedValue + ', #combined_filter_sourceContent_' + selectedValue).removeAttr('hidden');
        $('label[for="combined_filter_document_' + selectedValue + '"], label[for="combined_filter_rule_' + selectedValue + '"], label[for="combined_filter_sourceContent_' + selectedValue + '"]').removeAttr('hidden');
        $('.' + selectedValue).removeAttr('hidden');

        $('#combined_filter_rule_' + selectedValue).after('<div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault" name="inversed_gandalf" value="cabotin"></div>');
    });

    // This function is executed when an element with the 'removeFilter' class is clicked
    $('.removeFilter').on('click', function() {
        var lastClass = $(this).attr('class').split(' ').pop();
        
        // Hide all elements with the class name equal to the last class name of the clicked element   
        $('#combined_filter_document_' + lastClass + ', #combined_filter_rule_' + lastClass + ', #combined_filter_sourceContent_' + lastClass).attr('hidden', true);
        $('#combined_filter_document_' + lastClass + ', #combined_filter_rule_' + lastClass + ', #combined_filter_sourceContent_' + lastClass).val('');
        $('label[for="combined_filter_document_' + lastClass + '"], label[for="combined_filter_rule_' + lastClass + '"], label[for="combined_filter_sourceContent_' + lastClass + '"]').attr('hidden', true);
        $('.' + lastClass).attr('hidden', true);
    });
});