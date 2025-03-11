console.log("lookup-filter.js loaded");

// wait until the page is loaded
$(document).ready(function() {
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
  $(".lookup-link-myddleware").on("click", function () {

    // prevent default behavior of the link
    event.preventDefault();
    //   console.log("lookup-link-myddleware clicked on " + $(this).attr("class"));

    var lookupFieldClassName = $(this).attr("class").split("lookup-link-myddleware-")[1];
    console.log('lookupFieldClassName godzilla', lookupFieldClassName);
    // console.log('this is the current rule', current_rule);


    // extract the lookupRuleId from href of the link href="/myddleware_NORMAL/public/rule/document/list/page?source_id=1&lookup-field-rule=suitecrm%20-%20users"
    var urlencodedLookupRuleId = $(this).attr("href").split("lookup-field-rule=")[1];
    console.log('urlencodedLookupRuleId godzilla', urlencodedLookupRuleId);

    var lookupRuleId = decodeURIComponent(urlencodedLookupRuleId);
    console.log('lookupRuleId godzilla', lookupRuleId);

    

    saveFiltersToLocalStorageLookup(lookupRuleId);
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
function saveFiltersToLocalStorageLookup(lookupRuleId) {
  console.log('lookupRuleId volund', lookupRuleId);
  var storedFilters = {};

  // Store the lookupRuleId under the "name" key with proper structure
  storedFilters["name"] = {
    value: 5,
    hidden: false,
    reverse: false
  };
  
  // You can add other hardcoded filters if needed
  // For example, to clear other filters:
  storedFilters["reference"] = { value: "", hidden: true, reverse: false };
  storedFilters["moduleSource"] = { value: "", hidden: true, reverse: false };
  storedFilters["moduleTarget"] = { value: "", hidden: true, reverse: false };
  // etc.

  localStorage.setItem("storedFilters", JSON.stringify(storedFilters));
}