$(function() {
    // Define an array of filters, each containing a name and selector
    var filters = [
      { name: 'name', selector: '#combined_filter_rule_name' },
      { name: 'reference', selector: '#combined_filter_document_reference' },
      { name: 'moduleSource', selector: '#combined_filter_rule_moduleSource' },
      { name: 'moduleTarget', selector: '#combined_filter_rule_moduleTarget' },
      { name: 'status', selector: '#combined_filter_document_status' },
      { name: 'globalStatus', selector: '#combined_filter_document_globalStatus' },
      { name: 'sourceId', selector: '#combined_filter_document_sourceId' },
      { name: 'target', selector: '#combined_filter_document_target' },
      { name: 'type', selector: '#combined_filter_document_type' },
      { name: 'date_modif_start', selector: '#combined_filter_document_date_modif_start' },
      { name: 'date_modif_end', selector: '#combined_filter_document_date_modif_end' },
      { name: 'sourceContent', selector: '#combined_filter_sourceContent_sourceContent' },      
      { name: 'targetContent', selector: '#combined_filter_sourceContent_targetContent' }
    ];
    
    // Function to show a filter if its value is not empty
    function showFilter(filter) {
      var filterValue = $(filter.selector).val();
      if (filterValue !== null && filterValue !== '' && filterValue.length > 0) {
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
      if (selectedValue !== 'date_modif_start' && selectedValue !== 'date_modif_end' && selectedValue !== 'sourceId' && selectedValue !== 'target' &&selectedValue !== 'sourceContent' && selectedValue !== 'targetContent') {
        $('.removeFilters.' + selectedValue).after('<div class="form-check form-switch mt-3"><input class="form-check-input p-2" type="checkbox" role="switch" name="'+selectedValue+'" value="reverse"><label for="'+selectedValue+'">Reverse</label></div>');
      }
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

    // Save filters to localStorage
    function saveFiltersToLocalStorage() {
      var storedFilters = {};
      filters.forEach(function(filter) {
        storedFilters[filter.name] = {
          value: $(filter.selector).val(),
          hidden: $('#' + filter.name).attr('hidden') === 'hidden',
          reverse: $('[name="' + filter.name + '"][type="checkbox"]').prop('checked')
        };
      });
      localStorage.setItem('storedFilters', JSON.stringify(storedFilters));
    }

    function loadFiltersFromLocalStorage() {
      var storedFilters = JSON.parse(localStorage.getItem('storedFilters'));
      if (storedFilters) {
        filters.forEach(function(filter) {
          if (storedFilters[filter.name]) {
            $(filter.selector).val(storedFilters[filter.name].value);
            if (storedFilters[filter.name].hidden) {
              hideFilter(filter);
            } else {
              showFilter(filter);
              if (filter.name !== 'date_modif_start' && filter.name !== 'date_modif_end' && filter.name !== 'sourceId' && filter.name !== 'target' && filter.name !== 'sourceContent' && filter.name !== 'targetContent') {
                $('.removeFilters.' + filter.name).after('<div class="form-check form-switch mt-3"><input class="form-check-input p-2" type="checkbox" role="switch" name="' + filter.name + '" value="reverse"><label for="' + filter.name + '">Reverse</label></div>');
                $('[name="' + filter.name + '"][type="checkbox"]').prop('checked', storedFilters[filter.name].reverse);
              }
            }
          }
        });
      }
    }

  // Load filters from localStorage on page load
    loadFiltersFromLocalStorage();

  // Save filters to localStorage when the form is submitted
  $('form').on('submit', function() {
    saveFiltersToLocalStorage();
  });

  // if a link with the class page-link is clicked, save the filters to localStorage
  $('.page-link').on('click', function() {
    console.log('page-link clicked');
    saveFiltersToLocalStorage();
    loadFiltersFromLocalStorage();
  });

  // Clear localStorage when the clear button is clicked
  $('.removeFilter').on('click', function() {
    localStorage.clear();
  });

  });
  