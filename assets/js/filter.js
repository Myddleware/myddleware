$(function () {

  // if, on page load, the sort_field and sort_order are set in localStorage, set the form values to those values
  if (
    localStorage.getItem("sort_field") !== null &&
    localStorage.getItem("sort_order") !== null
  ) {
    // Find the element with the data-sort attribute that matches the sort_field value
    var element = $(
      "span[data-sort='" + localStorage.getItem("sort_field") + "']"
    );
    // Give the element the correct class according to the sort_order value
    if (localStorage.getItem("sort_order") === "DESC") {
      element
        .find(
          ".nameIcon, .date_createdIcon, .idIcon, .date_modifiedIcon, .statusIcon, .source_idIcon, .target_idIcon, .typeIcon, .source_date_modifiedIcon"
        )
        .removeClass("fa-angle-down")
        .addClass("fa-angle-up");
    } else {
      element
        .find(
          ".nameIcon, .date_createdIcon, .idIcon, .date_modifiedIcon, .statusIcon, .source_idIcon, .target_idIcon, .typeIcon, .source_date_modifiedIcon"
        )
        .removeClass("fa-angle-up")
        .addClass("fa-angle-down");
    }
  }

  $("span").on("click", function () {
    var icon = $(this).find(
      ".nameIcon, .date_createdIcon, .idIcon, .date_modifiedIcon, .statusIcon, .source_idIcon, .target_idIcon, .typeIcon, .source_date_modifiedIcon"
    ); // Trouver l'icône dans l'élément cliqué
    if (icon.hasClass("fa-angle-down")) {
      icon.removeClass("fa-angle-down").addClass("fa-angle-up");
      $("#combined_filter_document_sort_field").val($(this).data("sort"));
      $("#combined_filter_document_sort_order").val("DESC");
      localStorage.setItem("sort_field", $(this).data("sort"));
      localStorage.setItem("sort_order", "DESC");
      $("form").submit();
    } else {
      icon.removeClass("fa-angle-up").addClass("fa-angle-down");
      $("#combined_filter_document_sort_field").val($(this).data("sort"));
      $("#combined_filter_document_sort_order").val("ASC");
      localStorage.setItem("sort_field", $(this).data("sort"));
      localStorage.setItem("sort_order", "ASC");
      $("form").submit();
    }
  });

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

  function showFilter(filter) {
    if (filter && filter.selector) {
      var filterValue = $(filter.selector).val();
      if (
        filterValue !== undefined &&
        filterValue !== null &&
        filterValue !== "" &&
        filterValue.length > 0
      ) {
        $("#" + filter.name).removeAttr("hidden");
      }
    }
  }

  // Function to hide a filter and clear its value
  function hideFilter(filter) {
    var lastClass = filter.selector.split("_").pop();
    $(
      "#combined_filter_document_" +
        lastClass +
        ", #combined_filter_rule_" +
        lastClass +
        ", #combined_filter_sourceContent_" +
        lastClass +
        ", #combined_filter_message_" +
        lastClass
    ).val("");
    $("#" + filter.name).attr("hidden", true);
  }

  // Show all filters that have a value initially
  filters.forEach(function (filter) {
    showFilter(filter);
  });

  // Show default filters
  function showDefaultFilters() {
    ["name", "sourceId", "globalStatus"].forEach(function (
      filterName
    ) {
      $("#" + filterName).removeAttr("hidden");
    });
    rearrangeFilters();
  }

  // Call the function to show default filters
  showDefaultFilters();

  // Show a filter when the corresponding option is selected
  $("#item_filter_filter").on("change", function () {
    var selectedValue = $(this).val();
    $("#" + selectedValue).removeAttr("hidden");
    if (
      selectedValue !== "date_modif_start" &&
      selectedValue !== "date_modif_end" &&
      selectedValue !== "sourceId" &&
      selectedValue !== "target" &&
      selectedValue !== "sourceContent" &&
      selectedValue !== "targetContent" &&
      selectedValue !== "message"
    ) {
      // If there isn't already a reverse checkbox, add one
      if (!$('[name="' + selectedValue + '"][type="checkbox"]').length) {
        $(".removeFilters." + selectedValue).after(
          '<div class="form-check form-switch mt-3"><input class="form-check-input p-2" type="checkbox" role="switch" name="' +
            selectedValue +
            '" value="reverse"><label for="' +
            selectedValue +
            '">Reverse</label></div>'
        );
      }
    }
    rearrangeFilters();
  });

  // Save filters to localStorage
  function saveFiltersToLocalStorage() {
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

  function loadFiltersFromLocalStorage() {
    var storedFilters = JSON.parse(localStorage.getItem("storedFilters"));
    if (storedFilters) {
      filters.forEach(function (filter) {
        if (storedFilters[filter.name]) {
          $(filter.selector).val(storedFilters[filter.name].value);
          if (storedFilters[filter.name].hidden) {
            hideFilter(filter);
          } else {
            showFilter(filter);
            if (
              filter.name !== "date_modif_start" &&
              filter.name !== "date_modif_end" &&
              filter.name !== "sourceId" &&
              filter.name !== "target" &&
              filter.name !== "sourceContent" &&
              filter.name !== "targetContent" &&
              filter.name !== "message"
            ) {
              $(".removeFilters." + filter.name).after(
                '<div class="form-check form-switch mt-3"><input class="form-check-input p-2" type="checkbox" role="switch" name="' +
                  filter.name +
                  '" value="reverse"><label for="' +
                  filter.name +
                  '">Reverse</label></div>'
              );
              $('[name="' + filter.name + '"][type="checkbox"]').prop(
                "checked",
                storedFilters[filter.name].reverse
              );
            }
          }
        }
      });
    }
    rearrangeFilters();
  }

  // Load filters from localStorage on page load
  loadFiltersFromLocalStorage();

  // Save filters to localStorage when the form is submitted
  $("form").on("submit", function () {
    saveFiltersToLocalStorage();
  });

  // if a link with the class page-link is clicked, save the filters to localStorage
  $(".page-link").on("click", function () {
    saveFiltersToLocalStorage();
    loadFiltersFromLocalStorage();
  });

  // Remove Filter
  $(".removeFilters").on("click", function () {
    var lastClass = $(this).attr("class").split(" ").pop();
    filters.forEach(function (filter) {
      if (filter.name === lastClass) {
        hideFilter(filter);
        localStorage.clear();
      }
    });
    rearrangeFilters();
  });
});

function rearrangeFilters() {
  var visibleFilters = $("#filters-container > div:visible");
  visibleFilters.each(function (index, filter) {
    $(filter).appendTo("#filters-container");
  });
}

$(document).ready(function() {
  $('#globalStatus select').select2({
      placeholder: "Select Global Status",
      allowClear: true,
      width: 'resolve' 
  });
});

