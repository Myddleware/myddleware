$(document).ready(function () {
  $(".form-check-input").on("change", function () {
    var workflowId = $(this).data("id");
    var newState = $(this).is(":checked") ? 1 : 0;

    $.ajax({
      url: `/myddleware/public/workflow/workflow/toggle/${workflowId}`,
      type: "POST",
      beforeSend: function () {
        console.log(
          `Before sending the request for workflow ID: ${workflowId}`
        );
      },
      success: function (response) {
        console.log(
          `Success response received for workflow ID: ${workflowId}`,
          response
        );
      },
      error: function (xhr, status, error) {
        console.error(
          `Error received for workflow ID: ${workflowId}`,
          xhr,
          status,
          error
        );
        alert("Erreur lors de la bascule du workflow");
      },
      complete: function (xhr, status) {
        console.log(`Request completed for workflow ID: ${workflowId}`, status);
      },
    });
  });
});
