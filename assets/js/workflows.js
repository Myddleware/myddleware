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

document.addEventListener("DOMContentLoaded", function () {
  function toggleIcon(button, content) {
    if (content.classList.contains("show")) {
      button.innerHTML = '<i class="fa fa-minus"></i>';
    } else {
      button.innerHTML = '<i class="fa fa-plus"></i>';
    }
  }

  // Panneau principal
  var mainToggleButton = document.querySelector(".toggle-button");
  var mainCollapseContent = document.getElementById("workflow-content");

  mainCollapseContent.addEventListener("shown.bs.collapse", function () {
    toggleIcon(mainToggleButton, mainCollapseContent);
  });

  mainCollapseContent.addEventListener("hidden.bs.collapse", function () {
    toggleIcon(mainToggleButton, mainCollapseContent);
  });

  // Sous-panneau des actions
  var subToggleButton = document.querySelectorAll(".toggle-button")[1];
  var subCollapseContent = document.getElementById("actions-content");

  subCollapseContent.addEventListener("shown.bs.collapse", function () {
    toggleIcon(subToggleButton, subCollapseContent);
  });

  subCollapseContent.addEventListener("hidden.bs.collapse", function () {
    toggleIcon(subToggleButton, subCollapseContent);
  });

  // Panneau des logs
  var logsToggleButton = document.querySelectorAll(".toggle-button")[2];
  var logsCollapseContent = document.getElementById("logs-content");

  logsCollapseContent.addEventListener("shown.bs.collapse", function () {
    toggleIcon(logsToggleButton, logsCollapseContent);
  });

  logsCollapseContent.addEventListener("hidden.bs.collapse", function () {
    toggleIcon(logsToggleButton, logsCollapseContent);
  });
});