$(document).ready(function() {
    $('#item_filter_filter').on('change', function() {
        var selectedValue = $(this).val();
        $('#item_filter_' + selectedValue).removeAttr('hidden');

        // Récupérer l'élément label
        var labelFor = $('label[for="item_filter_' + selectedValue + '"]');
        var labelForValue = labelFor.attr('for');
        // Récupérer à nouveau l'élément label
        var labelFor = $('label[for="item_filter_' + selectedValue + '"]');
        var labelForValue = labelFor.attr('for');

        labelFor.removeAttr('hidden');
    });
});






