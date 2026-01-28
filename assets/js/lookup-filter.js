var ruleNameToIndexMap = {}; // Global variable to store the mapping

// First, fetch the rule names mapping
function fetchRuleNamesMapping() {
  // if rule_lookup_names is not defined, return a resolved Promise
  if (typeof rule_lookup_names === 'undefined') {
    // console.warn('rule_lookup_names is not defined');
    return $.Deferred().resolve().promise();
  }

  return $.ajax({
    url: rule_lookup_names,
    type: 'GET',
    success: function(response) {
      // Check the structure of the response
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error('Error parsing response as JSON:', e);
        }
      }
      
      // The response might be in the format where rule names are keys and indices are values
      // We need to check and possibly invert the mapping
      var firstKey = Object.keys(response)[0];
      var firstValue = response[firstKey];
      
      // If the values are numbers and keys are rule names, we have the correct format
      if (typeof firstValue === 'number') {
        ruleNameToIndexMap = response;
      } else {
        // Otherwise, we need to invert the mapping
        ruleNameToIndexMap = {};
        for (var key in response) {
          ruleNameToIndexMap[response[key]] = parseInt(key);
        }
      }
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

      // Call the lookup function for each link and handle the response with a callback
      getLookupruleFromFieldName(lookupFieldClassName, current_rule, function(response) {
        var lookupRulename = response.rule;
        var urlencodedLookupRulename = encodeURIComponent(lookupRulename);

        // Check if the URL already has query parameters
        if ($link.attr("href").includes('?')) {
          $link.attr("href", $link.attr("href") + "&lookup-field-rule=" + urlencodedLookupRulename);
        } else {
          $link.attr("href", $link.attr("href") + "?lookup-field-rule=" + urlencodedLookupRulename);
        }
      });
    });

    // Replace the click handler with a mousedown handler
    $(".lookup-link-myddleware").on("mousedown", function(event) {
      var $link = $(this);
      var lookupFieldClassName = $(this).attr("class").split("lookup-link-myddleware-")[1];

      // extract the lookupRuleId from href of the link
      var urlencodedLookupRuleId = $(this).attr("href").split("lookup-field-rule=")[1];
      var lookupRuleName = decodeURIComponent(urlencodedLookupRuleId);

      // Save to localStorage
      saveFiltersToLocalStorageLookup(lookupRuleName, $link);
      
      // Don't prevent default - let the browser handle the click normally
      // This works for left-click, middle-click (new tab), and right-click (context menu)
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
  // Extract source_id from the link URL
  var href = $link.attr("href");
  var sourceId = "";
  
  // Extract source_id using regex to handle different URL formats
  var sourceIdMatch = href.match(/[?&]source_id=([^&]*)/);
  if (sourceIdMatch && sourceIdMatch[1]) {
    sourceId = decodeURIComponent(sourceIdMatch[1]);
  }
  
  // Check for exact match for rule name
  var ruleIndex = ruleNameToIndexMap[lookupRuleName];
  
  // If not found, try trimming whitespace
  if (ruleIndex === undefined) {
    var trimmedName = lookupRuleName.trim();
    ruleIndex = ruleNameToIndexMap[trimmedName];
  }
  
  // If still not found, try case-insensitive search
  if (ruleIndex === undefined) {
    var lowerName = lookupRuleName.toLowerCase();
    for (var key in ruleNameToIndexMap) {
      if (key.toLowerCase() === lowerName) {
        ruleIndex = ruleNameToIndexMap[key];
        break;
      }
    }
  }
  
  // Get existing filters from localStorage to preserve their visibility state
  var storedFilters = {};
  var existingFilters = localStorage.getItem("storedFilters");
  
  if (existingFilters) {
    try {
      storedFilters = JSON.parse(existingFilters);
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
  
  localStorage.setItem("storedFilters", JSON.stringify(storedFilters));
}