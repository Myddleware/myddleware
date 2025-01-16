function fetchFilteredData() {
    var workflowName = $("#workflow_name").val();
    var ruleName = $("#rule_name").val();

    workflowListUrl = workflowListUrl.replace("/workflow/workflow/", "/workflow/list/");

    $.ajax({
      url: workflowListUrl,
      type: "GET",
      data: {
        workflow_name: workflowName,
        rule_name: ruleName,
      },
      success: function (response) {
        $("#workflowTableContainer").html(response);

        // $('#workflowTableContainer').html($(response).find('#workflowTableContainer').html());
      },
      error: function (xhr, status, error) {
        console.error("Erreur lors de la recherche :", status, error);
      },
    });
  }

  $("#workflow_name, #rule_name").on("keyup", function () {
    fetchFilteredData();
  });