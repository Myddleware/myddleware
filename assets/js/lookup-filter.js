console.log("lookup-filter.js loaded");
var ruleNameToIndexMap = {}; // Global variable to store the mapping

// First, fetch the rule names mapping
function fetchRuleNamesMapping() {
  return $.ajax({
    url: rule_lookup_names,
    type: 'GET',
    success: function(response) {
      console.log('Rule names mapping (raw response):', response);
      
      // Check the structure of the response
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error('Error parsing response as JSON:', e);
        }
      }
      
      // Log the type and structure
      console.log('Response type:', typeof response);
      console.log('Response keys:', Object.keys(response));
      
      // The response might be in the format where rule names are keys and indices are values
      // We need to check and possibly invert the mapping
      var firstKey = Object.keys(response)[0];
      var firstValue = response[firstKey];
      console.log('First key:', firstKey, 'First value:', firstValue);
      
      // If the values are numbers and keys are rule names, we have the correct format
      if (typeof firstValue === 'number') {
        ruleNameToIndexMap = response;
        console.log('Using response directly as mapping');
      } else {
        // Otherwise, we need to invert the mapping
        ruleNameToIndexMap = {};
        for (var key in response) {
          ruleNameToIndexMap[response[key]] = parseInt(key);
        }
        console.log('Inverted the mapping');
      }
      
      console.log('Final rule names mapping:', ruleNameToIndexMap);
    },
    error: function(error) {
      console.error('Error fetching rule names:', error);
    }
  });
}

// wait until the page is loaded
$(document).ready(function() {
  // First fetch the rule names mapping, then process the links
  fetchRuleNamesMapping().then(function() {
    // Process all lookup links on page load
    $(".lookup-link-myddleware").each(function() {
      var $link = $(this); // Store reference to the current link element
      var lookupFieldClassName = $(this).attr("class").split("lookup-link-myddleware-")[1];
      console.log('lookupFieldClassName', lookupFieldClassName);
      console.log('this is the current rule', current_rule);

      // Call the lookup function for each link and handle the response with a callback
      getLookupruleFromFieldName(lookupFieldClassName, current_rule, function(response) {
        console.log('lookupRuleId response:', response);
        var lookupRulename = response.rule;
        console.log('lookupRuleId', lookupRulename);

        var urlencodedLookupRulename = encodeURIComponent(lookupRulename);

        // Check if the URL already has query parameters
        if ($link.attr("href").includes('?')) {
          $link.attr("href", $link.attr("href") + "&lookup-field-rule=" + urlencodedLookupRulename);
        } else {
          $link.attr("href", $link.attr("href") + "?lookup-field-rule=" + urlencodedLookupRulename);
        }
      });
    });

    // Keep the click handler if you still want it to work on clicks as well
    $(".lookup-link-myddleware").on("click", function(event) {
      // Prevent default behavior temporarily
      event.preventDefault();
      
      var $link = $(this); // Store reference to the current link element
      var lookupFieldClassName = $(this).attr("class").split("lookup-link-myddleware-")[1];
      console.log('lookupFieldClassName godzilla', lookupFieldClassName);

      // extract the lookupRuleId from href of the link
      var urlencodedLookupRuleId = $(this).attr("href").split("lookup-field-rule=")[1];
      console.log('urlencodedLookupRuleId godzilla', urlencodedLookupRuleId);

      var lookupRuleName = decodeURIComponent(urlencodedLookupRuleId);
      console.log('lookupRuleName godzilla', lookupRuleName);

      // Save to localStorage
      saveFiltersToLocalStorageLookup(lookupRuleName, $link);
      
      // Now manually navigate to the link's destination
      window.location.href = $link.attr("href");
    });
  });
});

function getLookupruleFromFieldName(lookupFieldClassName, current_rule, callback) {
  // do an ajax call to get the formula for the lookupfieldName
  $.ajax({
    url: lookup_rule_url,
    type: 'GET',
    data: { lookupfieldName: lookupFieldClassName, currentRule: current_rule },
    success: function(response) {
      console.log('response', response);
      if (callback) {
        callback(response);
      }
    }
  });
}

  // Define an array of filters, each containing a name and selector
  var filters = [
    { name: "name", selector: "#combined_filter_rule_name" },
    { name: "reference", selector: "#combined_filter_document_reference" },
    { name: "moduleSource", selector: "#combined_filter_rule_moduleSource" },
    { name: "moduleTarget", selector: "#combined_filter_rule_moduleTarget" },
    { name: "status", selector: "#combined_filter_document_status" },
    {
      name: "globalStatus",
      selector: "#combined_filter_document_globalStatus",
    },
    { name: "sourceId", selector: "#combined_filter_document_sourceId" },
    { name: "target", selector: "#combined_filter_document_target" },
    { name: "type", selector: "#combined_filter_document_type" },
    { name: "message", selector: "#combined_filter_message_message" },
    {
      name: "date_modif_start",
      selector: "#combined_filter_document_date_modif_start",
    },
    {
      name: "date_modif_end",
      selector: "#combined_filter_document_date_modif_end",
    },
    {
      name: "sourceContent",
      selector: "#combined_filter_sourceContent_sourceContent",
    },
    {
      name: "targetContent",
      selector: "#combined_filter_sourceContent_targetContent",
    },
  ];

// Save filters to localStorage
function saveFiltersToLocalStorageLookup(lookupRuleName, $link) {
  console.log('lookupRuleName volund', lookupRuleName);
  console.log('Current ruleNameToIndexMap:', ruleNameToIndexMap);
  
  // Extract source_id from the link URL
  var href = $link.attr("href");
  var sourceId = "";
  
  // Extract source_id using regex to handle different URL formats
  var sourceIdMatch = href.match(/[?&]source_id=([^&]*)/);
  if (sourceIdMatch && sourceIdMatch[1]) {
    sourceId = decodeURIComponent(sourceIdMatch[1]);
    console.log('Extracted source_id:', sourceId);
  }
  
  // Check for exact match for rule name
  var ruleIndex = ruleNameToIndexMap[lookupRuleName];
  console.log('Direct lookup result for "' + lookupRuleName + '":', ruleIndex);
  
  // If not found, try trimming whitespace
  if (ruleIndex === undefined) {
    var trimmedName = lookupRuleName.trim();
    ruleIndex = ruleNameToIndexMap[trimmedName];
    console.log('Lookup with trimmed name "' + trimmedName + '":', ruleIndex);
  }
  
  // If still not found, try case-insensitive search
  if (ruleIndex === undefined) {
    var lowerName = lookupRuleName.toLowerCase();
    for (var key in ruleNameToIndexMap) {
      if (key.toLowerCase() === lowerName) {
        ruleIndex = ruleNameToIndexMap[key];
        console.log('Found via case-insensitive match with key "' + key + '"');
        break;
      }
    }
  }
  
  console.log('Final rule index for ' + lookupRuleName + ':', ruleIndex);
  
  // Get existing filters from localStorage to preserve their visibility state
  var storedFilters = {};
  var existingFilters = localStorage.getItem("storedFilters");
  
  if (existingFilters) {
    try {
      storedFilters = JSON.parse(existingFilters);
      console.log('Loaded existing filters from localStorage:', storedFilters);
    } catch (e) {
      console.error('Error parsing existing filters:', e);
    }
  }

  // Update the "name" filter with the rule index
  storedFilters["name"] = {
    value: ruleIndex !== undefined ? ruleIndex : lookupRuleName, // Fallback to name if index not found
    hidden: false,
    reverse: false
  };
  
  // Update the "sourceId" filter with the extracted source_id
  if (sourceId) {
    storedFilters["sourceId"] = {
      value: sourceId,
      hidden: false,
      reverse: false
    };
  }
  
  // Empty all other filters but keep their visibility state
  filters.forEach(function(filter) {
    if (filter.name !== "name" && filter.name !== "sourceId") {
      // If the filter already exists in storedFilters, keep its hidden state
      var hidden = storedFilters[filter.name] ? storedFilters[filter.name].hidden : false;
      
      storedFilters[filter.name] = {
        value: "", // Empty the value
        hidden: hidden, // Keep the existing hidden state
        reverse: false // Reset reverse to false
      };
    }
  });
  
  console.log('Storing updated filters in localStorage:', storedFilters);
  localStorage.setItem("storedFilters", JSON.stringify(storedFilters));
}