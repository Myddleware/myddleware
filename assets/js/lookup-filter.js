console.log("lookup-filter.js loaded");

// wait until the page is loaded
$(document).ready(function() {
  // Process all lookup links on page load
  $(".lookup-link-myddleware").each(function() {
    var lookupFieldClassName = $(this).attr("class").split("lookup-link-myddleware-")[1];
    console.log('lookupFieldClassName', lookupFieldClassName);
    console.log('this is the current rule', current_rule);

    // Call the lookup function for each link
    var lookupRuleId = getLookupruleFromFieldName(lookupFieldClassName, current_rule);
    console.log('lookupRuleId', lookupRuleId);
  });

  // Keep the click handler if you still want it to work on clicks as well
  // $(".lookup-link-myddleware").on("click", function () {
  //   //   console.log("lookup-link-myddleware clicked on " + $(this).attr("class"));

  //   var lookupFieldClassName = $(this).attr("class").split("lookup-link-myddleware-")[1];
  //   // console.log('lookupFieldClassName', lookupFieldClassName);
  //   console.log('this is the current rule', current_rule);

  //   // wait for the result of the ajax call to continue
  //   var lookupRuleId = getLookupruleFromFieldName(lookupFieldClassName, current_rule);
  //   console.log('lookupRuleId', lookupRuleId);

  //   // saveFiltersToLocalStorageLookup();
  // });
});


  function getLookupruleFromFieldName(lookupFieldClassName, current_rule) {
    // do an ajax call to get the formula for the lookupfieldName
    $.ajax({
      url: lookup_rule_url,
      type: 'GET',
      data: { lookupfieldName: lookupFieldClassName, currentRule: current_rule },
      success: function(response) {
        console.log('response', response);
        return response;
      }
    });
    }



  // Save filters to localStorage
  function saveFiltersToLocalStorageLookup() {
    var storedFilters = {};
    filters.forEach(function (filter) {
      storedFilters[filter.name] = {
        value: $(filter.selector).val(),
        hidden: $("#" + filter.name).attr("hidden") === "hidden",
        reverse: $('[name="' + filter.name + '"][type="checkbox"]').prop(
          "checked"
        ),
      };
    });
    localStorage.setItem("storedFilters", JSON.stringify(storedFilters));
  }