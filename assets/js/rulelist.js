const $ = require('jquery');

$(function(){
    $(".form-check-input.toggle-switch-rule").on('change', function(e) {
        path_fiche_update = $(this).attr('title');
        $.ajax({
            type: "POST",
            url: path_fiche_update,						
                success: function(data){                 
            }			
        });	
    });
});

  $("#rulenamesearchbar").on("submit", function (e) { e.preventDefault(); });

  // save the initial table state and pagination state
  var initialTableState = $("#tbody_rule_list").html();
  var initialPaginationState = $("#pagination_container").html();

  var debounceTimer = null;
  var currentXhr = null;

  $("#rule_name_searchbar").on("input", function () {
    var q = $.trim($(this).val());
    var url = $(this).data("url");

    if (q.length > 0) {
      $("#clear_rule_search").show();
    } else {
      $("#clear_rule_search").hide();
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {

      if (q.length === 0) {
        if (currentXhr) { currentXhr.abort(); currentXhr = null; }
        $("#tbody_rule_list").html(initialTableState);
        $("#pagination_container").html(initialPaginationState);
        return;
      }

      // Cancel request
      if (currentXhr) { currentXhr.abort(); }

      // Loader
      $("#tbody_rule_list").html('<tr><td colspan="100%" class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading…</td></tr>');

      var queryAtDispatch = q;

      currentXhr = $.ajax({
        type: "GET",
        url: url,
        data: { rule_name: q },
        success: function (data) {
          if ($.trim($("#rule_name_searchbar").val()) !== queryAtDispatch) return;
          var $resp = $(data);
          var $tbody = $resp.is("#tbody_rule_list") ? $resp : $resp.find("#tbody_rule_list");
          var html = $tbody.length ? $tbody.html() : "";

          if (!html || html.replace(/\s+/g, "") === "") {
            $("#tbody_rule_list").html('<tr><td colspan="100%" class="text-center">No rules found</td></tr>');
          } else {
            $("#tbody_rule_list").html(html);
          }

          // Update pagination with filtered results
          var $pagination = $resp.find("#pagination_container");
          if ($pagination.length) {
            $("#pagination_container").html($pagination.html());
          }
        },
        error: function (xhr, status) {
          if (status !== "abort") {
            $("#tbody_rule_list").html('<tr><td colspan="100%" class="text-center text-danger">Erreur de recherche</td></tr>');
          }
        },
        complete: function () {
          currentXhr = null;
        }
      });
    }, 200);
  });

// --For 'rule name' in the list view
$('.edit-button-name-list').on('click', function () {        
    var field = $(this).closest('td'); 
    var editFormContainer = field.find('.edit-form-container'); 
    editFormContainer.show();
});


$('.close-button-name-list').on('click', function () {
    var editFormContainer = $(this).closest('.edit-form-container');
    editFormContainer.css('display', 'none');
});

$('.edit-form-name-list').on('submit', function (event) {
    event.preventDefault();

    var editForm = $(this);
    var displayText = editForm.closest('td').find('.rule-name-display');
    var newValueField = editForm.find('input[name="ruleName"]');
    var ruleId = editForm.find('input[name="ruleId"]').val();
    var newValue = newValueField.val().trim();
    var updateUrl = editForm.attr('action');

    if (newValue === "") {
        alert("Rule name is empty");
        return;
    }

    $.ajax({
        type: 'GET',
        url: checkRuleNameUrlList,
        data: {
            ruleId: ruleId,
            ruleName: newValue
        },
        success: function (response) {
            if (response.exists) {
                alert("This rule name already exists. Please choose a different name.");
            } else {
                $.ajax({
                    type: 'POST',
                    url: updateUrl,
                    data: {
                        ruleId: ruleId,
                        ruleName: newValue
                    },
                    success: function (response) {
                        displayText.text(newValue);
                        editForm.closest('.edit-form-container').css('display', 'none');
                    },
                    error: function (error) {
                        alert("An error occurred while updating the rule name.");
                    }
                });
            }
        },
        error: function (error) {
            alert("An error occurred while checking the rule name.");
        }
    });
});
