$(document).ready(function() {
    $('#item_filter_filter').on('change', function() {
        var selectedValue = $(this).val();
        $('#item_filter_' + selectedValue).removeAttr('hidden');
    });
});

