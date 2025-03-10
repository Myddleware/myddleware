console.log("lookup-filter.js loaded");

$(".lookup-link-myddleware").on("click", function () {
    console.log("lookup-link-myddleware clicked on " + $(this).attr("class"));

    var lookupFieldClassName = $(this).attr("class").split("lookup-link-myddleware-")[1];
    console.log('lookupFieldClassName', lookupFieldClassName);

    var lookupfieldName = getLookupruleFromFieldName(lookupFieldClassName);
    console.log('lookupfieldName', lookupfieldName);

    // saveFiltersToLocalStorageLookup();
  });


  function getLookupruleFromFieldName(lookupfieldName) {
    // do an ajax call to get the formula for the lookupfieldName
    $.ajax({
      url: '/get-lookup-rule-from-field-name',
      type: 'GET',
      data: { lookupfieldName: lookupfieldName },
      success: function(response) {
        console.log('response', response);
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