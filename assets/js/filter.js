$(function() {
    // Define an array of filters, each containing a name and selector
    var filters = [
      { name: 'name', selector: '#combined_filter_rule_name' },
      { name: 'dateCreated', selector: '#combined_filter_rule_dateCreated' },
      { name: 'id', selector: '#combined_filter_rule_id' },
      { name: 'moduleSource', selector: '#combined_filter_rule_moduleSource' },
      { name: 'moduleTarget', selector: '#combined_filter_rule_moduleTarget' },
      { name: 'status', selector: '#combined_filter_document_status' },
      { name: 'globalStatus', selector: '#combined_filter_document_globalStatus' },
      { name: 'source', selector: '#combined_filter_document_source' },
      { name: 'target', selector: '#combined_filter_document_target' },
      { name: 'type', selector: '#combined_filter_document_type' },
      { name: 'date_modif_start', selector: '#combined_filter_document_date_modif_start' },
      { name: 'date_modif_end', selector: '#combined_filter_document_date_modif_end' },
      { name: 'sourceContent', selector: '#combined_filter_sourceContent_sourceContent' },      
      { name: 'targetContent', selector: '#combined_filter_sourceContent_targetContent' }
    ];
    
    // Function to show a filter if its value is not empty
    function showFilter(filter) {
      if ($(filter.selector).val() !== '') {
        $('#' + filter.name).removeAttr('hidden');
      }
      //GlobalStatus
      if (filter.name === 'globalStatus' && $(filter.selector).val() != []) {
        console.log($(filter.selector).val());
        console.log(filter.name);
        $('#' + filter.name).removeAttr('hidden');
      }
    }
    
    // Function to hide a filter and clear its value
    function hideFilter(filter) {
      var lastClass = filter.selector.split('_').pop();
      $('#combined_filter_document_' + lastClass + ', #combined_filter_rule_' + lastClass + ', #combined_filter_sourceContent_' + lastClass).val('');
      $('#' + filter.name).attr('hidden', true);
    }
    
    // Show all filters that have a value initially
    filters.forEach(function(filter) {
      showFilter(filter);
    });
    
    // Show a filter when the corresponding option is selected
    $('#item_filter_filter').on('change', function() {
      var selectedValue = $(this).val();
      $('#' + selectedValue).removeAttr('hidden');
    });

    // Remove Filter
    $('.removeFilters').on('click', function() {
      var lastClass = $(this).attr('class').split(' ').pop();
      filters.forEach(function(filter) {
        if (filter.name === lastClass) {
          hideFilter(filter);
        }
      });
    });
  });
  